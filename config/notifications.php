<?php

return [

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
