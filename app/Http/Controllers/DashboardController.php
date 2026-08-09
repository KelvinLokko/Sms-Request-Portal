<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsService;
use App\Services\CompanyDashboardService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(
        Request $request,
        AnalyticsService $analytics,
        CompanyDashboardService $companyDashboard,
    ): Response {
        $user = $request->user();
        $isStaff = $user?->isPlatformStaff() ?? false;

        return Inertia::render('Dashboard', [
            'isStaff' => $isStaff,
            'summary' => $isStaff ? $analytics->summary() : null,
            'companyOverview' => (! $isStaff && $user?->current_company_id)
                ? $companyDashboard->overview()
                : null,
        ]);
    }
}
