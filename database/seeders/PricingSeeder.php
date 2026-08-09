<?php

namespace Database\Seeders;

use App\Models\CompanyRate;
use App\Models\ProviderRate;
use App\Models\TaxRate;
use Illuminate\Database\Seeder;

class PricingSeeder extends Seeder
{
    public function run(): void
    {
        if (! CompanyRate::query()->whereNull('company_id')->exists()) {
            CompanyRate::query()->create([
                'company_id' => null,
                'rate_per_sms' => '0.030000',
                'effective_from' => now()->toDateString(),
                'created_by' => null,
            ]);
        }

        if (! ProviderRate::query()->exists()) {
            // Placeholder — replace with the live third-party unit price.
            ProviderRate::query()->create([
                'rate_per_sms' => '0.020000',
                'effective_from' => now()->toDateString(),
                'created_by' => null,
            ]);
        }

        if (! TaxRate::query()->exists()) {
            // Placeholder — replace with the real levy structure before go-live.
            TaxRate::query()->create([
                'name' => 'Service tax (placeholder)',
                'rate' => '0.0000',
                'effective_from' => now()->toDateString(),
                'is_active' => false,
            ]);
        }
    }
}
