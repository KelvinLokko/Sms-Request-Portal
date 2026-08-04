<?php

namespace App\Enums;

enum PlatformPermission: string
{
    case AdminAccess = 'admin.access';
    case UsersManage = 'users.manage';
    case RolesManage = 'roles.manage';
    case ActivityView = 'activity.view';
    case CompaniesView = 'companies.view';
    case CompaniesManage = 'companies.manage';
    case SenderIdsReview = 'sender-ids.review';
    case CampaignsReview = 'campaigns.review';
    case CampaignsCancel = 'campaigns.cancel';
    case CampaignsFulfil = 'campaigns.fulfil';
    case CampaignsInvoice = 'campaigns.invoice';
    case RatesManage = 'rates.manage';
    case TaxRatesManage = 'tax-rates.manage';
    case PaymentsManage = 'payments.manage';
    case HorizonView = 'horizon.view';

    public function label(): string
    {
        return match ($this) {
            self::AdminAccess => 'Access admin portal',
            self::UsersManage => 'Manage users',
            self::RolesManage => 'Manage roles',
            self::ActivityView => 'View audit log',
            self::CompaniesView => 'View companies',
            self::CompaniesManage => 'Manage companies',
            self::SenderIdsReview => 'Review sender IDs',
            self::CampaignsReview => 'Review campaigns',
            self::CampaignsCancel => 'Cancel campaigns',
            self::CampaignsFulfil => 'Fulfil campaigns',
            self::CampaignsInvoice => 'Issue invoices',
            self::RatesManage => 'Manage SMS rates',
            self::TaxRatesManage => 'Manage tax rates',
            self::PaymentsManage => 'Manage payments',
            self::HorizonView => 'View Horizon',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::AdminAccess => 'Open the admin area, analytics, and campaign browse.',
            self::UsersManage => 'Create, edit, and delete staff and client users.',
            self::RolesManage => 'Create roles and assign permissions.',
            self::ActivityView => 'Browse the platform audit log.',
            self::CompaniesView => 'List and view company records.',
            self::CompaniesManage => 'Approve, reject, and suspend companies.',
            self::SenderIdsReview => 'Approve or reject sender ID requests.',
            self::CampaignsReview => 'Start review, request changes, and reject campaigns.',
            self::CampaignsCancel => 'Cancel campaigns as platform staff.',
            self::CampaignsFulfil => 'Mark campaigns fulfilled and download recipients.',
            self::CampaignsInvoice => 'Issue invoices from campaign review.',
            self::RatesManage => 'Create and update company SMS rates.',
            self::TaxRatesManage => 'Create and update tax rates.',
            self::PaymentsManage => 'Verify, reject, and report Mobile Money payments.',
            self::HorizonView => 'Access the Laravel Horizon dashboard.',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::AdminAccess, self::UsersManage, self::RolesManage, self::ActivityView, self::HorizonView => 'Administration',
            self::CompaniesView, self::CompaniesManage, self::SenderIdsReview, self::CampaignsReview, self::CampaignsCancel, self::CampaignsFulfil => 'Operations',
            self::CampaignsInvoice, self::RatesManage, self::TaxRatesManage, self::PaymentsManage => 'Finance',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<self>
     */
    public static function forRole(PlatformRole $role): array
    {
        return match ($role) {
            PlatformRole::SuperAdmin, PlatformRole::Admin => self::cases(),
            PlatformRole::Finance => [
                self::AdminAccess,
                self::CampaignsInvoice,
                self::RatesManage,
                self::TaxRatesManage,
                self::PaymentsManage,
            ],
            PlatformRole::Support => [
                self::AdminAccess,
                self::CompaniesView,
                self::SenderIdsReview,
                self::CampaignsReview,
                self::CampaignsFulfil,
            ],
        };
    }

    /**
     * @return list<array{group: string, permissions: list<array{value: string, label: string, description: string}>}>
     */
    public static function catalog(): array
    {
        $grouped = [];

        foreach (self::cases() as $permission) {
            $grouped[$permission->group()][] = [
                'value' => $permission->value,
                'label' => $permission->label(),
                'description' => $permission->description(),
            ];
        }

        return collect($grouped)
            ->map(fn (array $permissions, string $group) => [
                'group' => $group,
                'permissions' => $permissions,
            ])
            ->values()
            ->all();
    }
}
