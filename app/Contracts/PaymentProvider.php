<?php

namespace App\Contracts;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;

interface PaymentProvider
{
    public function name(): string;

    /**
     * @param  array{
     *     amount_pesewas: int,
     *     momo_reference: string,
     *     payer_number: string,
     *     proof_path?: string|null
     * }  $payload
     */
    public function submit(Invoice $invoice, User $submitter, array $payload): Payment;

    public function verify(Payment $payment, User $actor): Payment;

    public function reject(Payment $payment, User $actor, string $reason): Payment;
}
