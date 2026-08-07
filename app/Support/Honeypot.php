<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

final class Honeypot
{
    public const FIELD = 'website';

    /**
     * Reject submissions that fill the hidden honeypot field.
     *
     * @throws ValidationException
     */
    public static function assertEmpty(mixed $value): void
    {
        if (filled($value)) {
            throw ValidationException::withMessages([
                'email' => 'Unable to process this request.',
            ]);
        }
    }
}
