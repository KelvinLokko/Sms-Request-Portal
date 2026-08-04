<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $company = null;

        if ($user !== null) {
            // Reload missing attributes that factories / partial selects may omit under strict mode.
            if (! array_key_exists('current_company_id', $user->getAttributes())) {
                $user->refresh();
            }

            $companyModel = $user->currentCompany;
            $company = $companyModel
                ? [
                    'id' => $companyModel->id,
                    'name' => $companyModel->name,
                    'status' => $companyModel->status->value,
                    'status_label' => $companyModel->status->label(),
                ]
                : null;
        }

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $user,
                'roles' => $user?->getRoleNames()->values()->all() ?? [],
                'permissions' => $user?->getAllPermissions()->pluck('name')->values()->all() ?? [],
                'isPlatformStaff' => $user?->isPlatformStaff() ?? false,
                'company' => $company,
                'companyRole' => $user?->companyRole()?->value,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
