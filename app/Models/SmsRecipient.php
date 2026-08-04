<?php

namespace App\Models;

use App\Enums\RecipientRowStatus;
use App\Models\Concerns\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $recipient_list_id
 * @property int $sms_request_id
 * @property int $company_id
 * @property int $row_number
 * @property string $raw_value
 * @property string|null $normalised_msisdn
 * @property RecipientRowStatus $status
 * @property string|null $rejection_reason
 * @property array<string, mixed>|null $personalisation_data
 */
class SmsRecipient extends Model
{
    use BelongsToCompany;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'recipient_list_id',
        'sms_request_id',
        'company_id',
        'row_number',
        'raw_value',
        'normalised_msisdn',
        'status',
        'rejection_reason',
        'personalisation_data',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RecipientRowStatus::class,
            'personalisation_data' => 'array',
        ];
    }

    /**
     * @return BelongsTo<RecipientList, $this>
     */
    public function recipientList(): BelongsTo
    {
        return $this->belongsTo(RecipientList::class);
    }

    /**
     * @return BelongsTo<SmsRequest, $this>
     */
    public function smsRequest(): BelongsTo
    {
        return $this->belongsTo(SmsRequest::class);
    }
}
