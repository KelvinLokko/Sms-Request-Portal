<?php

namespace App\Http\Controllers\Api\V1\Payments;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Payments\PaystackCheckout;
use App\Services\Payments\PaystackClient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PaystackWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        PaystackClient $paystack,
        PaystackCheckout $checkout,
    ): Response {
        $payload = $request->getContent();

        if (! $paystack->validSignature($payload, $request->header('x-paystack-signature'))) {
            Log::warning('paystack.webhook.invalid_signature');

            return response('Invalid signature', 400);
        }

        /** @var array<string, mixed> $body */
        $body = $request->json()->all();
        $event = (string) ($body['event'] ?? '');

        if ($event !== 'charge.success') {
            return response('Ignored', 200);
        }

        $data = $body['data'] ?? null;
        if (! is_array($data)) {
            return response('Bad payload', 400);
        }

        $reference = (string) ($data['reference'] ?? '');
        if ($reference === '') {
            return response('Missing reference', 400);
        }

        $payment = Payment::query()
            ->where('provider', 'paystack')
            ->where('provider_reference', $reference)
            ->first();

        if ($payment === null) {
            Log::warning('paystack.webhook.unknown_reference', ['reference' => $reference]);

            return response('Unknown reference', 404);
        }

        try {
            $checkout->settleFromPaystackPayload($payment, $data);
        } catch (ValidationException $e) {
            Log::warning('paystack.webhook.rejected', [
                'reference' => $reference,
                'errors' => $e->errors(),
            ]);

            return response('Rejected', 422);
        }

        return response('OK', 200);
    }
}
