<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CompanyStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectCompanyRequest;
use App\Models\Company;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CompanyController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Company::class);

        $status = $request->string('status')->toString();

        $companies = Company::query()
            ->withCount('users')
            ->when(
                $status !== '' && CompanyStatus::tryFrom($status),
                fn ($query) => $query->where('status', $status),
            )
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (Company $company) => [
                'id' => $company->id,
                'name' => $company->name,
                'email' => $company->email,
                'phone' => $company->phone,
                'status' => $company->status->value,
                'status_label' => $company->status->label(),
                'users_count' => $company->users_count,
                'rejection_reason' => $company->rejection_reason,
                'created_at' => $company->created_at?->toIso8601String(),
            ]);

        return Inertia::render('admin/companies/Index', [
            'companies' => $companies,
            'filters' => [
                'status' => $status !== '' ? $status : null,
            ],
        ]);
    }

    public function approve(Company $company, ActivityLogger $logger): RedirectResponse
    {
        $this->authorize('approve', $company);

        $company->forceFill([
            'status' => CompanyStatus::Approved,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'rejection_reason' => null,
        ])->save();

        $logger->log('company.approved', $company);

        return back()->with('success', "{$company->name} has been approved.");
    }

    public function reject(
        RejectCompanyRequest $request,
        Company $company,
        ActivityLogger $logger,
    ): RedirectResponse {
        $company->forceFill([
            'status' => CompanyStatus::Rejected,
            'approved_by' => null,
            'approved_at' => null,
            'rejection_reason' => $request->validated('rejection_reason'),
        ])->save();

        $logger->log('company.rejected', $company, [
            'reason' => $company->rejection_reason,
        ]);

        return back()->with('success', "{$company->name} has been rejected.");
    }

    public function suspend(Company $company, ActivityLogger $logger): RedirectResponse
    {
        $this->authorize('suspend', $company);

        $company->forceFill([
            'status' => CompanyStatus::Suspended,
        ])->save();

        $logger->log('company.suspended', $company);

        return back()->with('success', "{$company->name} has been suspended.");
    }
}
