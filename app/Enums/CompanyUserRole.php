<?php

namespace App\Enums;

enum CompanyUserRole: string
{
    case Owner = 'owner';
    case Manager = 'manager';
    case Billing = 'billing';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Manager => 'Manager',
            self::Billing => 'Billing',
        };
    }

    public function canManageSenderIds(): bool
    {
        return match ($this) {
            self::Owner, self::Manager => true,
            self::Billing => false,
        };
    }

    public function canManageBilling(): bool
    {
        return match ($this) {
            self::Owner, self::Billing => true,
            self::Manager => false,
        };
    }
}
