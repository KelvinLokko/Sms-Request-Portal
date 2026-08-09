<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCompanyRateRequest;
use App\Http\Requests\Admin\StoreProviderRateRequest;
use App\Models\Company;
use App\Models\CompanyRate;
use App\Models\ProviderRate;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CompanyRateController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', CompanyRate::class);

        $rates = CompanyRate::query()
            ->with('company:id,name')
            ->latest('effective_from')
            ->latest('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (CompanyRate $rate) => [
                'id' => $rate->id,
                'company' => $rate->company
                    ? ['id' => $rate->company->id, 'name' => $rate->company->name]
                    : null,
                'rate_per_sms' => $rate->rate_per_sms,
                'effective_from' => $rate->effective_from->toDateString(),
                'is_platform_default' => $rate->company_id === null,
            ]);

        $providerRates = ProviderRate::query()
            ->latest('effective_from')
            ->latest('id')
            ->paginate(10, pageName: 'provider_page')
            ->withQueryString()
            ->through(fn (ProviderRate $rate) => [
                'id' => $rate->id,
                'rate_per_sms' => $rate->rate_per_sms,
                'effective_from' => $rate->effective_from->toDateString(),
            ]);

        $currentProvider = ProviderRate::resolve();
        $currentClient = CompanyRate::resolveFor(null);

        $companies = Company::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('admin/rates/Index', [
            'rates' => $rates,
            'providerRates' => $providerRates,
            'currentProviderRate' => $currentProvider?->rate_per_sms,
            'currentClientRate' => $currentClient?->rate_per_sms,
            'companies' => $companies,
        ]);
    }

    public function store(StoreCompanyRateRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $rate = CompanyRate::query()->create([
            'company_id' => $request->validated('company_id'),
            'rate_per_sms' => $request->validated('rate_per_sms'),
            'effective_from' => $request->validated('effective_from'),
            'created_by' => $request->user()->id,
        ]);

        $logger->log('company_rate.created', $rate, [
            'company_id' => $rate->company_id,
            'rate_per_sms' => $rate->rate_per_sms,
            'effective_from' => $rate->effective_from->toDateString(),
        ]);

        return back()->with('success', 'Client rate saved.');
    }

    public function storeProvider(StoreProviderRateRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $this->authorize('create', CompanyRate::class);

        $rate = ProviderRate::query()->create([
            'rate_per_sms' => $request->validated('rate_per_sms'),
            'effective_from' => $request->validated('effective_from'),
            'created_by' => $request->user()->id,
        ]);

        $logger->log('provider_rate.created', $rate, [
            'rate_per_sms' => $rate->rate_per_sms,
            'effective_from' => $rate->effective_from->toDateString(),
        ]);

        return back()->with('success', 'Provider unit price saved.');
    }
}
