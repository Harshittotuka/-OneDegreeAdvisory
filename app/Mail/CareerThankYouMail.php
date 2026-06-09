<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Confirmation / thank-you email sent back to the applicant who submitted the
 * Careers form. Sent from the careers mailbox.
 */
class CareerThankYouMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param array<string,mixed> $data */
    public function __construct(public array $data)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('site.forms.careers.from'), config('site.forms.careers.from_name')),
            subject: 'We received your application — One Degree Advisory',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.career-thank-you');
    }
}
