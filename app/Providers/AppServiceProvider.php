<?php

namespace App\Providers;

use App\Contracts\PaymentProvider;
use App\Policies\RolePolicy;
use App\Services\Payments\ManualPayment;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentProvider::class, ManualPayment::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();
        $this->configureAssetUrls();

        Gate::policy(Role::class, RolePolicy::class);
    }

    /**
     * Prefer relative Vite asset URLs so tunnels (ngrok/localtunnel) do not
     * break CSS/JS with absolute http:// links or host mismatches.
     */
    protected function configureAssetUrls(): void
    {
        Vite::createAssetPathsUsing(fn (string $path): string => '/'.ltrim($path, '/'));

        if (filled(config('app.url'))) {
            URL::forceRootUrl(rtrim((string) config('app.url'), '/'));
        }

        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Model::shouldBeStrict();
        Model::automaticallyEagerLoadRelationships();

        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            $this->app->isProduction(),
        );

        Password::defaults(function (): Password {
            $rule = Password::min(12)
                ->mixedCase()
                ->numbers()
                ->symbols();

            return $this->app->isProduction()
                ? $rule->uncompromised()
                : $rule;
        });
    }

    /**
     * Configure application rate limiters.
     *
     * Note: the "login" limiter (5/minute per email+IP) is registered in
     * FortifyServiceProvider for Fortify compatibility.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('uploads', function (Request $request) {
            return Limit::perMinute(10)->by((string) ($request->user()?->getAuthIdentifier() ?: $request->ip()));
        });

        RateLimiter::for('downloads', function (Request $request) {
            return Limit::perMinute(30)->by((string) ($request->user()?->getAuthIdentifier() ?: $request->ip()));
        });
    }
}
