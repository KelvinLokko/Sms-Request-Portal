<?php

namespace App\Policies;

use App\Enums\PlatformRole;
use App\Models\TaxRate;
use App\Models\User;

class TaxRatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            PlatformRole::SuperAdmin->value,
            PlatformRole::Admin->value,
            PlatformRole::Finance->value,
        ]);
    }

    public function view(User $user, TaxRate $taxRate): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasAnyRole([
            PlatformRole::SuperAdmin->value,
            PlatformRole::Admin->value,
            PlatformRole::Finance->value,
        ]);
    }

    public function update(User $user, TaxRate $taxRate): bool
    {
        return $this->create($user);
    }
}
