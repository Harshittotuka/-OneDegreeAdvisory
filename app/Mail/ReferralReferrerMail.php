<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Confirmation sent to the person who filled in the referral form: what we
 * recorded, what happens next, and when the reward is released.
 */
class ReferralReferrerMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param array<string,mixed> $data */
    public function __construct(public array $data)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('site.forms.referral.from'), config('site.forms.referral.from_name')),
            subject: 'Thanks for referring '.$this->data['student_name'].' to One Degree Advisory',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.referral-referrer');
    }
}
