<?php

namespace App\Http\Requests\Payments;

use App\Support\Money;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pay', $this->route('invoice')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'momo_reference' => ['required', 'string', 'max:100'],
            'payer_number' => ['required', 'string', 'max:32'],
            'proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $invoice = $this->route('invoice');
            if ($invoice === null || ! $this->filled('amount')) {
                return;
            }

            try {
                $pesewas = Money::fromMajor($this->input('amount'));
            } catch (\InvalidArgumentException) {
                $validator->errors()->add('amount', 'Enter a valid amount.');

                return;
            }

            if ($pesewas !== (int) $invoice->total_pesewas) {
                $validator->errors()->add(
                    'amount',
                    'Payment amount must match the invoice total of '.Money::format((int) $invoice->total_pesewas).'.',
                );
            }
        });
    }
}
