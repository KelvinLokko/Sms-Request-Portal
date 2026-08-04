<?php

namespace Tests\Concerns;

use App\Enums\CompanyStatus;
use App\Enums\CompanyUserRole;
use App\Enums\PlatformRole;
use App\Models\Company;
use App\Models\User;
use Database\Seeders\RoleSeeder;

trait CreatesCompanies
{
    protected function seedRoles(): void
    {
        $this->seed(RoleSeeder::class);
    }

    /**
     * @param  array<string, mixed>  $companyAttributes
     * @param  array<string, mixed>  $userAttributes
     * @return array{0: User, 1: Company}
     */
    protected function createCompanyOwner(
        array $companyAttributes = [],
        array $userAttributes = [],
        CompanyUserRole $role = CompanyUserRole::Owner,
    ): array {
        $company = Company::factory()->create($companyAttributes);

        $user = User::factory()->create([
            'current_company_id' => $company->id,
            ...$userAttributes,
        ]);

        $company->attachUser($user, $role);

        return [$user, $company];
    }

    /**
     * @param  array<string, mixed>  $companyAttributes
     * @param  array<string, mixed>  $userAttributes
     * @return array{0: User, 1: Company}
     */
    protected function createApprovedCompanyOwner(
        array $companyAttributes = [],
        array $userAttributes = [],
    ): array {
        return $this->createCompanyOwner([
            'status' => CompanyStatus::Approved,
            'approved_at' => now(),
            ...$companyAttributes,
        ], $userAttributes);
    }

    protected function createPlatformAdmin(PlatformRole $role = PlatformRole::Admin): User
    {
        $this->seedRoles();

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }
}
