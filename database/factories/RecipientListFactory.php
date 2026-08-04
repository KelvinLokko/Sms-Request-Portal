<?php

namespace Database\Factories;

use App\Enums\RecipientListStatus;
use App\Models\Company;
use App\Models\RecipientList;
use App\Models\SmsRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecipientList>
 */
class RecipientListFactory extends Factory
{
    protected $model = RecipientList::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sms_request_id' => SmsRequest::factory(),
            'company_id' => Company::factory(),
            'original_path' => 'recipient-lists/test.csv',
            'original_filename' => 'contacts.csv',
            'mime_type' => 'text/csv',
            'status' => RecipientListStatus::Completed,
            'headers' => ['phone', 'name'],
            'phone_column' => 'phone',
            'total_rows' => 10,
            'valid_count' => 8,
            'invalid_count' => 1,
            'duplicate_count' => 1,
            'billable_count' => 8,
            'processed_at' => now(),
        ];
    }
}
