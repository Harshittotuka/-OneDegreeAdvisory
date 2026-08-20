<?php

namespace Tests\Feature;

use App\Http\Controllers\ReferralController;
use App\Mail\ReferralReferrerMail;
use App\Mail\ReferralStudentMail;
use App\Mail\ReferralTeamMail;
use App\Models\CrmLead;
use App\Models\CrmWebsiteSubmission;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * The Referral Program page (/referral-program): its render, the Student Hub
 * entry points that reach it, the self-hosted flag sprite that replaced 23
 * cross-origin images, and the referral submission — which is the only form on
 * the site that captures two people, so the CRM shape it produces (student as
 * the lead, referrer recorded alongside) is worth pinning down.
 */
class ReferralProgramTest extends TestCase
{
    private function migrate(): void
    {
        if (! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The PHP SQLite PDO driver is not installed.');
        }

        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    /** A complete, valid submission. */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'referrer_name' => 'Meera Iyer',
            'referrer_phone' => '+91 90000 11111',
            'referrer_email' => 'meera@mailbox.test',
            'student_name' => 'Rohan Gupta',
            'student_phone' => '+91 98765 43210',
            'student_email' => 'rohan@mailbox.test',
            'level' => 'Master',
            'country' => 'Germany',
            'notes' => 'Targeting the September intake.',
            'consent' => '1',
        ], $overrides);
    }

    /* ─────────────────────── Public page ─────────────────────── */

    public function test_the_page_renders(): void
    {
        $this->get(route('referral'))
            ->assertOk()
            ->assertSee('Refer a student.')
            ->assertSee('Earn rewards.')
            ->assertSee('Anyone in your circle can refer')
            ->assertSee('Spin &amp; discover your destination', false)
            ->assertSee('Submit a referral in minutes')
            ->assertSee('The fine print, made simple');
    }

    public function test_the_form_offers_every_level_and_country_the_controller_accepts(): void
    {
        $html = $this->get(route('referral'))->assertOk()->getContent();

        foreach (ReferralController::LEVELS as $level) {
            $this->assertStringContainsString('<option value="'.e($level).'"', $html, "Missing level option: {$level}");
        }
        foreach (ReferralController::COUNTRIES as $country) {
            $this->assertStringContainsString('<option value="'.e($country).'"', $html, "Missing country option: {$country}");
        }
    }

    /**
     * The 23 flags used to be 23 cross-origin requests from flagcdn.com — and 46
     * <img> elements once the marquee duplicates the list. They are now one local
     * sprite, positioned by index.
     */
    public function test_flags_come_from_one_local_sprite(): void
    {
        $html = $this->get(route('referral'))->assertOk()->getContent();

        $this->assertStringContainsString('assets/referral/flags.webp', $html);
        $this->assertFileExists(public_path('assets/referral/flags.webp'));

        // Scoped to this page's own container: the shared navbar's Destinations
        // mega-menu still loads its flags from flagcdn on every page of the site,
        // which is pre-existing chrome and not what this test is about.
        $own = substr($html, (int) strpos($html, 'id="ref-page"'));
        $own = substr($own, 0, (int) strpos($own, '</main>'));
        $this->assertDoesNotMatchRegularExpression('~<(img|source)[^>]+flagcdn~', $own);

        // The sprite URL lives in the page's CSS; the elements only carry their
        // cell index. All 23 marquee flags are rendered twice (the marquee needs
        // a duplicate copy to loop seamlessly), plus 8 on the wheel.
        $this->assertStringContainsString('--rf-flag-cells:23', $html);
        $this->assertStringContainsString('class="rf-cloth rf-flag" style="--i:0"', $own);
        $this->assertSame(23 * 2 + 8, substr_count($own, 'rf-flag" style="--i:'));
    }

    public function test_the_page_pulls_no_third_party_script_or_stylesheet(): void
    {
        $html = $this->get(route('referral'))->assertOk()->getContent();

        // AOS, the design's CDN scroll-animation library, is gone.
        $this->assertStringNotContainsString('unpkg.com/aos', $html);

        preg_match_all('~<script[^>]+src="([^"]+)"~', $html, $scripts);
        $this->assertNotEmpty($scripts[1]);
        foreach ($scripts[1] as $src) {
            $this->assertStringStartsWith(url('/'), $src, "Third-party script: {$src}");
        }
    }

    /* ─────────────────────── Student Hub entry points ─────────────────────── */

    public function test_the_page_is_reachable_from_the_student_hub_nav_and_the_hero_quick_links(): void
    {
        // The nav dropdown appears on every page; the hero drawer is home only.
        $this->get(route('referral'))
            ->assertOk()
            ->assertSee('Referral Program')
            ->assertSee('href="'.route('referral').'"', false);

        $home = $this->get(route('home'))->assertOk()->getContent();
        $this->assertStringContainsString('href="'.route('referral').'"', $home);
        // The dropdown's own tool count has to keep up with the card list.
        $this->assertStringContainsString('6 tools', $home);
        // Both entry points list it, so the link appears at least twice.
        $this->assertGreaterThanOrEqual(2, substr_count($home, route('referral')));
    }

    /* ─────────────────────── Submission ─────────────────────── */

    public function test_a_referral_records_the_student_as_the_lead_with_the_referrer_attached(): void
    {
        $this->migrate();

        $this->postJson(route('referral.submit'), $this->payload())
            ->assertOk()
            ->assertJson(['ok' => true]);

        $submission = CrmWebsiteSubmission::query()->where('source', 'referral')->firstOrFail();
        $this->assertSame('Referral Program', $submission->source_label);

        // The STUDENT is the lead — they are the prospect a counsellor works.
        $lead = CrmLead::query()->where('email', 'rohan@mailbox.test')->firstOrFail();
        $this->assertSame('Rohan Gupta', $lead->name);
        $this->assertSame('9876543210', $lead->phone);
        $this->assertSame('referral', $lead->lead_type);
        $this->assertSame('website', $lead->lead_origin);
        // Labelled "Study level" / "Preferred country" so they land on the lead.
        $this->assertSame('Master', $lead->course_interest);
        $this->assertSame('Germany', $lead->country_interest);

        // The referrer must NOT become a lead of their own...
        $this->assertNull(CrmLead::query()->where('email', 'meera@mailbox.test')->first());
        // ...but must be recorded, or nobody knows who to pay.
        $sections = $submission->sections;
        $this->assertCount(2, $sections);
        $referrer = collect($sections[1]['answers'])->pluck('value')->flatten()->all();
        $this->assertContains('Meera Iyer', $referrer);
        $this->assertContains('meera@mailbox.test', $referrer);
        $this->assertContains('+91 90000 11111', $referrer);
    }

    /* ─────────────────────── Emails ─────────────────────── */

    public function test_one_referral_sends_three_emails(): void
    {
        $this->migrate();
        Mail::fake();

        $this->postJson(route('referral.submit'), $this->payload())->assertOk();

        // 1. The admissions team, with the referrer on Reply-To so a thank-you
        //    goes to the person waiting on one.
        Mail::assertSent(ReferralTeamMail::class, function (ReferralTeamMail $mail) {
            return $mail->hasTo(config('site.forms.referral.to'))
                && $mail->hasReplyTo('meera@mailbox.test')
                && $mail->data['student_name'] === 'Rohan Gupta';
        });

        // 2. The person who filled the form in.
        Mail::assertSent(ReferralReferrerMail::class, fn (ReferralReferrerMail $mail) => $mail->hasTo('meera@mailbox.test'));

        // 3. The referred student.
        Mail::assertSent(ReferralStudentMail::class, function (ReferralStudentMail $mail) {
            return $mail->hasTo('rohan@mailbox.test')
                // Named in the subject so the introduction never reads as spam.
                && str_contains($mail->envelope()->subject, 'Meera Iyer');
        });

        Mail::assertSentCount(3);
    }

    public function test_the_student_introduction_can_be_switched_off_per_environment(): void
    {
        $this->migrate();
        Mail::fake();
        config()->set('site.forms.referral.notify_student', false);

        $this->postJson(route('referral.submit'), $this->payload())->assertOk();

        // The team and the referrer still hear about it; the student does not.
        Mail::assertSent(ReferralTeamMail::class);
        Mail::assertSent(ReferralReferrerMail::class);
        Mail::assertNotSent(ReferralStudentMail::class);
    }

    public function test_a_mail_failure_never_fails_the_submission(): void
    {
        $this->migrate();

        // A mailer that always throws — the referral is already in the CRM by the
        // time mail is attempted, so the visitor must still see success.
        Mail::shouldReceive('mailer')->andThrow(new \RuntimeException('SMTP down'));

        $this->postJson(route('referral.submit'), $this->payload())
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseHas('crm_leads', ['email' => 'rohan@mailbox.test']);
    }

    public function test_a_rejected_referral_sends_nothing(): void
    {
        $this->migrate();
        Mail::fake();

        // Self-referral.
        $this->postJson(route('referral.submit'), $this->payload(['student_email' => 'meera@mailbox.test']))
            ->assertStatus(422);

        Mail::assertNothingSent();
    }

    public function test_a_second_referral_of_the_same_student_attaches_to_the_existing_lead(): void
    {
        $this->migrate();

        $this->postJson(route('referral.submit'), $this->payload())->assertOk();
        $this->postJson(route('referral.submit'), $this->payload([
            'referrer_name' => 'Second Referrer',
            'referrer_phone' => '+91 90000 22222',
            'referrer_email' => 'second@mailbox.test',
        ]))->assertOk();

        // One student, one lead — but both referrals on file, so a counsellor can
        // see who submitted first (the terms credit the earlier one).
        $this->assertSame(1, CrmLead::query()->where('email', 'rohan@mailbox.test')->count());
        $this->assertSame(2, CrmWebsiteSubmission::query()->where('source', 'referral')->count());
    }

    public function test_a_self_referral_is_rejected(): void
    {
        $this->migrate();

        // Same email for referrer and student.
        $this->postJson(route('referral.submit'), $this->payload(['student_email' => 'meera@mailbox.test']))
            ->assertStatus(422)
            ->assertJsonPath('title', 'Self-referrals are not eligible');

        // Same phone, written differently — still the same person.
        $this->postJson(route('referral.submit'), $this->payload(['student_phone' => '9000011111']))
            ->assertStatus(422);

        $this->assertDatabaseCount('crm_website_submissions', 0);
    }

    public function test_consent_is_required(): void
    {
        $this->migrate();

        $payload = $this->payload();
        unset($payload['consent']);

        $this->postJson(route('referral.submit'), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('consent');

        $this->assertDatabaseCount('crm_website_submissions', 0);
    }

    public function test_submission_validates_both_parties_and_the_dropdowns(): void
    {
        $this->migrate();

        $this->postJson(route('referral.submit'), [
            'referrer_name' => '',
            'referrer_email' => 'nope',
            'student_email' => 'also-nope',
            'level' => 'Astronaut',
            'country' => 'Atlantis',
            'consent' => '1',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors([
                'referrer_name', 'referrer_phone', 'referrer_email',
                'student_name', 'student_phone', 'student_email',
                'level', 'country',
            ]);

        $this->assertDatabaseCount('crm_website_submissions', 0);
    }
}
