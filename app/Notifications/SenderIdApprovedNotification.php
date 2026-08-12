<?php

namespace App\Notifications;

use App\Models\SenderId;
use App\Notifications\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SenderIdApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SenderId $senderId)
    {
        $this->onQueue('notifications');
    }

    /**
     * @return list<string|class-string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail', 'database'];

        if (config('notifications.telegram.enabled')) {
            $channels[] = TelegramChannel::class;
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $senderId = $this->senderId->loadMissing('company');

        return (new MailMessage)
            ->subject('Sender ID approved: '.$senderId->value)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your sender ID request has been approved.')
            ->line('Sender ID: '.$senderId->value)
            ->line('You can now use it when submitting SMS campaigns.')
            ->action('View sender IDs', url(route('sender-ids.index')));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'sender_id.approved',
            'sender_id_id' => $this->senderId->id,
            'value' => $this->senderId->value,
        ];
    }

    public function toTelegram(object $notifiable): string
    {
        return sprintf(
            "Sender ID approved\n%s\nYou can use it on new campaigns.",
            $this->senderId->value,
        );
    }
}
