<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Internal notification sent to the admissions team when a visitor submits the
 * Contact / Home enquiry form. Reply-To is set to the enquirer so the team can
 * respond with a single click.
 */
class ContactEnquiryMail extends Mailable
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
            replyTo: [new Address($this->data['email'], $this->data['name'])],
            subject: 'New website enquiry - '.$this->data['name'],
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.contact-enquiry');
    }
}
