@extends('marketing.layout')

@section('title', config('marketing.brand').' — Managed SMS campaign requests')
@section('og_title', config('marketing.brand').' — Managed SMS campaign requests')
@section('meta_description', config('marketing.description'))

@push('head')
    @php
        $organization = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => config('marketing.brand'),
            'url' => url('/'),
            'description' => config('marketing.description'),
            'email' => config('marketing.contact.email'),
            'telephone' => config('marketing.contact.phone') ?: null,
        ], fn ($value) => $value !== null && $value !== '');
    @endphp
    <script type="application/ld+json">
        {!! json_encode($organization, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('body')
    <header class="relative z-20 border-b border-ink/10 bg-mist/90 backdrop-blur-sm">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
            <a
                href="{{ route('home') }}"
                class="inline-flex min-h-11 items-center gap-2.5 text-lg font-semibold tracking-tight text-ink"
            >
                <span class="flex size-8 items-center justify-center rounded-xl bg-signal text-white shadow-sm shadow-signal/30" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" fill="none" class="size-5">
                        <rect x="4" y="8" width="26" height="18" rx="5" fill="currentColor" opacity="0.92"/>
                        <path d="M12 15.5h10M12 19.5h7" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/>
                        <circle cx="32" cy="11" r="3.5" fill="currentColor" opacity="0.55"/>
                        <circle cx="32" cy="11" r="1.6" fill="#fff"/>
                    </svg>
                </span>
                {{ config('marketing.brand') }}
            </a>

            <nav class="hidden items-center gap-1 md:flex" aria-label="Primary">
                <a href="#how-it-works" class="inline-flex min-h-11 items-center px-3 text-sm font-medium text-ink/80 hover:text-ink">
                    How it works
                </a>
                <a href="#benefits" class="inline-flex min-h-11 items-center px-3 text-sm font-medium text-ink/80 hover:text-ink">
                    Why us
                </a>
                @auth
                    <a
                        href="{{ route('dashboard') }}"
                        class="ml-2 inline-flex min-h-11 items-center justify-center rounded-xl bg-ink px-4 text-sm font-medium text-white transition hover:bg-ink/90"
                    >
                        Dashboard
                    </a>
                @else
                    <a
                        href="{{ route('login') }}"
                        class="inline-flex min-h-11 items-center px-3 text-sm font-medium text-ink/80 hover:text-ink"
                    >
                        Log in
                    </a>
                    <a
                        href="{{ route('register') }}"
                        class="ml-1 inline-flex min-h-11 items-center justify-center rounded-xl bg-signal px-4 text-sm font-medium text-white shadow-sm shadow-signal/25 transition hover:brightness-110"
                    >
                        Create an account
                    </a>
                @endauth
            </nav>

            <details class="relative md:hidden">
                <summary
                    class="flex min-h-11 min-w-11 cursor-pointer list-none items-center justify-center rounded-md border border-ink/15 bg-white text-ink"
                    aria-label="Open menu"
                >
                    <span class="sr-only">Menu</span>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </summary>
                <div class="absolute right-0 mt-2 w-56 rounded-lg border border-ink/10 bg-white p-2 shadow-lg">
                    <a href="#how-it-works" class="flex min-h-11 items-center rounded-md px-3 text-sm font-medium text-ink hover:bg-mist">
                        How it works
                    </a>
                    <a href="#benefits" class="flex min-h-11 items-center rounded-md px-3 text-sm font-medium text-ink hover:bg-mist">
                        Why us
                    </a>
                    @auth
                        <a href="{{ route('dashboard') }}" class="mt-1 flex min-h-11 items-center rounded-md bg-ink px-3 text-sm font-medium text-white">
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="flex min-h-11 items-center rounded-md px-3 text-sm font-medium text-ink hover:bg-mist">
                            Log in
                        </a>
                        <a href="{{ route('register') }}" class="mt-1 flex min-h-11 items-center rounded-md bg-signal px-3 text-sm font-medium text-white">
                            Create an account
                        </a>
                    @endauth
                </div>
            </details>
        </div>
    </header>

    <main id="main">
        {{-- Hero: one composition — brand, headline, support, CTA, dominant visual --}}
        <section class="relative overflow-hidden marketing-hero-glow text-white" aria-labelledby="hero-heading">
            <div
                class="pointer-events-none absolute inset-0 opacity-40 animate-drift"
                aria-hidden="true"
            >
                <svg class="h-full w-full" viewBox="0 0 1200 800" preserveAspectRatio="xMidYMid slice" role="img">
                    <title>Abstract message network</title>
                    <defs>
                        <linearGradient id="wave" x1="0" y1="0" x2="1" y2="1">
                            <stop offset="0%" stop-color="#2dd4bf" stop-opacity="0.35"/>
                            <stop offset="100%" stop-color="#ffffff" stop-opacity="0"/>
                        </linearGradient>
                    </defs>
                    <path fill="url(#wave)" d="M0,420 C180,360 260,520 450,480 C640,440 720,300 900,340 C1050,372 1120,300 1200,280 L1200,800 L0,800 Z"/>
                    <g stroke="#99f6e4" stroke-opacity="0.35" fill="none" stroke-width="1.5">
                        <circle cx="920" cy="220" r="56"/>
                        <circle cx="920" cy="220" r="96"/>
                        <path d="M920 220 L760 340 M920 220 L1040 360 M920 220 L860 120"/>
                    </g>
                </svg>
            </div>

            <div class="relative mx-auto grid max-w-6xl gap-10 px-4 pb-16 pt-14 sm:px-6 sm:pb-20 sm:pt-20 lg:grid-cols-2 lg:items-center lg:gap-12 lg:px-8 lg:pb-24 lg:pt-24">
                <div class="max-w-xl">
                    <p class="animate-fade-up text-2xl font-semibold tracking-tight text-white sm:text-3xl lg:text-4xl">
                        {{ config('marketing.brand') }}
                    </p>
                    <h1
                        id="hero-heading"
                        class="animate-fade-up-delay mt-4 text-balance text-3xl font-semibold leading-tight tracking-tight sm:text-4xl lg:text-5xl"
                    >
                        {{ config('marketing.tagline') }}
                    </h1>
                    <p class="animate-fade-up-delay-2 mt-4 max-w-md text-pretty text-base leading-relaxed text-white/80 sm:text-lg">
                        Upload your list, get a clear quote and invoice, then we send the campaign for you — no self-service gateway to learn.
                    </p>
                    <div class="animate-fade-up-delay-2 mt-8 flex flex-wrap gap-3">
                        @auth
                            <a
                                href="{{ route('dashboard') }}"
                                class="inline-flex min-h-11 items-center justify-center rounded-md bg-white px-5 text-sm font-semibold text-ink hover:bg-white/90"
                            >
                                Go to dashboard
                            </a>
                        @else
                            <a
                                href="{{ route('register') }}"
                                class="inline-flex min-h-11 items-center justify-center rounded-md bg-signal px-5 text-sm font-semibold text-white shadow-sm hover:brightness-110"
                            >
                                Create an account
                            </a>
                            <a
                                href="{{ route('login') }}"
                                class="inline-flex min-h-11 items-center justify-center rounded-md border border-white/30 bg-white/5 px-5 text-sm font-semibold text-white hover:bg-white/10"
                            >
                                Log in
                            </a>
                        @endauth
                    </div>
                </div>

                <div class="relative mx-auto w-full max-w-md lg:max-w-none" aria-hidden="true">
                    <svg
                        class="h-auto w-full drop-shadow-sm"
                        viewBox="0 0 480 360"
                        role="img"
                        width="480"
                        height="360"
                    >
                        <title>SMS request workflow illustration</title>
                        <rect x="48" y="36" width="280" height="288" rx="28" fill="#0f2740" stroke="#5eead4" stroke-opacity="0.35" stroke-width="2"/>
                        <rect x="72" y="72" width="232" height="28" rx="6" fill="#1a3a55"/>
                        <text x="84" y="91" fill="#99f6e4" font-size="14" font-family="ui-monospace, monospace">From: AcmeGH</text>
                        <rect x="72" y="116" width="232" height="96" rx="8" fill="#143247"/>
                        <text x="84" y="142" fill="#e2e8f0" font-size="13" font-family="Instrument Sans, sans-serif">Your message goes here…</text>
                        <text x="84" y="166" fill="#94a3b8" font-size="12" font-family="Instrument Sans, sans-serif">18,432 recipients · cleaned</text>
                        <text x="84" y="190" fill="#5eead4" font-size="12" font-family="Instrument Sans, sans-serif">Quote ready · invoice next</text>
                        <circle cx="380" cy="120" r="46" fill="#0d9488" fill-opacity="0.9"/>
                        <path d="M362 120 h24 M386 108 v24" stroke="#ecfdf5" stroke-width="3" stroke-linecap="round"/>
                        <path d="M330 180 C350 200 360 230 368 260" stroke="#99f6e4" stroke-opacity="0.5" stroke-width="2" fill="none"/>
                        <circle cx="368" cy="268" r="10" fill="#99f6e4" fill-opacity="0.8"/>
                    </svg>
                </div>
            </div>
        </section>

        <section id="how-it-works" class="bg-surface scroll-mt-20" aria-labelledby="how-heading">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
                <h2 id="how-heading" class="text-2xl font-semibold tracking-tight text-ink sm:text-3xl">
                    How it works
                </h2>
                <p class="mt-3 max-w-2xl text-muted-foreground">
                    Four steps from request to send — our team handles the gateway work.
                </p>

                <ol class="mt-10 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    <li>
                        <p class="font-mono text-sm font-semibold text-signal">01</p>
                        <h3 class="mt-2 text-lg font-semibold text-ink">Submit your request</h3>
                        <p class="mt-2 text-sm leading-relaxed text-muted-foreground">
                            Compose your message, pick an approved sender ID, and upload your recipient list.
                        </p>
                    </li>
                    <li>
                        <p class="font-mono text-sm font-semibold text-signal">02</p>
                        <h3 class="mt-2 text-lg font-semibold text-ink">We clean and price</h3>
                        <p class="mt-2 text-sm leading-relaxed text-muted-foreground">
                            Invalid numbers and duplicates are removed. You see a clear cost before you commit.
                        </p>
                    </li>
                    <li>
                        <p class="font-mono text-sm font-semibold text-signal">03</p>
                        <h3 class="mt-2 text-lg font-semibold text-ink">Pay your invoice</h3>
                        <p class="mt-2 text-sm leading-relaxed text-muted-foreground">
                            Download the invoice, pay offline, and submit your payment reference for verification.
                        </p>
                    </li>
                    <li>
                        <p class="font-mono text-sm font-semibold text-signal">04</p>
                        <h3 class="mt-2 text-lg font-semibold text-ink">We send it for you</h3>
                        <p class="mt-2 text-sm leading-relaxed text-muted-foreground">
                            Our operators fulfil the campaign and mark it complete in your portal.
                        </p>
                    </li>
                </ol>
            </div>
        </section>

        <section id="benefits" class="scroll-mt-20 border-t border-border bg-mist" aria-labelledby="benefits-heading">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-20 lg:px-8">
                <h2 id="benefits-heading" class="text-2xl font-semibold tracking-tight text-ink sm:text-3xl">
                    Built around what actually matters
                </h2>
                <p class="mt-3 max-w-2xl text-muted-foreground">
                    Rates are negotiated per client — no public price list. The portal keeps the paperwork and lists tidy.
                </p>

                <ul class="mt-10 grid gap-10 sm:grid-cols-2">
                    <li>
                        <h3 class="text-lg font-semibold text-ink">Recipient list cleaning</h3>
                        <p class="mt-2 text-sm leading-relaxed text-muted-foreground">
                            Numbers are normalised and validated before quoting, so you are billed for reachable contacts — not junk rows.
                        </p>
                    </li>
                    <li>
                        <h3 class="text-lg font-semibold text-ink">A cost you see before you commit</h3>
                        <p class="mt-2 text-sm leading-relaxed text-muted-foreground">
                            Segment counting and your company rate produce a server-side quote. No surprises after upload.
                        </p>
                    </li>
                    <li>
                        <h3 class="text-lg font-semibold text-ink">Invoices and payments in one place</h3>
                        <p class="mt-2 text-sm leading-relaxed text-muted-foreground">
                            Download invoices, submit payment references, and keep history without chasing email threads.
                        </p>
                    </li>
                    <li>
                        <h3 class="text-lg font-semibold text-ink">Managed sender IDs</h3>
                        <p class="mt-2 text-sm leading-relaxed text-muted-foreground">
                            Register sender names once with supporting documents. Campaigns pick from approved IDs only.
                        </p>
                    </li>
                </ul>
            </div>
        </section>

        <section class="bg-ink text-white" aria-labelledby="cta-heading">
            <div class="mx-auto flex max-w-6xl flex-col items-start gap-6 px-4 py-16 sm:px-6 sm:py-20 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                <div class="max-w-xl">
                    <h2 id="cta-heading" class="text-2xl font-semibold tracking-tight sm:text-3xl">
                        Ready to submit your next campaign?
                    </h2>
                    <p class="mt-3 text-white/75">
                        Create an account to register your company and sender IDs. Campaign requests unlock after approval.
                    </p>
                </div>
                @guest
                    <a
                        href="{{ route('register') }}"
                        class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-md bg-signal px-6 text-sm font-semibold text-white hover:brightness-110"
                    >
                        Create an account
                    </a>
                @else
                    <a
                        href="{{ route('dashboard') }}"
                        class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-md bg-white px-6 text-sm font-semibold text-ink hover:bg-white/90"
                    >
                        Go to dashboard
                    </a>
                @endguest
            </div>
        </section>
    </main>

    <footer class="border-t border-border bg-surface" aria-label="Site footer">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:px-6 lg:grid-cols-[1.2fr_1fr_1fr] lg:px-8">
            <div>
                <p class="text-lg font-semibold text-ink">{{ config('marketing.brand') }}</p>
                <p class="mt-2 max-w-sm text-sm text-muted-foreground">
                    A managed SMS request portal. We send campaigns on your behalf — this site does not include a self-service send button.
                </p>
            </div>
            <div>
                <p class="text-sm font-semibold text-ink">Contact</p>
                <ul class="mt-3 space-y-2 text-sm text-muted-foreground">
                    <li>
                        <a class="min-h-11 inline-flex items-center hover:text-ink" href="mailto:{{ config('marketing.contact.email') }}">
                            {{ config('marketing.contact.email') }}
                        </a>
                    </li>
                    @if (filled(config('marketing.contact.phone')))
                        <li>
                            <a class="min-h-11 inline-flex items-center hover:text-ink" href="tel:{{ preg_replace('/\s+/', '', config('marketing.contact.phone')) }}">
                                {{ config('marketing.contact.phone') }}
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
            <div>
                <p class="text-sm font-semibold text-ink">Legal</p>
                <ul class="mt-3 space-y-2 text-sm text-muted-foreground">
                    <li>
                        <span class="inline-flex min-h-11 items-center text-muted-foreground/70" title="Coming soon">
                            Terms of Service
                        </span>
                    </li>
                    <li>
                        <span class="inline-flex min-h-11 items-center text-muted-foreground/70" title="Coming soon">
                            Privacy Policy
                        </span>
                    </li>
                </ul>
            </div>
        </div>
        <div class="border-t border-border">
            <p class="mx-auto max-w-6xl px-4 py-4 text-xs text-muted-foreground sm:px-6 lg:px-8">
                &copy; {{ now()->year }} {{ config('marketing.brand') }}. All rights reserved.
            </p>
        </div>
    </footer>
@endsection
