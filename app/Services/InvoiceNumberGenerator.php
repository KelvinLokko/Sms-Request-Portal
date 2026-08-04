<?php

namespace App\Services;

use App\Models\InvoiceNumberSequence;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InvoiceNumberGenerator
{
    /**
     * Allocate the next gapless invoice number for the given year.
     *
     * Must be called inside a database transaction. Uses SELECT … FOR UPDATE
     * on the per-year counter row — never MAX(id)+1.
     */
    public function next(int $year): string
    {
        if (! DB::transactionLevel()) {
            throw new RuntimeException('Invoice numbers must be allocated inside a transaction.');
        }

        $sequence = InvoiceNumberSequence::query()
            ->where('year', $year)
            ->lockForUpdate()
            ->first();

        if ($sequence === null) {
            DB::table('invoice_number_sequences')->insertOrIgnore([
                'year' => $year,
                'last_number' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $sequence = InvoiceNumberSequence::query()
                ->where('year', $year)
                ->lockForUpdate()
                ->firstOrFail();
        }

        $next = $sequence->last_number + 1;
        $sequence->forceFill([
            'last_number' => $next,
            'updated_at' => now(),
        ])->save();

        return sprintf('INV-%d-%06d', $year, $next);
    }
}
