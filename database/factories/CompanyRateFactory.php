<?php

namespace Database\Factories;

use App\Models\CompanyRate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CompanyRate>
 */
class CompanyRateFactory extends Factory
{
    protected $model = CompanyRate::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => null,
            'rate_per_sms' => '0.030000',
            'effective_from' => now()->toDateString(),
            'created_by' => null,
        ];
    }

    public function forCompany(int $companyId): static
    {
        return $this->state(fn () => [
            'company_id' => $companyId,
        ]);
    }
}
