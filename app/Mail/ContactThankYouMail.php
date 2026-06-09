<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Confirmation / thank-you email sent back to the visitor who submitted the
 * Contact / Home enquiry form. Sent from the admissions mailbox.
 */
class ContactThankYouMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param array<string,mixed> $data */
    public function __construct(public array $data)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('site.forms.contact.from'), config('site.forms.contact.from_name')),
            subject: 'Thanks for reaching out to One Degree Advisory',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.contact-thank-you');
    }
}
