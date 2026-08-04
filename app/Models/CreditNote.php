<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use Database\Factories\CreditNoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $invoice_id
 * @property int $company_id
 * @property string $number
 * @property int $amount_pesewas
 * @property string $reason
 * @property int $issued_by
 * @property Carbon $issued_at
 * @property string|null $pdf_path
 */
class CreditNote extends Model
{
    /** @use HasFactory<CreditNoteFactory> */
    use BelongsToCompany, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'invoice_id',
        'company_id',
        'number',
        'amount_pesewas',
        'reason',
        'issued_by',
        'issued_at',
        'pdf_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Invoice, $this>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
