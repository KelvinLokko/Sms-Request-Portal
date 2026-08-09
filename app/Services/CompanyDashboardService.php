<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\SenderIdStatus;
use App\Enums\SmsRequestStatus;
use App\Models\Invoice;
use App\Models\SenderId;
use App\Models\SmsRequest;
use App\Support\Money;
use Illuminate\Support\Facades\DB;

class CompanyDashboardService
{
    /**
     * Company-scoped overview for the client portal dashboard.
     *
     * @return array{
     *     stats: array<string, int|string>,
     *     recent_campaigns: list<array<string, mixed>>,
     *     recent_invoices: list<array<string, mixed>>,
     *     sender_ids: list<array<string, mixed>>,
     * }
     */
    public function overview(): array
    {
        return [
            'stats' => $this->stats(),
            'recent_campaigns' => $this->recentCampaigns(),
            'recent_invoices' => $this->recentInvoices(),
            'sender_ids' => $this->senderIds(),
        ];
    }

    /**
     * @return array<string, int|string>
     */
    public function stats(): array
    {
        $inFlight = [
            SmsRequestStatus::Submitted,
            SmsRequestStatus::UnderReview,
            SmsRequestStatus::ChangesRequested,
            SmsRequestStatus::Invoiced,
            SmsRequestStatus::Paid,
            SmsRequestStatus::AwaitingFulfilment,
        ];

        $smsVolume = (int) SmsRequest::query()
            ->whereIn('status', [
                SmsRequestStatus::Paid,
                SmsRequestStatus::AwaitingFulfilment,
                SmsRequestStatus::Fulfilled,
            ])
            ->sum(DB::raw('COALESCE(billable_recipients, 0) * COALESCE(pages, 1)'));

        $openInvoiceTotal = (int) Invoice::query()
            ->where('status', InvoiceStatus::Issued)
            ->sum('total_pesewas');

        return [
            'drafts' => SmsRequest::query()->where('status', SmsRequestStatus::Draft)->count(),
            'in_flight' => SmsRequest::query()->whereIn('status', $inFlight)->count(),
            'fulfilled' => SmsRequest::query()->where('status', SmsRequestStatus::Fulfilled)->count(),
            'open_invoices' => Invoice::query()->where('status', InvoiceStatus::Issued)->count(),
            'open_invoice_total' => Money::format($openInvoiceTotal),
            'approved_sender_ids' => SenderId::query()->where('status', SenderIdStatus::Approved)->count(),
            'pending_sender_ids' => SenderId::query()->where('status', SenderIdStatus::Pending)->count(),
            'sms_volume' => $smsVolume,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentCampaigns(): array
    {
        return SmsRequest::query()
            ->with(['senderId:id,value'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (SmsRequest $campaign) => [
                'id' => $campaign->id,
                'reference' => $campaign->reference,
                'name' => $campaign->name,
                'status' => $campaign->status->value,
                'status_label' => $campaign->status->label(),
                'sender_id' => $campaign->senderId?->value,
                'billable_recipients' => $campaign->billable_recipients,
                'quoted_cost' => $campaign->quoted_cost_pesewas !== null
                    ? Money::format($campaign->quoted_cost_pesewas)
                    : null,
                'estimated_cost' => $campaign->estimated_cost_pesewas !== null
                    ? Money::format($campaign->estimated_cost_pesewas)
                    : null,
                'created_at' => $campaign->created_at?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentInvoices(): array
    {
        return Invoice::query()
            ->with(['smsRequest:id,reference,name'])
            ->latest('issued_at')
            ->limit(5)
            ->get()
            ->map(fn (Invoice $invoice) => [
                'id' => $invoice->id,
                'number' => $invoice->number,
                'status' => $invoice->status->value,
                'status_label' => $invoice->status->label(),
                'total' => Money::format($invoice->total_pesewas),
                'campaign_reference' => $invoice->smsRequest?->reference,
                'issued_at' => $invoice->issued_at?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function senderIds(): array
    {
        return SenderId::query()
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (SenderId $senderId) => [
                'id' => $senderId->id,
                'value' => $senderId->value,
                'status' => $senderId->status->value,
                'status_label' => $senderId->status->label(),
            ])
            ->all();
    }
}
