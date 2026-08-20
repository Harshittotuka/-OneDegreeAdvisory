<?php

namespace Tests\Feature;

use App\Models\PaymentAttempt;
use App\Models\CrmUser;
use App\Services\WebsiteLeadManager;
use App\Support\TestPrepCompareStore;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The Test-Prep "Compare & enrol" section: file-backed CMS store, the four
 * visual styles, the admin editor round-trip, and the server-priced Razorpay
 * order flow (compare prices resolve through PaymentBlockResolver's sentinel
 * slug, so a student can only ever be charged the CMS price).
 */
class TestPrepCompareTest extends TestCase
{
    private string $storePath;

    /** A copy of the real JSON (if any), restored after each test. */
    private ?string $backup = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storePath = storage_path('app/test-prep-compare.json');
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

    private function store(): TestPrepCompareStore
    {
        return new TestPrepCompareStore();
    }

    /* ─────────────────────── Store ─────────────────────── */

    public function test_store_seeds_defaults_with_the_program_roster(): void
    {
        $data = $this->store()->get();

        $this->assertSame('bars', $data['style']);
        $this->assertNotEmpty($data['programs']);
        $names = array_column($data['programs'], 'name');
        $this->assertContains('IELTS', $names);
        $this->assertContains('GRE', $names);
    }

    public function test_normalize_drops_nameless_rows_bounds_values_and_validates_style(): void
    {
        $saved = $this->store()->save([
            'style' => 'not-a-real-style',
            'heading' => ['title' => 'Hi'],
            'programs' => [
                ['name' => 'IELTS', 'price' => '4,000', 'months' => '1.5'],
                ['name' => '', 'price' => '9999'],                       // dropped (no name)
                ['name' => 'Weird', 'price' => 'abc', 'months' => '999'],
            ],
        ]);

        // Unknown style falls back to the default.
        $this->assertSame('bars', $saved['style']);
        // Nameless row dropped; two remain.
        $this->assertCount(2, $saved['programs']);
        // Price string "4,000" parsed to int 4000.
        $this->assertSame(4000, $saved['programs'][0]['price']);
        $this->assertSame(1.5, $saved['programs'][0]['months']);
        // Non-numeric price → 0; months capped at 60.
        $this->assertSame(0, $saved['programs'][1]['price']);
        $this->assertLessThanOrEqual(60.0, $saved['programs'][1]['months']);
        // Category/classes are no longer stored.
        $this->assertArrayNotHasKey('category', $saved['programs'][0]);
        $this->assertArrayNotHasKey('classes', $saved['programs'][0]);
    }

    public function test_visible_program_index_maps_to_the_visible_list_only(): void
    {
        $this->store()->save([
            'style' => 'bars',
            'programs' => [
                ['name' => 'Hidden', 'price' => '5000', 'visible' => false],
                ['name' => 'Shown', 'price' => '6000', 'visible' => true],
            ],
        ]);

        $store = $this->store();
        // Index 0 of the *visible* list is "Shown", not the hidden first row.
        $this->assertSame('Shown', $store->visibleProgramAt(0)['name']);
        $this->assertNull($store->visibleProgramAt(1));
    }

    /* ─────────────────────── Public page ─────────────────────── */

    public function test_public_page_renders_the_active_style_variant(): void
    {
        foreach (['bars' => 'tpc--bars', 'cards' => 'tpc--cards', 'table' => 'tpc--table', 'stack' => 'tpc--stack'] as $style => $marker) {
            // The program name only becomes a popup trigger when the row carries
            // details (title or tagline) — so the fixture sets one.
            $this->store()->save(['style' => $style, 'programs' => [[
                'name' => 'IELTS', 'price' => '4000', 'months' => '1', 'visible' => true,
                'details' => ['title' => 'IELTS Academic', 'tagline' => 'Four modules, band 0-9.'],
            ]]]);

            $this->get(route('services.test-prep'))
                ->assertOk()
                ->assertSee($marker, false)
                ->assertSee('IELTS')
                ->assertSee('tpc-program-name-btn', false)
                // data-tpc-exam carries the program's index in the *visible*
                // list (not an exam slug); one visible program means index 0.
                ->assertSee('data-tpc-exam="0"', false)
                ->assertDontSee('Server-verified amount')
                ->assertDontSee('verified on our server before checkout.');
        }
    }

    public function test_public_page_shows_the_enquiry_fallback_when_razorpay_unconfigured(): void
    {
        config()->set('services.razorpay.key_id', null);
        config()->set('services.razorpay.key_secret', null);

        $this->get(route('services.test-prep'))
            ->assertOk()
            ->assertSee('tpc-enquire-link', false)
            ->assertDontSee('checkout.razorpay.com', false);
    }

    public function test_public_page_loads_razorpay_checkout_when_configured(): void
    {
        config()->set('services.razorpay.key_id', 'rzp_test_x');
        config()->set('services.razorpay.key_secret', 'secret_x');

        $this->get(route('services.test-prep'))
            ->assertOk()
            ->assertSee('data-tpc-pay', false)
            ->assertSee('checkout.razorpay.com', false);
    }

    /* ─────────────────────── Admin CMS ─────────────────────── */

    public function test_admin_can_open_and_save_the_compare_editor(): void
    {
        $this->withSession(['cms_authenticated' => true])
            ->get(route('admin.test-prep-compare.index'))
            ->assertOk()
            ->assertSee('Section style')
            ->assertSee('Programs');

        $this->withSession(['cms_authenticated' => true])
            ->post(route('admin.test-prep-compare.update'), [
                'style' => 'cards',
                'heading' => ['eyebrow' => 'Compare', 'title' => 'Pick a plan', 'subtitle' => 'Sub'],
                'programs' => [
                    ['name' => 'IELTS Academic', 'price' => '4500', 'months' => '1.5', 'badge' => 'Best value', 'visible' => '1'],
                    ['name' => '', 'price' => '0'], // dropped
                ],
                'payment' => ['title' => 'Reserve your seat', 'accent' => '#123456', 'button_label' => 'Pay now'],
            ])
            ->assertRedirect(route('admin.test-prep-compare.index'))
            ->assertSessionHas('status');

        $data = $this->store()->get();
        $this->assertSame('cards', $data['style']);
        $this->assertCount(1, $data['programs']);
        $this->assertSame('IELTS Academic', $data['programs'][0]['name']);
        $this->assertSame(4500, $data['programs'][0]['price']);
        $this->assertSame('#123456', $data['payment']['accent']);
    }

    public function test_admin_editor_requires_authentication(): void
    {
        $this->get(route('admin.test-prep-compare.index'))->assertRedirect(route('admin.login'));
    }

    public function test_admin_save_rejects_an_invalid_style(): void
    {
        $this->withSession(['cms_authenticated' => true])
            ->post(route('admin.test-prep-compare.update'), ['style' => 'bogus', 'programs' => []])
            ->assertSessionHasErrors('style');
    }

    /* ─────────────────────── Payment flow ─────────────────────── */

    public function test_compare_enrolment_creates_a_server_priced_razorpay_order(): void
    {
        if (! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The PHP SQLite PDO driver is not installed.');
        }
        Artisan::call('migrate:fresh', ['--force' => true]);

        config()->set('services.razorpay.key_id', 'rzp_test_public123');
        config()->set('services.razorpay.key_secret', 'test_secret_456');
        $this->withCredentials()->withCookie((string) config('session.cookie'), str_repeat('s', 40));

        // A known store state: index 0 of the visible list is IELTS @ ₹4,000.
        $this->store()->save([
            'style' => 'bars',
            'programs' => [
                ['name' => 'IELTS', 'price' => '4000', 'months' => '1', 'visible' => true],
                ['name' => 'GRE', 'price' => '18000', 'months' => '2.5', 'visible' => true],
            ],
        ]);

        Http::fake([
            'https://api.razorpay.com/v1/orders' => Http::response([
                'id' => 'order_tpc123', 'amount' => 400000, 'currency' => 'INR',
            ], 200),
        ]);

        // Even though the client could send any option_index, the amount is
        // resolved from the store — index 1 (GRE) must be ₹18,000 = 1,800,000 paise.
        $order = $this->postJson(route('payments.order'), [
            'page_slug' => TestPrepCompareStore::PAGE_SLUG,
            'block_id' => TestPrepCompareStore::BLOCK_ID,
            'option_index' => 1,
            'name' => 'Test Student',
            'email' => 'student@mailbox.test',
            'phone' => '+91 9876543210',
        ])->assertOk();

        $token = (string) $order->json('token');

        $this->assertDatabaseHas('payment_attempts', [
            'request_token' => $token,
            'page_slug' => TestPrepCompareStore::PAGE_SLUG,
            'block_id' => TestPrepCompareStore::BLOCK_ID,
            'item_name' => 'GRE',
            'amount' => 1_800_000,
            'status' => 'order_created',
        ]);
    }

    public function test_a_program_priced_zero_is_not_payable_online(): void
    {
        if (! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The PHP SQLite PDO driver is not installed.');
        }
        Artisan::call('migrate:fresh', ['--force' => true]);

        config()->set('services.razorpay.key_id', 'rzp_test_public123');
        config()->set('services.razorpay.key_secret', 'test_secret_456');
        $this->withCredentials()->withCookie((string) config('session.cookie'), str_repeat('s', 40));

        $this->store()->save([
            'style' => 'bars',
            'programs' => [['name' => 'Free Counselling', 'price' => '0', 'visible' => true]],
        ]);

        // price 0 → resolver returns null → controller 422.
        $this->postJson(route('payments.order'), [
            'page_slug' => TestPrepCompareStore::PAGE_SLUG,
            'block_id' => TestPrepCompareStore::BLOCK_ID,
            'option_index' => 0,
            'name' => 'Test Student',
            'email' => 'student@mailbox.test',
        ])->assertStatus(422);

        $this->assertDatabaseCount('payment_attempts', 0);
    }

    /* ─────────────────────── Admin: Test-Prep enrolments ─────────────────────── */

    public function test_test_prep_type_filter_offers_cms_and_legacy_program_names(): void
    {
        if (! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The PHP SQLite PDO driver is not installed.');
        }
        Artisan::call('migrate:fresh', ['--force' => true]);

        // CMS lists GRE + IELTS.
        $this->store()->save([
            'style' => 'bars',
            'programs' => [
                ['name' => 'GRE', 'price' => '18000', 'months' => '2.5', 'visible' => true],
                ['name' => 'IELTS', 'price' => '4000', 'months' => '1', 'visible' => true],
            ],
        ]);

        // Two attempts: one for a current program, one for a program that only
        // exists in the historical data (renamed/removed since — must still show).
        $this->seedTestPrepAttempt('GRE', 'a@x.test');
        $this->seedTestPrepAttempt('GRE Coaching (Legacy)', 'b@x.test');

        $admin = CrmUser::create(['name' => 'Owner', 'phone' => '9999999999', 'email' => 'owner@mailbox.test', 'role' => 'super_admin', 'is_active' => true]);
        $html = $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'enrollments']))
            ->assertOk()
            // The dropdown offers both the CMS program and the legacy data name.
            ->assertSee('All programs')
            ->assertSee('GRE Coaching (Legacy)')
            ->assertSee('IELTS')
            ->getContent();

        // Sanity: the legacy program is a real tickable choice in the filter, not
        // just body text that happens to appear in the rows below.
        $this->assertStringContainsString(
            'name="enrollment_plan[]" value="GRE Coaching (Legacy)"',
            preg_replace('/\s+/', ' ', $html),
        );
    }

    public function test_test_prep_type_filter_scopes_the_list_to_one_program(): void
    {
        if (! in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The PHP SQLite PDO driver is not installed.');
        }
        Artisan::call('migrate:fresh', ['--force' => true]);

        $this->store()->save([
            'style' => 'bars',
            'programs' => [['name' => 'GRE', 'price' => '18000', 'months' => '2.5', 'visible' => true]],
        ]);

        $this->seedTestPrepAttempt('GRE', 'gre@x.test', 'GRE Student');
        $this->seedTestPrepAttempt('GRE Coaching (Legacy)', 'legacy@x.test', 'Legacy Student');

        // Filtering by the legacy type shows only that student, not the GRE one.
        $admin = CrmUser::create(['name' => 'Owner', 'phone' => '9999999999', 'email' => 'owner@mailbox.test', 'role' => 'super_admin', 'is_active' => true]);
        $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'enrollments', 'enrollment_plan' => 'GRE Coaching (Legacy)']))
            ->assertOk()
            ->assertSee('Legacy Student')
            ->assertDontSee('GRE Student');

        // An unknown/unoffered type is ignored — the full list comes back.
        $this->withSession(['crm_user_id' => $admin->id])
            ->get(route('crm.dashboard', ['view' => 'enrollments', 'enrollment_plan' => 'Not A Program']))
            ->assertOk()
            ->assertDontSee('Legacy Student')
            ->assertDontSee('GRE Student');
    }

    /** Insert a paid Test-Prep enrolment row directly (bypassing the pay flow). */
    private function seedTestPrepAttempt(string $itemName, string $email, string $name = 'Someone'): void
    {
        $attempt = PaymentAttempt::create([
            'request_token' => bin2hex(random_bytes(16)),
            'session_hash' => str_repeat('a', 64),
            'page_slug' => TestPrepCompareStore::PAGE_SLUG,
            'block_id' => TestPrepCompareStore::BLOCK_ID,
            'option_index' => 0,
            'item_name' => $itemName,
            'amount' => 1_800_000,
            'currency' => 'INR',
            'status' => 'paid',
            'paid_at' => now(),
            'customer_name' => $name,
            'customer_email' => $email,
            'customer_phone' => (string) (9_000_000_000 + (abs(crc32($email)) % 100_000_000)),
        ]);
        app(WebsiteLeadManager::class)->capturePayment($attempt);
    }
}
