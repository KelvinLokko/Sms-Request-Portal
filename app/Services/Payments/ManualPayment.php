<?php

namespace App\Services\Payments;

use App\Contracts\PaymentProvider;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\SmsRequestStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\SmsRequest;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\CampaignNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManualPayment implements PaymentProvider
{
    public function __construct(
        private ActivityLogger $logger,
        private CampaignNotifier $notifier,
    ) {}

    public function name(): string
    {
        return 'manual';
    }

    public function submit(Invoice $invoice, User $submitter, array $payload): Payment
    {
        if ($invoice->status !== InvoiceStatus::Issued) {
            throw ValidationException::withMessages([
                'invoice' => 'Payments can only be submitted against an open invoice.',
            ]);
        }

        $pendingExists = Payment::query()
            ->where('invoice_id', $invoice->id)
            ->where('status', PaymentStatus::Pending)
            ->exists();

        if ($pendingExists) {
            throw ValidationException::withMessages([
                'invoice' => 'A payment is already pending verification for this invoice.',
            ]);
        }

        return DB::transaction(function () use ($invoice, $submitter, $payload): Payment {
            $payment = Payment::query()->create([
                'invoice_id' => $invoice->id,
                'company_id' => $invoice->company_id,
                'provider' => $this->name(),
                'status' => PaymentStatus::Pending,
                'amount_pesewas' => $payload['amount_pesewas'],
                'momo_reference' => $payload['momo_reference'],
                'payer_number' => $payload['payer_number'],
                'proof_path' => $payload['proof_path'] ?? null,
                'submitted_by' => $submitter->id,
            ]);

            $this->ledger($payment, 'submitted', $submitter, [
                'momo_reference' => $payment->momo_reference,
                'payer_number' => $payment->payer_number,
            ]);

            $this->logger->log('payment.submitted', $payment, [
                'invoice_number' => $invoice->number,
                'amount_pesewas' => $payment->amount_pesewas,
            ], $submitter);

            DB::afterCommit(fn () => $this->notifier->paymentReceived($payment));

            return $payment;
        });
    }

    public function verify(Payment $payment, User $actor): Payment
    {
        if ($payment->status !== PaymentStatus::Pending) {
            throw ValidationException::withMessages([
                'payment' => 'Only pending payments can be verified.',
            ]);
        }

        return DB::transaction(function () use ($payment, $actor): Payment {
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();
            $invoice = Invoice::query()->whereKey($locked->invoice_id)->lockForUpdate()->firstOrFail();

            if ($invoice->status !== InvoiceStatus::Issued) {
                throw ValidationException::withMessages([
                    'payment' => 'This invoice is no longer open for payment.',
                ]);
            }

            $smsRequest = SmsRequest::query()->whereKey($invoice->sms_request_id)->lockForUpdate()->firstOrFail();
            $beforePayment = ['status' => $locked->status->value];
            $beforeInvoice = ['status' => $invoice->status->value];
            $beforeRequest = ['status' => $smsRequest->status->value];

            $locked->forceFill([
                'status' => PaymentStatus::Verified,
                'verified_by' => $actor->id,
                'verified_at' => now(),
                'rejection_reason' => null,
            ])->save();

            $invoice->forceFill([
                'status' => InvoiceStatus::Paid,
                'paid_at' => now(),
            ])->save();

            $smsRequest->forceFill([
                'status' => SmsRequestStatus::Paid,
            ])->save();

            $this->ledger($locked, 'verified', $actor);
            $this->logger->logChange('payment.verified', $locked, $beforePayment, [
                'status' => $locked->status->value,
            ], ['invoice_number' => $invoice->number], $actor);
            $this->logger->logChange('sms_request.paid', $smsRequest, $beforeRequest, [
                'status' => $smsRequest->status->value,
            ], [
                'invoice_number' => $invoice->number,
                'invoice_before' => $beforeInvoice,
                'invoice_after' => ['status' => $invoice->status->value],
            ], $actor);

            return $locked;
        });
    }

    public function reject(Payment $payment, User $actor, string $reason): Payment
    {
        if ($payment->status !== PaymentStatus::Pending) {
            throw ValidationException::withMessages([
                'payment' => 'Only pending payments can be rejected.',
            ]);
        }

        return DB::transaction(function () use ($payment, $actor, $reason): Payment {
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            $locked->forceFill([
                'status' => PaymentStatus::Rejected,
                'verified_by' => $actor->id,
                'verified_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            $this->ledger($locked, 'rejected', $actor, [
                'reason' => $reason,
            ]);

            $this->logger->log('payment.rejected', $locked, [
                'reason' => $reason,
            ], $actor);

            return $locked;
        });
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    private function ledger(Payment $payment, string $event, User $actor, array $properties = []): void
    {
        PaymentTransaction::query()->create([
            'payment_id' => $payment->id,
            'event' => $event,
            'amount_pesewas' => $payment->amount_pesewas,
            'properties' => $properties === [] ? null : $properties,
            'actor_id' => $actor->id,
            'created_at' => now(),
        ]);
    }
}
