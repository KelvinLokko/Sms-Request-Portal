<?php

namespace App\Services\Payments;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\CampaignNotifier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PaystackCheckout
{
    public function __construct(
        private PaystackClient $paystack,
        private PaymentSettlement $settlement,
        private ActivityLogger $logger,
        private CampaignNotifier $notifier,
    ) {}

    public function enabled(): bool
    {
        return $this->paystack->enabled();
    }

    /**
     * Create (or reuse) a pending Paystack payment and return the hosted checkout URL.
     */
    public function start(Invoice $invoice, User $payer): string
    {
        if (! $this->enabled()) {
            throw ValidationException::withMessages([
                'payment' => 'Online payment is not configured. Contact support.',
            ]);
        }

        if ($invoice->status !== InvoiceStatus::Issued) {
            throw ValidationException::withMessages([
                'invoice' => 'Payments can only be started against an open invoice.',
            ]);
        }

        if ($invoice->total_pesewas < 1) {
            throw ValidationException::withMessages([
                'invoice' => 'This invoice has no amount due.',
            ]);
        }

        $email = $payer->email;
        if (blank($email)) {
            throw ValidationException::withMessages([
                'payment' => 'A verified email is required to pay online.',
            ]);
        }

        return DB::transaction(function () use ($invoice, $payer, $email): string {
            $existing = Payment::query()
                ->where('invoice_id', $invoice->id)
                ->where('status', PaymentStatus::Pending)
                ->lockForUpdate()
                ->first();

            if ($existing !== null && $existing->provider !== 'paystack') {
                throw ValidationException::withMessages([
                    'invoice' => 'A payment is already pending verification for this invoice.',
                ]);
            }

            $reference = $existing?->provider_reference;
            if ($reference === null || $reference === '') {
                $reference = $this->uniqueReference($invoice);
            }

            $callbackUrl = route('invoices.payments.callback', $invoice);

            $checkout = $this->paystack->initialize(
                $email,
                $invoice->total_pesewas,
                $reference,
                $callbackUrl,
                [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->number,
                    'company_id' => $invoice->company_id,
                ],
            );

            if ($existing !== null) {
                $existing->forceFill([
                    'amount_pesewas' => $invoice->total_pesewas,
                    'provider_reference' => $checkout['reference'],
                    'authorization_url' => $checkout['authorization_url'],
                    'momo_reference' => $checkout['reference'],
                    'payer_number' => Str::limit($email, 32, ''),
                ])->save();

                $this->settlement->ledger($existing, 'paystack_reinitialized', $payer, [
                    'reference' => $checkout['reference'],
                ]);

                return $checkout['authorization_url'];
            }

            $payment = Payment::query()->create([
                'invoice_id' => $invoice->id,
                'company_id' => $invoice->company_id,
                'provider' => 'paystack',
                'provider_reference' => $checkout['reference'],
                'authorization_url' => $checkout['authorization_url'],
                'status' => PaymentStatus::Pending,
                'amount_pesewas' => $invoice->total_pesewas,
                'momo_reference' => $checkout['reference'],
                'payer_number' => Str::limit($email, 32, ''),
                'submitted_by' => $payer->id,
            ]);

            $this->settlement->ledger($payment, 'submitted', $payer, [
                'channel' => 'paystack',
                'reference' => $checkout['reference'],
            ]);

            $this->logger->log('payment.submitted', $payment, [
                'invoice_number' => $invoice->number,
                'amount_pesewas' => $payment->amount_pesewas,
                'provider' => 'paystack',
            ], $payer);

            DB::afterCommit(fn () => $this->notifier->paymentReceived($payment));

            return $checkout['authorization_url'];
        });
    }

    /**
     * Confirm a Paystack transaction by reference (callback or polling).
     */
    public function confirmByReference(string $reference): Payment
    {
        $payment = Payment::query()
            ->where('provider', 'paystack')
            ->where('provider_reference', $reference)
            ->first();

        if ($payment === null) {
            throw ValidationException::withMessages([
                'reference' => 'No payment was found for that Paystack reference.',
            ]);
        }

        if ($payment->status === PaymentStatus::Verified) {
            return $payment;
        }

        $data = $this->paystack->verify($reference);

        return $this->settleFromPaystackPayload($payment, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function settleFromPaystackPayload(Payment $payment, array $data): Payment
    {
        $status = strtolower((string) ($data['status'] ?? ''));
        $amount = (int) ($data['amount'] ?? 0);
        $currency = strtoupper((string) ($data['currency'] ?? ''));
        $reference = (string) ($data['reference'] ?? '');

        if ($status !== 'success') {
            throw ValidationException::withMessages([
                'payment' => 'Paystack payment was not successful.',
            ]);
        }

        if ($reference !== '' && $payment->provider_reference !== null && $reference !== $payment->provider_reference) {
            throw ValidationException::withMessages([
                'payment' => 'Paystack reference mismatch.',
            ]);
        }

        if ($currency !== '' && $currency !== 'GHS') {
            throw ValidationException::withMessages([
                'payment' => 'Unexpected payment currency.',
            ]);
        }

        if ($amount !== $payment->amount_pesewas) {
            throw ValidationException::withMessages([
                'payment' => 'Paid amount does not match the invoice total.',
            ]);
        }

        $channel = (string) ($data['channel'] ?? '');
        $payer = (string) (data_get($data, 'customer.email')
            ?? data_get($data, 'authorization.receiver_bank_account_number')
            ?? data_get($data, 'authorization.last4')
            ?? 'paystack');

        $payment->forceFill([
            'payer_number' => Str::limit($payer, 32, ''),
            'momo_reference' => $payment->provider_reference ?? $reference,
        ])->save();

        return $this->settlement->markVerified($payment, null, [
            'channel' => $channel,
            'paystack' => [
                'reference' => $reference,
                'id' => $data['id'] ?? null,
                'gateway_response' => $data['gateway_response'] ?? null,
                'paid_at' => $data['paid_at'] ?? null,
            ],
        ]);
    }

    private function uniqueReference(Invoice $invoice): string
    {
        for ($i = 0; $i < 5; $i++) {
            $reference = 'inv'.$invoice->id.'_'.Str::lower(Str::random(10));

            $exists = Payment::query()
                ->where('provider_reference', $reference)
                ->exists();

            if (! $exists) {
                return $reference;
            }
        }

        throw new RuntimeException('Unable to allocate a unique Paystack reference.');
    }
}
