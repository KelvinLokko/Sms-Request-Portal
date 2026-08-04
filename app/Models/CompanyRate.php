<?php

namespace App\Models;

use Database\Factories\CompanyRateFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $company_id
 * @property string $rate_per_sms
 * @property Carbon $effective_from
 * @property int|null $created_by
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class CompanyRate extends Model
{
    /** @use HasFactory<CompanyRateFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'company_id',
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
     * Resolve the rate in effect for a company on a given date.
     * Company-specific rates take precedence over the platform default.
     */
    public static function resolveFor(?Company $company, ?Carbon $on = null): ?self
    {
        $on ??= now();

        if ($company !== null) {
            $override = static::query()
                ->where('company_id', $company->id)
                ->whereDate('effective_from', '<=', $on)
                ->orderByDesc('effective_from')
                ->orderByDesc('id')
                ->first();

            if ($override !== null) {
                return $override;
            }
        }

        return static::query()
            ->whereNull('company_id')
            ->whereDate('effective_from', '<=', $on)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePlatformDefault(Builder $query): Builder
    {
        return $query->whereNull('company_id');
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
