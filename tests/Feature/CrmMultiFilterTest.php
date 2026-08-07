<?php

namespace Tests\Feature;

use App\Models\CrmLead;
use App\Models\CrmSubscriber;
use App\Models\CrmUser;
use App\Models\PaymentAttempt;
use App\Support\CrmOptions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The CRM filter bar takes several values per filter. Two values on one filter
 * mean "either"; separate filters still narrow each other.
 */
class CrmMultiFilterTest extends TestCase
{
    use RefreshDatabase;

    private CrmUser $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = CrmUser::query()->create([
            'name' => 'Admin', 'phone' => '9876543210', 'role' => 'super_admin', 'is_active' => true,
        ]);
    }

    private function lead(array $attributes): CrmLead
    {
        static $n = 0;
        $n++;

        return CrmLead::query()->create(array_merge([
            'lead_number' => 'OD-'.str_pad((string) $n, 5, '0', STR_PAD_LEFT),
            'name' => 'Lead '.$n,
            'phone' => '90000'.str_pad((string) $n, 5, '0', STR_PAD_LEFT),
            'status' => 'new',
            'priority' => 'medium',
            'lead_type' => 'general',
        ], $attributes));
    }

    /** @return list<string> */
    private function leadsFor(array $query): array
    {
        $response = $this->withSession(['crm_user_id' => $this->admin->id])
            ->get(route('crm.dashboard', array_merge(['view' => 'leads'], $query)));
        $response->assertOk();

        return $response->viewData('leads')->pluck('name')->sort()->values()->all();
    }

    public function test_two_statuses_return_the_union_of_both(): void
    {
        $this->lead(['name' => 'Ann', 'status' => 'new']);
        $this->lead(['name' => 'Bob', 'status' => 'call_back']);
        $this->lead(['name' => 'Cal', 'status' => 'not_interested']);

        $this->assertSame(['Ann', 'Bob'], $this->leadsFor(['status' => ['new', 'call_back']]));
    }

    public function test_a_single_value_still_arrives_as_a_plain_scalar(): void
    {
        // Every pre-filtered link into the CRM — dashboard cards, notifications,
        // anything bookmarked before the bar took arrays — sends ?status=new.
        $this->lead(['name' => 'Ann', 'status' => 'new']);
        $this->lead(['name' => 'Bob', 'status' => 'call_back']);

        $this->assertSame(['Ann'], $this->leadsFor(['status' => 'new']));
    }

    public function test_different_filters_still_narrow_each_other(): void
    {
        $this->lead(['name' => 'Ann', 'status' => 'new', 'priority' => 'high']);
        $this->lead(['name' => 'Bob', 'status' => 'call_back', 'priority' => 'low']);
        $this->lead(['name' => 'Cal', 'status' => 'new', 'priority' => 'low']);

        $this->assertSame(
            ['Ann'],
            $this->leadsFor(['status' => ['new', 'call_back'], 'priority' => ['high']]),
        );
    }

    public function test_the_follow_up_group_widens_rather_than_replaces_named_statuses(): void
    {
        $this->lead(['name' => 'Ann', 'status' => 'call_back']);       // inside the group
        $this->lead(['name' => 'Bob', 'status' => 'converted']);       // named, outside it
        $this->lead(['name' => 'Cal', 'status' => 'not_interested']);  // neither

        $this->assertSame(
            ['Ann', 'Bob'],
            $this->leadsFor(['status' => [CrmOptions::FOLLOW_UP_GROUP, 'converted']]),
        );
    }

    public function test_not_recorded_can_be_combined_with_a_recorded_value(): void
    {
        $this->lead(['name' => 'Ann', 'counselling' => 'yes']);
        $this->lead(['name' => 'Bob', 'counselling' => 'no']);
        $this->lead(['name' => 'Cal', 'counselling' => null]);

        $this->assertSame(
            ['Ann', 'Cal'],
            $this->leadsFor(['counselling' => ['yes', CrmOptions::NOT_RECORDED]]),
        );
        $this->assertSame(['Cal'], $this->leadsFor(['counselling' => [CrmOptions::NOT_RECORDED]]));
    }

    public function test_owners_and_unassigned_can_be_picked_together(): void
    {
        $one = CrmUser::query()->create(['name' => 'One', 'phone' => '9000000001', 'role' => 'counsellor', 'is_active' => true]);
        $two = CrmUser::query()->create(['name' => 'Two', 'phone' => '9000000002', 'role' => 'counsellor', 'is_active' => true]);

        $this->lead(['name' => 'Ann', 'assigned_to' => $one->id]);
        $this->lead(['name' => 'Bob', 'assigned_to' => $two->id]);
        $this->lead(['name' => 'Cal', 'assigned_to' => null]);

        $this->assertSame(['Ann', 'Cal'], $this->leadsFor(['assigned_to' => [(string) $one->id, 'unassigned']]));
        $this->assertSame(['Cal'], $this->leadsFor(['assigned_to' => 'unassigned']));
    }

    public function test_junk_values_are_ignored_rather_than_emptying_the_list(): void
    {
        $this->lead(['name' => 'Ann', 'status' => 'new']);

        $this->assertSame(['Ann'], $this->leadsFor(['status' => ['not-a-status']]));
        $this->assertSame(['Ann'], $this->leadsFor(['status' => ['', 'new']]));
    }

    public function test_payments_filter_on_several_statuses(): void
    {
        foreach ([['paid', 'A'], ['payment_failed', 'B'], ['order_created', 'C']] as [$status, $name]) {
            PaymentAttempt::query()->create([
                'request_token' => str_repeat(strtolower($name), 64),
                'session_hash' => str_repeat('d', 64),
                'page_slug' => 'test-preparation', 'block_id' => 'b', 'item_name' => $name,
                'option_index' => 0, 'amount' => 1000, 'currency' => 'INR', 'status' => $status,
                'customer_name' => $name, 'customer_email' => strtolower($name).'@example.com',
            ]);
        }

        $response = $this->withSession(['crm_user_id' => $this->admin->id])
            ->get(route('crm.dashboard', ['view' => 'enrollments', 'payment_status' => ['paid', 'payment_failed']]));
        $response->assertOk();

        $this->assertSame(['A', 'B'], $response->viewData('enrollments')->pluck('item_name')->sort()->values()->all());
    }

    public function test_subscriptions_filter_on_several_sources(): void
    {
        foreach ([['a@example.com', 'footer'], ['b@example.com', 'blog'], ['c@example.com', 'popup']] as [$email, $source]) {
            CrmSubscriber::query()->create(['email' => $email, 'source' => $source, 'status' => 'active', 'subscribed_at' => now()]);
        }

        $response = $this->withSession(['crm_user_id' => $this->admin->id])
            ->get(route('crm.dashboard', ['view' => 'subscriptions', 'subscriber_source' => ['footer', 'popup']]));
        $response->assertOk();

        $this->assertSame(['a@example.com', 'c@example.com'], $response->viewData('subscribers')->pluck('email')->sort()->values()->all());
    }

    public function test_the_bar_renders_checkboxes_that_post_an_array(): void
    {
        $response = $this->withSession(['crm_user_id' => $this->admin->id])
            ->get(route('crm.dashboard', ['view' => 'leads', 'status' => ['new', 'call_back']]));

        $response->assertOk()
            ->assertSee('name="status[]"', false)
            ->assertSee('data-mfilter', false)
            // the trigger reports the count rather than just one of the two
            ->assertSee('2 statuses');

        // Both ticks survive the round trip. The markup wraps across lines, so
        // match on the collapsed form rather than on incidental whitespace.
        $flat = preg_replace('/\s+/', ' ', $response->getContent());
        $this->assertStringContainsString('value="new" checked', $flat);
        $this->assertStringContainsString('value="call_back" checked', $flat);
        $this->assertSame(2, substr_count($flat, 'name="status[]" value="new" checked')
            + substr_count($flat, 'name="status[]" value="call_back" checked'));
    }
}
