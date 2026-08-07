<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $code,
        public string $name,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your '.config('app.name').' verification code',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.registration-otp',
            with: [
                'code' => $this->code,
                'name' => $this->name,
                'minutes' => 15,
            ],
        );
    }
}
