<?php

namespace Database\Seeders;

use App\Enums\CompanyStatus;
use App\Enums\CompanyUserRole;
use App\Enums\PlatformRole;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PricingSeeder::class,
        ]);

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Platform Admin',
                'password' => 'password',
            ],
        );
        $admin->assignRole(PlatformRole::SuperAdmin);

        $company = Company::query()->firstOrCreate(
            ['email' => 'demo@example.com'],
            [
                'name' => 'Demo Company',
                'status' => CompanyStatus::Approved,
                'approved_by' => $admin->id,
                'approved_at' => now(),
            ],
        );

        if ($company->status !== CompanyStatus::Approved) {
            $company->forceFill([
                'status' => CompanyStatus::Approved,
                'approved_by' => $admin->id,
                'approved_at' => now(),
            ])->save();
        }

        $owner = User::query()->firstOrCreate(
            ['email' => 'owner@example.com'],
            [
                'name' => 'Demo Owner',
                'password' => 'password',
                'current_company_id' => $company->id,
            ],
        );

        if ($owner->current_company_id !== $company->id) {
            $owner->forceFill(['current_company_id' => $company->id])->save();
        }

        if (! $owner->belongsToCompany($company)) {
            $company->attachUser($owner, CompanyUserRole::Owner);
        }
    }
}
