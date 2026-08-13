<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        // Keep crawler documents (sitemap/robots) free of HTML CSP so Google
        // does not treat an error/challenge page as the sitemap itself.
        if (! $request->is('sitemap.xml', 'robots.txt')) {
            $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy());
        }

        if (app()->isProduction()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains',
            );
        }

        return $response;
    }

    /**
     * Build a CSP suitable for Laravel + Inertia + Vite.
     */
    protected function contentSecurityPolicy(): string
    {
        $scriptSrc = [
            "'self'",
            "'unsafe-inline'",
            'https://challenges.cloudflare.com',
            'https://static.cloudflareinsights.com',
        ];
        $connectSrc = [
            "'self'",
            'https://challenges.cloudflare.com',
            'https://cloudflareinsights.com',
            'https://*.cloudflareinsights.com',
        ];
        $frameSrc = ['https://challenges.cloudflare.com'];

        if (! app()->isProduction()) {
            // Vite HMR / dev server needs eval and websocket connects.
            $scriptSrc[] = "'unsafe-eval'";
            $connectSrc[] = 'ws:';
            $connectSrc[] = 'wss:';
            $connectSrc[] = 'http://localhost:5173';
            $connectSrc[] = 'ws://localhost:5173';
        }

        $directives = [
            "default-src 'self'",
            'script-src '.implode(' ', $scriptSrc),
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            'connect-src '.implode(' ', $connectSrc),
            'frame-src '.implode(' ', $frameSrc),
            "frame-ancestors 'none'",
            "base-uri 'self'",
            // Allow Paystack hosted checkout redirects from native form posts.
            "form-action 'self' https://checkout.paystack.com https://*.paystack.co",
        ];

        return implode('; ', $directives);
    }
}
