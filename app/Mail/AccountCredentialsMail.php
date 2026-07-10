<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AccountCredentialsMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $recipientName,
        public readonly string $loginEmail,
        public readonly string $plainPassword,
        public readonly string $loginUrl,
        public readonly string $accountType = 'committee',
    ) {}

    public function envelope(): Envelope
    {
        $label = $this->accountType === 'admin' ? 'Administrator' : 'Committee';

        return new Envelope(
            subject: "Your SSCEVS {$label} Account Credentials",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.account-credentials',
        );
    }
}
