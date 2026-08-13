<?php

namespace App\Services\Payments;

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

class PaymentSettlement
{
    public function __construct(
        private ActivityLogger $logger,
        private CampaignNotifier $notifier,
    ) {}

    /**
     * @param  array<string, mixed>  $properties
     */
    public function markVerified(Payment $payment, ?User $actor = null, array $properties = []): Payment
    {
        if ($payment->status === PaymentStatus::Verified) {
            return $payment;
        }

        if ($payment->status !== PaymentStatus::Pending) {
            throw ValidationException::withMessages([
                'payment' => 'Only pending payments can be verified.',
            ]);
        }

        return DB::transaction(function () use ($payment, $actor, $properties): Payment {
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            if ($locked->status === PaymentStatus::Verified) {
                return $locked;
            }

            if ($locked->status !== PaymentStatus::Pending) {
                throw ValidationException::withMessages([
                    'payment' => 'Only pending payments can be verified.',
                ]);
            }

            $invoice = Invoice::query()->whereKey($locked->invoice_id)->lockForUpdate()->firstOrFail();

            if ($invoice->status === InvoiceStatus::Paid) {
                $locked->forceFill([
                    'status' => PaymentStatus::Verified,
                    'verified_by' => $actor?->id,
                    'verified_at' => now(),
                    'rejection_reason' => null,
                ])->save();

                return $locked;
            }

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
                'verified_by' => $actor?->id,
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

            $this->ledger($locked, 'verified', $actor, $properties);
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

            DB::afterCommit(
                fn () => $this->notifier->paymentVerified($locked->fresh()),
            );

            return $locked;
        });
    }

    /**
     * @param  array<string, mixed>  $properties
     */
    public function markRejected(Payment $payment, User $actor, string $reason, array $properties = []): Payment
    {
        if ($payment->status !== PaymentStatus::Pending) {
            throw ValidationException::withMessages([
                'payment' => 'Only pending payments can be rejected.',
            ]);
        }

        return DB::transaction(function () use ($payment, $actor, $reason, $properties): Payment {
            $locked = Payment::query()->whereKey($payment->id)->lockForUpdate()->firstOrFail();

            $locked->forceFill([
                'status' => PaymentStatus::Rejected,
                'verified_by' => $actor->id,
                'verified_at' => now(),
                'rejection_reason' => $reason,
            ])->save();

            $this->ledger($locked, 'rejected', $actor, [
                'reason' => $reason,
                ...$properties,
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
    public function ledger(Payment $payment, string $event, ?User $actor = null, array $properties = []): void
    {
        PaymentTransaction::query()->create([
            'payment_id' => $payment->id,
            'event' => $event,
            'amount_pesewas' => $payment->amount_pesewas,
            'properties' => $properties === [] ? null : $properties,
            'actor_id' => $actor?->id,
            'created_at' => now(),
        ]);
    }
}
