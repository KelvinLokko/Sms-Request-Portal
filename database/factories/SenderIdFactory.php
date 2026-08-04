<?php

namespace Database\Factories;

use App\Enums\SenderIdStatus;
use App\Models\Company;
use App\Models\SenderId;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SenderId>
 */
class SenderIdFactory extends Factory
{
    protected $model = SenderId::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'value' => strtoupper(fake()->unique()->lexify('??????')),
            'status' => SenderIdStatus::Pending,
            'document_path' => null,
            'document_original_name' => null,
            'uses_company_letterhead' => false,
            'rejection_reason' => null,
            'requested_by' => User::factory(),
            'reviewed_by' => null,
            'reviewed_at' => null,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => SenderIdStatus::Approved,
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(string $reason = 'Document insufficient.'): static
    {
        return $this->state(fn () => [
            'status' => SenderIdStatus::Rejected,
            'rejection_reason' => $reason,
            'reviewed_at' => now(),
        ]);
    }
}
