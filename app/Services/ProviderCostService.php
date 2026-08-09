<?php

namespace App\Services;

use App\Models\ProviderRate;
use App\Models\SmsRequest;
use App\Support\Sms\CostEngine;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

/**
 * Snapshots third-party provider unit cost onto campaigns for accurate P&L.
 */
class ProviderCostService
{
    /**
     * Freeze provider unit price and cost on the campaign if not already set.
     *
     * @throws ValidationException when $required is true and no provider rate exists
     */
    public function snapshot(SmsRequest $request, bool $required = false, ?Carbon $on = null): SmsRequest
    {
        if ($request->provider_rate_per_sms !== null && $request->provider_cost_pesewas !== null) {
            return $request;
        }

        $rate = ProviderRate::resolve($on);

        if ($rate === null) {
            if ($required) {
                throw ValidationException::withMessages([
                    'provider_rate' => 'Set a third-party provider unit price under SMS rates before continuing.',
                ]);
            }

            return $request;
        }

        $billable = max(0, (int) ($request->billable_recipients ?? 0));
        $pages = max(1, (int) $request->pages);
        $rateValue = number_format((float) $rate->rate_per_sms, 6, '.', '');

        $request->forceFill([
            'provider_rate_per_sms' => $rateValue,
            'provider_cost_pesewas' => CostEngine::amountPesewas($billable, $pages, $rateValue),
        ])->save();

        return $request->refresh();
    }
}
