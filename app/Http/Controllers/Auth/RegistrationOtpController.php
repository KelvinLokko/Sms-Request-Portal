<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendRegistrationOtpRequest;
use App\Http\Requests\Auth\VerifyRegistrationOtpRequest;
use App\Services\RegistrationOtpService;
use App\Services\Turnstile;
use App\Support\Honeypot;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class RegistrationOtpController extends Controller
{
    public function send(
        SendRegistrationOtpRequest $request,
        RegistrationOtpService $otp,
        Turnstile $turnstile,
    ): JsonResponse {
        Honeypot::assertEmpty($request->input(Honeypot::FIELD));
        $turnstile->assertValid(
            $request->input('cf-turnstile-response'),
            $request->ip(),
        );

        $email = $request->string('email')->toString();
        $ipKey = 'register-otp-ip:'.$request->ip();
        $emailKey = 'register-otp-email:'.sha1(strtolower($email));

        if (RateLimiter::tooManyAttempts($ipKey, 10)) {
            throw ValidationException::withMessages([
                'email' => 'Too many verification requests from this network. Try again later.',
            ]);
        }

        if (RateLimiter::tooManyAttempts($emailKey, 3)) {
            throw ValidationException::withMessages([
                'email' => 'Too many codes sent to this email. Try again in a few minutes.',
            ]);
        }

        RateLimiter::hit($ipKey, 3600);
        RateLimiter::hit($emailKey, 900);

        $otp->send($email, $request->string('name')->toString());

        return response()->json([
            'message' => 'We sent a 6-digit code to your email.',
        ]);
    }

    public function verify(
        VerifyRegistrationOtpRequest $request,
        RegistrationOtpService $otp,
    ): JsonResponse {
        Honeypot::assertEmpty($request->input(Honeypot::FIELD));

        $email = $request->string('email')->toString();
        $key = 'register-otp-verify:'.sha1(strtolower($email).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($key, 10)) {
            throw ValidationException::withMessages([
                'code' => 'Too many attempts. Request a new code and try again later.',
            ]);
        }

        RateLimiter::hit($key, 900);

        $token = $otp->verify($email, $request->string('code')->toString());

        RateLimiter::clear($key);

        return response()->json([
            'registration_token' => $token,
            'message' => 'Email verified. Continue with your company details.',
        ]);
    }
}
