<?php

namespace App\Enums;

enum PlatformRole: string
{
    case SuperAdmin = 'super-admin';
    case Admin = 'admin';
    case Finance = 'finance';
    case Support = 'support';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super admin',
            self::Admin => 'Admin',
            self::Finance => 'Finance',
            self::Support => 'Support',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::SuperAdmin, self::Admin => 'Full platform access for operating and administering the portal.',
            self::Finance => 'Billing and payment operations without company or fulfilment workflows.',
            self::Support => 'Day-to-day operations for companies, sender IDs, campaigns, and fulfilment.',
        };
    }

    public static function tryLabel(string $name): string
    {
        $role = self::tryFrom($name);

        return $role?->label()
            ?? str($name)->replace('-', ' ')->title()->toString();
    }

    public static function tryDescription(string $name): string
    {
        $role = self::tryFrom($name);

        return $role?->description()
            ?? 'Custom staff role. Access comes from the permissions assigned below.';
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function isSystem(string $name): bool
    {
        return in_array($name, self::values(), true);
    }
}
