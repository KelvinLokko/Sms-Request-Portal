<?php

namespace App\Notifications;

use App\Models\SenderId;
use App\Notifications\Concerns\RoutesSupportChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SenderIdRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RoutesSupportChannels;

    public function __construct(public SenderId $senderId)
    {
        $this->onQueue('notifications');
    }

    /**
     * @return list<string|class-string>
     */
    public function via(object $notifiable): array
    {
        return $this->channelsFor($notifiable, includeDatabase: false);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $senderId = $this->senderId->loadMissing(['company', 'requester']);

        return (new MailMessage)
            ->subject('New sender ID request: '.$senderId->value)
            ->line('A sender ID has been submitted for review.')
            ->line('Sender ID: '.$senderId->value)
            ->line('Company: '.($senderId->company->name ?? '—'))
            ->line('Requested by: '.($senderId->requester->email ?? '—'))
            ->action('Review sender IDs', url(route('admin.sender-ids.index')));
    }

    public function toTelegram(object $notifiable): string
    {
        $senderId = $this->senderId->loadMissing('company');

        return sprintf(
            "New sender ID request\n%s\n%s",
            $senderId->value,
            $senderId->company->name ?? '',
        );
    }
}
