<?php

namespace App\Enums;

enum MessageFlashType: string
{
    case Text = 'text';
    case Flash = 'flash';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Standard',
            self::Flash => 'Flash',
        };
    }
}
