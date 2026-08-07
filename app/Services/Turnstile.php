<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

class Turnstile
{
    public function enabled(): bool
    {
        return filled(config('services.turnstile.site_key'))
            && filled(config('services.turnstile.secret'));
    }

    public function siteKey(): ?string
    {
        $key = config('services.turnstile.site_key');

        return filled($key) ? (string) $key : null;
    }

    /**
     * @throws ValidationException
     */
    public function assertValid(?string $token, ?string $ip = null): void
    {
        if (! $this->enabled()) {
            return;
        }

        if (! filled($token)) {
            throw ValidationException::withMessages([
                'cf-turnstile-response' => 'Please complete the security check and try again.',
            ]);
        }

        $response = Http::asForm()
            ->timeout(8)
            ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                'secret' => config('services.turnstile.secret'),
                'response' => $token,
                'remoteip' => $ip,
            ]);

        if (! $response->successful() || ! $response->json('success')) {
            throw ValidationException::withMessages([
                'cf-turnstile-response' => 'Security check failed. Please try again.',
            ]);
        }
    }
}
