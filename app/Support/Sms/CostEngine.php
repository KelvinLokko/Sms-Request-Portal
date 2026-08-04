<?php

namespace App\Support\Sms;

use InvalidArgumentException;

/**
 * Authoritative server-side cost: billable × pages × rate → pesewas.
 */
final class CostEngine
{
    /**
     * @return array{
     *     pages: int,
     *     exceeds_621_warning: bool,
     *     character_count: int,
     *     cost_pesewas: int,
     *     cost_major: string
     * }
     */
    public static function quote(string $message, int $billableRecipients, string $ratePerSms): array
    {
        if ($billableRecipients < 0) {
            throw new InvalidArgumentException('Billable recipients cannot be negative.');
        }

        if (! is_numeric($ratePerSms) || bccomp($ratePerSms, '0', 6) < 0) {
            throw new InvalidArgumentException('Rate must be a non-negative number.');
        }

        $characterCount = mb_strlen($message);
        $pages = SegmentCounter::pages($characterCount);

        $totalMajor = '0.000000';
        if ($billableRecipients > 0 && $pages > 0) {
            $volume = bcmul((string) $billableRecipients, (string) $pages, 0);
            $totalMajor = bcmul($volume, $ratePerSms, 6);
        }

        // Round half-up to pesewas.
        $costPesewas = (int) bcadd(bcmul($totalMajor, '100', 2), '0.5', 0);

        return [
            'pages' => $pages,
            'exceeds_621_warning' => SegmentCounter::exceedsPracticalLimit($characterCount),
            'character_count' => $characterCount,
            'cost_pesewas' => $costPesewas,
            'cost_major' => bcdiv((string) $costPesewas, '100', 2),
        ];
    }
}
