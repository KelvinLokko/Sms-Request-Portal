<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * GSM alphanumeric originator: letters, digits and spaces only, max 11 chars.
 */
class GsmAlphanumericSenderId implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail('The :attribute must be a string.');

            return;
        }

        $trimmed = trim($value);

        if ($trimmed === '' || strlen($trimmed) > 11) {
            $fail('The :attribute must be between 1 and 11 characters.');

            return;
        }

        if (! preg_match('/^[A-Za-z0-9 ]+$/', $trimmed)) {
            $fail('The :attribute may only contain letters, numbers and spaces.');
        }
    }
}
