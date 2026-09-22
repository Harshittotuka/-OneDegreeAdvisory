<?php

namespace Tests\Feature;

use App\Http\Middleware\CrmAuth;
use App\Models\CrmRememberToken;
use App\Models\CrmUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * "Keep me signed in" on the CRM sign-in screen: a device that ticked the box
 * skips the OTP until the token lapses, and every route out of that state —
 * signing out, losing access, the token expiring — closes it again.
 */
class CrmRememberDeviceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
    }

    public function test_a_remembered_device_reaches_the_dashboard_without_an_otp(): void
    {
        $user = $this->counsellor();
        $secret = $this->signIn($user);

        $this->assertNotNull($secret);
        $this->assertDatabaseCount('crm_remember_tokens', 1);
        // The secret itself is never written down — only its hash.
        $this->assertDatabaseMissing('crm_remember_tokens', ['token_hash' => $secret]);

        $this->flushSession();

        $this->withCookie(CrmAuth::REMEMBER_COOKIE, $secret)
            ->get(route('crm.dashboard'))
            ->assertOk()
            ->assertSessionHas('crm_user_id', $user->id);
    }

    public function test_the_sign_in_screen_sends_a_remembered_device_straight_through(): void
    {
        $user = $this->counsellor();
        $secret = $this->signIn($user);
        $this->flushSession();

        $this->withCookie(CrmAuth::REMEMBER_COOKIE, $secret)
            ->get(route('crm.login'))
            ->assertRedirect(route('crm.dashboard'));
    }

    public function test_leaving_the_box_unticked_issues_no_cookie(): void
    {
        $user = $this->counsellor();
        $response = $this->completeOtp($user, remember: false);

        $response->assertRedirect(route('crm.dashboard'));
        $this->assertNull($response->getCookie(CrmAuth::REMEMBER_COOKIE));
        $this->assertDatabaseCount('crm_remember_tokens', 0);
    }

    public function test_signing_out_revokes_only_the_device_it_was_done_on(): void
    {
        $user = $this->counsellor();
        $laptop = $this->signIn($user);
        $this->flushSession();
        $phone = $this->signIn($user);
        $this->flushSession();

        $this->assertDatabaseCount('crm_remember_tokens', 2);

        $this->withSession(['crm_user_id' => $user->id])
            ->withCookie(CrmAuth::REMEMBER_COOKIE, $laptop)
            ->post(route('crm.logout'))
            ->assertRedirect(route('crm.login'))
            ->assertCookieExpired(CrmAuth::REMEMBER_COOKIE);

        $this->assertDatabaseCount('crm_remember_tokens', 1);
        $this->flushSession();

        // The laptop is out; the phone was never signed out and still gets in.
        $this->withCookie(CrmAuth::REMEMBER_COOKIE, $laptop)
            ->get(route('crm.dashboard'))
            ->assertRedirect(route('crm.login'));

        $this->flushSession();
        $this->withCookie(CrmAuth::REMEMBER_COOKIE, $phone)
            ->get(route('crm.dashboard'))
            ->assertOk();
    }

    public function test_a_lapsed_token_buys_nothing(): void
    {
        $user = $this->counsellor();
        $secret = $this->signIn($user);
        CrmRememberToken::query()->update(['expires_at' => now()->subDay()]);
        $this->flushSession();

        $this->withCookie(CrmAuth::REMEMBER_COOKIE, $secret)
            ->get(route('crm.dashboard'))
            ->assertRedirect(route('crm.login'));
    }

    public function test_a_forged_cookie_buys_nothing(): void
    {
        $this->counsellor();

        $this->withCookie(CrmAuth::REMEMBER_COOKIE, str_repeat('a', 64))
            ->get(route('crm.dashboard'))
            ->assertRedirect(route('crm.login'));
    }

    public function test_withdrawing_access_turns_the_remembered_devices_away(): void
    {
        $user = $this->counsellor();
        $secret = $this->signIn($user);
        $this->flushSession();

        $admin = CrmUser::query()->create([
            'name' => 'Super Admin', 'phone' => '9000000002', 'email' => 'super@mailbox.test',
            'role' => 'super_admin', 'is_active' => true,
        ]);

        $this->withSession(['crm_user_id' => $admin->id])
            ->patch(route('crm.team.toggle', $user))
            ->assertRedirect();

        $this->assertDatabaseCount('crm_remember_tokens', 0);
        $this->flushSession();

        $this->withCookie(CrmAuth::REMEMBER_COOKIE, $secret)
            ->get(route('crm.dashboard'))
            ->assertRedirect(route('crm.login'));
    }

    private function counsellor(): CrmUser
    {
        return CrmUser::query()->create([
            'name' => 'Remembered Counsellor', 'phone' => '9000000001', 'email' => 'remembered@mailbox.test',
            'role' => 'counsellor', 'is_active' => true,
        ]);
    }

    /** Sign in with "keep me signed in" ticked and hand back the cookie's secret. */
    private function signIn(CrmUser $user): ?string
    {
        return $this->completeOtp($user, remember: true)->getCookie(CrmAuth::REMEMBER_COOKIE)?->getValue();
    }

    private function completeOtp(CrmUser $user, bool $remember): TestResponse
    {
        $this->post(route('crm.otp.request'), ['login' => $user->email])
            ->assertSessionHas('crm_otp_user_id', $user->id);

        $payload = ['otp' => session('debug_otp')];
        if ($remember) {
            $payload['remember'] = '1';
        }

        return $this->post(route('crm.otp.verify'), $payload);
    }
}
