<?php

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Enums\PlatformRole;
use App\Enums\SmsRequestStatus;
use App\Jobs\GenerateInvoicePdfJob;
use App\Models\CompanyRate;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use App\Models\ProviderRate;
use App\Models\SmsRequest;
use App\Models\TaxRate;
use App\Support\PrivateStorage;
use App\Support\TaxCalculator;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\URL;
use Tests\Concerns\CreatesCompanies;

uses(CreatesCompanies::class);

beforeEach(function () {
    ProviderRate::factory()->create([
        'rate_per_sms' => '0.020000',
        'effective_from' => now()->subDay()->toDateString(),
    ]);
});

it('applies tax percentages to pesewa subtotals with half-up rounding', function () {
    TaxRate::factory()->create([
        'name' => 'Levy',
        'rate' => '5.0000',
        'is_active' => true,
        'effective_from' => now()->subDay(),
    ]);

    $result = TaxCalculator::apply(1000);

    expect($result['tax_pesewas'])->toBe(50)
        ->and($result['total_pesewas'])->toBe(1050)
        ->and($result['lines'][0]['name'])->toBe('Levy');
});

it('allocates gapless invoice numbers under lock', function () {
    CompanyRate::factory()->create(['rate_per_sms' => '0.030000']);
    [$user, $company] = $this->createApprovedCompanyOwner();
    $admin = $this->createPlatformAdmin();

    $first = SmsRequest::factory()->submitted()->create([
        'company_id' => $company->id,
        'created_by' => $user->id,
        'quoted_cost_pesewas' => 300,
        'billable_recipients' => 100,
        'pages' => 1,
        'rate_per_sms' => '0.030000',
        'status' => SmsRequestStatus::UnderReview,
    ]);
    $second = SmsRequest::factory()->submitted()->create([
        'company_id' => $company->id,
        'created_by' => $user->id,
        'quoted_cost_pesewas' => 300,
        'billable_recipients' => 100,
        'pages' => 1,
        'rate_per_sms' => '0.030000',
        'status' => SmsRequestStatus::UnderReview,
    ]);

    Queue::fake();

    $this->actingAs($admin)
        ->post(route('admin.campaigns.invoice', $first))
        ->assertRedirect();

    $this->actingAs($admin)
        ->post(route('admin.campaigns.invoice', $second))
        ->assertRedirect();

    $numbers = Invoice::query()->orderBy('id')->pluck('number')->all();
    $year = now()->format('Y');

    expect($numbers)->toBe([
        "INV-{$year}-000001",
        "INV-{$year}-000002",
    ]);
});

it('lets support request changes and reject with a reason', function () {
    [$user, $company] = $this->createApprovedCompanyOwner();
    $support = $this->createPlatformAdmin(PlatformRole::Support);

    $campaign = SmsRequest::factory()->submitted()->create([
        'company_id' => $company->id,
        'created_by' => $user->id,
    ]);

    $this->actingAs($support)
        ->post(route('admin.campaigns.request-changes', $campaign), [
            'reason' => 'Please shorten the message body.',
        ])
        ->assertRedirect(route('admin.campaigns.index'));

    expect($campaign->fresh()->status)->toBe(SmsRequestStatus::ChangesRequested)
        ->and($campaign->fresh()->changes_requested_reason)->toBe('Please shorten the message body.');

    $campaign2 = SmsRequest::factory()->submitted()->create([
        'company_id' => $company->id,
        'created_by' => $user->id,
    ]);

    $this->actingAs($support)
        ->post(route('admin.campaigns.reject', $campaign2), [
            'rejection_reason' => 'Content not permitted.',
        ])
        ->assertRedirect(route('admin.campaigns.index'));

    expect($campaign2->fresh()->status)->toBe(SmsRequestStatus::Rejected)
        ->and($campaign2->fresh()->rejection_reason)->toBe('Content not permitted.');
});

it('issues an invoice, accepts MoMo payment, and verifies it', function () {
    CompanyRate::factory()->create(['rate_per_sms' => '0.030000']);
    TaxRate::factory()->create([
        'name' => 'Service levy',
        'rate' => '0.0000',
        'is_active' => true,
        'effective_from' => now()->subDay(),
    ]);

    [$user, $company] = $this->createApprovedCompanyOwner();
    $admin = $this->createPlatformAdmin();
    $finance = $this->createPlatformAdmin(PlatformRole::Finance);

    $campaign = SmsRequest::factory()->submitted()->create([
        'company_id' => $company->id,
        'created_by' => $user->id,
        'quoted_cost_pesewas' => 300,
        'billable_recipients' => 100,
        'pages' => 1,
        'rate_per_sms' => '0.030000',
        'status' => SmsRequestStatus::Submitted,
    ]);

    Queue::fake();

    $this->actingAs($admin)
        ->post(route('admin.campaigns.start-review', $campaign))
        ->assertRedirect();

    expect($campaign->fresh()->status)->toBe(SmsRequestStatus::UnderReview);

    $this->actingAs($finance)
        ->post(route('admin.campaigns.invoice', $campaign))
        ->assertRedirect();

    $campaign->refresh();
    $invoice = $campaign->invoice;

    expect($campaign->status)->toBe(SmsRequestStatus::Invoiced)
        ->and($invoice)->not->toBeNull()
        ->and($invoice->status)->toBe(InvoiceStatus::Issued)
        ->and($invoice->total_pesewas)->toBe(300);

    Queue::assertPushed(GenerateInvoicePdfJob::class);

    $this->actingAs($user)
        ->post(route('invoices.payments.store', $invoice), [
            'amount' => '3.00',
            'momo_reference' => 'MOMO12345678',
            'payer_number' => '233244304528',
        ])
        ->assertRedirect(route('invoices.show', $invoice));

    $payment = Payment::query()->where('invoice_id', $invoice->id)->first();

    expect($payment)->not->toBeNull()
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and(PaymentTransaction::query()->where('payment_id', $payment->id)->count())->toBe(1);

    $this->actingAs($finance)
        ->post(route('admin.payments.verify', $payment))
        ->assertRedirect();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Verified)
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::Paid)
        ->and($campaign->fresh()->status)->toBe(SmsRequestStatus::Paid)
        ->and(PaymentTransaction::query()->where('payment_id', $payment->id)->count())->toBe(2);
});

it('forbids support from issuing invoices', function () {
    [$user, $company] = $this->createApprovedCompanyOwner();
    $support = $this->createPlatformAdmin(PlatformRole::Support);

    $campaign = SmsRequest::factory()->submitted()->create([
        'company_id' => $company->id,
        'created_by' => $user->id,
        'status' => SmsRequestStatus::UnderReview,
    ]);

    $this->actingAs($support)
        ->post(route('admin.campaigns.invoice', $campaign))
        ->assertForbidden();
});

it('rejects a pending payment with a reason and leaves the invoice open', function () {
    [$user, $company] = $this->createApprovedCompanyOwner();
    $finance = $this->createPlatformAdmin(PlatformRole::Finance);

    $campaign = SmsRequest::factory()->submitted()->create([
        'company_id' => $company->id,
        'created_by' => $user->id,
        'status' => SmsRequestStatus::Invoiced,
    ]);

    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'sms_request_id' => $campaign->id,
        'total_pesewas' => 300,
        'subtotal_pesewas' => 300,
        'issued_by' => $finance->id,
    ]);

    $payment = Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'company_id' => $company->id,
        'amount_pesewas' => 300,
        'submitted_by' => $user->id,
    ]);

    $this->actingAs($finance)
        ->post(route('admin.payments.reject', $payment), [
            'rejection_reason' => 'Reference not found.',
        ])
        ->assertRedirect();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Rejected)
        ->and($invoice->fresh()->status)->toBe(InvoiceStatus::Issued)
        ->and($campaign->fresh()->status)->toBe(SmsRequestStatus::Invoiced);
});

it('generates and downloads an invoice pdf on demand when missing', function () {
    [$user, $company] = $this->createApprovedCompanyOwner();

    $campaign = SmsRequest::factory()->submitted()->create([
        'company_id' => $company->id,
        'created_by' => $user->id,
        'status' => SmsRequestStatus::Invoiced,
    ]);

    $invoice = Invoice::factory()->create([
        'company_id' => $company->id,
        'sms_request_id' => $campaign->id,
        'pdf_path' => null,
    ]);

    InvoiceItem::factory()->create([
        'invoice_id' => $invoice->id,
    ]);

    $url = URL::temporarySignedRoute(
        'invoices.pdf',
        now()->addMinutes(30),
        ['invoice' => $invoice->id],
    );

    $this->actingAs($user)
        ->get($url)
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');

    $invoice->refresh();

    expect($invoice->pdf_path)->not->toBeNull()
        ->and(PrivateStorage::disk()->exists($invoice->pdf_path))->toBeTrue();
});
