<?php

namespace App\Mail;

use App\Models\CrmUser;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CrmOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CrmUser $crmUser, public string $otp) {}

    public function envelope(): Envelope
    {
        $from = trim((string) config('crm.email.from')) ?: (string) config('site.forms.contact.from');
        $fromName = trim((string) config('crm.email.from_name')) ?: 'One Degree CRM';

        return new Envelope(
            from: new Address($from, $fromName),
            subject: 'Your One Degree CRM login code',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.crm-otp');
    }
}
