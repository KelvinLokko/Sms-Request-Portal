<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="theme-color" content="#0B1F33">

        <title>@yield('title', config('marketing.brand'))</title>
        <meta name="description" content="@yield('meta_description', config('marketing.description'))">
        <link rel="canonical" href="{{ url()->current() }}">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ config('marketing.brand') }}">
        <meta property="og:title" content="@yield('og_title', config('marketing.brand'))">
        <meta property="og:description" content="@yield('meta_description', config('marketing.description'))">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="@yield('og_title', config('marketing.brand'))">
        <meta name="twitter:description" content="@yield('meta_description', config('marketing.description'))">

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts
        @vite(['resources/css/marketing.css'])

        @stack('head')
    </head>
    <body class="min-h-screen">
        <a class="skip-link" href="#main">Skip to content</a>

        @yield('body')
    </body>
</html>
