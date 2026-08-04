<?php

namespace App\Http\Controllers;

use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, AnalyticsService $analytics): Response
    {
        $user = $request->user();
        $isStaff = $user?->isPlatformStaff() ?? false;

        return Inertia::render('Dashboard', [
            'summary' => $isStaff ? $analytics->summary() : null,
            'isStaff' => $isStaff,
        ]);
    }
}
