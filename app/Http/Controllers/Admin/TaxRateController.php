<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTaxRateRequest;
use App\Http\Requests\Admin\UpdateTaxRateRequest;
use App\Models\TaxRate;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TaxRateController extends Controller
{
    public function index(): Response
    {
        $this->authorize('viewAny', TaxRate::class);

        $taxRates = TaxRate::query()
            ->orderByDesc('effective_from')
            ->orderBy('name')
            ->paginate(20)
            ->through(fn (TaxRate $taxRate) => [
                'id' => $taxRate->id,
                'name' => $taxRate->name,
                'rate' => $taxRate->rate,
                'effective_from' => $taxRate->effective_from->toDateString(),
                'is_active' => $taxRate->is_active,
            ]);

        return Inertia::render('admin/tax-rates/Index', [
            'taxRates' => $taxRates,
        ]);
    }

    public function store(StoreTaxRateRequest $request, ActivityLogger $logger): RedirectResponse
    {
        $taxRate = TaxRate::query()->create([
            'name' => $request->validated('name'),
            'rate' => $request->validated('rate'),
            'effective_from' => $request->validated('effective_from'),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $logger->log('tax_rate.created', $taxRate);

        return back()->with('success', 'Tax rate created.');
    }

    public function update(
        UpdateTaxRateRequest $request,
        TaxRate $taxRate,
        ActivityLogger $logger,
    ): RedirectResponse {
        $taxRate->update([
            'name' => $request->validated('name'),
            'rate' => $request->validated('rate'),
            'effective_from' => $request->validated('effective_from'),
            'is_active' => $request->boolean('is_active', $taxRate->is_active),
        ]);

        $logger->log('tax_rate.updated', $taxRate);

        return back()->with('success', 'Tax rate updated.');
    }
}
