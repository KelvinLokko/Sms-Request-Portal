<?php

namespace App\Notifications;

use App\Models\Company;
use App\Notifications\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompanyApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Company $company)
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
            ->subject('Your company has been approved: '.$this->company->name)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('Your company registration has been approved.')
            ->line('Company: '.$this->company->name)
            ->line('You can now register sender IDs and submit SMS campaign requests.')
            ->action('Go to dashboard', url(route('dashboard')));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'company.approved',
            'company_id' => $this->company->id,
            'name' => $this->company->name,
        ];
    }

    public function toTelegram(object $notifiable): string
    {
        return sprintf(
            "Company approved\n%s\nYou can now submit campaigns.",
            $this->company->name,
        );
    }
}
