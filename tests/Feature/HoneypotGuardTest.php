<?php

namespace Tests\Feature;

use App\Mail\ContactEnquiryMail;
use App\Mail\ReferralTeamMail;
use App\Models\CrmLead;
use App\Models\CrmSpamAttempt;
use App\Models\CrmUser;
use App\Models\CrmWebsiteSubmission;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The honeypot field shared by every public lead form (App\Support\HoneypotGuard):
 * a hidden input real visitors never fill. A submission that fills it must be
 * turned away silently — no lead, no mail — while still being logged (with IP)
 * so repeat bot traffic is visible in the CRM's "Blocked submissions" view.
 */
class HoneypotGuardTest extends TestCase
{
    private function migrate(): void
    {
        if (! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The PHP SQLite PDO driver is not installed.');
        }

        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    public function test_a_filled_honeypot_on_the_referral_form_is_silently_turned_away(): void
    {
        $this->migrate();
        Mail::fake();

        $response = $this->postJson(route('referral.submit'), [
            'referrer_name' => 'Meera Iyer',
            'referrer_phone' => '+91 90000 11111',
            'referrer_email' => 'meera@mailbox.test',
            'student_name' => 'Rohan Gupta',
            'student_phone' => '+91 98765 43210',
            'student_email' => 'rohan@mailbox.test',
            'level' => 'Master',
            'country' => 'Germany',
            'consent' => true,
            'website' => 'http://spam-bot.example',
        ]);

        // The bot sees a normal success response — nothing hints it was caught.
        $response->assertOk()->assertJson(['ok' => true]);

        Mail::assertNothingSent();
        $this->assertSame(0, CrmLead::query()->count());
        $this->assertSame(0, CrmWebsiteSubmission::query()->count());

        $attempt = CrmSpamAttempt::query()->firstOrFail();
        $this->assertSame('referral', $attempt->source);
        $this->assertSame('127.0.0.1', $attempt->ip_address);
        $this->assertSame('Rohan Gupta', $attempt->payload['student_name'] ?? null);
        $this->assertArrayNotHasKey('website', $attempt->payload);
    }

    public function test_a_filled_honeypot_on_the_contact_form_is_silently_turned_away(): void
    {
        $this->migrate();
        Mail::fake();

        $this->postJson(route('contact.submit'), [
            'name' => 'Rohan',
            'email' => 'rohan@mailbox.test',
            'phone' => '+91 98765 43210',
            'level' => 'Undergraduate',
            'website' => 'spam',
        ])->assertOk()->assertJson(['ok' => true]);

        Mail::assertNotSent(ContactEnquiryMail::class);
        $this->assertSame(0, CrmLead::query()->count());
        $this->assertSame(1, CrmSpamAttempt::query()->where('source', 'contact')->count());
    }

    public function test_a_normal_referral_submission_records_the_submitters_ip(): void
    {
        $this->migrate();
        Mail::fake();

        $this->postJson(route('referral.submit'), [
            'referrer_name' => 'Meera Iyer',
            'referrer_phone' => '+91 90000 11111',
            'referrer_email' => 'meera@mailbox.test',
            'student_name' => 'Rohan Gupta',
            'student_phone' => '+91 98765 43210',
            'student_email' => 'rohan@mailbox.test',
            'level' => 'Master',
            'country' => 'Germany',
            'consent' => true,
        ])->assertOk()->assertJson(['ok' => true]);

        $submission = CrmWebsiteSubmission::query()->where('source', 'referral')->firstOrFail();
        $this->assertSame('127.0.0.1', $submission->ip_address);
        $this->assertSame(0, CrmSpamAttempt::query()->count());
        Mail::assertSent(ReferralTeamMail::class);
    }

    public function test_super_admin_can_see_blocked_submissions_in_the_crm(): void
    {
        $this->migrate();

        $admin = CrmUser::query()->create([
            'name' => 'Admin', 'phone' => '9876543210', 'role' => 'super_admin', 'is_active' => true,
        ]);

        $this->postJson(route('referral.submit'), [
            'referrer_name' => 'Meera Iyer',
            'referrer_phone' => '+91 90000 11111',
            'referrer_email' => 'meera@mailbox.test',
            'student_name' => 'Rohan Gupta',
            'student_phone' => '+91 98765 43210',
            'student_email' => 'rohan@mailbox.test',
            'level' => 'Master',
            'country' => 'Germany',
            'consent' => true,
            'website' => 'http://spam-bot.example',
        ])->assertOk();

        $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'spam']))
            ->assertOk()
            ->assertSee('Blocked submissions')
            ->assertSee('Referral', false)
            ->assertSee('127.0.0.1');
    }
}
