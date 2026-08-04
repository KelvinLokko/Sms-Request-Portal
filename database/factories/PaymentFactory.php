<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'invoice_id' => Invoice::factory(),
            'company_id' => Company::factory(),
            'provider' => 'manual',
            'status' => PaymentStatus::Pending,
            'amount_pesewas' => 300,
            'momo_reference' => 'MOMO'.fake()->numerify('########'),
            'payer_number' => '23324'.fake()->numerify('#######'),
            'submitted_by' => User::factory(),
        ];
    }

    public function verified(): static
    {
        return $this->state(fn () => [
            'status' => PaymentStatus::Verified,
            'verified_at' => now(),
            'verified_by' => User::factory(),
        ]);
    }
}
