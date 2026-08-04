<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public marketing site
    |--------------------------------------------------------------------------
    |
    | Placeholder copy until real brand details are supplied. Rates stay private.
    |
    */

    'brand' => env('MARKETING_BRAND', env('APP_NAME', 'SMS Client Portal')),

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

];
