<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Support inbox
    |--------------------------------------------------------------------------
    |
    | Operational alerts (new registrations, sender IDs, campaigns, payments)
    | are emailed here in addition to any role-based staff notifications.
    | Defaults to the public marketing contact address.
    |
    */

    'support' => [
        'email' => env(
            'SUPPORT_EMAIL',
            env('MARKETING_CONTACT_EMAIL', 'support@smsbulkportal.com'),
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Optional Telegram alerts
    |--------------------------------------------------------------------------
    |
    | When enabled, privileged lifecycle notifications are also posted to a
    | Telegram chat via the Bot API. Leave disabled until credentials are set.
    |
    */

    'telegram' => [
        'enabled' => (bool) env('TELEGRAM_NOTIFICATIONS_ENABLED', false),
        'bot_token' => env('TELEGRAM_BOT_TOKEN'),
        'chat_id' => env('TELEGRAM_CHAT_ID'),
    ],

];
