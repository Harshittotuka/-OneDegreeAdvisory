<?php

namespace Tests\Feature;

use App\Mail\ProfileReportTeamMail;
use App\Mail\ProfileReportThankYouMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * On submit, the Student Profiler (/profiler) sends a team notification + an
 * applicant thank-you, each carrying the generated profile report. Direct
 * SMTP, no queue.
 */
class ProfileReportMailTest extends TestCase
{
    use RefreshDatabase;

    public function test_profiler_submit_sends_team_and_student_emails(): void
    {
        Mail::fake();

        $this->post('/profiler', [
            'action'  => 'submit',
            'degree'  => 'masters',
            'section' => 6,
            'answers' => ['q_ec_level' => 'Just Participated'],
            'contact' => ['name' => 'Alex Student', 'email' => 'alex@example.com', 'phone' => '+91 90000 00000'],
        ])->assertOk()->assertJson(['ok' => true]);

        Mail::assertSent(ProfileReportTeamMail::class, function (ProfileReportTeamMail $mail) {
            return $mail->hasTo(config('site.forms.profiler.to'))
                && $mail->data['sourceLabel'] === 'Student Profiler'
                && $mail->data['degreeLabel'] === 'Master’s'
                && $mail->pdf !== null
                && $mail->hasAttachment(
                    \Illuminate\Mail\Mailables\Attachment::fromData(fn () => $mail->pdf, $mail->pdfName)->withMime('application/pdf')
                );
        });

        Mail::assertSent(ProfileReportThankYouMail::class, function (ProfileReportThankYouMail $mail) {
            return $mail->hasTo('alex@example.com')
                && $mail->data['name'] === 'Alex Student'
                && $mail->pdf !== null;
        });
    }

    public function test_no_thank_you_without_a_valid_email(): void
    {
        Mail::fake();

        $this->post('/profiler', [
            'action'  => 'submit',
            'degree'  => 'masters',
            'section' => 6,
            'answers' => ['q_ec_level' => 'Just Participated'],
            'contact' => ['name' => 'No Email', 'email' => '', 'phone' => '123'],
        ])->assertOk()->assertJson(['ok' => true]);

        // Team still notified; the applicant thank-you is skipped (no address).
        Mail::assertSent(ProfileReportTeamMail::class);
        Mail::assertNotSent(ProfileReportThankYouMail::class);
    }

    public function test_invalid_degree_sends_no_profiler_email(): void
    {
        Mail::fake();

        $this->post('/profiler', [
            'action'  => 'submit',
            'degree'  => 'hacker',
            'section' => 1,
            'answers' => ['x' => 'y'],
            'contact' => ['name' => 'X', 'email' => 'x@example.com', 'phone' => '1'],
        ])->assertOk()->assertJson(['ok' => true]);

        Mail::assertNothingSent();
    }
}
