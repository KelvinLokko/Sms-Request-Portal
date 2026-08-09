<?php

use App\Enums\CompanyStatus;
use App\Enums\CompanyUserRole;
use App\Enums\SenderIdStatus;
use App\Models\Company;
use App\Models\CompanyRate;
use App\Models\SenderId;
use App\Models\TaxRate;
use App\Models\User;
use App\Support\Money;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesCompanies;
use Tests\Concerns\RegistersVerifiedUsers;

uses(CreatesCompanies::class, RegistersVerifiedUsers::class);

it('converts money without floating point drift', function () {
    expect(Money::fromMajor('1.23'))->toBe(123)
        ->and(Money::toMajor(123))->toBe('1.23')
        ->and(Money::format(123))->toBe('GHS 1.23');
});

it('registers a user with a pending company as owner', function () {
    $response = $this->registerVerifiedUser([
        'name' => 'Ada Lovelace',
        'email' => 'ada@example.com',
        'company_name' => 'Analytical Engines Ltd',
        'company_phone' => '0244123456',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'ada@example.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->currentCompany)->not->toBeNull()
        ->and($user->currentCompany->name)->toBe('Analytical Engines Ltd')
        ->and($user->currentCompany->status)->toBe(CompanyStatus::Pending)
        ->and($user->companyRole())->toBe(CompanyUserRole::Owner)
        ->and($user->email_verified_at)->not->toBeNull();
});

it('prevents unapproved companies from passing the approval gate', function () {
    [$user] = $this->createCompanyOwner();

    $this->actingAs($user)
        ->get(route('campaigns.index'))
        ->assertForbidden();
});

it('allows approved companies through the approval gate', function () {
    [$user] = $this->createApprovedCompanyOwner();

    $this->actingAs($user)
        ->withoutVite()
        ->get(route('campaigns.index'))
        ->assertOk();
});

it('scopes sender ids to the current company', function () {
    [$ownerA, $companyA] = $this->createApprovedCompanyOwner();
    [$ownerB, $companyB] = $this->createApprovedCompanyOwner();

    $own = SenderId::factory()->create([
        'company_id' => $companyA->id,
        'requested_by' => $ownerA->id,
        'value' => 'AlphaOne',
    ]);

    SenderId::factory()->create([
        'company_id' => $companyB->id,
        'requested_by' => $ownerB->id,
        'value' => 'BetaTwo',
    ]);

    $this->actingAs($ownerA);

    $visible = SenderId::query()->pluck('value')->all();

    expect($visible)->toBe(['AlphaOne'])
        ->and(SenderId::query()->find($own->id))->not->toBeNull();
});

it('lets a company owner create a sender id with a private document', function () {
    Storage::fake(config('filesystems.default'));
    [$user] = $this->createApprovedCompanyOwner();

    $file = UploadedFile::fake()->create('letterhead.pdf', 100, 'application/pdf');

    $this->actingAs($user)
        ->post(route('sender-ids.store'), [
            'value' => 'AcmeGH',
            'document' => $file,
        ])
        ->assertRedirect(route('sender-ids.index'));

    $senderId = SenderId::query()->first();

    expect($senderId)->not->toBeNull()
        ->and($senderId->value)->toBe('AcmeGH')
        ->and($senderId->status)->toBe(SenderIdStatus::Pending)
        ->and($senderId->document_path)->not->toBeNull();

    Storage::disk(config('filesystems.default'))->assertExists($senderId->document_path);
});

it('rejects invalid sender id characters', function () {
    [$user] = $this->createApprovedCompanyOwner();

    $this->actingAs($user)
        ->post(route('sender-ids.store'), [
            'value' => 'Bad@Name!',
        ])
        ->assertSessionHasErrors('value');
});

it('allows editing a pending sender id', function () {
    [$user, $company] = $this->createApprovedCompanyOwner();

    $senderId = SenderId::factory()->create([
        'company_id' => $company->id,
        'requested_by' => $user->id,
        'value' => 'OldName',
        'status' => SenderIdStatus::Pending,
    ]);

    $this->actingAs($user)
        ->put(route('sender-ids.update', $senderId), [
            'value' => 'NewName',
        ])
        ->assertRedirect(route('sender-ids.index'));

    expect($senderId->fresh()->status)->toBe(SenderIdStatus::Pending)
        ->and($senderId->fresh()->value)->toBe('NewName');
});

it('forbids editing approved or rejected sender ids', function () {
    [$user, $company] = $this->createApprovedCompanyOwner();

    $approved = SenderId::factory()->approved()->create([
        'company_id' => $company->id,
        'requested_by' => $user->id,
        'value' => 'Approved',
    ]);

    $rejected = SenderId::factory()->create([
        'company_id' => $company->id,
        'requested_by' => $user->id,
        'value' => 'Rejected',
        'status' => SenderIdStatus::Rejected,
    ]);

    $this->actingAs($user)
        ->put(route('sender-ids.update', $approved), [
            'value' => 'Changed',
        ])
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('sender-ids.edit', $approved))
        ->assertForbidden();

    $this->actingAs($user)
        ->delete(route('sender-ids.destroy', $approved))
        ->assertForbidden();

    $this->actingAs($user)
        ->put(route('sender-ids.update', $rejected), [
            'value' => 'Changed',
        ])
        ->assertForbidden();

    expect($approved->fresh()->value)->toBe('Approved')
        ->and($rejected->fresh()->value)->toBe('Rejected');
});

it('allows an admin to approve a pending company', function () {
    $admin = $this->createPlatformAdmin();
    $company = Company::factory()->create(['status' => CompanyStatus::Pending]);

    $this->actingAs($admin)
        ->post(route('admin.companies.approve', $company))
        ->assertRedirect();

    expect($company->fresh()->status)->toBe(CompanyStatus::Approved)
        ->and($company->fresh()->approved_by)->toBe($admin->id);
});

it('allows an admin to approve a pending sender id', function () {
    $admin = $this->createPlatformAdmin();
    [$owner, $company] = $this->createApprovedCompanyOwner();

    $senderId = SenderId::factory()->create([
        'company_id' => $company->id,
        'requested_by' => $owner->id,
        'status' => SenderIdStatus::Pending,
    ]);

    $this->actingAs($admin)
        ->post(route('admin.sender-ids.approve', $senderId))
        ->assertRedirect();

    expect($senderId->fresh()->status)->toBe(SenderIdStatus::Approved);
});

it('lists sender ids across statuses with an optional status filter', function () {
    $admin = $this->createPlatformAdmin();
    [$owner, $company] = $this->createApprovedCompanyOwner();

    $pending = SenderId::factory()->create([
        'company_id' => $company->id,
        'requested_by' => $owner->id,
        'value' => 'PendingID',
        'status' => SenderIdStatus::Pending,
    ]);
    $approved = SenderId::factory()->create([
        'company_id' => $company->id,
        'requested_by' => $owner->id,
        'value' => 'ApprovedID',
        'status' => SenderIdStatus::Approved,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.sender-ids.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/sender-ids/Index')
            ->where('filters.status', null)
            ->has('senderIds.data', 2)
            ->has('statusOptions', 3));

    $this->actingAs($admin)
        ->get(route('admin.sender-ids.index', ['status' => 'approved']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('filters.status', 'approved')
            ->has('senderIds.data', 1)
            ->where('senderIds.data.0.id', $approved->id)
            ->where('senderIds.data.0.status', 'approved')
            ->where('senderIds.data.0.reviewer', null));

    expect($pending->fresh()->status)->toBe(SenderIdStatus::Pending);
});

it('resolves company-specific rates over the platform default', function () {
    $company = Company::factory()->approved()->create();

    CompanyRate::factory()->create([
        'company_id' => null,
        'rate_per_sms' => '0.050000',
        'effective_from' => now()->subDays(10)->toDateString(),
    ]);

    CompanyRate::factory()->forCompany($company->id)->create([
        'rate_per_sms' => '0.025000',
        'effective_from' => now()->subDay()->toDateString(),
    ]);

    $resolved = CompanyRate::resolveFor($company);

    expect($resolved)->not->toBeNull()
        ->and($resolved->rate_per_sms)->toBe('0.025000');
});

it('saves a platform default client rate without activity log FK errors', function () {
    $admin = $this->createPlatformAdmin();

    $this->actingAs($admin)
        ->post(route('admin.rates.store'), [
            'company_id' => null,
            'rate_per_sms' => '0.050000',
            'effective_from' => now()->toDateString(),
        ])
        ->assertRedirect();

    $rate = CompanyRate::query()->whereNull('company_id')->latest('id')->first();

    expect($rate)->not->toBeNull()
        ->and($rate->rate_per_sms)->toBe('0.050000');

    $this->assertDatabaseHas('activity_logs', [
        'action' => 'company_rate.created',
        'subject_id' => $rate->id,
        'company_id' => null,
    ]);
});

it('lists active tax rates by effective date', function () {
    TaxRate::factory()->create([
        'name' => 'Future tax',
        'rate' => '10.0000',
        'effective_from' => now()->addWeek()->toDateString(),
        'is_active' => true,
    ]);

    TaxRate::factory()->create([
        'name' => 'Current tax',
        'rate' => '5.0000',
        'effective_from' => now()->subDay()->toDateString(),
        'is_active' => true,
    ]);

    TaxRate::factory()->create([
        'name' => 'Inactive tax',
        'rate' => '2.0000',
        'effective_from' => now()->subDay()->toDateString(),
        'is_active' => false,
    ]);

    $active = TaxRate::activeOn();

    expect($active)->toHaveCount(1)
        ->and($active->first()->name)->toBe('Current tax');
});

it('forbids clients from accessing admin company routes', function () {
    [$user] = $this->createApprovedCompanyOwner();

    $this->actingAs($user)
        ->get(route('admin.companies.index'))
        ->assertForbidden();
});
