<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\CompanyStatus;
use App\Enums\CompanyUserRole;
use App\Models\Company;
use App\Models\User;
use App\Services\RegistrationOtpService;
use App\Services\Turnstile;
use App\Support\Honeypot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user with their company.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Honeypot::assertEmpty($input[Honeypot::FIELD] ?? null);

        $ip = request()->ip() ?? 'unknown';
        $registerKey = 'register:'.$ip;

        if (RateLimiter::tooManyAttempts($registerKey, 5)) {
            throw ValidationException::withMessages([
                'email' => 'Too many registration attempts. Please try again later.',
            ]);
        }

        RateLimiter::hit($registerKey, 3600);

        app(Turnstile::class)->assertValid(
            $input['cf-turnstile-response'] ?? null,
            $ip,
        );

        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'company_name' => ['required', 'string', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:32'],
            'registration_token' => ['required', 'string', 'size:64'],
            Honeypot::FIELD => ['nullable', 'string', 'max:0'],
            'cf-turnstile-response' => ['nullable', 'string'],
        ])->validate();

        $verified = app(RegistrationOtpService::class)->consume(
            $input['registration_token'],
            $input['email'],
        );

        return DB::transaction(function () use ($input, $verified): User {
            $company = Company::query()->create([
                'name' => $input['company_name'],
                'email' => $verified['email'],
                'phone' => $input['company_phone'] ?? null,
                'status' => CompanyStatus::Pending,
            ]);

            $user = User::query()->create([
                'name' => $verified['name'],
                'email' => $verified['email'],
                'password' => $input['password'],
                'current_company_id' => $company->id,
            ]);

            $user->forceFill([
                'email_verified_at' => now(),
            ])->save();

            $company->attachUser($user, CompanyUserRole::Owner);

            return $user->fresh();
        });
    }
}
