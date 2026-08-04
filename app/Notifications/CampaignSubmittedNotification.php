<?php

namespace App\Notifications;

use App\Models\SmsRequest;
use App\Notifications\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CampaignSubmittedNotification extends Notification implements ShouldQueue
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
        $request = $this->smsRequest->loadMissing('company');

        return (new MailMessage)
            ->subject('New campaign submitted: '.$request->reference)
            ->line("Company {$request->company->name} submitted campaign {$request->reference}.")
            ->line('Billable recipients: '.number_format((int) $request->billable_recipients))
            ->action('Open review queue', url(route('admin.campaigns.show', $request)));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'campaign.submitted',
            'sms_request_id' => $this->smsRequest->id,
            'reference' => $this->smsRequest->reference,
            'company' => $this->smsRequest->company->name,
        ];
    }

    public function toTelegram(object $notifiable): string
    {
        $request = $this->smsRequest->loadMissing('company');

        return sprintf(
            "New campaign submitted\n%s · %s\nBillable: %s",
            $request->reference,
            $request->company->name,
            number_format((int) $request->billable_recipients),
        );
    }
}
