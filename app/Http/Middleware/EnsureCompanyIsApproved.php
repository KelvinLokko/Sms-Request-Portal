<?php

namespace App\Http\Middleware;

use App\Enums\CompanyStatus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyIsApproved
{
    /**
     * Gate campaign-submission (and similar) routes on company approval.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->isPlatformStaff()) {
            return $next($request);
        }

        $company = $user->currentCompany;

        if ($company === null || $company->status !== CompanyStatus::Approved) {
            abort(403, 'Your company must be approved before you can perform this action.');
        }

        return $next($request);
    }
}
