<?php

namespace Database\Factories;

use App\Models\ProviderRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProviderRate>
 */
class ProviderRateFactory extends Factory
{
    protected $model = ProviderRate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'rate_per_sms' => '0.020000',
            'effective_from' => now()->toDateString(),
            'created_by' => null,
        ];
    }
}
