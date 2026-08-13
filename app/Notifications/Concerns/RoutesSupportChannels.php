<?php

namespace App\Notifications\Concerns;

use App\Notifications\Channels\TelegramChannel;
use Illuminate\Notifications\AnonymousNotifiable;

trait RoutesSupportChannels
{
    /**
     * @return list<string|class-string>
     */
    protected function channelsFor(object $notifiable, bool $includeDatabase = true): array
    {
        if ($notifiable instanceof AnonymousNotifiable) {
            $channels = ['mail'];
        } else {
            $channels = $includeDatabase ? ['mail', 'database'] : ['mail'];
        }

        if (config('notifications.telegram.enabled')) {
            $channels[] = TelegramChannel::class;
        }

        return $channels;
    }
}
