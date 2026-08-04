<?php

namespace Database\Factories;

use App\Enums\InvoiceStatus;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\SmsRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = 300;
        $tax = 0;

        return [
            'company_id' => Company::factory(),
            'sms_request_id' => SmsRequest::factory()->submitted(),
            'number' => 'INV-'.now()->format('Y').'-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'status' => InvoiceStatus::Issued,
            'subtotal_pesewas' => $subtotal,
            'tax_pesewas' => $tax,
            'total_pesewas' => $subtotal + $tax,
            'currency' => 'GHS',
            'issued_at' => now(),
            'issued_by' => User::factory(),
            'due_at' => now()->addDays(7)->toDateString(),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn () => [
            'status' => InvoiceStatus::Paid,
            'paid_at' => now(),
        ]);
    }
}
