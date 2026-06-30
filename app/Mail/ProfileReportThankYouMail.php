<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Confirmation / thank-you email sent back to the student who completed the
 * Student Profiler or Profile Evaluator. Includes a copy of their profile
 * report (key facts, rule-based analysis, full Q&A). Sent from the admissions
 * mailbox.
 */
class ProfileReportThankYouMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param array<string,mixed> $data Built by App\Support\ProfileReportBuilder. */
    public function __construct(public array $data)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('site.forms.profiler.from'), config('site.forms.profiler.from_name')),
            subject: 'Your profile report - One Degree Advisory',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.profile-thank-you');
    }
}
