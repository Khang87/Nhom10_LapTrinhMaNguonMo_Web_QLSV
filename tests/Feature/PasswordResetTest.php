<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\PasswordResetDeliveryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_guest_can_open_the_login_and_password_recovery_forms(): void
    {
        $this->withoutVite()
            ->get(route('login'))
            ->assertOk()
            ->assertSee(route('password.request'));

        $this->withoutVite()
            ->get(route('password.request'))
            ->assertOk()
            ->assertSee('Quên mật khẩu')
            ->assertDontSee('name="otp"', false);

        $this->withSession(['reset_email' => 'student@example.com'])
            ->withoutVite()
            ->get(route('password.request'))
            ->assertOk()
            ->assertSee('name="otp"', false)
            ->assertSee('15 phút');
    }

    public function test_password_reset_email_contains_a_signed_six_digit_otp(): void
    {
        $user = User::factory()->create(['email' => 'student@example.com']);
        $secret = str_repeat('a', 64);
        config([
            'services.nodemailer.url' => 'http://127.0.0.1:3001',
            'services.nodemailer.secret' => $secret,
        ]);
        Http::fake();

        $this->post(route('password.email'), ['email' => strtoupper($user->email)])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status', 'Nếu email tồn tại, mã xác thực sẽ được gửi đến hộp thư của bạn.')
            ->assertSessionHas('reset_email', $user->email);

        Http::assertSent(function (Request $request) use ($secret, $user): bool {
            $timestamp = $request->header('X-Reset-Timestamp')[0] ?? '';
            $signature = $request->header('X-Reset-Signature')[0] ?? '';
            $payload = json_decode($request->body(), true, flags: JSON_THROW_ON_ERROR);

            return $request->url() === 'http://127.0.0.1:3001/send-password-reset-otp'
                && hash_equals(hash_hmac('sha256', $timestamp.'.'.$request->body(), $secret), $signature)
                && $payload['email'] === $user->email
                && preg_match('/^\d{6}$/', $payload['otp']) === 1
                && $payload['expiresInMinutes'] === 15;
        });
    }

    public function test_unknown_email_gets_the_same_response_and_no_email_is_sent(): void
    {
        config([
            'services.nodemailer.url' => 'http://127.0.0.1:3001',
            'services.nodemailer.secret' => str_repeat('a', 64),
        ]);
        Http::fake();

        $this->post(route('password.email'), ['email' => 'unknown@example.com'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('status', 'Nếu email tồn tại, mã xác thực sẽ được gửi đến hộp thư của bạn.')
            ->assertSessionHas('reset_email', 'unknown@example.com');

        Http::assertNothingSent();
    }

    public function test_mailer_failure_is_logged_and_reported_without_exposing_email_existence(): void
    {
        User::factory()->create(['email' => 'student@example.com']);
        config([
            'services.nodemailer.url' => 'http://127.0.0.1:3001',
            'services.nodemailer.secret' => str_repeat('a', 64),
        ]);
        Http::fake(['*' => Http::response([], 502)]);
        Log::shouldReceive('error')
            ->once()
            ->with('Password reset OTP could not be delivered by Nodemailer.', [
                'exception' => PasswordResetDeliveryException::class,
                'message' => 'Nodemailer service rejected the password reset email.',
                'cause' => RequestException::class,
            ]);

        $this->post(route('password.email'), ['email' => 'student@example.com'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('error', 'Không thể gửi mã lúc này. Vui lòng thử lại sau.')
            ->assertSessionMissing('status');
    }

    public function test_otp_must_be_verified_before_password_form_opens_and_only_works_once(): void
    {
        $user = User::factory()->create(['password' => 'OldPassword123!']);
        Http::fake();
        config([
            'services.nodemailer.url' => 'http://127.0.0.1:3001',
            'services.nodemailer.secret' => str_repeat('a', 64),
        ]);

        $this->get(route('password.reset'))
            ->assertRedirect(route('password.request'));

        $this->post(route('password.email'), ['email' => $user->email]);
        $otp = json_decode(Http::recorded()->last()[0]->body(), true, flags: JSON_THROW_ON_ERROR)['otp'];
        $originalPasswordHash = $user->fresh()->password;

        $this->post(route('password.verify'), ['email' => $user->email, 'otp' => $otp])
            ->assertRedirect(route('password.reset'))
            ->assertSessionHas('password_reset_grant');
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->assertSame($originalPasswordHash, $user->fresh()->password);
        $this->get(route('password.reset'))
            ->assertOk()
            ->assertSee('name="password"', false)
            ->assertDontSee('name="otp"', false);

        $this->post(route('password.update'), [
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));

        $this->post(route('password.verify'), ['email' => $user->email, 'otp' => $otp])
            ->assertRedirect(route('password.request'));
        $this->get(route('password.reset'))
            ->assertRedirect(route('password.request'));
    }

    public function test_five_wrong_otp_attempts_revoke_the_code(): void
    {
        $user = User::factory()->create();
        Http::fake();
        config([
            'services.nodemailer.url' => 'http://127.0.0.1:3001',
            'services.nodemailer.secret' => str_repeat('a', 64),
        ]);

        $this->post(route('password.email'), ['email' => $user->email]);
        $correctOtp = json_decode(Http::recorded()->last()[0]->body(), true, flags: JSON_THROW_ON_ERROR)['otp'];
        $wrongOtp = $correctOtp === '000000' ? '000001' : '000000';

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('password.verify'), ['email' => $user->email, 'otp' => $wrongOtp])
                ->assertRedirect(route('password.request'));
        }

        $this->post(route('password.verify'), ['email' => $user->email, 'otp' => $correctOtp])
            ->assertRedirect(route('password.request'));
        $this->get(route('password.reset'))
            ->assertRedirect(route('password.request'));
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_verified_reset_grant_expires_and_is_bound_to_its_session(): void
    {
        $user = User::factory()->create();
        Http::fake();
        config([
            'services.nodemailer.url' => 'http://127.0.0.1:3001',
            'services.nodemailer.secret' => str_repeat('a', 64),
        ]);

        $this->post(route('password.email'), ['email' => $user->email]);
        $otp = json_decode(Http::recorded()->last()[0]->body(), true, flags: JSON_THROW_ON_ERROR)['otp'];
        $this->post(route('password.verify'), ['email' => $user->email, 'otp' => $otp]);
        $this->withCookie(config('session.cookie'), session()->getId());
        $this->get(route('password.reset'))->assertOk();

        $grant = session('password_reset_grant');
        $this->travel(15)->minutes();
        $this->get(route('password.reset'))
            ->assertRedirect(route('password.request'));
        $this->assertIsString($grant);
    }

    public function test_otp_expires_after_fifteen_minutes(): void
    {
        $user = User::factory()->create();
        Http::fake();
        config([
            'services.nodemailer.url' => 'http://127.0.0.1:3001',
            'services.nodemailer.secret' => str_repeat('a', 64),
        ]);

        $this->post(route('password.email'), ['email' => $user->email]);
        $otp = json_decode(Http::recorded()->last()[0]->body(), true, flags: JSON_THROW_ON_ERROR)['otp'];
        $this->travel(15)->minutes();

        $this->post(route('password.verify'), ['email' => $user->email, 'otp' => $otp])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('error');
        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_resending_otp_invalidates_the_previous_code(): void
    {
        $user = User::factory()->create();
        Http::fake();
        config([
            'services.nodemailer.url' => 'http://127.0.0.1:3001',
            'services.nodemailer.secret' => str_repeat('a', 64),
        ]);

        $this->post(route('password.email'), ['email' => $user->email]);
        $firstOtp = json_decode(Http::recorded()->last()[0]->body(), true, flags: JSON_THROW_ON_ERROR)['otp'];
        $this->travel(60)->seconds();
        $this->post(route('password.email'), ['email' => $user->email]);
        $secondOtp = json_decode(Http::recorded()->last()[0]->body(), true, flags: JSON_THROW_ON_ERROR)['otp'];

        $this->assertNotSame($firstOtp, $secondOtp);
        $this->post(route('password.verify'), ['email' => $user->email, 'otp' => $firstOtp])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('error');
        $this->post(route('password.verify'), ['email' => $user->email, 'otp' => $secondOtp])
            ->assertRedirect(route('password.reset'));
    }

    public function test_resend_cooldown_is_sixty_seconds(): void
    {
        $user = User::factory()->create();
        Http::fake();
        config([
            'services.nodemailer.url' => 'http://127.0.0.1:3001',
            'services.nodemailer.secret' => str_repeat('a', 64),
        ]);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.request'));
        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.request'))
            ->assertSessionHas('error');
    }
}
