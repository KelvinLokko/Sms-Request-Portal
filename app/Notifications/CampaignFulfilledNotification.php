<?php

namespace App\Notifications;

use App\Models\SmsRequest;
use App\Notifications\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CampaignFulfilledNotification extends Notification implements ShouldQueue
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
            ->subject('Campaign sent: '.$this->smsRequest->reference)
            ->line('Our team has sent your campaign in Deywuro.')
            ->line('Status: Sent by our team')
            ->action('View campaign', url(route('campaigns.show', $this->smsRequest)));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'campaign.fulfilled',
            'sms_request_id' => $this->smsRequest->id,
            'reference' => $this->smsRequest->reference,
        ];
    }

    public function toTelegram(object $notifiable): string
    {
        return sprintf(
            "Campaign fulfilled\n%s · Sent by our team",
            $this->smsRequest->reference,
        );
    }
}
