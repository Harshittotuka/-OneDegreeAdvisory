<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Introduction sent to the referred student.
 *
 * This is the one email on the site that goes to somebody who did not fill in
 * the form themselves, so it is deliberately built to be transparent: it names
 * the person who referred them in the subject and the opening line, and it tells
 * them how to stop hearing from us. The referrer has to tick a consent box
 * confirming they had the student's permission before this can be sent, and the
 * whole email can be switched off with REFERRAL_NOTIFY_STUDENT=false.
 */
class ReferralStudentMail extends Mailable
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
            // Replies go to the admissions mailbox, including any "please stop".
            replyTo: [new Address(config('site.forms.referral.to'), config('site.forms.referral.from_name'))],
            subject: $this->data['referrer_name'].' has referred you to One Degree Advisory',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.referral-student');
    }
}
