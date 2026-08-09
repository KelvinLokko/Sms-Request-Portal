<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\SmsRequestStatus;
use App\Jobs\GenerateInvoicePdfJob;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\SmsRequest;
use App\Models\User;
use App\Support\TaxCalculator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function __construct(
        private InvoiceNumberGenerator $numbers,
        private ActivityLogger $logger,
        private CampaignNotifier $notifier,
        private ProviderCostService $providerCosts,
    ) {}

    public function issue(SmsRequest $request, User $issuer): Invoice
    {
        if (! in_array($request->status, [SmsRequestStatus::UnderReview, SmsRequestStatus::Submitted], true)) {
            throw ValidationException::withMessages([
                'status' => 'Only submitted or under-review campaigns can be invoiced.',
            ]);
        }

        if ($request->quoted_cost_pesewas === null || $request->quoted_cost_pesewas < 1) {
            throw ValidationException::withMessages([
                'quoted_cost' => 'A positive quoted cost is required before invoicing.',
            ]);
        }

        if ($request->requires_manual_cost_review && $request->status !== SmsRequestStatus::UnderReview) {
            throw ValidationException::withMessages([
                'status' => 'Unicode campaigns require an explicit under-review step before invoicing.',
            ]);
        }

        return DB::transaction(function () use ($request, $issuer): Invoice {
            $locked = SmsRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if (Invoice::query()->where('sms_request_id', $locked->id)->exists()) {
                throw ValidationException::withMessages([
                    'invoice' => 'An invoice already exists for this campaign.',
                ]);
            }

            $subtotal = (int) $locked->quoted_cost_pesewas;
            $tax = TaxCalculator::apply($subtotal);
            $year = (int) now()->format('Y');
            $number = $this->numbers->next($year);

            $pages = max(1, (int) $locked->pages);
            $billable = max(1, (int) $locked->billable_recipients);
            $quantity = $billable * $pages;
            $unitPrice = intdiv($subtotal, $quantity);
            // Absorb rounding remainder on the last unit so the line totals match the frozen quote.
            $lineAmount = $subtotal;

            $invoice = Invoice::query()->create([
                'company_id' => $locked->company_id,
                'sms_request_id' => $locked->id,
                'number' => $number,
                'status' => InvoiceStatus::Issued,
                'subtotal_pesewas' => $subtotal,
                'tax_pesewas' => $tax['tax_pesewas'],
                'total_pesewas' => $tax['total_pesewas'],
                'currency' => 'GHS',
                'issued_at' => now(),
                'issued_by' => $issuer->id,
                'due_at' => now()->addDays(7)->toDateString(),
            ]);

            InvoiceItem::query()->create([
                'invoice_id' => $invoice->id,
                'description' => sprintf(
                    'SMS campaign %s — %d recipients × %d page(s) @ %s GHS/SMS',
                    $locked->reference,
                    $billable,
                    $pages,
                    (string) $locked->rate_per_sms,
                ),
                'quantity' => $quantity,
                'unit_price_pesewas' => $unitPrice,
                'amount_pesewas' => $lineAmount,
                'meta' => [
                    'billable_recipients' => $billable,
                    'pages' => $pages,
                    'rate_per_sms' => (string) $locked->rate_per_sms,
                    'tax_lines' => $tax['lines'],
                    'campaign_reference' => $locked->reference,
                ],
                'position' => 0,
            ]);

            foreach ($tax['lines'] as $index => $line) {
                InvoiceItem::query()->create([
                    'invoice_id' => $invoice->id,
                    'description' => sprintf('%s (%s%%)', $line['name'], $line['rate']),
                    'quantity' => 1,
                    'unit_price_pesewas' => $line['amount_pesewas'],
                    'amount_pesewas' => $line['amount_pesewas'],
                    'meta' => ['tax' => true, 'rate' => $line['rate']],
                    'position' => $index + 1,
                ]);
            }

            $beforeStatus = $locked->status->value;

            $this->providerCosts->snapshot($locked, required: true);

            $locked->forceFill([
                'status' => SmsRequestStatus::Invoiced,
                'rejection_reason' => null,
                'changes_requested_reason' => null,
            ])->save();

            $this->logger->log('invoice.issued', $invoice, [
                'number' => $invoice->number,
                'total_pesewas' => $invoice->total_pesewas,
                'sms_request_id' => $locked->id,
            ], $issuer);

            $this->logger->logChange('sms_request.invoiced', $locked, [
                'status' => $beforeStatus,
            ], [
                'status' => $locked->status->value,
                'invoice_number' => $invoice->number,
            ], [], $issuer);

            GenerateInvoicePdfJob::dispatch($invoice->id);

            DB::afterCommit(fn () => $this->notifier->invoiceReady($invoice));

            return $invoice->load('items');
        });
    }

    public function void(Invoice $invoice, User $actor, string $reason): Invoice
    {
        if ($invoice->status !== InvoiceStatus::Issued) {
            throw ValidationException::withMessages([
                'invoice' => 'Only issued invoices can be voided.',
            ]);
        }

        return DB::transaction(function () use ($invoice, $actor, $reason): Invoice {
            $locked = Invoice::query()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();
            $before = ['status' => $locked->status->value];

            $locked->forceFill([
                'status' => InvoiceStatus::Voided,
                'voided_at' => now(),
            ])->save();

            CreditNote::query()->create([
                'invoice_id' => $locked->id,
                'company_id' => $locked->company_id,
                'number' => str_replace('INV-', 'CN-', $locked->number),
                'amount_pesewas' => $locked->total_pesewas,
                'reason' => $reason,
                'issued_by' => $actor->id,
                'issued_at' => now(),
            ]);

            $this->logger->logChange('invoice.voided', $locked, $before, [
                'status' => $locked->status->value,
                'voided_at' => $locked->voided_at?->toIso8601String(),
            ], ['reason' => $reason], $actor);

            return $locked;
        });
    }
}
