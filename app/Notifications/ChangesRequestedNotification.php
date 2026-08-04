<?php

namespace App\Notifications;

use App\Models\SmsRequest;
use App\Notifications\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChangesRequestedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SmsRequest $smsRequest)
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
        return (new MailMessage)
            ->subject('Changes requested: '.$this->smsRequest->reference)
            ->line('We need updates before we can continue with this campaign.')
            ->line($this->smsRequest->changes_requested_reason ?? '')
            ->action('Open campaign', url(route('campaigns.show', $this->smsRequest)));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'campaign.changes_requested',
            'sms_request_id' => $this->smsRequest->id,
            'reference' => $this->smsRequest->reference,
            'reason' => $this->smsRequest->changes_requested_reason,
        ];
    }

    public function toTelegram(object $notifiable): string
    {
        return sprintf(
            "Changes requested\n%s\n%s",
            $this->smsRequest->reference,
            (string) $this->smsRequest->changes_requested_reason,
        );
    }
}
