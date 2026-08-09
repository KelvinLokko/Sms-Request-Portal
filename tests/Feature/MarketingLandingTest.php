<?php

use App\Models\CompanyRate;
use App\Models\User;

it('renders the public marketing landing page as blade html', function () {
    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee(config('marketing.brand'), false)
        ->assertSee('Create an account', false)
        ->assertSee('How it works', false)
        ->assertSee('We send it for you', false)
        ->assertSee('application/ld+json', false)
        ->assertDontSee('data-page=', false);
});

it('includes skip link and landmark structure for accessibility', function () {
    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee('Skip to content', false)
        ->assertSee('id="main"', false)
        ->assertSee('<footer', false)
        ->assertSee('aria-label="Primary"', false);
});

it('shows a pricing calculator using the platform default rate', function () {
    CompanyRate::factory()->create([
        'company_id' => null,
        'rate_per_sms' => '0.030000',
        'effective_from' => now()->subDay()->toDateString(),
    ]);

    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee('Estimate your campaign cost', false)
        ->assertSee('data-pricing-calculator', false)
        ->assertSee('data-rate="0.030000"', false)
        ->assertSee('GHS / page', false);
});

it('points every public call to action at registration for guests', function () {
    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee(route('register'), false);
});

it('shows dashboard links instead of register when authenticated', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('home'))
        ->assertOk()
        ->assertSee(route('dashboard'), false)
        ->assertSee('Go to dashboard', false);
});

it('serves robots.txt with sitemap and private path disallow rules', function () {
    $response = $this->get(route('robots'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
        ->assertSee('Disallow: /dashboard', false)
        ->assertSee('Disallow: /admin', false)
        ->assertSee('Sitemap: '.url('/sitemap.xml'), false);
});

it('serves sitemap.xml with home login and register urls', function () {
    $response = $this->get(route('sitemap'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
        ->assertSee(route('home'), false)
        ->assertSee(route('login'), false)
        ->assertSee(route('register'), false);
});
