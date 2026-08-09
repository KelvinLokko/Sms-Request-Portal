<?php

use App\Enums\PlatformRole;
use App\Enums\SmsRequestStatus;
use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\ProviderRate;
use App\Models\SmsRequest;
use App\Notifications\CampaignSubmittedNotification;
use App\Notifications\ChangesRequestedNotification;
use App\Notifications\InvoiceReadyNotification;
use App\Services\AnalyticsService;
use App\Services\CampaignNotifier;
use App\Services\CampaignReviewService;
use App\Services\InvoiceService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\CreatesCompanies;

uses(CreatesCompanies::class);

beforeEach(function () {
    $this->withoutVite();
});

it('notifies platform staff when a campaign is submitted', function () {
    Notification::fake();

    [$user, $company] = $this->createApprovedCompanyOwner();
    $admin = $this->createPlatformAdmin();

    $campaign = SmsRequest::factory()->submitted()->create([
        'company_id' => $company->id,
        'created_by' => $user->id,
        'status' => SmsRequestStatus::Draft,
        'billable_recipients' => 10,
        'quoted_cost_pesewas' => 30,
    ]);

    // Use notifier directly to avoid full submit prerequisites.
    app(CampaignNotifier::class)->submitted($campaign->fresh());

    Notification::assertSentTo($admin, CampaignSubmittedNotification::class);
});

it('notifies company users when changes are requested', function () {
    Notification::fake();

    [$user, $company] = $this->createApprovedCompanyOwner();
    $support = $this->createPlatformAdmin(PlatformRole::Support);

    $campaign = SmsRequest::factory()->submitted()->create([
        'company_id' => $company->id,
        'created_by' => $user->id,
    ]);

    app(CampaignReviewService::class)->requestChanges(
        $campaign,
        $support,
        'Please revise the message.',
    );

    Notification::assertSentTo($user, ChangesRequestedNotification::class);

    $log = ActivityLog::query()->where('action', 'sms_request.changes_requested')->latest('id')->first();

    expect($log)->not->toBeNull()
        ->and($log->properties['before']['status'] ?? null)->toBe('submitted')
        ->and($log->properties['after']['status'] ?? null)->toBe('changes_requested')
        ->and($log->properties['after']['changes_requested_reason'] ?? null)->toBe('Please revise the message.');
});

it('notifies company users when an invoice is issued', function () {
    Notification::fake();
    Queue::fake();

    ProviderRate::factory()->create(['rate_per_sms' => '0.020000']);

    [$user, $company] = $this->createApprovedCompanyOwner();
    $admin = $this->createPlatformAdmin();

    $campaign = SmsRequest::factory()->submitted()->create([
        'company_id' => $company->id,
        'created_by' => $user->id,
        'status' => SmsRequestStatus::UnderReview,
        'quoted_cost_pesewas' => 300,
        'billable_recipients' => 100,
        'pages' => 1,
        'rate_per_sms' => '0.030000',
    ]);

    $invoice = app(InvoiceService::class)->issue($campaign, $admin);

    Notification::assertSentTo($user, InvoiceReadyNotification::class);
    expect($invoice->number)->toStartWith('INV-')
        ->and(bccomp((string) $campaign->fresh()->provider_rate_per_sms, '0.020000', 6))->toBe(0)
        ->and($campaign->fresh()->provider_cost_pesewas)->toBe(200);
});

it('reports realized profit from client billed minus provider cost', function () {
    ProviderRate::factory()->create(['rate_per_sms' => '0.020000']);

    [$user, $company] = $this->createApprovedCompanyOwner();
    $admin = $this->createPlatformAdmin();

    SmsRequest::factory()->submitted()->create([
        'company_id' => $company->id,
        'created_by' => $user->id,
        'status' => SmsRequestStatus::Fulfilled,
        'submitted_at' => now()->subDays(2),
        'fulfilled_at' => now()->subDay(),
        'billable_recipients' => 100,
        'pages' => 1,
        'quoted_cost_pesewas' => 300,
        'provider_rate_per_sms' => '0.020000',
        'provider_cost_pesewas' => 200,
    ]);

    Invoice::factory()->paid()->create([
        'company_id' => $company->id,
        'sms_request_id' => SmsRequest::factory()->submitted()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'status' => SmsRequestStatus::Paid,
        ])->id,
        'issued_by' => $admin->id,
        'total_pesewas' => 300,
        'subtotal_pesewas' => 300,
        'paid_at' => now(),
    ]);

    $summary = app(AnalyticsService::class)->summary();

    expect($summary['billed_sms_pesewas'])->toBe(300)
        ->and($summary['provider_cost_pesewas'])->toBe(200)
        ->and($summary['profit_pesewas'])->toBe(100)
        ->and($summary['is_loss'])->toBeFalse()
        ->and($summary['margin_percent'])->toBe(33.3);
});

it('aggregates analytics in SQL for the admin dashboard', function () {
    [$user, $company] = $this->createApprovedCompanyOwner();
    $admin = $this->createPlatformAdmin();

    SmsRequest::factory()->submitted()->create([
        'company_id' => $company->id,
        'created_by' => $user->id,
        'status' => SmsRequestStatus::Fulfilled,
        'submitted_at' => now()->subDays(2),
        'fulfilled_at' => now()->subDay(),
        'billable_recipients' => 100,
        'pages' => 2,
        'quoted_cost_pesewas' => 600,
    ]);

    Invoice::factory()->paid()->create([
        'company_id' => $company->id,
        'sms_request_id' => SmsRequest::factory()->submitted()->create([
            'company_id' => $company->id,
            'created_by' => $user->id,
            'status' => SmsRequestStatus::Paid,
        ])->id,
        'issued_by' => $admin->id,
        'total_pesewas' => 450,
        'subtotal_pesewas' => 450,
        'paid_at' => now(),
    ]);

    $dashboard = app(AnalyticsService::class)->dashboard();

    expect($dashboard['summary']['fulfilled'])->toBe(1)
        ->and($dashboard['summary']['revenue_pesewas'])->toBe(450)
        ->and($dashboard['avg_turnaround_hours'])->not->toBeNull()
        ->and($dashboard['monthly']['revenue'])->toBeArray()
        ->and(count($dashboard['monthly']['requests']))->toBeGreaterThan(0);

    $this->actingAs($admin)
        ->get(route('admin.analytics.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/analytics/Index')
            ->has('summary')
            ->has('monthly.revenue'));
});

it('lets admins browse the append-only audit log', function () {
    $admin = $this->createPlatformAdmin();

    ActivityLog::query()->create([
        'user_id' => $admin->id,
        'company_id' => null,
        'action' => 'sms_request.submitted',
        'subject_type' => SmsRequest::class,
        'subject_id' => 1,
        'properties' => ['before' => ['status' => 'draft'], 'after' => ['status' => 'submitted']],
        'ip_address' => '127.0.0.1',
        'user_agent' => 'pest',
        'created_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.activity.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/activity/Index')
            ->has('logs.data', 1)
            ->where('logs.data.0.action', 'sms_request.submitted'));
});

it('shows staff KPI summary on the dashboard', function () {
    $admin = $this->createPlatformAdmin();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('isStaff', true)
            ->has('summary.pending_review'));
});
