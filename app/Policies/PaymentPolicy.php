<?php

namespace App\Policies;

use App\Enums\CompanyUserRole;
use App\Enums\PaymentStatus;
use App\Enums\PlatformRole;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            PlatformRole::SuperAdmin->value,
            PlatformRole::Admin->value,
            PlatformRole::Finance->value,
        ]) || $user->currentCompanyId() !== null;
    }

    public function view(User $user, Payment $payment): bool
    {
        if ($user->hasAnyRole([
            PlatformRole::SuperAdmin->value,
            PlatformRole::Admin->value,
            PlatformRole::Finance->value,
        ])) {
            return true;
        }

        return $user->currentCompanyId() === $payment->company_id;
    }

    public function create(User $user): bool
    {
        if ($user->isPlatformStaff()) {
            return false;
        }

        $role = $user->companyRole();

        return $role !== null && in_array($role, [
            CompanyUserRole::Owner,
            CompanyUserRole::Manager,
            CompanyUserRole::Billing,
        ], true);
    }

    public function verify(User $user, Payment $payment): bool
    {
        return $user->hasAnyRole([
            PlatformRole::SuperAdmin->value,
            PlatformRole::Admin->value,
            PlatformRole::Finance->value,
        ]) && $payment->status === PaymentStatus::Pending;
    }

    public function reject(User $user, Payment $payment): bool
    {
        return $this->verify($user, $payment);
    }

    public function downloadProof(User $user, Payment $payment): bool
    {
        return $this->view($user, $payment) && $payment->hasProof();
    }
}
