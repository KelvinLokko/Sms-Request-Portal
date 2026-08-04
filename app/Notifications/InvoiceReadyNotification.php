<?php

namespace App\Notifications;

use App\Models\Invoice;
use App\Notifications\Channels\TelegramChannel;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvoiceReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Invoice $invoice)
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
        $invoice = $this->invoice->loadMissing('smsRequest');

        return (new MailMessage)
            ->subject('Invoice ready: '.$invoice->number)
            ->line('Your invoice for campaign '.($invoice->smsRequest->reference ?? '').' is ready.')
            ->line('Total: '.Money::format($invoice->total_pesewas))
            ->action('View invoice', url(route('invoices.show', $invoice)));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'invoice.ready',
            'invoice_id' => $this->invoice->id,
            'number' => $this->invoice->number,
            'total_pesewas' => $this->invoice->total_pesewas,
        ];
    }

    public function toTelegram(object $notifiable): string
    {
        $invoice = $this->invoice->loadMissing('smsRequest');

        return sprintf(
            "Invoice ready\n%s · %s\n%s",
            $invoice->number,
            $invoice->smsRequest->reference ?? '',
            Money::format($invoice->total_pesewas),
        );
    }
}
