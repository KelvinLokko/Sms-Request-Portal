<?php

namespace App\Models;

use Database\Factories\ProviderRateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Third-party SMS provider unit price (what we pay to send).
 *
 * @property int $id
 * @property string $rate_per_sms
 * @property Carbon $effective_from
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class ProviderRate extends Model
{
    /** @use HasFactory<ProviderRateFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'rate_per_sms',
        'effective_from',
        'created_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'rate_per_sms' => 'decimal:6',
        ];
    }

    /**
     * Resolve the provider unit price in effect on a given date.
     */
    public static function resolve(?Carbon $on = null): ?self
    {
        $on ??= now();

        return static::query()
            ->whereDate('effective_from', '<=', $on)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
