<?php

namespace App\Console\Commands;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Services\CampaignNotifier;
use Illuminate\Console\Command;

class SendCompanyApprovedEmails extends Command
{
    protected $signature = 'companies:send-approved-emails
                            {--company= : Limit to a single company ID}
                            {--dry-run : List recipients without sending}';

    protected $description = 'Email company users that their account has been approved';

    public function handle(CampaignNotifier $notifier): int
    {
        $query = Company::query()
            ->where('status', CompanyStatus::Approved)
            ->with('users:id,name,email')
            ->orderBy('id');

        if ($this->option('company') !== null) {
            $query->whereKey((int) $this->option('company'));
        }

        $companies = $query->get();

        if ($companies->isEmpty()) {
            $this->warn('No approved companies matched.');

            return self::SUCCESS;
        }

        $dryRun = (bool) $this->option('dry-run');
        $sentCompanies = 0;
        $sentUsers = 0;

        foreach ($companies as $company) {
            $users = $company->users;

            if ($users->isEmpty()) {
                $this->line("[{$company->id}] {$company->name} — no users, skipped");

                continue;
            }

            $emails = $users->pluck('email')->implode(', ');
            $this->line("[{$company->id}] {$company->name} → {$emails}");

            if ($dryRun) {
                continue;
            }

            $notifier->companyApproved($company);
            $sentCompanies++;
            $sentUsers += $users->count();
        }

        if ($dryRun) {
            $this->info('Dry run only — no emails queued.');

            return self::SUCCESS;
        }

        $this->info("Queued approval emails for {$sentUsers} user(s) across {$sentCompanies} company(ies).");
        $this->comment('Ensure Horizon is running so the notifications queue can deliver them.');

        return self::SUCCESS;
    }
}
