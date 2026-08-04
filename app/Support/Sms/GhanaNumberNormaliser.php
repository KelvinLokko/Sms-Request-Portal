<?php

namespace App\Support\Sms;

/**
 * Ghana-first MSISDN normalisation matching Deywuro SmsTools::format_gh_number().
 */
final class GhanaNumberNormaliser
{
    /**
     * @return array{ok: bool, msisdn: string|null, reason: string|null}
     */
    public static function normalise(string $raw): array
    {
        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if ($digits === '') {
            return ['ok' => false, 'msisdn' => null, 'reason' => 'Empty number'];
        }

        // Spreadsheet often strips leading zero: 244304528 → 233244304528
        if (strlen($digits) === 9) {
            $digits = '233'.$digits;
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            $digits = '233'.substr($digits, 1);
        }

        if (str_starts_with($digits, '233')) {
            if (strlen($digits) !== 12) {
                return ['ok' => false, 'msisdn' => null, 'reason' => 'Ghana numbers must be 233 followed by 9 digits'];
            }

            $national = substr($digits, 3);
            if (! str_starts_with($national, '2') && ! str_starts_with($national, '5')) {
                // Still accept other GH prefixes; only length is hard-validated by Deywuro for 233.
            }

            return ['ok' => true, 'msisdn' => $digits, 'reason' => null];
        }

        // International: must already be full international form (no leading 0), 10–15 digits.
        if (str_starts_with($digits, '0')) {
            return ['ok' => false, 'msisdn' => null, 'reason' => 'International numbers must include the country code'];
        }

        $length = strlen($digits);
        if ($length < 10 || $length > 15) {
            return ['ok' => false, 'msisdn' => null, 'reason' => 'Invalid international number length'];
        }

        return ['ok' => true, 'msisdn' => $digits, 'reason' => null];
    }
}
