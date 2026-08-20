<?php

use App\Enums\MessageEncoding;
use App\Enums\RecipientListStatus;
use App\Enums\RecipientRowStatus;
use App\Enums\SenderIdStatus;
use App\Enums\SmsRequestStatus;
use App\Jobs\ValidateRecipientListJob;
use App\Models\CompanyRate;
use App\Models\RecipientList;
use App\Models\SenderId;
use App\Models\SmsRecipient;
use App\Models\SmsRequest;
use App\Support\Sms\CostEngine;
use App\Support\Sms\GhanaNumberNormaliser;
use App\Support\Sms\PlaceholderValidator;
use App\Support\Sms\SegmentCounter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesCompanies;

uses(CreatesCompanies::class);

function fakeRecipientCsv(string $name = 'contacts.csv'): UploadedFile
{
    return UploadedFile::fake()->createWithContent(
        $name,
        "phone\n0244123456\n0244987654\n",
    );
}

it('counts segments with the 8-tier Deywuro table', function () {
    expect(SegmentCounter::pages(1))->toBe(1)
        ->and(SegmentCounter::pages(160))->toBe(1)
        ->and(SegmentCounter::pages(161))->toBe(2)
        ->and(SegmentCounter::pages(306))->toBe(2)
        ->and(SegmentCounter::pages(307))->toBe(3)
        ->and(SegmentCounter::pages(621))->toBe(4)
        ->and(SegmentCounter::pages(622))->toBe(5)
        ->and(SegmentCounter::pages(1073))->toBe(8)
        ->and(SegmentCounter::exceedsPracticalLimit(622))->toBeTrue();
});

it('normalises Ghanaian numbers to 233 format', function () {
    expect(GhanaNumberNormaliser::normalise('0244304528')['msisdn'])->toBe('233244304528')
        ->and(GhanaNumberNormaliser::normalise('244304528')['msisdn'])->toBe('233244304528')
        ->and(GhanaNumberNormaliser::normalise('233244304528')['msisdn'])->toBe('233244304528')
        ->and(GhanaNumberNormaliser::normalise('0244-304-528')['msisdn'])->toBe('233244304528')
        ->and(GhanaNumberNormaliser::normalise('123')['ok'])->toBeFalse();
});

it('quotes cost as billable times pages times rate in pesewas', function () {
    $quote = CostEngine::quote(str_repeat('a', 161), 100, '0.030000');

    expect($quote['pages'])->toBe(2)
        ->and($quote['cost_pesewas'])->toBe(600) // 100 * 2 * 0.03 = 6.00 GHS
        ->and($quote['exceeds_621_warning'])->toBeFalse();
});

it('detects missing personalisation placeholders', function () {
    $missing = PlaceholderValidator::missing('Hello [Name], ref [CODE]', ['name', 'phone']);

    expect($missing)->toBe(['CODE']);
});

it('forbids unapproved companies from listing campaigns', function () {
    [$user] = $this->createCompanyOwner();

    $this->actingAs($user)
        ->get(route('campaigns.index'))
        ->assertForbidden();
});

it('lets an approved owner create a draft campaign', function () {
    Queue::fake();
    CompanyRate::factory()->create(['rate_per_sms' => '0.030000']);
    [$user, $company] = $this->createApprovedCompanyOwner();

    $sender = SenderId::factory()->approved()->create([
        'company_id' => $company->id,
        'requested_by' => $user->id,
        'value' => 'AcmeGH',
    ]);

    $this->actingAs($user)
        ->post(route('campaigns.store'), [
            'name' => 'Promo',
            'message_body' => 'Hello customers',
            'campaign_type' => 'bulk',
            'sender_id_id' => $sender->id,
            'file' => fakeRecipientCsv(),
        ])
        ->assertRedirect();

    $campaign = SmsRequest::query()->first();
    expect($campaign)->not->toBeNull()
        ->and($campaign->status)->toBe(SmsRequestStatus::Draft)
        ->and($campaign->pages)->toBe(1)
        ->and($campaign->is_personalised)->toBeFalse()
        ->and($campaign->company_id)->toBe($company->id)
        ->and(RecipientList::query()->where('sms_request_id', $campaign->id)->count())->toBe(1);

    Queue::assertPushed(ValidateRecipientListJob::class);
});

it('creates a personalised bulk draft and serves recipient templates', function () {
    Queue::fake();
    [$user, $company] = $this->createApprovedCompanyOwner();

    $this->actingAs($user)
        ->post(route('campaigns.store'), [
            'name' => 'Personal',
            'message_body' => 'Hello [Name]',
            'campaign_type' => 'personalised_bulk',
            'file' => fakeRecipientCsv('personal.csv'),
        ])
        ->assertRedirect();

    expect(SmsRequest::query()->first()?->is_personalised)->toBeTrue();

    $this->actingAs($user)
        ->get(route('campaigns.templates.download', ['type' => 'bulk']))
        ->assertOk()
        ->assertHeader('content-disposition');

    $this->actingAs($user)
        ->get(route('campaigns.templates.download', ['type' => 'personalised-bulk']))
        ->assertOk();
});

it('allows equal requested send time and hard deadline on draft create', function () {
    Queue::fake();
    [$user] = $this->createApprovedCompanyOwner();
    $when = now()->addDay()->seconds(0);

    $this->actingAs($user)
        ->post(route('campaigns.store'), [
            'message_body' => 'Hello',
            'campaign_type' => 'bulk',
            'requested_send_at' => $when->format('Y-m-d\\TH:i'),
            'hard_deadline_at' => $when->format('Y-m-d\\TH:i'),
            'file' => fakeRecipientCsv(),
        ])
        ->assertRedirect();

    expect(SmsRequest::query()->count())->toBe(1);
});

it('requires a recipient list when creating a campaign', function () {
    [$user] = $this->createApprovedCompanyOwner();

    $this->actingAs($user)
        ->post(route('campaigns.store'), [
            'message_body' => 'Hello',
            'campaign_type' => 'bulk',
        ])
        ->assertSessionHasErrors('file');
});

it('validates a recipient csv and counts billable rows', function () {
    Storage::fake(config('filesystems.default'));
    CompanyRate::factory()->create(['rate_per_sms' => '0.030000']);
    [$user, $company] = $this->createApprovedCompanyOwner();

    $campaign = SmsRequest::factory()->create([
        'company_id' => $company->id,
        'created_by' => $user->id,
        'status' => SmsRequestStatus::Draft,
        'message_body' => 'Hello',
    ]);

    $csv = "phone,name\n0244123456,Ada\n0244987654,Kwame\n0244123456,Dup\nbad,X\n";
    $path = 'recipient-lists/'.$company->id.'/list.csv';
    Storage::disk(config('filesystems.default'))->put($path, $csv);

    $list = RecipientList::factory()->create([
        'sms_request_id' => $campaign->id,
        'company_id' => $company->id,
        'original_path' => $path,
        'original_filename' => 'list.csv',
        'status' => RecipientListStatus::Pending,
        'valid_count' => 0,
        'billable_count' => 0,
    ]);

    (new ValidateRecipientListJob($list->id))->handle();

    $list->refresh();
    $campaign->refresh();

    expect($list->status)->toBe(RecipientListStatus::Completed)
        ->and($list->valid_count)->toBe(2)
        ->and($list->duplicate_count)->toBe(1)
        ->and($list->invalid_count)->toBe(1)
        ->and($list->billable_count)->toBe(2)
        ->and($campaign->billable_recipients)->toBe(2)
        ->and($campaign->estimated_cost_pesewas)->toBe(6)
        ->and($list->hasRejectedExport())->toBeTrue();

    expect(SmsRecipient::query()->where('status', RecipientRowStatus::Valid)->count())->toBe(2);
});

it('submits a campaign when list is validated and sender approved', function () {
    CompanyRate::factory()->create(['rate_per_sms' => '0.030000']);
    [$user, $company] = $this->createApprovedCompanyOwner();

    $sender = SenderId::factory()->approved()->create([
        'company_id' => $company->id,
        'requested_by' => $user->id,
        'status' => SenderIdStatus::Approved,
        'value' => 'AcmeGH',
    ]);

    $campaign = SmsRequest::factory()->create([
        'company_id' => $company->id,
        'created_by' => $user->id,
        'sender_id_id' => $sender->id,
        'status' => SmsRequestStatus::Draft,
        'message_body' => 'Hello world',
        'encoding' => MessageEncoding::Text,
        'billable_recipients' => 10,
    ]);

    RecipientList::factory()->create([
        'sms_request_id' => $campaign->id,
        'company_id' => $company->id,
        'status' => RecipientListStatus::Completed,
        'billable_count' => 10,
        'valid_count' => 10,
    ]);

    $this->actingAs($user)
        ->post(route('campaigns.submit', $campaign))
        ->assertRedirect(route('campaigns.show', $campaign));

    $campaign->refresh();

    expect($campaign->status)->toBe(SmsRequestStatus::Submitted)
        ->and($campaign->quoted_cost_pesewas)->toBe(30) // 10 * 1 * 0.03 = 0.30 GHS = 30 pesewas
        ->and($campaign->submitted_at)->not->toBeNull();
});

it('rejects personalised submit when placeholders are missing from headers', function () {
    CompanyRate::factory()->create(['rate_per_sms' => '0.030000']);
    [$user, $company] = $this->createApprovedCompanyOwner();

    $sender = SenderId::factory()->approved()->create([
        'company_id' => $company->id,
        'requested_by' => $user->id,
        'value' => 'AcmeGH',
    ]);

    $campaign = SmsRequest::factory()->create([
        'company_id' => $company->id,
        'created_by' => $user->id,
        'sender_id_id' => $sender->id,
        'is_personalised' => true,
        'message_body' => 'Hi [FirstName]',
        'status' => SmsRequestStatus::Draft,
    ]);

    RecipientList::factory()->create([
        'sms_request_id' => $campaign->id,
        'company_id' => $company->id,
        'headers' => ['phone', 'name'],
        'status' => RecipientListStatus::Completed,
        'billable_count' => 5,
    ]);

    $this->actingAs($user)
        ->post(route('campaigns.submit', $campaign))
        ->assertSessionHasErrors('message_body');
});

it('uploads a recipient file and queues validation', function () {
    Storage::fake(config('filesystems.default'));
    [$user, $company] = $this->createApprovedCompanyOwner();

    $campaign = SmsRequest::factory()->create([
        'company_id' => $company->id,
        'created_by' => $user->id,
        'status' => SmsRequestStatus::Draft,
    ]);

    $file = UploadedFile::fake()->createWithContent(
        'contacts.csv',
        "phone\n0244123456\n",
    );

    Queue::fake();

    $this->actingAs($user)
        ->post(route('campaigns.recipients.upload', $campaign), [
            'file' => $file,
        ])
        ->assertRedirect();

    Queue::assertPushed(ValidateRecipientListJob::class);
    expect(RecipientList::query()->count())->toBe(1);
});

it('flags unicode campaigns for manual cost review on submit', function () {
    CompanyRate::factory()->create(['rate_per_sms' => '0.030000']);
    [$user, $company] = $this->createApprovedCompanyOwner();

    $sender = SenderId::factory()->approved()->create([
        'company_id' => $company->id,
        'requested_by' => $user->id,
        'value' => 'AcmeGH',
    ]);

    $campaign = SmsRequest::factory()->create([
        'company_id' => $company->id,
        'created_by' => $user->id,
        'sender_id_id' => $sender->id,
        'encoding' => MessageEncoding::Unicode,
        'message_body' => 'Bonjour',
        'status' => SmsRequestStatus::Draft,
    ]);

    RecipientList::factory()->create([
        'sms_request_id' => $campaign->id,
        'company_id' => $company->id,
        'billable_count' => 3,
        'status' => RecipientListStatus::Completed,
    ]);

    $this->actingAs($user)
        ->post(route('campaigns.submit', $campaign))
        ->assertRedirect();

    expect($campaign->fresh()->requires_manual_cost_review)->toBeTrue();
});

it('lets staff browse the full campaign history including rejected records', function () {
    [$user, $company] = $this->createApprovedCompanyOwner();
    $admin = $this->createPlatformAdmin();

    $rejected = SmsRequest::factory()->create([
        'company_id' => $company->id,
        'created_by' => $user->id,
        'status' => SmsRequestStatus::Rejected,
        'submitted_at' => now()->subDay(),
        'name' => 'Rejected history campaign',
    ]);
    SmsRequest::factory()->create([
        'company_id' => $company->id,
        'created_by' => $user->id,
        'status' => SmsRequestStatus::Fulfilled,
        'submitted_at' => now()->subHours(2),
        'name' => 'Fulfilled history campaign',
    ]);
    SmsRequest::factory()->submitted()->create([
        'company_id' => $company->id,
        'created_by' => $user->id,
        'status' => SmsRequestStatus::Submitted,
        'name' => 'Needs review campaign',
    ]);

    $this->actingAs($admin)
        ->withoutVite()
        ->get(route('admin.campaigns.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/campaigns/Index')
            ->has('campaigns.data', 3)
            ->where('campaigns.data.0.name', 'Needs review campaign'));

    $this->actingAs($admin)
        ->withoutVite()
        ->get(route('admin.campaigns.index', ['status' => 'rejected']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('campaigns.data', 1)
            ->where('campaigns.data.0.id', $rejected->id));

    $this->actingAs($admin)
        ->withoutVite()
        ->get(route('admin.campaigns.index', ['status' => 'needs_review']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('campaigns.data', 1)
            ->where('campaigns.data.0.name', 'Needs review campaign'));
});
