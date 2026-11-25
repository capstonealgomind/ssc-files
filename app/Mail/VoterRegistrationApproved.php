<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class VoterRegistrationApproved extends Mailable
{
    public function __construct(
        public readonly string $recipientName,
        public readonly string $voterIdNumber,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Your SSCEVS voter account is approved');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.voter-registration-approved', text: 'mail.voter-registration-approved-text');
    }
}
