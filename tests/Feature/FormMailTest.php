<?php

namespace Tests\Feature;

use App\Mail\CareerApplicationMail;
use App\Mail\CareerThankYouMail;
use App\Mail\ContactEnquiryMail;
use App\Mail\ContactThankYouMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class FormMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_form_sends_team_and_visitor_emails(): void
    {
        Mail::fake();

        $payload = [
            'name' => 'Alex Student',
            'email' => 'alex@example.com',
            'phone' => '+91 90000 00000',
            'city' => 'Jaipur',
            'destination' => 'United Kingdom',
            'level' => 'Undergraduate',
            'message' => 'I want help shortlisting universities.',
            'consent' => '1',
        ];

        $this->postJson('/contact', $payload)
            ->assertOk()
            ->assertJsonPath('ok', true);

        Mail::assertSent(ContactEnquiryMail::class, function (ContactEnquiryMail $mail) use ($payload) {
            return $mail->hasTo(config('site.forms.contact.to'))
                && $mail->data['email'] === $payload['email'];
        });

        Mail::assertSent(ContactThankYouMail::class, function (ContactThankYouMail $mail) use ($payload) {
            return $mail->hasTo($payload['email'])
                && $mail->data['name'] === $payload['name'];
        });
        $this->assertDatabaseHas('crm_leads', ['email' => $payload['email'], 'lead_origin' => 'website']);
    }

    public function test_career_form_sends_team_and_applicant_emails(): void
    {
        Mail::fake();

        $payload = [
            'name' => 'Priya Applicant',
            'email' => 'priya@example.com',
            'phone' => '+91 91111 11111',
            'linkedin' => 'https://www.linkedin.com/in/priya-applicant',
            'role' => 'Admissions Counsellor',
            'experience' => '3 years',
            'message' => 'I have worked with study abroad applicants.',
            'resume_link' => 'https://example.com/priya-resume.pdf',
            'consent' => '1',
        ];

        $this->postJson('/careers', $payload)
            ->assertOk()
            ->assertJsonPath('ok', true);

        Mail::assertSent(CareerApplicationMail::class, function (CareerApplicationMail $mail) use ($payload) {
            return $mail->hasTo(config('site.forms.careers.to'))
                && $mail->data['email'] === $payload['email']
                && $mail->attachments() === [];
        });

        Mail::assertSent(CareerThankYouMail::class, function (CareerThankYouMail $mail) use ($payload) {
            return $mail->hasTo($payload['email'])
                && $mail->data['role'] === $payload['role'];
        });
        $this->assertDatabaseHas('crm_leads', ['email' => $payload['email'], 'lead_origin' => 'website']);
    }

    public function test_contact_and_career_leads_are_retained_when_email_delivery_fails(): void
    {
        Mail::shouldReceive('mailer')->times(4)->andThrow(new RuntimeException('SMTP offline'));

        $this->postJson('/contact', [
            'name' => 'Offline Contact', 'email' => 'offline-contact@example.com', 'phone' => '9000000001',
            'level' => 'Postgraduate', 'consent' => '1',
        ])->assertOk()->assertJsonPath('ok', true);

        $this->postJson('/careers', [
            'name' => 'Offline Applicant', 'email' => 'offline-career@example.com', 'phone' => '9000000002',
            'role' => 'Counsellor', 'experience' => '2 years', 'message' => 'Application details',
            'resume_link' => 'https://example.com/resume.pdf', 'consent' => '1',
        ])->assertOk()->assertJsonPath('ok', true);

        $this->assertDatabaseHas('crm_leads', ['email' => 'offline-contact@example.com', 'lead_type' => 'general']);
        $this->assertDatabaseHas('crm_leads', ['email' => 'offline-career@example.com', 'lead_type' => 'general']);
        $this->assertDatabaseCount('crm_website_submissions', 2);
    }

    public function test_mail_doctor_accepts_complete_smtp_configuration(): void
    {
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp.example.com',
            'mail.mailers.smtp.port' => 587,
            'mail.mailers.smtp.scheme' => 'smtp',
            'mail.mailers.smtp.username' => 'Admissions@onedegreeadvisory.com',
            'mail.mailers.smtp.password' => 'secret',
            'mail.from.address' => 'Admissions@onedegreeadvisory.com',
            'site.forms.contact.mailer' => null,
            'site.forms.contact.to' => 'Admissions@onedegreeadvisory.com',
            'site.forms.contact.from' => 'Admissions@onedegreeadvisory.com',
            'site.forms.careers.mailer' => null,
            'site.forms.careers.to' => 'Smita@onedegreeadvisory.com',
            'site.forms.careers.from' => 'Smita@onedegreeadvisory.com',
        ]);

        $this->artisan('mail:doctor')
            ->expectsOutput('Mail configuration looks ready for direct SMTP delivery.')
            ->assertExitCode(0);
    }

    public function test_mail_test_flow_sends_real_contact_and_careers_confirmations(): void
    {
        Mail::fake();

        config([
            'mail.default' => 'array',
            'mail.mailers.array.transport' => 'array',
            'site.forms.contact.mailer' => 'array',
            'site.forms.contact.to' => 'Admissions@onedegreeadvisory.com',
            'site.forms.contact.from' => 'Admissions@onedegreeadvisory.com',
            'site.forms.careers.mailer' => 'array',
            'site.forms.careers.to' => 'Smita@onedegreeadvisory.com',
            'site.forms.careers.from' => 'Smita@onedegreeadvisory.com',
        ]);

        $this->artisan('mail:test-flow', [
            'type' => 'contact',
            'email' => 'student@example.com',
            '--team-to' => 'team@example.com',
        ])->assertExitCode(0);

        $this->artisan('mail:test-flow', [
            'type' => 'careers',
            'email' => 'applicant@example.com',
            '--team-to' => 'hr@example.com',
        ])->assertExitCode(0);

        Mail::assertSent(ContactEnquiryMail::class, fn (ContactEnquiryMail $mail) => $mail->hasTo('team@example.com'));
        Mail::assertSent(ContactThankYouMail::class, fn (ContactThankYouMail $mail) => $mail->hasTo('student@example.com'));
        Mail::assertSent(CareerApplicationMail::class, fn (CareerApplicationMail $mail) => $mail->hasTo('hr@example.com'));
        Mail::assertSent(CareerThankYouMail::class, fn (CareerThankYouMail $mail) => $mail->hasTo('applicant@example.com'));
    }
}
