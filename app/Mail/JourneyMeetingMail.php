<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * The join details for one meeting on a student's journey plan, sent to the
 * people the counsellor listed.
 *
 * Deliberately not a calendar invitation: no .ics, nothing that asks the
 * recipient to accept or that writes to their calendar. It carries the when,
 * the who and the way in — the join link for a video call, the number for a
 * phone call, the address for a meeting in person.
 *
 * @param  array<string, mixed>  $meeting  a row from CrmJourneyPlan::meetingRows()
 */
class JourneyMeetingMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public array $meeting,
        public string $studentName,
        public ?string $counsellorName = null,
        public bool $isUpdate = false,
        // 'tomorrow' or 'today' for the reminders; null for booking and changes.
        public ?string $reminder = null,
    ) {}

    public function envelope(): Envelope
    {
        $from = trim((string) config('crm.email.from')) ?: (string) config('site.forms.contact.from');
        $fromName = trim((string) config('crm.email.from_name')) ?: 'One Degree CRM';

        return new Envelope(
            from: new Address($from, $fromName),
            subject: match ($this->reminder) {
                'tomorrow' => 'Reminder: '.$this->meeting['title'].' is tomorrow — '.$this->whenLine(),
                'today' => 'Today: '.$this->meeting['title'].' — '.$this->whenLine(),
                default => ($this->isUpdate ? 'Updated: ' : '').$this->meeting['title'].' — '.$this->whenLine(),
            },
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.journey-meeting', with: [
            'when' => $this->whenLine(),
            // Meeting in person still wants a number on it: someone always
            // ends up ringing ahead.
            'joinLabel' => $this->meeting['mode'] === 'In person' ? 'Contact number' : 'Phone number',
        ]);
    }

    /** "Fri 3 Oct 2026, 4:00 pm" — or just the date when no time was set. */
    private function whenLine(): string
    {
        $date = Carbon::parse($this->meeting['date']);
        if ($this->meeting['time'] === '') {
            return $date->format('D j M Y');
        }

        return $date->format('D j M Y').', '.Carbon::parse($this->meeting['date'].' '.$this->meeting['time'])->format('g:i a');
    }
}
