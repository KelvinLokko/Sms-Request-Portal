<?php

use App\Enums\PlatformRole;
use App\Enums\RecipientListStatus;
use App\Enums\RecipientRowStatus;
use App\Enums\SmsRequestStatus;
use App\Models\ProviderRate;
use App\Models\RecipientList;
use App\Models\SmsRecipient;
use App\Models\SmsRequest;
use Illuminate\Support\Facades\URL;
use Tests\Concerns\CreatesCompanies;

uses(CreatesCompanies::class);

beforeEach(function () {
    $this->withoutVite();
    ProviderRate::factory()->create([
        'rate_per_sms' => '0.020000',
        'effective_from' => now()->subDay()->toDateString(),
    ]);
});

it('builds the PORTAL campaign naming convention', function () {
    [$user, $company] = $this->createApprovedCompanyOwner([
        'slug' => 'acme-gh',
    ]);

    $campaign = SmsRequest::factory()->paid()->create([
        'company_id' => $company->id,
        'created_by' => $user->id,
        'reference' => 'SMS-2026-ABCD1234',
    ]);

    expect($campaign->portalCampaignName())->toBe('PORTAL-SMS-2026-ABCD1234-acme-gh');
});

it('lists paid campaigns in the fulfilment queue and highlights overdue ones', function () {
    [$user, $company] = $this->createApprovedCompanyOwner();
    $admin = $this->createPlatformAdmin();

    SmsRequest::factory()->paid()->create([
        'company_id' => $company->id,
        'created_by' => $user->id,
        'hard_deadline_at' => now()->subHour(),
        'reference' => 'SMS-2026-OVERDUE1',
    ]);

    SmsRequest::factory()->paid()->create([
        'company_id' => $company->id,
        'created_by' => $user->id,
        'hard_deadline_at' => now()->addDays(5),
        'reference' => 'SMS-2026-LATER001',
    ]);

    $this->actingAs($admin)
        ->get(route('admin.fulfilment.index', ['filter' => 'overdue']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/fulfilment/Index')
            ->has('campaigns.data', 1)
            ->where('campaigns.data.0.reference', 'SMS-2026-OVERDUE1')
            ->where('overdue_count', 1));
});

it('opens fulfilment, moves paid to awaiting, and marks fulfilled with an internal job ref', function () {
    [$user, $company] = $this->createApprovedCompanyOwner(['slug' => 'beta-co']);
    $admin = $this->createPlatformAdmin();

    $campaign = SmsRequest::factory()->paid()->create([
        'company_id' => $company->id,
        'created_by' => $user->id,
        'message_body' => 'Hello customers',
        'reference' => 'SMS-2026-FULFIL01',
    ]);

    RecipientList::factory()->create([
        'sms_request_id' => $campaign->id,
        'company_id' => $company->id,
        'status' => RecipientListStatus::Completed,
        'billable_count' => 2,
        'valid_count' => 2,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.fulfilment.show', $campaign))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/fulfilment/Show')
            ->where('campaign.status', SmsRequestStatus::AwaitingFulfilment->value)
            ->where('campaign.portal_campaign_name', 'PORTAL-SMS-2026-FULFIL01-beta-co')
            ->where('campaign.message_body', 'Hello customers'));

    expect($campaign->fresh()->status)->toBe(SmsRequestStatus::AwaitingFulfilment);

    $this->actingAs($admin)
        ->post(route('admin.fulfilment.fulfil', $campaign), [
            'deywuro_job_reference' => 'DW-JOB-999',
        ])
        ->assertRedirect(route('admin.fulfilment.index'));

    $campaign->refresh();

    expect($campaign->status)->toBe(SmsRequestStatus::Fulfilled)
        ->and($campaign->fulfilled_by)->toBe($admin->id)
        ->and($campaign->deywuro_job_reference)->toBe('DW-JOB-999')
        ->and($campaign->fulfilled_at)->not->toBeNull();
});

it('streams cleaned normalised recipients and hides the deywuro ref from clients', function () {
    [$user, $company] = $this->createApprovedCompanyOwner();
    $admin = $this->createPlatformAdmin();

    $campaign = SmsRequest::factory()->paid()->create([
        'company_id' => $company->id,
        'created_by' => $user->id,
        'status' => SmsRequestStatus::AwaitingFulfilment,
        'deywuro_job_reference' => null,
    ]);

    $list = RecipientList::factory()->create([
        'sms_request_id' => $campaign->id,
        'company_id' => $company->id,
        'status' => RecipientListStatus::Completed,
        'billable_count' => 2,
        'valid_count' => 2,
    ]);

    SmsRecipient::query()->create([
        'recipient_list_id' => $list->id,
        'sms_request_id' => $campaign->id,
        'company_id' => $company->id,
        'row_number' => 1,
        'raw_value' => '0244304528',
        'normalised_msisdn' => '233244304528',
        'status' => RecipientRowStatus::Valid,
    ]);
    SmsRecipient::query()->create([
        'recipient_list_id' => $list->id,
        'sms_request_id' => $campaign->id,
        'company_id' => $company->id,
        'row_number' => 2,
        'raw_value' => 'bad',
        'normalised_msisdn' => null,
        'status' => RecipientRowStatus::Invalid,
        'rejection_reason' => 'invalid',
    ]);
    SmsRecipient::query()->create([
        'recipient_list_id' => $list->id,
        'sms_request_id' => $campaign->id,
        'company_id' => $company->id,
        'row_number' => 3,
        'raw_value' => '=1+1',
        'normalised_msisdn' => '+233200000001',
        'status' => RecipientRowStatus::Valid,
    ]);

    $url = URL::temporarySignedRoute(
        'admin.fulfilment.recipients',
        now()->addMinutes(5),
        ['campaign' => $campaign->id],
    );

    $response = $this->actingAs($admin)->get($url);

    $response->assertOk();
    $content = $response->streamedContent();

    expect($content)->toContain('msisdn')
        ->and($content)->toContain('233244304528')
        ->and($content)->toContain("'+233200000001")
        ->and($content)->not->toContain(',bad');

    $campaign->forceFill([
        'status' => SmsRequestStatus::Fulfilled,
        'fulfilled_at' => now(),
        'fulfilled_by' => $admin->id,
        'deywuro_job_reference' => 'SECRET-JOB',
    ])->save();

    $this->actingAs($user)
        ->get(route('campaigns.show', $campaign))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('campaign.status_label', 'Sent by our team')
            ->missing('campaign.deywuro_job_reference'));
});

it('forbids finance from marking fulfilment', function () {
    [$user, $company] = $this->createApprovedCompanyOwner();
    $finance = $this->createPlatformAdmin(PlatformRole::Finance);

    $campaign = SmsRequest::factory()->paid()->create([
        'company_id' => $company->id,
        'created_by' => $user->id,
    ]);

    $this->actingAs($finance)
        ->get(route('admin.fulfilment.index'))
        ->assertForbidden();

    $this->actingAs($finance)
        ->post(route('admin.fulfilment.fulfil', $campaign), [
            'deywuro_job_reference' => 'x',
        ])
        ->assertForbidden();
});

it('forbids clients from opening the fulfilment queue', function () {
    [$user] = $this->createApprovedCompanyOwner();

    $this->actingAs($user)
        ->get(route('admin.fulfilment.index'))
        ->assertForbidden();
});
