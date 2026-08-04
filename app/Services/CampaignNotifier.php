<?php

namespace App\Services;

use App\Enums\PlatformRole;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SmsRequest;
use App\Models\User;
use App\Notifications\CampaignFulfilledNotification;
use App\Notifications\CampaignSubmittedNotification;
use App\Notifications\ChangesRequestedNotification;
use App\Notifications\InvoiceReadyNotification;
use App\Notifications\PaymentReceivedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class CampaignNotifier
{
    public function submitted(SmsRequest $request): void
    {
        Notification::send(
            $this->staff([PlatformRole::SuperAdmin, PlatformRole::Admin, PlatformRole::Support]),
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
            $this->staff([PlatformRole::SuperAdmin, PlatformRole::Admin, PlatformRole::Finance]),
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

    /**
     * @param  list<PlatformRole>  $roles
     * @return Collection<int, User>
     */
    private function staff(array $roles): Collection
    {
        $names = array_map(fn (PlatformRole $role) => $role->value, $roles);

        return User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', $names))
            ->get();
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
