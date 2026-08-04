<?php

use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AnalyticsController;
use App\Http\Controllers\Admin\CampaignReviewController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\CompanyRateController;
use App\Http\Controllers\Admin\FulfilmentController;
use App\Http\Controllers\Admin\PaymentReviewController;
use App\Http\Controllers\Admin\SenderIdReviewController;
use App\Http\Controllers\Admin\TaxRateController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\Marketing\HomeController;
use App\Http\Controllers\SenderIdController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/robots.txt', [HomeController::class, 'robots'])->name('robots');
Route::get('/sitemap.xml', [HomeController::class, 'sitemap'])->name('sitemap');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::resource('sender-ids', SenderIdController::class)
        ->except(['show']);

    Route::get('sender-ids/{sender_id}/document', [SenderIdController::class, 'downloadDocument'])
        ->middleware(['signed', 'throttle:downloads'])
        ->name('sender-ids.document');

    Route::post('campaigns/estimate', [CampaignController::class, 'estimate'])
        ->name('campaigns.estimate');

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

    Route::middleware(['role:super-admin|admin|support|finance'])
        ->prefix('admin')
        ->name('admin.')
        ->group(function () {
            Route::get('campaigns', [CampaignReviewController::class, 'index'])->name('campaigns.index');
            Route::get('campaigns/{campaign}', [CampaignReviewController::class, 'show'])->name('campaigns.show');
            Route::get('analytics', AnalyticsController::class)->name('analytics.index');

            Route::middleware(['role:super-admin|admin'])->group(function () {
                Route::get('activity', [ActivityLogController::class, 'index'])->name('activity.index');
            });

            Route::middleware(['role:super-admin|admin|support'])->group(function () {
                Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
                Route::post('companies/{company}/approve', [CompanyController::class, 'approve'])
                    ->middleware('role:super-admin|admin')
                    ->name('companies.approve');
                Route::post('companies/{company}/reject', [CompanyController::class, 'reject'])
                    ->middleware('role:super-admin|admin')
                    ->name('companies.reject');
                Route::post('companies/{company}/suspend', [CompanyController::class, 'suspend'])
                    ->middleware('role:super-admin|admin')
                    ->name('companies.suspend');

                Route::get('sender-ids', [SenderIdReviewController::class, 'index'])->name('sender-ids.index');
                Route::post('sender-ids/{sender_id}/approve', [SenderIdReviewController::class, 'approve'])
                    ->name('sender-ids.approve');
                Route::post('sender-ids/{sender_id}/reject', [SenderIdReviewController::class, 'reject'])
                    ->name('sender-ids.reject');

                Route::post('campaigns/{campaign}/start-review', [CampaignReviewController::class, 'startReview'])
                    ->name('campaigns.start-review');
                Route::post('campaigns/{campaign}/request-changes', [CampaignReviewController::class, 'requestChanges'])
                    ->name('campaigns.request-changes');
                Route::post('campaigns/{campaign}/reject', [CampaignReviewController::class, 'reject'])
                    ->name('campaigns.reject');

                Route::get('fulfilment', [FulfilmentController::class, 'index'])->name('fulfilment.index');
                Route::get('fulfilment/{campaign}', [FulfilmentController::class, 'show'])->name('fulfilment.show');
                Route::post('fulfilment/{campaign}/fulfil', [FulfilmentController::class, 'markFulfilled'])
                    ->name('fulfilment.fulfil');
                Route::get('fulfilment/{campaign}/recipients', [FulfilmentController::class, 'downloadRecipients'])
                    ->middleware(['signed', 'throttle:downloads'])
                    ->name('fulfilment.recipients');
            });

            Route::middleware(['role:super-admin|admin|finance'])->group(function () {
                Route::get('rates', [CompanyRateController::class, 'index'])->name('rates.index');
                Route::post('rates', [CompanyRateController::class, 'store'])->name('rates.store');

                Route::get('tax-rates', [TaxRateController::class, 'index'])->name('tax-rates.index');
                Route::post('tax-rates', [TaxRateController::class, 'store'])->name('tax-rates.store');
                Route::put('tax-rates/{tax_rate}', [TaxRateController::class, 'update'])->name('tax-rates.update');

                Route::post('campaigns/{campaign}/invoice', [CampaignReviewController::class, 'issueInvoice'])
                    ->name('campaigns.invoice');

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
