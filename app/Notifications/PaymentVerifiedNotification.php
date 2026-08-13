<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Notifications\Concerns\RoutesSupportChannels;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentVerifiedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RoutesSupportChannels;

    public function __construct(public Payment $payment)
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
        $payment = $this->payment->loadMissing(['invoice.smsRequest', 'company']);
        $reference = $payment->provider_reference
            ?? $payment->momo_reference
            ?? '—';

        return (new MailMessage)
            ->subject('Payment confirmed: '.$payment->invoice->number)
            ->line('An invoice payment has been confirmed successfully.')
            ->line('Company: '.($payment->company->name ?? '—'))
            ->line('Invoice: '.$payment->invoice->number)
            ->line('Campaign: '.($payment->invoice->smsRequest->reference ?? '—'))
            ->line('Amount: '.Money::format($payment->amount_pesewas))
            ->line('Provider: '.$payment->provider)
            ->line('Reference: '.$reference)
            ->action('Open payments', url(route('admin.payments.index')));
    }

    public function toTelegram(object $notifiable): string
    {
        $payment = $this->payment->loadMissing(['invoice', 'company']);

        return sprintf(
            "Payment confirmed\n%s · %s\n%s",
            $payment->company->name ?? '',
            $payment->invoice->number,
            Money::format($payment->amount_pesewas),
        );
    }
}
