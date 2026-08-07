<?php

namespace App\Services;

use App\Mail\RegistrationOtpMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RegistrationOtpService
{
    private const OTP_TTL_MINUTES = 15;

    private const TOKEN_TTL_MINUTES = 30;

    private const MAX_ATTEMPTS = 5;

    public function send(string $email, string $name): void
    {
        $email = $this->normaliseEmail($email);
        $code = (string) random_int(100000, 999999);

        Cache::put($this->otpKey($email), [
            'hash' => Hash::make($code),
            'name' => $name,
            'attempts' => 0,
        ], now()->addMinutes(self::OTP_TTL_MINUTES));

        Mail::to($email)->send(new RegistrationOtpMail($code, $name));
    }

    /**
     * @throws ValidationException
     */
    public function verify(string $email, string $code): string
    {
        $email = $this->normaliseEmail($email);
        $payload = Cache::get($this->otpKey($email));

        if (! is_array($payload) || ! isset($payload['hash'], $payload['name'])) {
            throw ValidationException::withMessages([
                'code' => 'That code has expired. Request a new one.',
            ]);
        }

        $attempts = (int) ($payload['attempts'] ?? 0);

        if ($attempts >= self::MAX_ATTEMPTS) {
            Cache::forget($this->otpKey($email));

            throw ValidationException::withMessages([
                'code' => 'Too many incorrect attempts. Request a new code.',
            ]);
        }

        if (! Hash::check($code, $payload['hash'])) {
            $payload['attempts'] = $attempts + 1;
            Cache::put($this->otpKey($email), $payload, now()->addMinutes(self::OTP_TTL_MINUTES));

            throw ValidationException::withMessages([
                'code' => 'That code is incorrect.',
            ]);
        }

        Cache::forget($this->otpKey($email));

        $token = Str::random(64);

        Cache::put($this->tokenKey($token), [
            'email' => $email,
            'name' => $payload['name'],
        ], now()->addMinutes(self::TOKEN_TTL_MINUTES));

        return $token;
    }

    /**
     * Consume a one-time registration token and return the verified identity.
     *
     * @return array{email: string, name: string}
     *
     * @throws ValidationException
     */
    public function consume(string $token, string $email): array
    {
        $email = $this->normaliseEmail($email);
        $payload = Cache::pull($this->tokenKey($token));

        if (! is_array($payload)
            || ! isset($payload['email'], $payload['name'])
            || ! hash_equals((string) $payload['email'], $email)
        ) {
            throw ValidationException::withMessages([
                'email' => 'Verify your email with the code we sent before creating an account.',
            ]);
        }

        return [
            'email' => (string) $payload['email'],
            'name' => (string) $payload['name'],
        ];
    }

    private function normaliseEmail(string $email): string
    {
        return Str::lower(trim($email));
    }

    private function otpKey(string $email): string
    {
        return 'register:otp:'.sha1($email);
    }

    private function tokenKey(string $token): string
    {
        return 'register:verified:'.sha1($token);
    }
}
