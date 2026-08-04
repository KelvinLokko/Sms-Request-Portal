<?php

namespace App\Models;

use App\Enums\RecipientListStatus;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\RecipientListFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $sms_request_id
 * @property int $company_id
 * @property string $original_path
 * @property string $original_filename
 * @property string|null $mime_type
 * @property RecipientListStatus $status
 * @property list<string>|null $headers
 * @property string|null $phone_column
 * @property int $total_rows
 * @property int $valid_count
 * @property int $invalid_count
 * @property int $duplicate_count
 * @property int $billable_count
 * @property string|null $rejected_export_path
 * @property string|null $error_message
 * @property Carbon|null $processed_at
 */
class RecipientList extends Model
{
    /** @use HasFactory<RecipientListFactory> */
    use BelongsToCompany, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'sms_request_id',
        'company_id',
        'original_path',
        'original_filename',
        'mime_type',
        'status',
        'headers',
        'phone_column',
        'total_rows',
        'valid_count',
        'invalid_count',
        'duplicate_count',
        'billable_count',
        'rejected_export_path',
        'error_message',
        'processed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RecipientListStatus::class,
            'headers' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SmsRequest, $this>
     */
    public function smsRequest(): BelongsTo
    {
        return $this->belongsTo(SmsRequest::class);
    }

    /**
     * @return HasMany<SmsRecipient, $this>
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(SmsRecipient::class);
    }

    public function hasRejectedExport(): bool
    {
        return filled($this->rejected_export_path);
    }
}
