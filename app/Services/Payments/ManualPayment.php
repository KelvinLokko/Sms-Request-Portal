<?php

namespace App\Services\Payments;

use App\Contracts\PaymentProvider;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
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
        private PaymentSettlement $settlement,
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

            $this->settlement->ledger($payment, 'submitted', $submitter, [
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
        return $this->settlement->markVerified($payment, $actor);
    }

    public function reject(Payment $payment, User $actor, string $reason): Payment
    {
        return $this->settlement->markRejected($payment, $actor, $reason);
    }
}
