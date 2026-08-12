<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public marketing site
    |--------------------------------------------------------------------------
    |
    | Placeholder copy until real brand details are supplied.
    |
    */

    'brand' => env('MARKETING_BRAND', env('APP_NAME', 'smsbulkportal')),

    'tagline' => env(
        'MARKETING_TAGLINE',
        'Submit SMS campaigns. We handle the rest.',
    ),

    'description' => env(
        'MARKETING_DESCRIPTION',
        'A secure client portal for businesses to submit SMS campaign requests, upload recipient lists, receive invoices, and track fulfilment — while our team sends every message for you.',
    ),

    'contact' => [
        'email' => env('MARKETING_CONTACT_EMAIL', 'hello@example.com'),
        'phone' => env('MARKETING_CONTACT_PHONE', ''),
    ],

    /*
    | Google Search Console HTML-tag verification.
    | In Search Console choose "HTML tag", then set the content value here.
    */
    'google_site_verification' => env('GOOGLE_SITE_VERIFICATION'),

    'og_image' => env('MARKETING_OG_IMAGE', '/images/logo.png'),

];
