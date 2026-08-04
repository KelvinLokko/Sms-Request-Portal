<?php

namespace App\Policies;

use App\Enums\PlatformPermission;
use App\Models\CompanyRate;
use App\Models\User;

class CompanyRatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PlatformPermission::RatesManage->value);
    }

    public function view(User $user, CompanyRate $companyRate): bool
    {
        if ($this->viewAny($user)) {
            return true;
        }

        return $user->currentCompanyId() !== null
            && $companyRate->company_id === $user->currentCompanyId();
    }

    public function create(User $user): bool
    {
        return $user->can(PlatformPermission::RatesManage->value);
    }
}
