<?php

namespace Tests\Feature;

use App\Models\CrmLead;
use App\Models\CrmSubscriber;
use App\Models\CrmUser;
use App\Models\CrmWebsiteSubmission;
use App\Models\PaymentAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CrmWebsiteLeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_website_forms_create_and_enrich_one_crm_lead(): void
    {
        $this->postJson(route('loan-acco.lead'), [
            'form' => 'loan', 'name' => 'Aarav Sharma', 'email' => 'aarav@mailbox.test',
            'phone' => '+91 98765 43210', 'country' => 'United Kingdom', 'course' => 'MBA',
        ])->assertOk();

        $this->postJson(route('visa-mock.lead'), [
            'name' => 'Aarav Sharma', 'email' => 'aarav@mailbox.test', 'phone' => '9876543210',
            'destination' => 'United Kingdom', 'level' => 'Postgraduate',
        ])->assertOk();

        $this->assertDatabaseCount('crm_leads', 1);
        $this->assertDatabaseCount('crm_website_submissions', 2);
        $lead = CrmLead::query()->sole();
        $this->assertSame('9876543210', $lead->phone);
        $this->assertSame('United Kingdom', $lead->country_interest);
        $this->assertSame('website', $lead->lead_origin);
        $this->assertSame('visa_mock_interview', $lead->lead_type);
        $this->assertCount(2, $lead->websiteSubmissions);
    }

    public function test_conflicting_phone_and_email_are_never_merged_into_one_lead(): void
    {
        CrmLead::query()->create([
            'lead_number' => 'OD-10001', 'name' => 'Phone Owner', 'phone' => '9876543210',
            'email' => 'phone-owner@mailbox.test', 'priority' => 'medium', 'status' => 'new',
        ]);
        CrmLead::query()->create([
            'lead_number' => 'OD-10002', 'name' => 'Email Owner', 'phone' => '9876543211',
            'email' => 'email-owner@mailbox.test', 'priority' => 'medium', 'status' => 'new',
        ]);

        $this->postJson(route('loan-acco.lead'), [
            'form' => 'loan', 'name' => 'Conflicting Person', 'phone' => '9876543210',
            'email' => 'email-owner@mailbox.test',
        ])->assertUnprocessable()->assertJsonValidationErrors('contact');

        $this->assertDatabaseCount('crm_leads', 2);
        $this->assertDatabaseCount('crm_website_submissions', 0);
        $this->assertDatabaseHas('crm_leads', ['lead_number' => 'OD-10001', 'name' => 'Phone Owner']);
        $this->assertDatabaseHas('crm_leads', ['lead_number' => 'OD-10002', 'name' => 'Email Owner']);
    }

    public function test_newsletter_is_a_deduplicated_subscriber_and_not_a_lead(): void
    {
        $payload = ['email' => 'reader@mailbox.test', 'source' => 'Blog newsletter'];
        $this->postJson(route('newsletter.subscribe'), $payload)->assertOk();
        $this->postJson(route('newsletter.subscribe'), $payload)->assertOk();

        $this->assertDatabaseCount('crm_leads', 0);
        $this->assertDatabaseCount('crm_website_submissions', 0);
        $this->assertDatabaseCount('crm_subscribers', 1);
        $this->assertDatabaseHas('crm_subscribers', ['email' => 'reader@mailbox.test', 'source' => 'Blog newsletter', 'status' => 'active']);
    }

    public function test_crm_manages_website_submissions_and_exports_while_legacy_routes_are_gone(): void
    {
        $submission = app(\App\Services\WebsiteLeadManager::class)->capture('sop', 'Statement of Purpose', 'Visa SOP', [[
            'eyebrow' => 'SOP', 'title' => 'Strategy call',
            'answers' => [['label' => 'Service needed', 'value' => ['Visa SOP']]],
        ]], ['name' => 'Mira Patel', 'email' => 'mira@mailbox.test']);
        $admin = CrmUser::query()->create(['name' => 'CRM Owner', 'phone' => '9999999999', 'email' => 'owner@mailbox.test', 'role' => 'super_admin', 'is_active' => true]);
        $session = ['crm_user_id' => $admin->id];

        $this->withSession($session)->get(route('crm.dashboard', ['view' => 'leads', 'lead_type' => 'statement_of_purpose']))
            ->assertOk()->assertSee('Mira Patel')->assertSee('Statement of purpose')->assertSee('Website');
        $this->withSession($session)->get(route('crm.website.export.csv'))->assertOk()->assertDownload();
        $this->withSession($session)->get(route('crm.website.export.excel'))->assertOk()->assertDownload();
        $this->withSession($session)->get(route('crm.website.download', $submission))->assertOk()->assertDownload();

        CrmSubscriber::query()->create(['email' => 'reader@mailbox.test', 'source' => 'Blog newsletter', 'status' => 'active', 'subscribed_at' => now()]);
        $this->withSession($session)->get(route('crm.dashboard', ['view' => 'subscriptions']))
            ->assertOk()->assertSee('Subscriptions')->assertSee('Newsletter subscriptions')->assertSee('reader@mailbox.test')->assertDontSee('Mira Patel');
        $this->withSession($session)->get(route('crm.subscribers.export'))->assertOk()->assertDownload();

        foreach (['/admin/enrollments', '/admin/enrollments/test-prep', '/admin/submissions/student-profiler', '/admin/submissions/loan-acco', '/admin/submissions/statement-of-purpose', '/admin/submissions/visa-mock', '/admin/newsletter'] as $url) {
            $this->get($url)->assertNotFound();
        }
    }

    public function test_profiler_answers_map_onto_the_lead_academic_background_without_overwriting_manual_edits(): void
    {
        $manager = app(\App\Services\WebsiteLeadManager::class);
        $sections = [[
            'eyebrow' => 'Academic background', 'title' => 'Your academic background',
            'answers' => [
                ['label' => 'Class 10 result (%) — actual', 'value' => ['88.4']],
                ['label' => 'Your overall percentage in 12th Class. If pursuing then expected percentage(* Mandatory)', 'value' => ['91']],
                ['label' => 'Year of Passing 12th class(* Mandatory)', 'value' => ['2021']],
                ['label' => 'Your overall % or CGPA in Bachelors. If pursuing then expected % or CGPA', 'value' => ['8.2']],
                ['label' => 'Year of Passing graduation(* Mandatory)', 'value' => ['2025']],
                ['label' => 'No. of backlogs/repeats (if any) Please mention the exact no of repeats.(* Mandatory)', 'value' => ['2']],
                ['label' => 'Individual score in IELTS / ToEFL / PTE Score (Listening, Reading, Writing, Speaking)', 'value' => ['IELTS', 'Overall: 7.5', 'Listening: 7']],
                ['label' => 'GRE Score', 'value' => ['320 - 330']],
                ['label' => 'GMAT Score', 'value' => ['700+']],
            ],
        ]];

        $lead = $manager->capture('profiler', 'Student Profiler', 'Postgraduate', $sections, [
            'name' => 'Aarav Sharma', 'email' => 'aarav@mailbox.test', 'phone' => '9876500077',
        ])->lead;

        $this->assertSame('88.4', $lead->tenth_score);
        $this->assertSame('91', $lead->twelfth_score);
        $this->assertSame(2021, $lead->twelfth_passing_year);
        $this->assertSame('8.2', $lead->graduation_score);
        $this->assertSame(2025, $lead->graduation_passing_year);
        $this->assertSame('2', $lead->backlogs);
        $this->assertSame([['test' => 'ielts', 'name' => null, 'score' => '7.5', 'date' => null]], $lead->english_tests);
        $this->assertSame([
            ['test' => 'gre', 'name' => null, 'score' => '320 - 330', 'date' => null],
            ['test' => 'gmat', 'name' => null, 'score' => '700+', 'date' => null],
        ], $lead->aptitude_tests);
        // Never asked on the website — left for the counsellor to fill in.
        $this->assertNull($lead->tenth_passing_year);

        $lead->update(['tenth_score' => '90 (verified marksheet)', 'tenth_passing_year' => 2019]);
        $manager->capture('profiler', 'Student Profiler', 'Postgraduate', $sections, [
            'name' => 'Aarav Sharma', 'email' => 'aarav@mailbox.test', 'phone' => '9876500077',
        ]);

        $this->assertSame('90 (verified marksheet)', $lead->fresh()->tenth_score);
        $this->assertSame(2019, $lead->fresh()->tenth_passing_year);
    }

    public function test_enrollment_payment_is_classified_without_becoming_a_website_submission(): void
    {
        $attempt = PaymentAttempt::query()->create([
            'request_token' => str_repeat('a', 64), 'session_hash' => str_repeat('b', 64),
            'page_slug' => 'test-preparation', 'block_id' => 'compare-plans', 'option_index' => 1,
            'item_name' => 'IELTS Coaching', 'amount' => 1800000, 'currency' => 'INR',
            'customer_name' => 'Rhea Kapoor', 'customer_email' => 'rhea@mailbox.test',
            'customer_phone' => '9876543210', 'status' => 'order_created',
        ]);

        $lead = app(\App\Services\WebsiteLeadManager::class)->capturePayment($attempt);
        $this->assertSame('enrollment', $lead->lead_origin);
        $this->assertSame('enrollment', $lead->lead_type);
        $this->assertDatabaseCount('crm_website_submissions', 0);
        $this->assertDatabaseHas('payment_attempts', ['id' => $attempt->id, 'crm_lead_id' => $lead->id]);

        $admin = CrmUser::query()->create(['name' => 'CRM Owner', 'phone' => '9999999999', 'email' => 'owner@mailbox.test', 'role' => 'super_admin', 'is_active' => true]);
        $this->withSession(['crm_user_id' => $admin->id])->get(route('crm.dashboard', [
            'view' => 'enrollments', 'enrollment_source' => 'test-preparation', 'payment_status' => 'order_created',
        ]))->assertOk()->assertSee('Rhea Kapoor')->assertSee('IELTS Coaching')->assertSee('Test Preparation')->assertSee('Awaiting payment');
    }

    public function test_super_admin_can_manage_subscriber_status(): void
    {
        $subscriber = CrmSubscriber::query()->create(['email' => 'reader@mailbox.test', 'source' => 'Blog newsletter', 'status' => 'active', 'subscribed_at' => now()]);
        $admin = CrmUser::query()->create(['name' => 'CRM Owner', 'phone' => '9999999999', 'email' => 'owner@mailbox.test', 'role' => 'super_admin', 'is_active' => true]);

        $this->withSession(['crm_user_id' => $admin->id])->patch(route('crm.subscribers.update', $subscriber), ['status' => 'unsubscribed'])->assertRedirect();
        $this->assertDatabaseHas('crm_subscribers', ['id' => $subscriber->id, 'status' => 'unsubscribed']);
    }

    public function test_counsellors_cannot_see_or_export_subscribers(): void
    {
        CrmSubscriber::query()->create(['email' => 'private-reader@mailbox.test', 'source' => 'Blog newsletter', 'status' => 'active', 'subscribed_at' => now()]);
        $counsellor = CrmUser::query()->create(['name' => 'Counsellor', 'phone' => '9999999998', 'email' => 'counsellor@mailbox.test', 'role' => 'counsellor', 'is_active' => true]);
        $session = ['crm_user_id' => $counsellor->id];

        $this->withSession($session)->get(route('crm.dashboard', ['view' => 'subscriptions']))
            ->assertOk()
            ->assertDontSee('Newsletter subscriptions')
            ->assertDontSee('private-reader@mailbox.test')
            ->assertDontSee('nav-subscriptions', false);
        $this->withSession($session)->get(route('crm.subscribers.export'))->assertForbidden();
    }
}
