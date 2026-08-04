<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! config('notifications.telegram.enabled')) {
            return;
        }

        $token = config('notifications.telegram.bot_token');
        $chatId = config('notifications.telegram.chat_id');

        if (! is_string($token) || $token === '' || ! is_string($chatId) || $chatId === '') {
            return;
        }

        if (! method_exists($notification, 'toTelegram')) {
            return;
        }

        /** @var string $text */
        $text = $notification->toTelegram($notifiable);

        if ($text === '') {
            return;
        }

        $response = Http::asForm()->post(
            "https://api.telegram.org/bot{$token}/sendMessage",
            [
                'chat_id' => $chatId,
                'text' => $text,
                'disable_web_page_preview' => true,
            ],
        );

        if (! $response->successful()) {
            Log::warning('Telegram notification failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }
}
