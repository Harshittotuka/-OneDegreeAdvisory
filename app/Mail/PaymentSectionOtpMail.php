<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * One-time authorization code for adding/saving a PAYMENT SECTION in the Page
 * Builder. Sent to the trusted recipients so only an authorized person can
 * publish a live payment gateway. This is a CMS gate — not a customer-facing
 * payment step (public checkout is now direct).
 */
class PaymentSectionOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $otp,
        public int $ttlMinutes,
        public string $pageTitle,
        public string $pagePath,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('site.forms.contact.from'), config('site.forms.contact.from_name')),
            subject: 'Authorization code to add a payment section — '.$this->pageTitle,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.payment-section-otp');
    }
}
