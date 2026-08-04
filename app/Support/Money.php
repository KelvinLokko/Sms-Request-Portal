<?php

namespace App\Support;

use InvalidArgumentException;

class Money
{
    /**
     * Convert a major-unit amount (cedis) to minor units (pesewas).
     *
     * Uses BCMath — never float arithmetic for money.
     */
    public static function fromMajor(string|float $cedis): int
    {
        $normalized = is_float($cedis)
            ? number_format($cedis, 2, '.', '')
            : trim((string) $cedis);

        if ($normalized === '' || ! is_numeric($normalized)) {
            throw new InvalidArgumentException('Amount must be a numeric major-unit value.');
        }

        return (int) bcmul($normalized, '100', 0);
    }

    /**
     * Convert minor units (pesewas) to a major-unit decimal string (cedis).
     */
    public static function toMajor(int $pesewas): string
    {
        return bcdiv((string) $pesewas, '100', 2);
    }

    /**
     * Format minor units as a currency display string.
     */
    public static function format(int $pesewas, string $currency = 'GHS'): string
    {
        return $currency.' '.self::toMajor($pesewas);
    }
}
