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
    @php
        $navLinks = [
            ['href' => '#how-it-works', 'label' => 'How it works'],
            ['href' => '#services', 'label' => 'Services'],
            ['href' => '#benefits', 'label' => 'Why us'],
            ['href' => '#faq', 'label' => 'FAQ'],
        ];

        $capabilities = [
            [
                'title' => 'Bulk campaigns',
                'body' => 'One message, one approved sender ID, every contact on your list.',
                'icon' => 'M4 6h16M4 12h16M4 18h10',
            ],
            [
                'title' => 'Personalised bulk',
                'body' => 'Merge names and codes from your spreadsheet into each message.',
                'icon' => 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8Zm-7 8a7 7 0 0 1 14 0',
            ],
            [
                'title' => 'Managed sender IDs',
                'body' => 'Register a sender name once with documents, reuse it on every send.',
                'icon' => 'm12 3 7.5 4v5c0 4.5-3 8.3-7.5 9.5C7.5 20.3 4.5 16.5 4.5 12V7L12 3Z',
            ],
        ];

        // Edit these figures to match your own reporting.
        $stats = [
            ['value' => 12, 'suffix' => 'M+', 'label' => 'Messages delivered for clients to date.'],
            ['value' => 98.4, 'decimals' => 1, 'suffix' => '%', 'label' => 'Average carrier delivery rate on cleaned lists.'],
            ['value' => 2, 'suffix' => 'h', 'label' => 'Median time from request to a priced quote.'],
            ['value' => 320, 'suffix' => '+', 'label' => 'Campaigns fulfilled by our operations team.'],
        ];

        $services = [
            [
                'title' => 'Campaign request handling',
                'body' => 'Submit the brief once. We validate the list, count segments, and return a firm cost before anything is sent.',
                'image' => 'images/marketing/service-campaigns.jpg',
                'alt' => 'An operations specialist reviewing a campaign request on screen',
            ],
            [
                'title' => 'List cleaning & validation',
                'body' => 'Malformed numbers, duplicates, and unreachable rows are stripped out so you only pay for contacts we can deliver to.',
                'image' => 'images/marketing/service-lists.jpg',
                'alt' => 'A team reviewing recipient data on a laptop',
            ],
            [
                'title' => 'Invoicing & fulfilment',
                'body' => 'Download the invoice, submit your payment reference, and track the campaign through to a completed send.',
                'image' => 'images/marketing/service-billing.jpg',
                'alt' => 'A finance colleague reconciling invoices at a desk',
            ],
        ];

        $steps = [
            [
                'title' => 'Submit your request',
                'body' => 'Compose your message, pick an approved sender ID, and upload your recipient list.',
            ],
            [
                'title' => 'We clean and price',
                'body' => 'Invalid numbers and duplicates are removed. You see a clear cost before you commit.',
            ],
            [
                'title' => 'Pay your invoice',
                'body' => 'Download the invoice, pay offline, and submit your payment reference for verification.',
            ],
            [
                'title' => 'We send it for you',
                'body' => 'Our operators fulfil the campaign and mark it complete in your portal.',
            ],
        ];

        $benefits = [
            [
                'title' => 'Recipient list cleaning',
                'body' => 'Numbers are normalised and validated before quoting, so you are billed for reachable contacts — not junk rows.',
            ],
            [
                'title' => 'A cost you see before you commit',
                'body' => 'Segment counting and your company rate produce a server-side quote. No surprises after upload.',
            ],
            [
                'title' => 'Invoices and payments in one place',
                'body' => 'Download invoices, submit payment references, and keep history without chasing email threads.',
            ],
            [
                'title' => 'Managed sender IDs',
                'body' => 'Register sender names once with supporting documents. Campaigns pick from approved IDs only.',
            ],
        ];

        $testimonials = [
            [
                'quote' => 'We used to email spreadsheets back and forth for every send. Now the quote, the invoice, and the delivery status all sit in one place.',
                'name' => 'Operations lead',
                'role' => 'Retail group',
                'initials' => 'OL',
            ],
            [
                'quote' => 'List cleaning alone paid for itself. We stopped being billed for numbers that were never going to receive anything.',
                'name' => 'Marketing manager',
                'role' => 'Financial services',
                'initials' => 'MM',
            ],
            [
                'quote' => 'Our finance team finally has invoices and payment references they can reconcile without asking us for screenshots.',
                'name' => 'Finance controller',
                'role' => 'Logistics',
                'initials' => 'FC',
            ],
        ];

        $faqs = [
            [
                'question' => 'Can I send messages myself from the portal?',
                'answer' => 'No — and that is deliberate. You submit the request and our operations team fulfils it through the gateway, so there is no send button to press by mistake.',
            ],
            [
                'question' => 'What file formats can I upload?',
                'answer' => 'CSV, TXT, and XLSX. Download the template for your campaign type from the request form so the columns line up, and save phone columns as text to avoid Excel corrupting them.',
            ],
            [
                'question' => 'How is the cost calculated?',
                'answer' => 'We count message segments, multiply by the number of billable recipients after cleaning, and apply the rate negotiated for your company. The quote is produced server-side before you are invoiced.',
            ],
            [
                'question' => 'How long does approval take?',
                'answer' => 'New companies and sender IDs are reviewed by our team. Campaign requests unlock once your company is approved and you have at least one approved sender ID.',
            ],
        ];
    @endphp

    <header
        data-site-header
        class="on-dark fixed inset-x-0 top-0 z-50 text-white transition-all duration-300"
    >
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
            <a
                href="{{ route('home') }}"
                class="inline-flex min-h-11 items-center gap-2.5 text-lg font-bold tracking-tight text-white"
            >
                <span class="flex size-9 items-center justify-center rounded-2xl bg-signal text-ink" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 40 40" fill="none" class="size-5">
                        <rect x="4" y="8" width="26" height="18" rx="5" fill="currentColor" opacity="0.92"/>
                        <path d="M12 15.5h10M12 19.5h7" stroke="#ECFCCB" stroke-width="2.2" stroke-linecap="round"/>
                        <circle cx="32" cy="11" r="3.5" fill="currentColor" opacity="0.55"/>
                        <circle cx="32" cy="11" r="1.6" fill="#ECFCCB"/>
                    </svg>
                </span>
                {{ config('marketing.brand') }}
            </a>

            <nav class="hidden items-center gap-1 rounded-full border border-white/10 bg-white/5 p-1 backdrop-blur-sm lg:flex" aria-label="Primary">
                @foreach ($navLinks as $link)
                    <a
                        href="{{ $link['href'] }}"
                        class="inline-flex min-h-11 items-center rounded-full px-4 text-sm font-medium text-white/80 transition hover:bg-white/10 hover:text-white"
                    >
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="hidden items-center gap-2 lg:flex">
                @auth
                    <a href="{{ route('dashboard') }}" class="btn-lime">Go to dashboard</a>
                @else
                    <a
                        href="{{ route('login') }}"
                        class="inline-flex min-h-11 items-center rounded-full px-4 text-sm font-medium text-white/85 transition hover:text-white"
                    >
                        Log in
                    </a>
                    <a href="{{ route('register') }}" class="btn-lime">
                        Create an account
                        <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                            <path d="M5 12h13m0 0-5-5m5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </a>
                @endauth
            </div>

            <details class="relative lg:hidden" data-mobile-menu>
                <summary
                    class="flex min-h-11 min-w-11 cursor-pointer list-none items-center justify-center rounded-full border border-white/20 bg-white/10 text-white"
                    aria-label="Open menu"
                >
                    <span class="sr-only">Menu</span>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </summary>
                <div class="absolute right-0 mt-2 w-64 rounded-2xl border border-ink/10 bg-white p-2 text-ink shadow-xl shadow-ink/20">
                    @foreach ($navLinks as $link)
                        <a href="{{ $link['href'] }}" class="flex min-h-11 items-center rounded-xl px-3 text-sm font-medium text-ink hover:bg-mist">
                            {{ $link['label'] }}
                        </a>
                    @endforeach
                    @auth
                        <a href="{{ route('dashboard') }}" class="mt-1 flex min-h-11 items-center justify-center rounded-xl bg-signal px-3 text-sm font-semibold text-ink">
                            Go to dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="flex min-h-11 items-center rounded-xl px-3 text-sm font-medium text-ink hover:bg-mist">
                            Log in
                        </a>
                        <a href="{{ route('register') }}" class="mt-1 flex min-h-11 items-center justify-center rounded-xl bg-signal px-3 text-sm font-semibold text-ink">
                            Create an account
                        </a>
                    @endauth
                </div>
            </details>
        </div>
    </header>

    <main id="main">
        {{-- Hero — no overflow-hidden here; the capability strip must hang into the next section. --}}
        <section class="marketing-hero-glow on-dark relative z-10 text-white" aria-labelledby="hero-heading">
            <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
                <div class="absolute inset-0 animate-drift opacity-50">
                    <svg class="h-full w-full" viewBox="0 0 1200 800" preserveAspectRatio="xMidYMid slice" role="presentation">
                        <defs>
                            <linearGradient id="wave" x1="0" y1="0" x2="1" y2="1">
                                <stop offset="0%" stop-color="#C6F24E" stop-opacity="0.25"/>
                                <stop offset="100%" stop-color="#ffffff" stop-opacity="0"/>
                            </linearGradient>
                        </defs>
                        <path fill="url(#wave)" d="M0,420 C180,360 260,520 450,480 C640,440 720,300 900,340 C1050,372 1120,300 1200,280 L1200,800 L0,800 Z"/>
                    </svg>
                </div>
            </div>

            <div class="relative mx-auto grid max-w-6xl gap-12 px-4 pt-28 pb-20 sm:px-6 sm:pt-32 lg:grid-cols-[1.05fr_1fr] lg:items-center lg:gap-10 lg:px-8 lg:pt-36 lg:pb-28">
                <div class="max-w-xl">
                    <p class="eyebrow-dark" data-reveal>
                        <span class="relative flex size-2">
                            <span class="absolute inline-flex size-full animate-pulse-ring rounded-full bg-signal"></span>
                            <span class="relative inline-flex size-2 rounded-full bg-signal"></span>
                        </span>
                        Welcome to {{ config('marketing.brand') }}
                    </p>

                    <h1
                        id="hero-heading"
                        class="mt-5 text-4xl leading-[1.05] font-bold tracking-tight text-balance sm:text-5xl lg:text-6xl"
                        data-reveal
                        data-reveal-delay="80"
                    >
                        {{ config('marketing.tagline') }}
                    </h1>

                    <p
                        class="mt-5 max-w-md text-base leading-relaxed text-pretty text-white/75 sm:text-lg"
                        data-reveal
                        data-reveal-delay="160"
                    >
                        Upload your list, get a clear quote and invoice, then we send the campaign for you — no self-service gateway to learn.
                    </p>

                    <div class="mt-9 flex flex-wrap items-center gap-3" data-reveal data-reveal-delay="240">
                        @auth
                            <a href="{{ route('dashboard') }}" class="btn-lime">
                                Go to dashboard
                                <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                                    <path d="M5 12h13m0 0-5-5m5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="btn-lime">
                                Create an account
                                <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                                    <path d="M5 12h13m0 0-5-5m5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </a>
                            <a href="{{ route('login') }}" class="btn-outline-light">Log in</a>
                        @endauth
                        <a
                            href="#how-it-works"
                            class="inline-flex min-h-11 items-center gap-3 rounded-full px-2 text-sm font-semibold text-white/85 transition hover:text-white"
                        >
                            <span class="flex size-11 items-center justify-center rounded-full border border-white/25 bg-white/10">
                                <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                                    <path d="M8 5.5v13l11-6.5-11-6.5Z" fill="currentColor"/>
                                </svg>
                            </span>
                            See how it works
                        </a>
                    </div>

                    <dl class="mt-12 flex flex-wrap items-center gap-x-10 gap-y-6" data-reveal data-reveal-delay="320">
                        <div>
                            <dt class="text-xs font-medium tracking-wide text-white/60 uppercase">Delivery rate</dt>
                            <dd class="mt-1 text-3xl font-bold">
                                <span data-countup="98.4" data-countup-decimals="1">98.4</span>%
                            </dd>
                        </div>
                        <div class="h-10 w-px bg-white/15" aria-hidden="true"></div>
                        <div>
                            <dt class="text-xs font-medium tracking-wide text-white/60 uppercase">Quote turnaround</dt>
                            <dd class="mt-1 text-3xl font-bold">
                                under <span data-countup="2">2</span>h
                            </dd>
                        </div>
                    </dl>
                </div>

                {{-- Product visual: photo slot inside a device frame, with floating status cards --}}
                <div class="relative mx-auto w-full max-w-md lg:max-w-none" data-reveal="scale" data-reveal-delay="200">
                    <div class="relative rounded-[2.25rem] border border-white/15 bg-white/5 p-3 shadow-2xl shadow-black/40 backdrop-blur-sm">
                        <x-marketing.photo
                            src="images/marketing/hero-portal.jpg"
                            alt="The campaign portal showing a request moving from quote to fulfilment"
                            ratio="aspect-[4/5]"
                            rounded="rounded-[1.75rem]"
                            label="Hero visual"
                            :eager="true"
                            class="border-white/20 text-white/70"
                        />

                        <div class="pointer-events-none absolute inset-x-6 bottom-6 rounded-2xl bg-ink/85 p-4 backdrop-blur-sm">
                            <p class="text-xs font-medium tracking-wide text-white/60 uppercase">Campaign status</p>
                            <div class="mt-3 flex items-center justify-between gap-3">
                                <span class="text-sm font-semibold text-white">August promo</span>
                                <span class="rounded-full bg-signal px-3 py-1 text-xs font-semibold text-ink">Sent</span>
                            </div>
                            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-white/15">
                                <div class="h-full w-[92%] rounded-full bg-signal"></div>
                            </div>
                        </div>
                    </div>

                    <div class="animate-float absolute -top-5 -left-4 hidden rounded-2xl bg-white p-4 text-ink shadow-xl shadow-black/25 sm:block">
                        <p class="text-xs font-medium text-muted-foreground">Recipients cleaned</p>
                        <p class="mt-1 text-xl font-bold">18,432</p>
                    </div>

                    <div class="animate-float-slow absolute -right-3 bottom-24 hidden rounded-2xl bg-signal p-4 text-ink shadow-xl shadow-black/25 sm:block">
                        <p class="text-xs font-semibold">Quote ready</p>
                        <p class="mt-1 text-xl font-bold">Invoice next</p>
                    </div>
                </div>
            </div>

            {{-- Capability strip that overlaps the section below (z-index keeps it above the mist background). --}}
            <div class="relative z-20 mx-auto -mb-16 max-w-6xl px-4 sm:px-6 lg:-mb-20 lg:px-8">
                <div class="grid gap-4 lg:grid-cols-[0.85fr_2fr]">
                    <div class="relative overflow-hidden rounded-3xl" data-reveal="left">
                        <x-marketing.photo
                            src="images/marketing/how-it-works.jpg"
                            alt="Two colleagues planning an SMS campaign together"
                            ratio="aspect-[16/10] lg:aspect-auto lg:h-full"
                            rounded="rounded-3xl"
                            label="Team photo"
                        />
                        <div class="pointer-events-none absolute inset-0 rounded-3xl bg-gradient-to-t from-ink/85 via-ink/20 to-transparent"></div>
                        <div class="absolute inset-x-5 bottom-5">
                            <p class="text-base font-semibold text-white">How does it work?</p>
                            <a href="#how-it-works" class="mt-2 inline-flex min-h-11 items-center gap-2 text-sm font-semibold text-signal">
                                Learn more
                                <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                                    <path d="M5 12h13m0 0-5-5m5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </a>
                        </div>
                    </div>

                    <div class="grid gap-4 rounded-3xl bg-forest p-5 sm:grid-cols-3 sm:p-6">
                        @foreach ($capabilities as $index => $capability)
                            <div
                                class="rounded-2xl bg-white/[0.04] p-5 transition duration-300 hover:-translate-y-1 hover:bg-white/[0.08]"
                                data-reveal
                                data-reveal-delay="{{ $index * 100 }}"
                            >
                                <span class="icon-chip" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" class="size-5">
                                        <path d="{{ $capability['icon'] }}" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                                <h2 class="mt-4 text-base font-semibold text-white">{{ $capability['title'] }}</h2>
                                <p class="mt-2 text-sm leading-relaxed text-white/65">{{ $capability['body'] }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </section>

        {{-- About --}}
        <section class="relative z-0 bg-mist pt-28 pb-16 sm:pb-20 lg:pt-36" aria-labelledby="about-heading">
            <div class="mx-auto grid max-w-6xl gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:items-center lg:px-8">
                <div class="relative" data-reveal="left">
                    <x-marketing.photo
                        src="images/marketing/about-team.jpg"
                        alt="The operations team reviewing campaign requests"
                        ratio="aspect-[4/3]"
                        label="About photo"
                    />
                    <div class="absolute -right-3 -bottom-6 hidden rounded-2xl bg-ink p-5 text-white shadow-xl shadow-ink/25 sm:block">
                        <p class="text-3xl font-bold text-signal">
                            <span data-countup="320">320</span>+
                        </p>
                        <p class="mt-1 max-w-[12ch] text-xs text-white/70">Campaigns fulfilled end to end</p>
                    </div>
                </div>

                <div data-reveal="right">
                    <p class="eyebrow">About us</p>
                    <h2 id="about-heading" class="section-title mt-4 text-ink">
                        A managed SMS desk, not another gateway login
                    </h2>
                    <p class="mt-4 text-muted-foreground">
                        {{ config('marketing.description') }}
                    </p>

                    <div class="mt-8 space-y-4">
                        <div class="flex gap-4 rounded-2xl border border-border bg-surface p-5">
                            <span class="icon-chip" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" class="size-5">
                                    <path d="M12 3v18m0-18 6 3v9l-6 3-6-3V6l6-3Z" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <div>
                                <h3 class="font-semibold text-ink">What we handle</h3>
                                <p class="mt-1 text-sm leading-relaxed text-muted-foreground">
                                    Gateway work, list hygiene, segment counting, invoicing, and the actual send — so your team only writes the message.
                                </p>
                            </div>
                        </div>
                        <div class="flex gap-4 rounded-2xl border border-border bg-surface p-5">
                            <span class="icon-chip" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" class="size-5">
                                    <path d="m4 12 5 5 11-11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <div>
                                <h3 class="font-semibold text-ink">What you keep</h3>
                                <p class="mt-1 text-sm leading-relaxed text-muted-foreground">
                                    Full visibility: every request, quote, invoice, payment reference, and delivery outcome stays in your portal history.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        {{-- Stats --}}
        <section class="border-y border-border bg-surface" aria-label="Platform results">
            <dl class="mx-auto grid max-w-6xl gap-8 px-4 py-14 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:px-8">
                @foreach ($stats as $index => $stat)
                    <div data-reveal data-reveal-delay="{{ $index * 90 }}">
                        <dt class="sr-only">{{ $stat['label'] }}</dt>
                        <dd>
                            <p class="text-4xl font-bold tracking-tight text-ink lg:text-5xl">
                                <span
                                    data-countup="{{ $stat['value'] }}"
                                    @isset($stat['decimals']) data-countup-decimals="{{ $stat['decimals'] }}" @endisset
                                >{{ $stat['value'] }}</span><span class="text-signal-strong">{{ $stat['suffix'] }}</span>
                            </p>
                            <p class="mt-2 max-w-[24ch] text-sm text-muted-foreground">{{ $stat['label'] }}</p>
                        </dd>
                    </div>
                @endforeach
            </dl>
        </section>

        {{-- Services --}}
        <section id="services" class="scroll-mt-24 bg-mist" aria-labelledby="services-heading">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between" data-reveal>
                    <div class="max-w-xl">
                        <p class="eyebrow">Our services</p>
                        <h2 id="services-heading" class="section-title mt-4 text-ink">
                            Everything between your message and the handset
                        </h2>
                    </div>
                    <p class="max-w-sm text-muted-foreground">
                        Three things we do on every campaign, so nothing lands on your team at the last minute.
                    </p>
                </div>

                <ul class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($services as $index => $service)
                        <li
                            class="card-lift group overflow-hidden rounded-3xl border border-border bg-surface"
                            data-reveal
                            data-reveal-delay="{{ $index * 120 }}"
                        >
                            <div class="overflow-hidden">
                                <x-marketing.photo
                                    :src="$service['image']"
                                    :alt="$service['alt']"
                                    ratio="aspect-[16/10]"
                                    rounded="rounded-none"
                                    label="Service photo"
                                    class="transition duration-500 group-hover:scale-105"
                                />
                            </div>
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-ink">{{ $service['title'] }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-muted-foreground">{{ $service['body'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        {{-- How it works --}}
        <section id="how-it-works" class="on-dark scroll-mt-24 bg-ink text-white" aria-labelledby="how-heading">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
                <div class="max-w-2xl" data-reveal>
                    <p class="eyebrow-dark">Process</p>
                    <h2 id="how-heading" class="section-title mt-4">How it works</h2>
                    <p class="mt-4 text-white/70">
                        Four steps from request to send — our team handles the gateway work.
                    </p>
                </div>

                <ol class="mt-14 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($steps as $index => $step)
                        <li
                            class="relative rounded-3xl border border-white/10 bg-white/[0.04] p-6 transition duration-300 hover:border-signal/40 hover:bg-white/[0.07]"
                            data-reveal
                            data-reveal-delay="{{ $index * 110 }}"
                        >
                            <span class="flex size-11 items-center justify-center rounded-full bg-signal font-mono text-sm font-bold text-ink">
                                {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}
                            </span>
                            <h3 class="mt-5 text-lg font-semibold">{{ $step['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-white/65">{{ $step['body'] }}</p>
                            @if (! $loop->last)
                                <span class="pointer-events-none absolute top-11 -right-4 hidden text-white/20 lg:block" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" class="size-6">
                                        <path d="M5 12h13m0 0-5-5m5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        {{-- Benefits --}}
        <section id="benefits" class="scroll-mt-24 bg-mist" aria-labelledby="benefits-heading">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
                <div class="max-w-2xl" data-reveal>
                    <p class="eyebrow">Why us</p>
                    <h2 id="benefits-heading" class="section-title mt-4 text-ink">
                        Built around what actually matters
                    </h2>
                    <p class="mt-4 text-muted-foreground">
                        Rates are negotiated per client — no public price list. The portal keeps the paperwork and lists tidy.
                    </p>
                </div>

                <ul class="mt-12 grid gap-6 sm:grid-cols-2">
                    @foreach ($benefits as $index => $benefit)
                        <li
                            class="card-lift flex gap-4 rounded-3xl border border-border bg-surface p-6"
                            data-reveal
                            data-reveal-delay="{{ ($index % 2) * 100 }}"
                        >
                            <span class="icon-chip" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none" class="size-5">
                                    <path d="m4 12 5 5 11-11" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </span>
                            <div>
                                <h3 class="text-lg font-semibold text-ink">{{ $benefit['title'] }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-muted-foreground">{{ $benefit['body'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        {{-- Testimonials --}}
        <section class="bg-surface" aria-labelledby="testimonials-heading">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 sm:py-24 lg:px-8">
                <div class="max-w-2xl" data-reveal>
                    <p class="eyebrow">Client feedback</p>
                    <h2 id="testimonials-heading" class="section-title mt-4 text-ink">
                        What teams say after their first send
                    </h2>
                </div>

                <ul class="mt-12 grid gap-6 lg:grid-cols-3">
                    @foreach ($testimonials as $index => $testimonial)
                        <li
                            class="card-lift flex flex-col justify-between rounded-3xl border border-border bg-mist p-6"
                            data-reveal
                            data-reveal-delay="{{ $index * 120 }}"
                        >
                            <svg viewBox="0 0 24 24" fill="none" class="size-8 text-signal-strong" aria-hidden="true">
                                <path d="M9 7c-2.8 0-5 2.2-5 5s2.2 5 5 5c0-3.3-1-4.5-3-5 .3-2.2 1.6-3.4 3-3.5V7Zm11 0c-2.8 0-5 2.2-5 5s2.2 5 5 5c0-3.3-1-4.5-3-5 .3-2.2 1.6-3.4 3-3.5V7Z" fill="currentColor"/>
                            </svg>
                            <blockquote class="mt-4 text-pretty text-ink">
                                “{{ $testimonial['quote'] }}”
                            </blockquote>
                            <div class="mt-6 flex items-center gap-3 border-t border-border pt-5">
                                <span class="flex size-11 items-center justify-center rounded-full bg-ink text-sm font-semibold text-signal" aria-hidden="true">
                                    {{ $testimonial['initials'] }}
                                </span>
                                <div>
                                    <p class="text-sm font-semibold text-ink">{{ $testimonial['name'] }}</p>
                                    <p class="text-sm text-muted-foreground">{{ $testimonial['role'] }}</p>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        {{-- FAQ --}}
        <section id="faq" class="scroll-mt-24 border-t border-border bg-mist" aria-labelledby="faq-heading">
            <div class="mx-auto grid max-w-6xl gap-10 px-4 py-16 sm:px-6 sm:py-24 lg:grid-cols-[0.8fr_1.2fr] lg:px-8">
                <div data-reveal="left">
                    <p class="eyebrow">FAQ</p>
                    <h2 id="faq-heading" class="section-title mt-4 text-ink">Questions we get asked first</h2>
                    <p class="mt-4 text-muted-foreground">
                        Still unsure about something? Email
                        <a class="font-semibold text-signal-strong underline underline-offset-4" href="mailto:{{ config('marketing.contact.email') }}">
                            {{ config('marketing.contact.email') }}
                        </a>
                        and a person will reply.
                    </p>
                </div>

                <div class="space-y-3">
                    @foreach ($faqs as $index => $faq)
                        <details
                            class="group rounded-2xl border border-border bg-surface px-5 transition hover:border-signal-strong/30"
                            data-reveal
                            data-reveal-delay="{{ $index * 80 }}"
                        >
                            <summary class="flex min-h-14 cursor-pointer items-center justify-between gap-4 py-4 text-left font-semibold text-ink">
                                {{ $faq['question'] }}
                                <span class="faq-icon flex size-8 shrink-0 items-center justify-center rounded-full bg-signal-soft text-signal-strong transition-transform duration-300" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" fill="none" class="size-4">
                                        <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                </span>
                            </summary>
                            <p class="pb-5 text-sm leading-relaxed text-muted-foreground">{{ $faq['answer'] }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- CTA --}}
        <section class="on-dark bg-mist pb-16 sm:pb-24" aria-labelledby="cta-heading">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                <div
                    class="marketing-hero-glow relative overflow-hidden rounded-[2rem] px-6 py-14 text-white sm:px-12 sm:py-16"
                    data-reveal="scale"
                >
                    <div class="relative flex flex-col items-start gap-8 lg:flex-row lg:items-center lg:justify-between">
                        <div class="max-w-xl">
                            <h2 id="cta-heading" class="section-title">Ready to submit your next campaign?</h2>
                            <p class="mt-4 text-white/75">
                                Create an account to register your company and sender IDs. Campaign requests unlock after approval.
                            </p>
                        </div>
                        @guest
                            <a href="{{ route('register') }}" class="btn-lime shrink-0 px-8">
                                Create an account
                                <svg viewBox="0 0 24 24" fill="none" class="size-4" aria-hidden="true">
                                    <path d="M5 12h13m0 0-5-5m5 5-5 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </a>
                        @else
                            <a href="{{ route('dashboard') }}" class="btn-lime shrink-0 px-8">Go to dashboard</a>
                        @endguest
                    </div>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-border bg-surface" aria-label="Site footer">
        <div class="mx-auto grid max-w-6xl gap-8 px-4 py-12 sm:px-6 lg:grid-cols-[1.2fr_1fr_1fr_1fr] lg:px-8">
            <div>
                <p class="text-lg font-bold text-ink">{{ config('marketing.brand') }}</p>
                <p class="mt-2 max-w-sm text-sm text-muted-foreground">
                    A managed SMS request portal. We send campaigns on your behalf — this site does not include a self-service send button.
                </p>
            </div>
            <div>
                <p class="text-sm font-semibold text-ink">Explore</p>
                <ul class="mt-3 space-y-1 text-sm text-muted-foreground">
                    @foreach ($navLinks as $link)
                        <li>
                            <a class="inline-flex min-h-11 items-center hover:text-ink" href="{{ $link['href'] }}">
                                {{ $link['label'] }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
            <div>
                <p class="text-sm font-semibold text-ink">Contact</p>
                <ul class="mt-3 space-y-1 text-sm text-muted-foreground">
                    <li>
                        <a class="inline-flex min-h-11 items-center hover:text-ink" href="mailto:{{ config('marketing.contact.email') }}">
                            {{ config('marketing.contact.email') }}
                        </a>
                    </li>
                    @if (filled(config('marketing.contact.phone')))
                        <li>
                            <a class="inline-flex min-h-11 items-center hover:text-ink" href="tel:{{ preg_replace('/\s+/', '', config('marketing.contact.phone')) }}">
                                {{ config('marketing.contact.phone') }}
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
            <div>
                <p class="text-sm font-semibold text-ink">Legal</p>
                <ul class="mt-3 space-y-1 text-sm text-muted-foreground">
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
