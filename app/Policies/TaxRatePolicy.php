<?php

namespace App\Policies;

use App\Enums\PlatformPermission;
use App\Models\TaxRate;
use App\Models\User;

class TaxRatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can(PlatformPermission::TaxRatesManage->value);
    }

    public function view(User $user, TaxRate $taxRate): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->can(PlatformPermission::TaxRatesManage->value);
    }

    public function update(User $user, TaxRate $taxRate): bool
    {
        return $this->create($user);
    }
}
