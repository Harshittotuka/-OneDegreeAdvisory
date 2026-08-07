<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Internal notification sent to admissions when a referral is submitted through
 * the Referral Program page. Reply-To is the REFERRER, since they are the one
 * waiting on an acknowledgement — the student gets their own introduction email.
 */
class ReferralTeamMail extends Mailable
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
            replyTo: [new Address($this->data['referrer_email'], $this->data['referrer_name'])],
            subject: 'New referral - '.$this->data['student_name'].' (referred by '.$this->data['referrer_name'].')',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.referral-team');
    }
}
