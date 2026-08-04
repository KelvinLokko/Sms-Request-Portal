<?php

namespace App\Notifications;

use App\Models\Payment;
use App\Notifications\Channels\TelegramChannel;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Payment $payment)
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
        $payment = $this->payment->loadMissing(['invoice', 'company']);

        return (new MailMessage)
            ->subject('Payment submitted: '.$payment->invoice->number)
            ->line("{$payment->company->name} submitted a MoMo payment for verification.")
            ->line('Amount: '.Money::format($payment->amount_pesewas))
            ->line('Reference: '.$payment->momo_reference)
            ->action('Verify payments', url(route('admin.payments.index')));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'payment.received',
            'payment_id' => $this->payment->id,
            'invoice_id' => $this->payment->invoice_id,
            'amount_pesewas' => $this->payment->amount_pesewas,
            'momo_reference' => $this->payment->momo_reference,
        ];
    }

    public function toTelegram(object $notifiable): string
    {
        $payment = $this->payment->loadMissing(['invoice', 'company']);

        return sprintf(
            "Payment received\n%s · %s\n%s · %s",
            $payment->company->name,
            $payment->invoice->number,
            Money::format($payment->amount_pesewas),
            $payment->momo_reference,
        );
    }
}
