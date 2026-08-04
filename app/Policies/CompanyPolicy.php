<?php

namespace App\Policies;

use App\Enums\CompanyStatus;
use App\Enums\PlatformRole;
use App\Models\Company;
use App\Models\User;

class CompanyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            PlatformRole::SuperAdmin->value,
            PlatformRole::Admin->value,
            PlatformRole::Support->value,
            PlatformRole::Finance->value,
        ]);
    }

    public function view(User $user, Company $company): bool
    {
        if ($user->isPlatformStaff()) {
            return true;
        }

        return $user->belongsToCompany($company);
    }

    public function update(User $user, Company $company): bool
    {
        return $user->hasAnyRole([
            PlatformRole::SuperAdmin->value,
            PlatformRole::Admin->value,
        ]);
    }

    public function approve(User $user, Company $company): bool
    {
        return $this->update($user, $company)
            && $company->status === CompanyStatus::Pending;
    }

    public function reject(User $user, Company $company): bool
    {
        return $this->approve($user, $company);
    }

    public function suspend(User $user, Company $company): bool
    {
        return $this->update($user, $company)
            && $company->status === CompanyStatus::Approved;
    }
}
