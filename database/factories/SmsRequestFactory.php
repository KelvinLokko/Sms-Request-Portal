<?php

namespace Database\Factories;

use App\Enums\MessageEncoding;
use App\Enums\MessageFlashType;
use App\Enums\SmsRequestStatus;
use App\Models\Company;
use App\Models\SmsRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SmsRequest>
 */
class SmsRequestFactory extends Factory
{
    protected $model = SmsRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'reference' => SmsRequest::generateReference(),
            'status' => SmsRequestStatus::Draft,
            'sender_id_id' => null,
            'name' => fake()->sentence(3),
            'message_body' => 'Hello from '.fake()->company(),
            'encoding' => MessageEncoding::Text,
            'flash_type' => MessageFlashType::Text,
            'is_personalised' => false,
            'pages' => 1,
            'exceeds_621_warning' => false,
            'requires_manual_cost_review' => false,
            'billable_recipients' => null,
            'rate_per_sms' => null,
            'estimated_cost_pesewas' => null,
            'quoted_cost_pesewas' => null,
            'created_by' => User::factory(),
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn () => [
            'status' => SmsRequestStatus::Submitted,
            'submitted_at' => now(),
            'billable_recipients' => 100,
            'pages' => 1,
            'rate_per_sms' => '0.030000',
            'quoted_cost_pesewas' => 300,
            'estimated_cost_pesewas' => 300,
        ]);
    }

    public function paid(): static
    {
        return $this->submitted()->state(fn () => [
            'status' => SmsRequestStatus::Paid,
        ]);
    }
}
