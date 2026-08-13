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
use App\Notifications\CompanyRegisteredNotification;
use App\Notifications\InvoiceReadyNotification;
use App\Notifications\PaymentReceivedNotification;
use App\Notifications\PaymentVerifiedNotification;
use App\Notifications\SenderIdApprovedNotification;
use App\Notifications\SenderIdRequestedNotification;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class CampaignNotifier
{
    public function companyRegistered(Company $company): void
    {
        $this->notifySupport(new CompanyRegisteredNotification($company));
    }

    public function senderIdRequested(SenderId $senderId): void
    {
        $this->notifySupport(new SenderIdRequestedNotification($senderId));
    }

    public function submitted(SmsRequest $request): void
    {
        $notification = new CampaignSubmittedNotification($request);

        Notification::send(
            $this->staffWith(PlatformPermission::CampaignsReview),
            $notification,
        );

        $this->notifySupport(new CampaignSubmittedNotification($request));
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
        $notification = new PaymentReceivedNotification($payment);

        Notification::send(
            $this->staffWith(PlatformPermission::PaymentsManage),
            $notification,
        );

        $this->notifySupport(new PaymentReceivedNotification($payment));
    }

    public function paymentVerified(Payment $payment): void
    {
        $this->notifySupport(new PaymentVerifiedNotification($payment));
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

    private function notifySupport(BaseNotification $notification): void
    {
        $email = config('notifications.support.email');

        if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        Notification::route('mail', $email)->notify($notification);
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
