<?php

namespace App\Mail;

use App\Models\PaymentAttempt;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Thank-you confirmation sent to the customer after a successful payment. Sent sync.
 */
class PaymentThankYouMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PaymentAttempt $attempt) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('site.forms.contact.from'), config('site.forms.contact.from_name')),
            subject: 'Payment received - '.config('site.name'),
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.payment-thank-you');
    }
}
