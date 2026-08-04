<?php

namespace App\Enums;

enum MessageEncoding: string
{
    case Text = 'text';
    case Unicode = 'unicode';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Text (GSM)',
            self::Unicode => 'Unicode',
        };
    }

    public function requiresManualCostReview(): bool
    {
        return $this === self::Unicode;
    }
}
