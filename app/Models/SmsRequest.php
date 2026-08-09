<?php

namespace App\Models;

use App\Enums\MessageEncoding;
use App\Enums\MessageFlashType;
use App\Enums\SmsRequestStatus;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\SmsRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $company_id
 * @property string $reference
 * @property SmsRequestStatus $status
 * @property int|null $sender_id_id
 * @property string|null $name
 * @property string|null $message_body
 * @property MessageEncoding $encoding
 * @property MessageFlashType $flash_type
 * @property bool $is_personalised
 * @property int $pages
 * @property bool $exceeds_621_warning
 * @property bool $requires_manual_cost_review
 * @property int|null $billable_recipients
 * @property string|null $rate_per_sms
 * @property int|null $estimated_cost_pesewas
 * @property int|null $quoted_cost_pesewas
 * @property string|null $provider_rate_per_sms
 * @property int|null $provider_cost_pesewas
 * @property Carbon|null $requested_send_at
 * @property Carbon|null $hard_deadline_at
 * @property Carbon|null $submitted_at
 * @property string|null $changes_requested_reason
 * @property string|null $rejection_reason
 * @property Carbon|null $cancelled_at
 * @property Carbon|null $fulfilled_at
 * @property int|null $fulfilled_by
 * @property string|null $deywuro_job_reference
 * @property int $created_by
 */
class SmsRequest extends Model
{
    /** @use HasFactory<SmsRequestFactory> */
    use BelongsToCompany, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'reference',
        'status',
        'sender_id_id',
        'name',
        'message_body',
        'encoding',
        'flash_type',
        'is_personalised',
        'pages',
        'exceeds_621_warning',
        'requires_manual_cost_review',
        'billable_recipients',
        'rate_per_sms',
        'estimated_cost_pesewas',
        'quoted_cost_pesewas',
        'provider_rate_per_sms',
        'provider_cost_pesewas',
        'requested_send_at',
        'hard_deadline_at',
        'submitted_at',
        'changes_requested_reason',
        'rejection_reason',
        'cancelled_at',
        'fulfilled_at',
        'fulfilled_by',
        'deywuro_job_reference',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SmsRequestStatus::class,
            'encoding' => MessageEncoding::class,
            'flash_type' => MessageFlashType::class,
            'is_personalised' => 'boolean',
            'exceeds_621_warning' => 'boolean',
            'requires_manual_cost_review' => 'boolean',
            'rate_per_sms' => 'decimal:6',
            'requested_send_at' => 'datetime',
            'hard_deadline_at' => 'datetime',
            'submitted_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'fulfilled_at' => 'datetime',
        ];
    }

    public static function generateReference(): string
    {
        do {
            $reference = 'SMS-'.now()->format('Y').'-'.Str::upper(Str::random(8));
        } while (static::query()->where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * Suggested Deywuro campaign name for mixed-account legibility.
     */
    public function portalCampaignName(): string
    {
        $slug = $this->company->slug ?: 'company';

        return 'PORTAL-'.$this->reference.'-'.$slug;
    }

    /**
     * @return BelongsTo<SenderId, $this>
     */
    public function senderId(): BelongsTo
    {
        return $this->belongsTo(SenderId::class, 'sender_id_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function fulfiller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fulfilled_by');
    }

    /**
     * @return HasOne<RecipientList, $this>
     */
    public function recipientList(): HasOne
    {
        return $this->hasOne(RecipientList::class)->latestOfMany();
    }

    /**
     * @return HasMany<RecipientList, $this>
     */
    public function recipientLists(): HasMany
    {
        return $this->hasMany(RecipientList::class);
    }

    /**
     * @return HasMany<RequestAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(RequestAttachment::class);
    }

    /**
     * @return HasMany<SmsRecipient, $this>
     */
    public function recipients(): HasMany
    {
        return $this->hasMany(SmsRecipient::class);
    }

    /**
     * @return HasOne<Invoice, $this>
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }
}
