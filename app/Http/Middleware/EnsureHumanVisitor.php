<?php

namespace App\Http\Middleware;

use App\Services\Turnstile;
use App\Support\Honeypot;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fortify login pipeline step — honeypot + Turnstile before credentials are checked.
 */
class EnsureHumanVisitor
{
    public function __construct(private Turnstile $turnstile) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): mixed
    {
        Honeypot::assertEmpty($request->input(Honeypot::FIELD));
        $this->turnstile->assertValid(
            $request->input('cf-turnstile-response'),
            $request->ip(),
        );

        return $next($request);
    }
}
