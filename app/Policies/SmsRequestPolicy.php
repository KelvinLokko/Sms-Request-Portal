<?php

namespace App\Policies;

use App\Enums\CompanyUserRole;
use App\Enums\PlatformPermission;
use App\Enums\SmsRequestStatus;
use App\Models\SmsRequest;
use App\Models\User;

class SmsRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isPlatformStaff() || $user->currentCompanyId() !== null;
    }

    public function view(User $user, SmsRequest $smsRequest): bool
    {
        if ($user->isPlatformStaff()) {
            return true;
        }

        return $user->currentCompanyId() === $smsRequest->company_id;
    }

    public function create(User $user): bool
    {
        if ($user->isPlatformStaff()) {
            return false;
        }

        $role = $user->companyRole();

        return $role !== null && in_array($role, [CompanyUserRole::Owner, CompanyUserRole::Manager], true)
            && $user->currentCompany?->isApproved();
    }

    public function update(User $user, SmsRequest $smsRequest): bool
    {
        if (! $this->view($user, $smsRequest) || $user->isPlatformStaff()) {
            return false;
        }

        $role = $user->companyRole();

        return $role !== null
            && in_array($role, [CompanyUserRole::Owner, CompanyUserRole::Manager], true)
            && $smsRequest->status->isEditable();
    }

    public function submit(User $user, SmsRequest $smsRequest): bool
    {
        return $this->update($user, $smsRequest) && $smsRequest->status->canSubmit();
    }

    public function cancel(User $user, SmsRequest $smsRequest): bool
    {
        if ($user->isPlatformStaff()) {
            return $user->can(PlatformPermission::CampaignsCancel->value);
        }

        if ($user->currentCompanyId() !== $smsRequest->company_id) {
            return false;
        }

        $role = $user->companyRole();

        return $role !== null
            && in_array($role, [CompanyUserRole::Owner, CompanyUserRole::Manager], true)
            && $smsRequest->status->canCancel();
    }

    public function uploadRecipients(User $user, SmsRequest $smsRequest): bool
    {
        return $this->update($user, $smsRequest);
    }

    public function downloadRejected(User $user, SmsRequest $smsRequest): bool
    {
        return $this->view($user, $smsRequest);
    }

    public function review(User $user, SmsRequest $smsRequest): bool
    {
        return $user->can(PlatformPermission::CampaignsReview->value);
    }

    public function requestChanges(User $user, SmsRequest $smsRequest): bool
    {
        return $this->review($user, $smsRequest)
            && in_array($smsRequest->status, [
                SmsRequestStatus::Submitted,
                SmsRequestStatus::UnderReview,
            ], true);
    }

    public function reject(User $user, SmsRequest $smsRequest): bool
    {
        return $this->requestChanges($user, $smsRequest);
    }

    public function issueInvoice(User $user, SmsRequest $smsRequest): bool
    {
        return $user->can(PlatformPermission::CampaignsInvoice->value)
            && in_array($smsRequest->status, [
                SmsRequestStatus::Submitted,
                SmsRequestStatus::UnderReview,
            ], true);
    }

    public function fulfil(User $user, SmsRequest $smsRequest): bool
    {
        return $user->can(PlatformPermission::CampaignsFulfil->value)
            && $smsRequest->status->canFulfil();
    }

    public function downloadCleanedRecipients(User $user, SmsRequest $smsRequest): bool
    {
        return $user->can(PlatformPermission::CampaignsFulfil->value)
            && in_array($smsRequest->status, [
                SmsRequestStatus::Paid,
                SmsRequestStatus::AwaitingFulfilment,
                SmsRequestStatus::Fulfilled,
            ], true);
    }
}
