<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class SendEmailOtp extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $otp,
        public string $email
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your Estate Hub verification code',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.email-otp',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
