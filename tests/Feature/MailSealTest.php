<?php

namespace Tests\Feature;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Mail\Transport\ArrayTransport;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * A test run on 26 Aug 2026 delivered live referral, payment and profiler mail
 * to the client's inbox: MAIL_MAILER=array only swapped the DEFAULT mailer,
 * while every form calls Mail::mailer('contact_form') and friends by name -
 * hardcoded smtp transports reading credentials straight from .env.
 *
 * These tests deliberately do NOT call Mail::fake(), because their whole point
 * is to prove the app is safe without it. They also avoid RefreshDatabase, so
 * that they still run - and still prove the seal - when APP_ENV is not
 * "testing" and PHPUnit's own <env> overrides therefore do not apply.
 */
class MailSealTest extends TestCase
{
    /** Every mailer name the app can pass to Mail::mailer(). */
    private function mailerNames(): array
    {
        return [
            'site.forms.contact.mailer',
            'site.forms.careers.mailer',
            'site.forms.profiler.mailer',
            'site.forms.referral.mailer',
            'site.payment_section_otp.mailer',
            'crm.email.mailer',
        ];
    }

    public function test_no_configured_mailer_can_reach_a_mail_server(): void
    {
        foreach ((array) config('mail.mailers', []) as $name => $mailer) {
            $this->assertSame('array', ((array) $mailer)['transport'] ?? null, "Mailer [{$name}] is not sealed.");
        }
    }

    public function test_every_mailer_the_app_selects_resolves_to_the_array_transport(): void
    {
        foreach ($this->mailerNames() as $key) {
            $transport = Mail::mailer(config($key))->getSymfonyTransport();

            $this->assertInstanceOf(ArrayTransport::class, $transport, "Mail sent via config [{$key}] would leave the machine.");
        }
    }

    public function test_a_real_form_submission_is_captured_instead_of_sent(): void
    {
        // The mailer is what is under test, not the HTTP stack - and CSRF
        // only self-disables when APP_ENV is "testing", so drop the middleware
        // to keep this passing in any environment.
        $this->withoutMiddleware();
        Artisan::call('migrate', ['--force' => true]);

        $mailer = Mail::mailer(config('site.forms.referral.mailer'));
        $before = $mailer->getSymfonyTransport()->messages()->count();

        $this->postJson(route('referral.submit'), [
            'referrer_name' => 'Seal Check',
            'referrer_email' => 'seal-referrer@mailbox.test',
            'referrer_phone' => '+91 90000 12345',
            'student_name' => 'Seal Student',
            'student_email' => 'seal-student@mailbox.test',
            'student_phone' => '+91 98765 54321',
            'level' => 'Master',
            'country' => 'Germany',
            'consent' => '1',
        ])->assertOk();

        // The mails were built and handed to a transport that keeps them in
        // memory - nothing opened a socket.
        $this->assertGreaterThan($before, $mailer->getSymfonyTransport()->messages()->count());
    }
}
