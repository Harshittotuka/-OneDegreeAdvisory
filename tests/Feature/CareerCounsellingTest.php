<?php

namespace Tests\Feature;

use App\Models\CrmLead;
use App\Models\CrmWebsiteSubmission;
use App\Support\CareerCounsellingStore;
use App\Support\HeroContent;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The Career Counselling page (/career-counselling): its file-backed CMS store,
 * the public render, the admin editor round-trip, the server-priced Razorpay
 * order flow (a plan price can only ever come from the CMS file, never from the
 * browser), the CRM lead capture, and the home-hero link that reaches it.
 */
class CareerCounsellingTest extends TestCase
{
    private string $storePath;

    /** A copy of the real JSON (if any), restored after each test. */
    private ?string $backup = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storePath = storage_path('app/career-counselling.json');
        $this->backup = is_file($this->storePath) ? file_get_contents($this->storePath) : null;
        @unlink($this->storePath); // each test starts from the seeded defaults
    }

    protected function tearDown(): void
    {
        if ($this->backup !== null) {
            file_put_contents($this->storePath, $this->backup);
        } else {
            @unlink($this->storePath);
        }

        parent::tearDown();
    }

    private function store(): CareerCounsellingStore
    {
        return new CareerCounsellingStore();
    }

    private function migrate(): void
    {
        if (! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The PHP SQLite PDO driver is not installed.');
        }

        Artisan::call('migrate:fresh', ['--force' => true]);
    }

    /* ─────────────────────── Store ─────────────────────── */

    public function test_store_seeds_the_plan_roster_and_prices(): void
    {
        $data = $this->store()->get();

        $this->assertSame(['Class 8–9', 'Class 10–12'], array_column($data['stages'], 'label'));
        $this->assertCount(4, $data['plans']);
        $this->assertSame(['Explore', 'Learn', 'Explore', 'Learn'], array_column($data['plans'], 'name'));

        // The design's prices: Explore 1 session ₹7,000 / 3 sessions ₹11,000, Learn ₹2,500.
        $this->assertSame([7000, 11000], array_column($data['plans'][0]['tiers'], 'price'));
        $this->assertSame([2500], array_column($data['plans'][1]['tiers'], 'price'));

        // The seed file is written on first read so the CMS opens populated.
        $this->assertFileExists($this->storePath);
    }

    public function test_normalize_drops_nameless_plans_and_bounds_prices(): void
    {
        $saved = $this->store()->save([
            'stages' => [['label' => 'Class 9']],
            'plans' => [
                ['name' => 'Explore', 'tiers' => [['label' => '1 Session', 'price' => '₹7,000']]],
                ['name' => '', 'tiers' => [['label' => 'x', 'price' => '9999']]],  // dropped: no name
                ['name' => 'Odd', 'tiers' => [['label' => '', 'price' => 'abc'], ['label' => 'Huge', 'price' => '99999999999']]],
            ],
        ]);

        $this->assertCount(2, $saved['plans']);
        // "₹7,000" parsed to a whole-rupee int.
        $this->assertSame(7000, $saved['plans'][0]['tiers'][0]['price']);
        // Non-numeric → 0 ("fee on request"); absurd → clamped, not stored raw.
        $this->assertSame(0, $saved['plans'][1]['tiers'][0]['price']);
        $this->assertSame(10_000_000, $saved['plans'][1]['tiers'][1]['price']);
    }

    public function test_normalize_clamps_a_plan_to_an_existing_stage_and_keeps_one_stage(): void
    {
        $saved = $this->store()->save([
            'stages' => [['label' => 'Only tab'], ['label' => '']], // blank label removed
            'plans' => [['name' => 'Explore', 'stage' => 7, 'tiers' => [['label' => '', 'price' => '2500']]]],
        ]);

        $this->assertCount(1, $saved['stages']);
        // A stage index past the end would point the card at a tab that does not
        // exist; it is clamped into range instead.
        $this->assertSame(0, $saved['plans'][0]['stage']);

        // Wiping every stage falls back to the seeded tabs — the strip needs one.
        $this->assertNotEmpty($this->store()->save(['stages' => [], 'plans' => []])['stages']);
    }

    public function test_a_plan_always_keeps_one_price_row(): void
    {
        $saved = $this->store()->save([
            'stages' => [['label' => 'S']],
            'plans' => [['name' => 'Explore', 'tiers' => []]],
        ]);

        $this->assertSame([['label' => '', 'price' => 0]], $saved['plans'][0]['tiers']);
    }

    public function test_payable_options_skip_hidden_plans_and_free_tiers(): void
    {
        $this->store()->save([
            'stages' => [['label' => 'Class 9']],
            'plans' => [
                ['name' => 'Hidden', 'visible' => false, 'tiers' => [['label' => '', 'price' => '5000']]],
                ['name' => 'Explore', 'visible' => true, 'tiers' => [
                    ['label' => 'On request', 'price' => '0'],
                    ['label' => '3 Sessions', 'price' => '11000'],
                ]],
            ],
        ]);

        $options = $this->store()->payableOptions();

        // The hidden plan and the ₹0 tier occupy no index at all: index 0 is the
        // first *payable* pair, so a client index can never buy a hidden plan.
        $this->assertCount(1, $options);
        $this->assertSame(0, $options[0]['index']);
        $this->assertSame('Explore', $options[0]['plan_name']);
        $this->assertSame('3 Sessions', $options[0]['tier_label']);
        $this->assertSame(11000, $options[0]['price']);
        $this->assertNull($this->store()->payableOptionAt(1));
    }

    public function test_option_label_names_the_order_line(): void
    {
        $this->assertSame(
            'Career Counselling · Class 10–12 · Explore (3 Sessions)',
            CareerCounsellingStore::optionLabel([
                'stage_label' => 'Class 10–12', 'plan_name' => 'Explore', 'tier_label' => '3 Sessions',
            ])
        );

        // A single-tier plan has no tier label, so no empty parentheses.
        $this->assertSame(
            'Career Counselling · Class 8–9 · Learn',
            CareerCounsellingStore::optionLabel([
                'stage_label' => 'Class 8–9', 'plan_name' => 'Learn', 'tier_label' => '',
            ])
        );
    }

    /* ─────────────────────── Public page ─────────────────────── */

    public function test_public_page_renders_the_cms_plans_and_prices(): void
    {
        $this->get(route('career-counselling'))
            ->assertOk()
            ->assertSee('Clarity, Confidence, and the Right Guidance')
            ->assertSee('Plans &amp; Pricing', false)
            ->assertSee('Class 8–9')
            ->assertSee('Class 10–12')
            ->assertSee('₹7,000')
            ->assertSee('₹11,000')
            ->assertSee('₹2,500')
            ->assertSee('data-page-slug="career-counselling"', false)
            ->assertSee('data-block-id="plans"', false);
    }

    public function test_a_cms_price_change_shows_on_the_public_page(): void
    {
        $this->store()->save([
            'heading' => ['title' => 'Our programs'],
            'stages' => [['label' => 'Class 11–12']],
            'plans' => [['name' => 'Deep Dive', 'visible' => true, 'tiers' => [['label' => '2 Sessions', 'price' => '13750']]]],
        ]);

        $this->get(route('career-counselling'))
            ->assertOk()
            ->assertSee('Our programs')
            ->assertSee('Deep Dive')
            ->assertSee('₹13,750')
            ->assertDontSee('₹7,000');
    }

    public function test_a_hidden_plan_never_reaches_the_public_page(): void
    {
        $this->store()->save([
            'stages' => [['label' => 'Class 9']],
            'plans' => [
                ['name' => 'Retired Plan', 'visible' => false, 'tiers' => [['label' => '', 'price' => '4000']]],
                ['name' => 'Live Plan', 'visible' => true, 'tiers' => [['label' => '', 'price' => '5000']]],
            ],
        ]);

        $this->get(route('career-counselling'))
            ->assertOk()
            ->assertSee('Live Plan')
            ->assertDontSee('Retired Plan');
    }

    public function test_the_live_pay_button_appears_only_when_razorpay_is_configured(): void
    {
        config()->set('services.razorpay.key_id', null);
        config()->set('services.razorpay.key_secret', null);
        $this->get(route('career-counselling'))
            ->assertOk()
            // No handshake warmed for a gateway that cannot be used.
            ->assertDontSee('preconnect" href="https://checkout.razorpay.com', false)
            ->assertSee('Request a callback');

        config()->set('services.razorpay.key_id', 'rzp_test_x');
        config()->set('services.razorpay.key_secret', 'secret_x');
        $this->get(route('career-counselling'))
            ->assertOk()
            ->assertSee('data-cc-pay', false)
            ->assertSee('preconnect" href="https://checkout.razorpay.com', false);
    }

    /**
     * The page pulls nothing from a third-party origin at load time: the hero
     * globe is drawn in canvas 2D (it used to fetch 150 KB of three.js) and
     * Razorpay Checkout (60 KB) is fetched only once a checkout is opened.
     */
    public function test_no_third_party_script_is_fetched_up_front(): void
    {
        config()->set('services.razorpay.key_id', 'rzp_test_x');
        config()->set('services.razorpay.key_secret', 'secret_x');

        $html = $this->get(route('career-counselling'))->assertOk()->getContent();

        $this->assertStringNotContainsString('three.min.js', $html, 'three.js is back on the critical path.');
        $this->assertDoesNotMatchRegularExpression(
            '~<script[^>]+src="https://checkout\.razorpay\.com~',
            $html,
            'Razorpay Checkout is being fetched eagerly again; it must load on demand.'
        );
        // The on-demand loader and the warmed handshake are what replace it.
        $this->assertStringContainsString('loadRazorpay', $html);

        // Every script the page loads is served from this origin.
        preg_match_all('~<script[^>]+src="([^"]+)"~', $html, $matches);
        $this->assertNotEmpty($matches[1]);
        foreach ($matches[1] as $src) {
            $this->assertStringStartsWith(url('/'), $src, "Third-party script on load: {$src}");
        }
    }

    public function test_images_are_served_as_webp_with_a_jpeg_fallback(): void
    {
        $this->get(route('career-counselling'))
            ->assertOk()
            // The India artwork also offers a 2x file for retina screens.
            ->assertSee('india-map@2x.webp 1800w', false)
            ->assertSee('india-map.jpg', false)
            ->assertSee('counselling-clarity.webp', false)
            ->assertSee('counselling-clarity.jpg', false);
    }

    public function test_the_standalone_contact_band_was_removed(): void
    {
        // The phone / email / office details live in the shared footer instead.
        $this->get(route('career-counselling'))
            ->assertOk()
            ->assertDontSee('Prefer to reach out directly?')
            ->assertDontSee('cc-contact-card', false);
    }

    /* ─────────────────────── Home hero link ─────────────────────── */

    public function test_the_home_hero_career_mentoring_button_links_to_the_page(): void
    {
        $hero = (new HeroContent())->forDisplay();
        $action = collect($hero['actions'])->firstWhere('label', 'Career Mentoring');

        $this->assertNotNull($action, 'The hero no longer has a "Career Mentoring" button.');
        $this->assertSame('/career-counselling', $action['href']);
        $this->assertNotSame('disabled', $action['style'], 'The button must be clickable, not a "coming soon" placeholder.');

        // The rendered home page carries a real link, not the disabled <span>.
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('href="/career-counselling"', false);
    }

    /* ─────────────────────── Admin CMS ─────────────────────── */

    public function test_admin_can_open_and_save_the_plans_editor(): void
    {
        $this->withSession(['cms_authenticated' => true])
            ->get(route('admin.career-counselling.index'))
            ->assertOk()
            ->assertSee('Stage tabs')
            ->assertSee('Plan cards')
            ->assertSee('Checkout dialog');

        $this->withSession(['cms_authenticated' => true])
            ->post(route('admin.career-counselling.update'), [
                'heading' => ['eyebrow' => 'Plans', 'title' => 'Pick a plan', 'subtitle' => 'Sub'],
                'stages' => [['label' => 'Class 9'], ['label' => 'Class 12']],
                'plans' => [
                    [
                        'name' => 'Explore', 'subtitle' => 'Assessment + Counselling', 'badge' => 'Popular',
                        'stage' => '1', 'visible' => '1', 'featured' => '1',
                        'feature_title' => ['Career Assessment', 'Mentor'],
                        'feature_text' => ['Five dimensions.', 'One-to-one.'],
                        'feature_locked' => ['included', 'locked'],
                        'tier_label' => ['1 Session', '3 Sessions'],
                        'tier_price' => ['8000', '12500'],
                    ],
                    ['name' => '', 'tier_price' => ['999']], // dropped
                ],
                'payment' => ['title' => 'Confirm your plan', 'accent' => '#123456', 'button_label' => 'Pay now'],
            ])
            ->assertRedirect(route('admin.career-counselling.index'))
            ->assertSessionHas('status');

        $data = $this->store()->get();
        $this->assertSame('Pick a plan', $data['heading']['title']);
        $this->assertCount(1, $data['plans']);

        $plan = $data['plans'][0];
        $this->assertSame('Explore', $plan['name']);
        $this->assertSame(1, $plan['stage']);
        $this->assertTrue($plan['featured']);
        $this->assertSame([8000, 12500], array_column($plan['tiers'], 'price'));
        // The parallel feature arrays zip back into rows with their own flag.
        $this->assertFalse($plan['features'][0]['locked']);
        $this->assertTrue($plan['features'][1]['locked']);
        $this->assertSame('#123456', $data['payment']['accent']);
    }

    public function test_admin_editor_requires_authentication(): void
    {
        $this->get(route('admin.career-counselling.index'))->assertRedirect(route('admin.login'));
        $this->post(route('admin.career-counselling.update'), [])->assertRedirect(route('admin.login'));
    }

    public function test_admin_save_rejects_an_invalid_accent_colour(): void
    {
        $this->withSession(['cms_authenticated' => true])
            ->post(route('admin.career-counselling.update'), ['payment' => ['accent' => 'red']])
            ->assertSessionHasErrors('payment.accent');
    }

    /* ─────────────────────── Payment flow ─────────────────────── */

    public function test_a_plan_purchase_is_priced_from_the_cms_not_the_browser(): void
    {
        $this->migrate();

        config()->set('services.razorpay.key_id', 'rzp_test_public123');
        config()->set('services.razorpay.key_secret', 'test_secret_456');
        $this->withCredentials()->withCookie((string) config('session.cookie'), str_repeat('s', 40));

        $this->store()->save([
            'stages' => [['label' => 'Class 10–12']],
            'plans' => [['name' => 'Explore', 'stage' => 0, 'visible' => true, 'tiers' => [
                ['label' => '1 Session', 'price' => '7000'],
                ['label' => '3 Sessions', 'price' => '11000'],
            ]]],
        ]);

        Http::fake([
            'https://api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_cc123', 'amount' => 1_100_000, 'currency' => 'INR',
            ], 200),
        ]);

        // The client sends only the option index it rendered; the amount comes
        // from the store — index 1 is the ₹11,000 tier = 1,100,000 paise.
        $order = $this->postJson(route('payments.order'), [
            'page_slug' => CareerCounsellingStore::PAGE_SLUG,
            'block_id' => CareerCounsellingStore::BLOCK_ID,
            'option_index' => 1,
            'name' => 'Test Parent',
            'email' => 'parent@mailbox.test',
            'phone' => '+91 9876543210',
        ])->assertOk();

        $this->assertDatabaseHas('payment_attempts', [
            'request_token' => (string) $order->json('token'),
            'page_slug' => CareerCounsellingStore::PAGE_SLUG,
            'block_id' => CareerCounsellingStore::BLOCK_ID,
            'item_name' => 'Career Counselling · Class 10–12 · Explore (3 Sessions)',
            'amount' => 1_100_000,
            'status' => 'order_created',
        ]);

        // Starting checkout files the buyer in the CRM as an enrolment lead.
        $lead = CrmLead::query()->where('email', 'parent@mailbox.test')->firstOrFail();
        $this->assertSame('career_counselling', $lead->category);
        $this->assertSame('enrollment', $lead->lead_origin);
        $this->assertSame('Career Counselling · Class 10–12 · Explore (3 Sessions)', $lead->course_interest);
    }

    public function test_an_out_of_range_or_unpayable_option_is_rejected(): void
    {
        $this->migrate();

        config()->set('services.razorpay.key_id', 'rzp_test_public123');
        config()->set('services.razorpay.key_secret', 'test_secret_456');
        $this->withCredentials()->withCookie((string) config('session.cookie'), str_repeat('s', 40));

        $this->store()->save([
            'stages' => [['label' => 'Class 9']],
            'plans' => [['name' => 'On request only', 'visible' => true, 'tiers' => [['label' => '', 'price' => '0']]]],
        ]);

        // The only tier is ₹0, so there is no payable option 0 at all.
        $this->postJson(route('payments.order'), [
            'page_slug' => CareerCounsellingStore::PAGE_SLUG,
            'block_id' => CareerCounsellingStore::BLOCK_ID,
            'option_index' => 0,
            'name' => 'Test Parent',
            'email' => 'parent@mailbox.test',
        ])->assertStatus(422);

        // A wrong block id cannot borrow the page's pricing either.
        $this->postJson(route('payments.order'), [
            'page_slug' => CareerCounsellingStore::PAGE_SLUG,
            'block_id' => 'not-plans',
            'option_index' => 0,
            'name' => 'Test Parent',
            'email' => 'parent@mailbox.test',
        ])->assertStatus(422);

        $this->assertDatabaseCount('payment_attempts', 0);
    }

    /* ─────────────────────── CRM lead capture ─────────────────────── */

    public function test_the_consultation_form_records_a_crm_lead(): void
    {
        $this->migrate();

        $this->postJson(route('career-counselling.lead'), [
            'name' => 'Asha Verma',
            'email' => 'asha@mailbox.test',
            'phone' => '+91 98765 43210',
            'stage' => 'Class 10–12',
            'message' => 'Deciding between engineering and design.',
        ])->assertOk()->assertJson(['ok' => true]);

        $submission = CrmWebsiteSubmission::query()->where('source', 'career-counselling')->firstOrFail();
        $this->assertSame('Career Counselling', $submission->source_label);

        $lead = CrmLead::query()->where('email', 'asha@mailbox.test')->firstOrFail();
        $this->assertSame('career_counselling', $lead->lead_type);
        $this->assertSame('career_counselling', $lead->category);
        $this->assertSame('website', $lead->lead_origin);
        $this->assertSame('9876543210', $lead->phone);

        // The answers the counsellor needs are stored, not just the contact row.
        $answers = collect($submission->sections[0]['answers'] ?? [])->pluck('label')->all();
        $this->assertContains('Study level', $answers);
        $this->assertContains('What they want help with', $answers);
    }

    public function test_the_consultation_form_validates_contact_details(): void
    {
        $this->migrate();

        $this->postJson(route('career-counselling.lead'), ['name' => '', 'email' => 'not-an-email'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email']);

        $this->assertDatabaseCount('crm_website_submissions', 0);
    }
}
