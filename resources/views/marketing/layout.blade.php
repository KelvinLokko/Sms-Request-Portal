<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#0B1F1C">

        {{-- Marks the document as script-capable so reveal animations never hide content from no-JS visitors. --}}
        <script>document.documentElement.classList.add('js');</script>

        <title>@yield('title', config('marketing.brand'))</title>
        <meta name="description" content="@yield('meta_description', config('marketing.description'))">
        <link rel="canonical" href="{{ url()->current() }}">
        @if (filled(config('marketing.google_site_verification')))
            <meta name="google-site-verification" content="{{ config('marketing.google_site_verification') }}">
        @endif

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ config('marketing.brand') }}">
        <meta property="og:title" content="@yield('og_title', config('marketing.brand'))">
        <meta property="og:description" content="@yield('meta_description', config('marketing.description'))">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}">
        <meta property="og:image" content="{{ url(config('marketing.og_image')) }}">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="@yield('og_title', config('marketing.brand'))">
        <meta name="twitter:description" content="@yield('meta_description', config('marketing.description'))">
        <meta name="twitter:image" content="{{ url(config('marketing.og_image')) }}">

        <link rel="icon" href="/images/favicon.png" type="image/png" sizes="any">
        <link rel="apple-touch-icon" href="/images/favicon.png">

        @fonts
        @vite(['resources/css/marketing.css', 'resources/js/marketing.ts'])

        @stack('head')
    </head>
    <body class="min-h-screen">
        <a class="skip-link" href="#main">Skip to content</a>

        @yield('body')
    </body>
</html>
