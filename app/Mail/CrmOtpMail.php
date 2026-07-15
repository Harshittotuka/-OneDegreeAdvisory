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
        return new Envelope(
            from: new Address(config('site.forms.contact.from'), 'One Degree CRM'),
            subject: 'Your One Degree CRM login code',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.crm-otp');
    }
}
