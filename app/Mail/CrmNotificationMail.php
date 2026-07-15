<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CrmNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param array<string, mixed> $details */
    public function __construct(
        public string $subjectLine,
        public string $headline,
        public string $messageText,
        public array $details = [],
        public ?string $actionUrl = null,
        public string $actionLabel = 'Open CRM',
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('site.forms.contact.from'), 'One Degree CRM'),
            subject: $this->subjectLine,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.crm-notification');
    }
}
