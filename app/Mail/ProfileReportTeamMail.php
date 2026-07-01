<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Internal notification sent to the admissions team when a visitor completes the
 * Student Profiler. Carries the generated profile report
 * (key facts, rule-based analysis, full Q&A). Reply-To is the student when an
 * email was provided, so the team can respond in one click.
 */
class ProfileReportTeamMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  array<string,mixed>  $data     Built by App\Support\ProfileReportBuilder.
     * @param  string|null          $pdf      Rendered report PDF bytes (null if rendering failed).
     * @param  string               $pdfName  Attachment filename.
     */
    public function __construct(
        public array $data,
        public ?string $pdf = null,
        public string $pdfName = 'OneDegree-Profile-Report.pdf'
    ) {
    }

    public function envelope(): Envelope
    {
        $name = $this->data['name'] !== '' ? $this->data['name'] : 'a student';

        $replyTo = [];
        if (filter_var($this->data['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            $replyTo = [new Address($this->data['email'], $this->data['name'] ?: $this->data['email'])];
        }

        return new Envelope(
            from: new Address(config('site.forms.profiler.from'), config('site.forms.profiler.from_name')),
            replyTo: $replyTo,
            subject: 'New '.$this->data['sourceLabel'].' submission - '.$name,
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.profile-report-team');
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        if ($this->pdf === null) {
            return [];
        }

        return [
            Attachment::fromData(fn () => $this->pdf, $this->pdfName)
                ->withMime('application/pdf'),
        ];
    }
}
