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
 * Internal notification sent to the careers mailbox when someone applies via
 * the Careers page. Reply-To is the applicant so a partner can respond directly.
 */
class CareerApplicationMail extends Mailable
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
            replyTo: [new Address($this->data['email'], $this->data['name'])],
            subject: 'New career application - '.$this->data['name'].' ('.$this->data['role'].')',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.career-application');
    }

    /**
     * Attach the applicant's uploaded resume, if they provided one (those who
     * pasted a link instead have no file to attach).
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        $resume = $this->data['resume'] ?? null;

        if (empty($resume['path'])) {
            return [];
        }

        return [
            Attachment::fromStorageDisk('local', $resume['path'])
                ->as($resume['name'] ?? 'resume')
                ->withMime($resume['mime'] ?? 'application/octet-stream'),
        ];
    }
}
