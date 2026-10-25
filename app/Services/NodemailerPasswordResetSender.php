<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use JsonException;

class NodemailerPasswordResetSender
{
    public function send(string $email, string $otp, int $expiresInMinutes = 15): void
    {
        $secret = config('services.nodemailer.secret');

        if (! is_string($secret) || strlen($secret) < 32) {
            throw new PasswordResetDeliveryException('Nodemailer shared secret must contain at least 32 characters.');
        }

        try {
            $payload = json_encode([
                'email' => $email,
                'otp' => $otp,
                'expiresInMinutes' => $expiresInMinutes,
            ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $exception) {
            throw new PasswordResetDeliveryException('Could not encode the password reset OTP request.', previous: $exception);
        }

        $timestamp = (string) now()->timestamp;
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);
        $url = rtrim((string) config('services.nodemailer.url'), '/').'/send-password-reset-otp';

        try {
            Http::timeout((int) config('services.nodemailer.timeout', 5))
                ->withHeaders([
                    'X-Reset-Timestamp' => $timestamp,
                    'X-Reset-Signature' => $signature,
                ])
                ->withBody($payload, 'application/json')
                ->post($url)
                ->throw();
        } catch (ConnectionException|RequestException $exception) {
            throw new PasswordResetDeliveryException('Nodemailer service rejected the password reset email.', previous: $exception);
        }
    }
}
