<?php

namespace App\Models\Concerns;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * @mixin Model
 *
 * @property int $company_id
 * @property-read Company $company
 */
trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::addGlobalScope('company', function (Builder $builder): void {
            $user = Auth::user();

            if (! $user instanceof User || $user->isPlatformStaff()) {
                return;
            }

            $companyId = $user->currentCompanyId();

            if ($companyId === null) {
                $builder->whereRaw('0 = 1');

                return;
            }

            $builder->where(
                $builder->getModel()->qualifyColumn('company_id'),
                $companyId,
            );
        });

        static::creating(function (Model $model): void {
            if ($model->getAttribute('company_id') !== null) {
                return;
            }

            $user = Auth::user();

            if ($user instanceof User && ! $user->isPlatformStaff()) {
                $model->setAttribute('company_id', $user->currentCompanyId());
            }
        });
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
