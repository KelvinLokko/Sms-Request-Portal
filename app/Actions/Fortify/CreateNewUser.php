<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\CompanyStatus;
use App\Enums\CompanyUserRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
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
        Validator::make($input, [
            ...$this->profileRules(),
            'password' => $this->passwordRules(),
            'company_name' => ['required', 'string', 'max:255'],
            'company_phone' => ['nullable', 'string', 'max:32'],
        ])->validate();

        return DB::transaction(function () use ($input): User {
            $company = Company::query()->create([
                'name' => $input['company_name'],
                'email' => $input['email'],
                'phone' => $input['company_phone'] ?? null,
                'status' => CompanyStatus::Pending,
            ]);

            $user = User::query()->create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
                'current_company_id' => $company->id,
            ]);

            $company->attachUser($user, CompanyUserRole::Owner);

            return $user;
        });
    }
}
