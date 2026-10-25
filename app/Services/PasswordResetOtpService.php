<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class PasswordResetOtpService
{
    private const OTP_LIFETIME_SECONDS = 900;

    private const GRANT_LIFETIME_SECONDS = 900;

    private const COOLDOWN_SECONDS = 60;

    private const MAX_ATTEMPTS = 5;

    public function __construct(private readonly NodemailerPasswordResetSender $mailer) {}

    public function cooldownSeconds(string $email): int
    {
        $deadline = Cache::get($this->emailThrottleKey($email), 0);

        return max(0, min(self::COOLDOWN_SECONDS, (int) $deadline - now()->timestamp));
    }

    public function send(string $email, string $ipAddress): bool
    {
        $email = mb_strtolower(trim($email));
        $key = $this->otpKey($email);
        $cache = Cache::store();

        return $cache->lock($key.':lock', 90)->block(5, function () use ($cache, $key, $email, $ipAddress): bool {
            $users = User::query()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->limit(2)
                ->get();

            if ($users->count() !== 1) {
                return false;
            }

            if (! $cache->add($this->emailThrottleKey($email), now()->addSeconds(self::COOLDOWN_SECONDS)->timestamp, self::COOLDOWN_SECONDS)) {
                throw new PasswordResetOtpRateLimitException('Vui lòng chờ trước khi yêu cầu mã mới.');
            }

            if (! $cache->add($this->ipThrottleKey($ipAddress), true, self::COOLDOWN_SECONDS)) {
                $cache->forget($this->emailThrottleKey($email));
                throw new PasswordResetOtpRateLimitException('Vui lòng chờ trước khi yêu cầu mã mới.');
            }

            $user = $users->first();
            $otp = (string) random_int(100000, 999999);
            $generation = bin2hex(random_bytes(32));

            if (! $cache->put($key, [
                'account_id' => (int) $user->getKey(),
                'email' => $email,
                'hash' => Hash::make($otp),
                'attempts' => 0,
                'generation' => $generation,
                'expires_at' => now()->addSeconds(self::OTP_LIFETIME_SECONDS)->timestamp,
            ], self::OTP_LIFETIME_SECONDS) || ! $cache->put($this->generationKey($email), $generation, self::GRANT_LIFETIME_SECONDS * 2)) {
                $cache->forget($key);
                $cache->forget($this->generationKey($email));
                $cache->forget($this->emailThrottleKey($email));
                throw new RuntimeException('Password reset OTP cache could not be written.');
            }

            try {
                $this->mailer->send($email, $otp, 15);
            } catch (PasswordResetDeliveryException $exception) {
                $cache->forget($key);
                $cache->forget($this->generationKey($email));
                $cache->forget($this->emailThrottleKey($email));
                throw $exception;
            }

            return true;
        });
    }

    public function verify(string $email, string $otp, string $sessionId): ?string
    {
        $email = mb_strtolower(trim($email));
        $key = $this->otpKey($email);
        $cache = Cache::store();

        return $cache->lock($key.':lock', 90)->block(5, function () use ($cache, $key, $email, $otp, $sessionId): ?string {
            $record = $cache->get($key);
            if (! is_array($record) || $record['expires_at'] <= now()->timestamp || $record['attempts'] >= self::MAX_ATTEMPTS) {
                $cache->forget($key);

                return null;
            }

            $user = User::query()->find($record['account_id']);
            if (! $user || mb_strtolower(trim($user->email)) !== $email || ! Hash::check($otp, $record['hash'])) {
                $record['attempts']++;
                if ($record['attempts'] >= self::MAX_ATTEMPTS) {
                    $cache->forget($key);
                } else {
                    $cache->put($key, $record, max(1, $record['expires_at'] - now()->timestamp));
                }

                return null;
            }

            $cache->forget($key);
            $token = bin2hex(random_bytes(32));
            $grant = [
                'account_id' => (int) $user->getKey(),
                'email' => $email,
                'password_digest' => hash('sha256', (string) $user->password),
                'session_digest' => hash('sha256', $sessionId),
                'generation' => $record['generation'],
                'expires_at' => now()->addSeconds(self::GRANT_LIFETIME_SECONDS)->timestamp,
            ];

            return $cache->put($this->grantKey($token), $grant, self::GRANT_LIFETIME_SECONDS) ? $token : null;
        });
    }

    public function canReset(?string $token, string $sessionId): bool
    {
        return $this->validGrant($token, $sessionId) !== null;
    }

    public function passwordMatchesCurrent(?string $token, string $password, string $sessionId): bool
    {
        $grant = $this->validGrant($token, $sessionId);
        if ($grant === null) {
            return false;
        }

        $user = User::query()->find($grant['account_id']);

        return is_string($user?->password) && Hash::check($password, $user->password);
    }

    public function reset(?string $token, string $password, string $sessionId): bool
    {
        $grant = $this->validGrant($token, $sessionId);
        if ($grant === null) {
            return false;
        }

        $cache = Cache::store();
        $otpKey = $this->otpKey($grant['email']);

        return $cache->lock($otpKey.':lock', 90)->block(5, function () use ($cache, $token, $password, $sessionId): bool {
            return DB::transaction(function () use ($cache, $token, $password, $sessionId): bool {
                $grant = $this->validGrant($token, $sessionId);
                if ($grant === null) {
                    return false;
                }

                $user = User::query()->lockForUpdate()->find($grant['account_id']);
                if (! $user
                    || mb_strtolower(trim($user->email)) !== $grant['email']
                    || ! hash_equals($grant['password_digest'], hash('sha256', (string) $user->password))
                    || Hash::check($password, $user->password)) {
                    return false;
                }

                $cache->forget($this->grantKey($token));
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
                DB::table('sessions')->where('user_id', $user->getKey())->delete();

                return true;
            });
        });
    }

    private function validGrant(?string $token, string $sessionId): ?array
    {
        if ($token === null || preg_match('/^[a-f0-9]{64}$/D', $token) !== 1) {
            return null;
        }

        $cache = Cache::store();
        $grant = $cache->get($this->grantKey($token));
        if (! is_array($grant)
            || ! hash_equals($grant['session_digest'], hash('sha256', $sessionId))
            || $grant['expires_at'] <= now()->timestamp
            || $cache->get($this->generationKey($grant['email'])) !== $grant['generation']) {
            return null;
        }

        $user = User::query()->find($grant['account_id']);
        if (! $user
            || mb_strtolower(trim($user->email)) !== $grant['email']
            || ! hash_equals($grant['password_digest'], hash('sha256', (string) $user->password))) {
            return null;
        }

        return $grant;
    }

    private function otpKey(string $email): string
    {
        return 'password-reset:otp:'.hash('sha256', $email);
    }

    private function generationKey(string $email): string
    {
        return 'password-reset:generation:'.hash('sha256', $email);
    }

    private function emailThrottleKey(string $email): string
    {
        return 'password-reset:email-throttle:'.hash('sha256', $email);
    }

    private function ipThrottleKey(string $ipAddress): string
    {
        return 'password-reset:ip-throttle:'.hash('sha256', $ipAddress);
    }

    private function grantKey(string $token): string
    {
        return 'password-reset:grant:'.hash('sha256', $token);
    }
}
