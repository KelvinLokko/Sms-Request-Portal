<?php

use App\Http\Controllers\Api\V1\Payments\PaystackWebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('payments/webhook/paystack', PaystackWebhookController::class)
        ->name('api.payments.webhook.paystack');
});
