<?php

namespace Tests\Concerns;

use App\Mail\RegistrationOtpMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;

trait RegistersVerifiedUsers
{
    /**
     * Complete email OTP verification and return the registration token.
     */
    protected function registrationTokenFor(string $email, string $name = 'Test User'): string
    {
        Mail::fake();

        $this->postJson(route('register.otp.send'), [
            'name' => $name,
            'email' => $email,
            'website' => '',
        ])->assertOk();

        $code = null;

        Mail::assertSent(RegistrationOtpMail::class, function (RegistrationOtpMail $mail) use (&$code): bool {
            $code = $mail->code;

            return true;
        });

        expect($code)->not->toBeNull();

        $response = $this->postJson(route('register.otp.verify'), [
            'email' => $email,
            'code' => $code,
            'website' => '',
        ])->assertOk();

        return (string) $response->json('registration_token');
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    protected function registerVerifiedUser(array $overrides = []): TestResponse
    {
        $email = $overrides['email'] ?? 'test@example.com';
        $name = $overrides['name'] ?? 'Test User';
        $token = $this->registrationTokenFor($email, $name);

        return $this->post(route('register.store'), array_merge([
            'name' => $name,
            'email' => $email,
            'password' => 'SmsPortal-Test-Pass1!',
            'password_confirmation' => 'SmsPortal-Test-Pass1!',
            'company_name' => 'Test Company Ltd',
            'registration_token' => $token,
            'website' => '',
        ], $overrides));
    }
}
