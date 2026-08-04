<?php

namespace App\Support;

use App\Models\TaxRate;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TaxCalculator
{
    /**
     * Apply active tax rates to a subtotal in pesewas.
     *
     * @return array{
     *     tax_pesewas: int,
     *     total_pesewas: int,
     *     lines: list<array{name: string, rate: string, amount_pesewas: int}>
     * }
     */
    public static function apply(int $subtotalPesewas, ?Carbon $on = null): array
    {
        /** @var Collection<int, TaxRate> $rates */
        $rates = TaxRate::activeOn($on);
        $lines = [];
        $taxTotal = 0;

        foreach ($rates as $rate) {
            $amount = self::percentageOf($subtotalPesewas, (string) $rate->rate);
            $taxTotal += $amount;
            $lines[] = [
                'name' => $rate->name,
                'rate' => (string) $rate->rate,
                'amount_pesewas' => $amount,
            ];
        }

        return [
            'tax_pesewas' => $taxTotal,
            'total_pesewas' => $subtotalPesewas + $taxTotal,
            'lines' => $lines,
        ];
    }

    /**
     * Round half-up percentage of an integer amount using BCMath.
     */
    public static function percentageOf(int $amountPesewas, string $ratePercent): int
    {
        $fraction = bcdiv($ratePercent, '100', 10);
        $raw = bcmul((string) $amountPesewas, $fraction, 10);

        return (int) bcadd($raw, '0.5', 0);
    }
}
