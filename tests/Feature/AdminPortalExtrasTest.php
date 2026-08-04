<?php

use App\Enums\CompanyUserRole;
use App\Enums\PaymentStatus;
use App\Enums\PlatformPermission;
use App\Enums\PlatformRole;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Tests\Concerns\CreatesCompanies;

uses(CreatesCompanies::class);

beforeEach(function () {
    $this->withoutVite();
});

it('lets admins open user management', function () {
    $admin = $this->createPlatformAdmin();

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/users/Index'));
});

it('forbids finance from managing users', function () {
    $finance = $this->createPlatformAdmin(PlatformRole::Finance);

    $this->actingAs($finance)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

it('creates a staff user with a platform role', function () {
    $admin = $this->createPlatformAdmin();

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'type' => 'staff',
            'name' => 'Finance Officer',
            'email' => 'finance.officer@example.com',
            'password' => 'SmsPortal-Test-Pass1!',
            'password_confirmation' => 'SmsPortal-Test-Pass1!',
            'platform_role' => PlatformRole::Finance->value,
        ])
        ->assertRedirect();

    $created = User::query()->where('email', 'finance.officer@example.com')->first();

    expect($created)->not->toBeNull()
        ->and($created->hasRole(PlatformRole::Finance))->toBeTrue()
        ->and($created->companies)->toHaveCount(0);
});

it('creates a client user attached to a company', function () {
    $admin = $this->createPlatformAdmin();
    [, $company] = $this->createApprovedCompanyOwner();

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'type' => 'client',
            'name' => 'Billing Contact',
            'email' => 'billing@client.example',
            'password' => 'SmsPortal-Test-Pass1!',
            'password_confirmation' => 'SmsPortal-Test-Pass1!',
            'company_id' => $company->id,
            'company_role' => CompanyUserRole::Billing->value,
        ])
        ->assertRedirect();

    $created = User::query()->where('email', 'billing@client.example')->first();

    expect($created)->not->toBeNull()
        ->and($created->getRoleNames()->all())->toBe([])
        ->and($created->current_company_id)->toBe($company->id)
        ->and($created->companyRole())->toBe(CompanyUserRole::Billing);
});

it('prevents admins from deleting themselves via user management', function () {
    $admin = $this->createPlatformAdmin();

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $admin))
        ->assertForbidden();

    expect($admin->fresh())->not->toBeNull();
});

it('lets admins delete other users', function () {
    $admin = $this->createPlatformAdmin();
    $other = User::factory()->create();

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $other))
        ->assertRedirect();

    expect($other->fresh())->toBeNull();
});

it('shows payment history for finance including verified and rejected rows', function () {
    [$user, $company] = $this->createApprovedCompanyOwner();
    $finance = $this->createPlatformAdmin(PlatformRole::Finance);

    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'total_pesewas' => 300,
        'subtotal_pesewas' => 300,
        'issued_by' => $finance->id,
    ]);

    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'company_id' => $company->id,
        'amount_pesewas' => 300,
        'submitted_by' => $user->id,
        'status' => PaymentStatus::Pending,
        'momo_reference' => 'PENDINGREF01',
    ]);

    Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'company_id' => $company->id,
        'amount_pesewas' => 300,
        'submitted_by' => $user->id,
        'status' => PaymentStatus::Verified,
        'momo_reference' => 'VERIFIEDREF01',
        'verified_by' => $finance->id,
        'verified_at' => now(),
    ]);

    $this->actingAs($finance)
        ->get(route('admin.payments.report'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/payments/Report')
            ->has('payments.data', 2));

    $this->actingAs($finance)
        ->get(route('admin.payments.report', ['status' => PaymentStatus::Verified->value]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/payments/Report')
            ->has('payments.data', 1)
            ->where('payments.data.0.momo_reference', 'VERIFIEDREF01'));
});

it('lets admins open role management', function () {
    $admin = $this->createPlatformAdmin();

    $this->actingAs($admin)
        ->get(route('admin.roles.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/roles/Index')
            ->has('roles')
            ->has('permissionCatalog')
            ->has('roles.0.permissions')
            ->has('roles.0.permission_labels'));
});

it('creates a custom role with selected permissions', function () {
    $admin = $this->createPlatformAdmin();

    $this->actingAs($admin)
        ->post(route('admin.roles.store'), [
            'name' => 'ops-lead',
            'permissions' => [
                PlatformPermission::AdminAccess->value,
                PlatformPermission::CampaignsReview->value,
                PlatformPermission::CampaignsFulfil->value,
            ],
        ])
        ->assertRedirect();

    $custom = Role::query()->where('name', 'ops-lead')->where('guard_name', 'web')->first();

    expect($custom)->not->toBeNull()
        ->and($custom->permissions->pluck('name')->sort()->values()->all())
        ->toBe([
            PlatformPermission::AdminAccess->value,
            PlatformPermission::CampaignsFulfil->value,
            PlatformPermission::CampaignsReview->value,
        ]);

    $system = Role::query()->where('name', PlatformRole::Admin->value)->first();

    $this->actingAs($admin)
        ->delete(route('admin.roles.destroy', $system))
        ->assertForbidden();

    $this->actingAs($admin)
        ->delete(route('admin.roles.destroy', $custom))
        ->assertRedirect();

    expect(Role::query()->where('name', 'ops-lead')->exists())->toBeFalse();
});

it('updates permissions on a system role without renaming it', function () {
    $admin = $this->createPlatformAdmin(PlatformRole::SuperAdmin);
    $support = Role::query()->where('name', PlatformRole::Support->value)->firstOrFail();

    $this->actingAs($admin)
        ->put(route('admin.roles.update', $support), [
            'permissions' => [
                PlatformPermission::AdminAccess->value,
                PlatformPermission::CompaniesView->value,
            ],
        ])
        ->assertRedirect();

    expect($support->fresh()->permissions->pluck('name')->sort()->values()->all())
        ->toBe([
            PlatformPermission::AdminAccess->value,
            PlatformPermission::CompaniesView->value,
        ])
        ->and($support->fresh()->name)->toBe(PlatformRole::Support->value);
});

it('forbids deleting a custom role that still has users', function () {
    $admin = $this->createPlatformAdmin();

    $this->actingAs($admin)
        ->post(route('admin.roles.store'), [
            'name' => 'ops-lead',
            'permissions' => [PlatformPermission::AdminAccess->value],
        ])
        ->assertRedirect();

    $user = User::factory()->create();
    $user->assignRole('ops-lead');

    $role = Role::query()->where('name', 'ops-lead')->firstOrFail();

    $this->actingAs($admin)
        ->from(route('admin.roles.index'))
        ->delete(route('admin.roles.destroy', $role))
        ->assertRedirect(route('admin.roles.index'))
        ->assertSessionHas('error');

    expect($role->fresh())->not->toBeNull();
});
