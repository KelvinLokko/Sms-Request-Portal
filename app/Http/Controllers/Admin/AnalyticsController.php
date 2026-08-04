<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function __invoke(Request $request, AnalyticsService $analytics): Response
    {
        abort_unless($request->user()?->isPlatformStaff(), 403);

        return Inertia::render('admin/analytics/Index', $analytics->dashboard());
    }
}
