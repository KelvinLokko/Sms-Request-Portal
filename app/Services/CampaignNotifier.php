<?php

namespace App\Services;

use App\Enums\PlatformPermission;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SenderId;
use App\Models\SmsRequest;
use App\Models\User;
use App\Notifications\CampaignFulfilledNotification;
use App\Notifications\CampaignSubmittedNotification;
use App\Notifications\ChangesRequestedNotification;
use App\Notifications\CompanyApprovedNotification;
use App\Notifications\InvoiceReadyNotification;
use App\Notifications\PaymentReceivedNotification;
use App\Notifications\SenderIdApprovedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class CampaignNotifier
{
    public function submitted(SmsRequest $request): void
    {
        Notification::send(
            $this->staffWith(PlatformPermission::CampaignsReview),
            new CampaignSubmittedNotification($request),
        );
    }

    public function changesRequested(SmsRequest $request): void
    {
        Notification::send(
            $this->companyUsers($request->company_id),
            new ChangesRequestedNotification($request),
        );
    }

    public function invoiceReady(Invoice $invoice): void
    {
        Notification::send(
            $this->companyUsers($invoice->company_id),
            new InvoiceReadyNotification($invoice),
        );
    }

    public function paymentReceived(Payment $payment): void
    {
        Notification::send(
            $this->staffWith(PlatformPermission::PaymentsManage),
            new PaymentReceivedNotification($payment),
        );
    }

    public function fulfilled(SmsRequest $request): void
    {
        Notification::send(
            $this->companyUsers($request->company_id),
            new CampaignFulfilledNotification($request),
        );
    }

    public function senderIdApproved(SenderId $senderId): void
    {
        $senderId->loadMissing('requester');

        if ($senderId->requester === null) {
            return;
        }

        $senderId->requester->notify(new SenderIdApprovedNotification($senderId));
    }

    public function companyApproved(Company $company): void
    {
        Notification::send(
            $this->companyUsers($company->id),
            new CompanyApprovedNotification($company),
        );
    }

    /**
     * @return Collection<int, User>
     */
    private function staffWith(PlatformPermission $permission): Collection
    {
        return User::permission($permission->value)->get();
    }

    /**
     * @return Collection<int, User>
     */
    private function companyUsers(int $companyId): Collection
    {
        return User::query()
            ->whereHas('companies', fn ($q) => $q->where('companies.id', $companyId))
            ->get();
    }
}
