<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use App\Models\Concerns\BelongsToCompany;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $company_id
 * @property int $sms_request_id
 * @property string $number
 * @property InvoiceStatus $status
 * @property int $subtotal_pesewas
 * @property int $tax_pesewas
 * @property int $total_pesewas
 * @property string $currency
 * @property Carbon $issued_at
 * @property int $issued_by
 * @property Carbon|null $due_at
 * @property string|null $pdf_path
 * @property Carbon|null $paid_at
 * @property Carbon|null $voided_at
 */
class Invoice extends Model
{
    /** @use HasFactory<InvoiceFactory> */
    use BelongsToCompany, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
        'sms_request_id',
        'number',
        'status',
        'subtotal_pesewas',
        'tax_pesewas',
        'total_pesewas',
        'currency',
        'issued_at',
        'issued_by',
        'due_at',
        'pdf_path',
        'paid_at',
        'voided_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'issued_at' => 'datetime',
            'due_at' => 'date',
            'paid_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public function hasPdf(): bool
    {
        return filled($this->pdf_path);
    }

    /**
     * @return BelongsTo<SmsRequest, $this>
     */
    public function smsRequest(): BelongsTo
    {
        return $this->belongsTo(SmsRequest::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    /**
     * @return HasMany<InvoiceItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('position');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasMany<CreditNote, $this>
     */
    public function creditNotes(): HasMany
    {
        return $this->hasMany(CreditNote::class);
    }
}
