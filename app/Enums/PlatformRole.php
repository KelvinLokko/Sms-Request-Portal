<?php

namespace App\Enums;

enum PlatformRole: string
{
    case SuperAdmin = 'super-admin';
    case Admin = 'admin';
    case Finance = 'finance';
    case Support = 'support';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
