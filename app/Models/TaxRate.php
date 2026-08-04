<?php

namespace App\Models;

use Database\Factories\TaxRateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $rate
 * @property Carbon $effective_from
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class TaxRate extends Model
{
    /** @use HasFactory<TaxRateFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'rate',
        'effective_from',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'effective_from' => 'date',
            'is_active' => 'boolean',
            'rate' => 'decimal:4',
        ];
    }

    /**
     * Active tax lines in effect on a given date (configurable — not hard-coded VAT).
     *
     * @return Collection<int, static>
     */
    public static function activeOn(?Carbon $on = null): Collection
    {
        $on ??= now();

        return static::query()
            ->where('is_active', true)
            ->whereDate('effective_from', '<=', $on)
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
