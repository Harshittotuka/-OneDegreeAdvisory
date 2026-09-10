<?php

namespace App\Mail;

use App\Models\CrmPartnerCode;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Referral notice to a partner company: a student they sent us through
 * /profiler?partner=CODE has completed the Student Profiler.
 *
 * Deliberately lighter than ProfileReportTeamMail. It carries who came in, how
 * to reach them and the headline facts — no attached report PDF and no full Q&A,
 * because the partner referred the student rather than being retained to advise
 * them. Reply-To is our admissions mailbox, so a partner replying reaches us.
 */
class ProfileReportPartnerMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param array<string,mixed> $data Built by App\Support\ProfileReportBuilder. */
    public function __construct(
        public array $data,
        public CrmPartnerCode $partner,
    ) {
    }

    public function envelope(): Envelope
    {
        $name = $this->data['name'] !== '' ? $this->data['name'] : 'a student';

        return new Envelope(
            from: new Address(config('site.forms.profiler.from'), config('site.forms.profiler.from_name')),
            replyTo: [new Address(config('site.forms.profiler.to'), config('site.forms.profiler.from_name'))],
            subject: 'Your referral '.$name.' completed the Student Profiler',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.profile-report-partner');
    }
}
