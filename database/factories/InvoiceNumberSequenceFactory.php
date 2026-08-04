<?php

namespace Database\Factories;

use App\Models\InvoiceNumberSequence;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InvoiceNumberSequence>
 */
class InvoiceNumberSequenceFactory extends Factory
{
    protected $model = InvoiceNumberSequence::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'year' => (int) now()->format('Y'),
            'last_number' => 0,
        ];
    }
}
