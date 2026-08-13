<?php

namespace App\Notifications;

use App\Models\Company;
use App\Notifications\Concerns\RoutesSupportChannels;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompanyRegisteredNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RoutesSupportChannels;

    public function __construct(public Company $company)
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
        $company = $this->company;

        $mail = (new MailMessage)
            ->subject('New company registration: '.$company->name)
            ->line('A new company has registered and is awaiting approval.')
            ->line('Company: '.$company->name)
            ->line('Email: '.$company->email);

        if (filled($company->phone)) {
            $mail->line('Phone: '.$company->phone);
        }

        return $mail->action('Review companies', url(route('admin.companies.index')));
    }

    public function toTelegram(object $notifiable): string
    {
        $company = $this->company;

        return sprintf(
            "New company registration\n%s\n%s",
            $company->name,
            $company->email,
        );
    }
}
