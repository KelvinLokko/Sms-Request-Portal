<?php

namespace Database\Factories;

use App\Enums\CompanyStatus;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Company::uniqueSlugFromName($name),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->numerify('0#########'),
            'address' => fake()->address(),
            'status' => CompanyStatus::Pending,
            'approved_by' => null,
            'approved_at' => null,
            'rejection_reason' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => CompanyStatus::Approved,
            'approved_at' => now(),
        ]);
    }

    public function rejected(string $reason = 'Incomplete registration details.'): static
    {
        return $this->state(fn () => [
            'status' => CompanyStatus::Rejected,
            'rejection_reason' => $reason,
        ]);
    }

    public function suspended(): static
    {
        return $this->state(fn () => [
            'status' => CompanyStatus::Suspended,
            'approved_at' => now()->subDay(),
        ]);
    }
}
