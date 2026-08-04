<?php

namespace App\Models;

use App\Enums\CompanyUserRole;
use App\Enums\PlatformPermission;
use App\Enums\PlatformRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property int|null $current_company_id
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Company|null $currentCompany
 */
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'current_company_id',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    public function isPlatformStaff(): bool
    {
        return $this->can(PlatformPermission::AdminAccess->value)
            || $this->hasAnyRole(PlatformRole::values());
    }

    public function hasPlatformPermission(PlatformPermission $permission): bool
    {
        return $this->can($permission->value);
    }

    public function currentCompanyId(): ?int
    {
        return $this->current_company_id;
    }

    /**
     * @return BelongsTo<Company, $this>
     */
    public function currentCompany(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'current_company_id');
    }

    /**
     * @return BelongsToMany<Company, $this>
     */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function companyRole(?Company $company = null): ?CompanyUserRole
    {
        $company ??= $this->currentCompany;

        if ($company === null) {
            return null;
        }

        $membership = $this->companies()
            ->where('companies.id', $company->id)
            ->first();

        if ($membership === null) {
            return null;
        }

        $role = $membership->pivot->getAttribute('role');

        if (! is_string($role)) {
            return null;
        }

        return CompanyUserRole::from($role);
    }

    public function belongsToCompany(Company $company): bool
    {
        return $this->companies()->where('companies.id', $company->id)->exists();
    }
}
