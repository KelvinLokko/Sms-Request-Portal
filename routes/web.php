<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\CampaignReviewController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\CompanyRateController;
use App\Http\Controllers\Admin\FulfilmentController;
use App\Http\Controllers\Admin\PaymentReviewController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SenderIdReviewController;
use App\Http\Controllers\Admin\TaxRateController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\RegistrationOtpController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\Marketing\HomeController;
use App\Http\Controllers\SenderIdController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/robots.txt', [HomeController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [HomeController::class, 'sitemap'])->name('sitemap');

Route::middleware(['guest', 'throttle:10,1'])->group(function () {
    Route::post('/register/otp/send', [RegistrationOtpController::class, 'send'])
        ->name('register.otp.send');
    Route::post('/register/otp/verify', [RegistrationOtpController::class, 'verify'])
        ->name('register.otp.verify');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('sender-ids', SenderIdController::class)
        ->except(['show']);

    Route::get('sender-ids/{sender_id}/document', [SenderIdController::class, 'downloadDocument'])
        ->middleware(['signed', 'throttle:downloads'])
        ->name('sender-ids.document');

    Route::post('campaigns/estimate', [CampaignController::class, 'estimate'])
        ->name('campaigns.estimate');
    Route::get('campaigns/templates/{type}', [CampaignController::class, 'downloadTemplate'])
        ->whereIn('type', ['bulk', 'personalised-bulk'])
        ->middleware('throttle:downloads')
        ->name('campaigns.templates.download');

    Route::middleware(['company.approved'])->group(function () {
        Route::resource('campaigns', CampaignController::class)->except(['destroy']);
        Route::post('campaigns/{campaign}/submit', [CampaignController::class, 'submit'])
            ->name('campaigns.submit');
        Route::post('campaigns/{campaign}/cancel', [CampaignController::class, 'cancel'])
            ->name('campaigns.cancel');
        Route::post('campaigns/{campaign}/recipients', [CampaignController::class, 'uploadRecipients'])
            ->middleware('throttle:uploads')
            ->name('campaigns.recipients.upload');
        Route::get('campaigns/{campaign}/rejected-recipients', [CampaignController::class, 'downloadRejected'])
            ->middleware('throttle:downloads')
            ->name('campaigns.recipients.rejected');
    });

    Route::get('invoices', [InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])
        ->middleware(['signed', 'throttle:downloads'])
        ->name('invoices.pdf');
    Route::post('invoices/{invoice}/payments', [InvoiceController::class, 'storePayment'])
        ->middleware(['company.approved', 'throttle:uploads'])
        ->name('invoices.payments.store');

    Route::middleware(['permission:admin.access'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('campaigns', [CampaignReviewController::class, 'index'])->name('campaigns.index');
            Route::get('campaigns/{campaign}', [CampaignReviewController::class, 'show'])->name('campaigns.show');
            Route::get('analytics', AnalyticsController::class)->name('analytics.index');

            Route::middleware(['permission:activity.view'])->group(function () {
                Route::get('activity', [ActivityLogController::class, 'index'])->name('activity.index');
            });

            Route::middleware(['permission:users.manage'])->group(function () {
                Route::get('users', [UserController::class, 'index'])->name('users.index');
                Route::post('users', [UserController::class, 'store'])->name('users.store');
                Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
                Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
            });

            Route::middleware(['permission:roles.manage'])->group(function () {
                Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
                Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
                Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
                Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
            });

            Route::middleware(['permission:companies.view|companies.manage'])->group(function () {
                Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
            });

            Route::middleware(['permission:companies.manage'])->group(function () {
                Route::post('companies/{company}/approve', [CompanyController::class, 'approve'])
                    ->name('companies.approve');
                Route::post('companies/{company}/reject', [CompanyController::class, 'reject'])
                    ->name('companies.reject');
                Route::post('companies/{company}/suspend', [CompanyController::class, 'suspend'])
                    ->name('companies.suspend');
            });

            Route::middleware(['permission:sender-ids.review'])->group(function () {
                Route::get('sender-ids', [SenderIdReviewController::class, 'index'])->name('sender-ids.index');
                Route::post('sender-ids/{sender_id}/approve', [SenderIdReviewController::class, 'approve'])
                    ->name('sender-ids.approve');
                Route::post('sender-ids/{sender_id}/reject', [SenderIdReviewController::class, 'reject'])
                    ->name('sender-ids.reject');
            });

            Route::middleware(['permission:campaigns.review'])->group(function () {
                Route::post('campaigns/{campaign}/start-review', [CampaignReviewController::class, 'startReview'])
                    ->name('campaigns.start-review');
                Route::post('campaigns/{campaign}/request-changes', [CampaignReviewController::class, 'requestChanges'])
                    ->name('campaigns.request-changes');
                Route::post('campaigns/{campaign}/reject', [CampaignReviewController::class, 'reject'])
                    ->name('campaigns.reject');
            });

            Route::middleware(['permission:campaigns.fulfil'])->group(function () {
                Route::get('fulfilment', [FulfilmentController::class, 'index'])->name('fulfilment.index');
                Route::get('fulfilment/{campaign}', [FulfilmentController::class, 'show'])->name('fulfilment.show');
                Route::post('fulfilment/{campaign}/fulfil', [FulfilmentController::class, 'markFulfilled'])
                    ->name('fulfilment.fulfil');
                Route::get('fulfilment/{campaign}/recipients', [FulfilmentController::class, 'downloadRecipients'])
                    ->middleware(['signed', 'throttle:downloads'])
                    ->name('fulfilment.recipients');
            });

            Route::middleware(['permission:rates.manage'])->group(function () {
                Route::get('rates', [CompanyRateController::class, 'index'])->name('rates.index');
                Route::post('rates', [CompanyRateController::class, 'store'])->name('rates.store');
                Route::post('rates/provider', [CompanyRateController::class, 'storeProvider'])
                    ->name('rates.provider.store');
            });

            Route::middleware(['permission:tax-rates.manage'])->group(function () {
                Route::get('tax-rates', [TaxRateController::class, 'index'])->name('tax-rates.index');
                Route::post('tax-rates', [TaxRateController::class, 'store'])->name('tax-rates.store');
                Route::put('tax-rates/{tax_rate}', [TaxRateController::class, 'update'])->name('tax-rates.update');
            });

            Route::middleware(['permission:campaigns.invoice'])->group(function () {
                Route::post('campaigns/{campaign}/invoice', [CampaignReviewController::class, 'issueInvoice'])
                    ->name('campaigns.invoice');
            });

            Route::middleware(['permission:payments.manage'])->group(function () {
                Route::get('payments/report', [PaymentReviewController::class, 'report'])
                    ->name('payments.report');
                Route::get('payments', [PaymentReviewController::class, 'index'])->name('payments.index');
                Route::post('payments/{payment}/verify', [PaymentReviewController::class, 'verify'])
                    ->name('payments.verify');
                Route::post('payments/{payment}/reject', [PaymentReviewController::class, 'reject'])
                    ->name('payments.reject');
                Route::get('payments/{payment}/proof', [PaymentReviewController::class, 'downloadProof'])
                    ->middleware(['signed', 'throttle:downloads'])
                    ->name('payments.proof');
            });
        });
});

require __DIR__.'/settings.php';
