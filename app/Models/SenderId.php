<?php

namespace App\Models;

use App\Enums\SenderIdStatus;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\SenderIdFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property string $value
 * @property SenderIdStatus $status
 * @property string|null $document_path
 * @property string|null $document_original_name
 * @property bool $uses_company_letterhead
 * @property string|null $rejection_reason
 * @property int $requested_by
 * @property int|null $reviewed_by
 * @property Carbon|null $reviewed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SenderId extends Model
{
    /** @use HasFactory<SenderIdFactory> */
    use BelongsToCompany, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'value',
        'status',
        'document_path',
        'document_original_name',
        'uses_company_letterhead',
        'rejection_reason',
        'requested_by',
        'reviewed_by',
        'reviewed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SenderIdStatus::class,
            'uses_company_letterhead' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    public function hasDocument(): bool
    {
        return filled($this->document_path);
    }

    /**
     * Editing an approved sender ID resets it to pending, mirroring Deywuro.
     */
    public function markPendingReview(): void
    {
        $this->forceFill([
            'status' => SenderIdStatus::Pending,
            'rejection_reason' => null,
            'reviewed_by' => null,
            'reviewed_at' => null,
        ])->save();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
