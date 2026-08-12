<?php

namespace App\Services\Payments;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class PaystackClient
{
    public function enabled(): bool
    {
        return filled(config('services.paystack.secret_key'));
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array{authorization_url: string, access_code: string, reference: string}
     */
    public function initialize(
        string $email,
        int $amountPesewas,
        string $reference,
        string $callbackUrl,
        array $metadata = [],
    ): array {
        $response = $this->request()->post('/transaction/initialize', [
            'email' => $email,
            'amount' => $amountPesewas,
            'currency' => 'GHS',
            'reference' => $reference,
            'callback_url' => $callbackUrl,
            'metadata' => $metadata,
        ]);

        if ($response->failed()) {
            $message = (string) ($response->json('message') ?: 'Paystack checkout failed.');

            throw new RuntimeException($message, $response->status());
        }

        $data = $response->json('data');

        if (! is_array($data) || blank($data['authorization_url'] ?? null)) {
            throw new RuntimeException('Paystack did not return a checkout URL.');
        }

        return [
            'authorization_url' => (string) $data['authorization_url'],
            'access_code' => (string) ($data['access_code'] ?? ''),
            'reference' => (string) ($data['reference'] ?? $reference),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function verify(string $reference): array
    {
        try {
            $response = $this->request()
                ->get('/transaction/verify/'.rawurlencode($reference))
                ->throw();
        } catch (RequestException $e) {
            throw new RuntimeException('Unable to verify Paystack transaction.', 0, $e);
        }

        $data = $response->json('data');

        if (! is_array($data)) {
            throw new RuntimeException('Paystack verification returned an unexpected payload.');
        }

        return $data;
    }

    public function validSignature(string $payload, ?string $signature): bool
    {
        if ($signature === null || $signature === '') {
            return false;
        }

        $secret = (string) config('services.paystack.secret_key');
        $expected = hash_hmac('sha512', $payload, $secret);

        return hash_equals($expected, $signature);
    }

    private function request(): PendingRequest
    {
        if (! $this->enabled()) {
            throw new RuntimeException('Paystack is not configured.');
        }

        return Http::baseUrl(rtrim((string) config('services.paystack.base_url'), '/'))
            ->withToken((string) config('services.paystack.secret_key'))
            ->acceptJson()
            ->asJson()
            ->timeout(30);
    }
}
