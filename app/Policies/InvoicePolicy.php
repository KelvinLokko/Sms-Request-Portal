<?php

namespace App\Policies;

use App\Enums\CompanyUserRole;
use App\Enums\InvoiceStatus;
use App\Enums\PlatformRole;
use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformStaff() || $user->currentCompanyId() !== null;
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->isPlatformStaff()) {
            return true;
        }

        return $user->currentCompanyId() === $invoice->company_id;
    }

    public function download(User $user, Invoice $invoice): bool
    {
        return $this->view($user, $invoice) && $invoice->hasPdf();
    }

    public function pay(User $user, Invoice $invoice): bool
    {
        if ($user->isPlatformStaff() || $invoice->status !== InvoiceStatus::Issued) {
            return false;
        }

        if ($user->currentCompanyId() !== $invoice->company_id) {
            return false;
        }

        $role = $user->companyRole();

        return $role !== null && in_array($role, [
            CompanyUserRole::Owner,
            CompanyUserRole::Manager,
            CompanyUserRole::Billing,
        ], true);
    }

    public function issue(User $user): bool
    {
        return $user->hasAnyRole([
            PlatformRole::SuperAdmin->value,
            PlatformRole::Admin->value,
            PlatformRole::Finance->value,
        ]);
    }
}
