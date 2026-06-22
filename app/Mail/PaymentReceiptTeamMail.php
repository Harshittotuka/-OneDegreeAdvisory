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
 * Internal notification to the admissions team when a checkout is paid.
 * Reply-To is the customer so the team can respond in one click. Sent sync.
 */
class PaymentReceiptTeamMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public PaymentAttempt $attempt) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('site.forms.contact.from'), config('site.forms.contact.from_name')),
            replyTo: [new Address($this->attempt->customer_email, $this->attempt->customer_name)],
            subject: 'New enrolment payment - '.$this->attempt->item_name.' - '.$this->attempt->customer_name,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.payment-team');
    }
}
