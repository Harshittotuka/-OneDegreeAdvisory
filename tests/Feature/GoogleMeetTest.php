<?php

namespace Tests\Feature;

use App\Http\Middleware\StudentAuth;
use App\Models\CrmGoogleAccount;
use App\Models\CrmJourneyPlan;
use App\Models\CrmLead;
use App\Models\CrmUser;
use App\Services\GoogleCalendar;
use App\Services\GoogleCalendarException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * Real Google Meet rooms from each counsellor's own Google account, and the
 * connection that has to stay alive for them. Google itself is faked: no
 * test here reaches the network.
 */
class GoogleMeetTest extends TestCase
{
    use RefreshDatabase;

    private const CAL = 'https://www.googleapis.com/calendar/v3/calendars/primary/events';

    protected function setUp(): void
    {
        parent::setUp();
        config([
            'google.client_id' => 'test-client.apps.googleusercontent.com',
            'google.client_secret' => 'test-secret',
            'google.redirect' => null,
        ]);
        Http::preventStrayRequests();
        Mail::fake();
    }

    /* ------------------------------------------------------------ helpers */

    private function user(string $role = 'counsellor'): CrmUser
    {
        static $n = 0;
        $n++;

        return CrmUser::query()->create([
            'name' => ucfirst($role).' '.$n, 'phone' => '98100000'.str_pad((string) $n, 2, '0', STR_PAD_LEFT),
            'email' => "{$role}{$n}@mailbox.test", 'role' => $role, 'is_active' => true,
        ]);
    }

    private function student(CrmUser $counsellor, array $extra = []): CrmLead
    {
        static $n = 0;
        $n++;

        return CrmLead::query()->create(array_merge([
            'lead_number' => 'OD-3'.str_pad((string) $n, 4, '0', STR_PAD_LEFT), 'name' => 'Ananya Rao', 'phone' => '97100000'.str_pad((string) $n, 2, '0', STR_PAD_LEFT),
            'email' => "gstudent{$n}@mailbox.test", 'priority' => 'medium', 'status' => 'converted',
            'is_student' => true, 'student_stage' => 'doc_pending', 'assigned_to' => $counsellor->id, 'intake' => 'Fall 2027',
        ], $extra));
    }

    private function as(CrmUser $user): static
    {
        return $this->withSession(['crm_user_id' => $user->id]);
    }

    private function started(CrmUser $counsellor, array $extra = []): CrmLead
    {
        $lead = $this->student($counsellor, $extra);
        $this->as($counsellor)->post(route('crm.journey.start', $lead))->assertRedirect();

        return $lead;
    }

    private function connected(CrmUser $user, array $extra = []): CrmGoogleAccount
    {
        return CrmGoogleAccount::query()->create(array_merge([
            'crm_user_id' => $user->id,
            'google_email' => 'priya.counsellor@gmail.com',
            'refresh_token' => '1//refresh-token-that-lasts',
            'access_token' => 'ya29.current',
            'access_token_expires_at' => now()->addHour(),
            'scopes' => 'openid email https://www.googleapis.com/auth/calendar.events',
            'connected_at' => now()->subMonth(),
            'last_refreshed_at' => now(),
        ], $extra));
    }

    private function idToken(string $email): string
    {
        $part = fn (array $a) => rtrim(strtr(base64_encode(json_encode($a)), '+/', '-_'), '=');

        return $part(['alg' => 'RS256']).'.'.$part(['email' => $email, 'email_verified' => true]).'.sig';
    }

    /** Google's calendar answering every call; returns the requests made. */
    private function fakeCalendar(string $link = 'https://meet.google.com/abc-defg-hij', array $token = []): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response($token ?: ['access_token' => 'ya29.renewed', 'expires_in' => 3599, 'token_type' => 'Bearer']),
            'www.googleapis.com/calendar/*' => function (HttpRequest $request) use ($link) {
                return match ($request->method()) {
                    'POST' => Http::response(['id' => 'evt'.substr(md5((string) microtime(true)), 0, 10), 'hangoutLink' => $link, 'conferenceData' => ['entryPoints' => [['entryPointType' => 'video', 'uri' => $link]]]]),
                    'PATCH' => Http::response(['id' => 'patched']),
                    'DELETE' => Http::response(null, 204),
                    default => Http::response([], 404),
                };
            },
        ]);
    }

    private function planOf(CrmLead $lead): CrmJourneyPlan
    {
        return CrmJourneyPlan::query()->where('crm_lead_id', $lead->id)->firstOrFail();
    }

    /* ------------------------------------------------------------ connecting */

    public function test_connecting_asks_google_for_a_lasting_connection(): void
    {
        $counsellor = $this->user();

        $response = $this->as($counsellor)->get(route('crm.google.connect', ['back' => '/crm/students/1/planner#calendar']));
        $location = (string) $response->headers->get('Location');
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $location);
        parse_str((string) parse_url($location, PHP_URL_QUERY), $q);

        // Offline + forced consent is what makes Google issue a refresh token
        // every time, which is what keeps the connection alive.
        $this->assertSame('offline', $q['access_type']);
        $this->assertSame('consent', $q['prompt']);
        $this->assertStringContainsString('https://www.googleapis.com/auth/calendar.events', $q['scope']);
        $this->assertSame(route('crm.google.callback'), $q['redirect_uri']);
        $this->assertSame($response->getSession()->get('google_oauth')['state'], $q['state']);
    }

    public function test_the_redirect_back_is_always_https_outside_localhost(): void
    {
        config(['app.url' => 'http://onedegreeadvisory.89.116.134.165.nip.io']);
        URL::forceRootUrl('http://onedegreeadvisory.89.116.134.165.nip.io');

        $this->assertSame('https://onedegreeadvisory.89.116.134.165.nip.io/crm/google/callback', app(GoogleCalendar::class)->redirectUri());

        URL::forceRootUrl('http://localhost');
        $this->assertSame('http://localhost/crm/google/callback', app(GoogleCalendar::class)->redirectUri());
    }

    public function test_a_partner_cannot_connect_and_nothing_happens_without_google_set_up(): void
    {
        $this->as($this->user('partner'))->get(route('crm.google.connect'))->assertForbidden();

        config(['google.client_id' => null]);
        $this->as($this->user())->get(route('crm.google.connect', ['back' => '/crm']))
            ->assertRedirect('/crm')->assertSessionHas('google_status.ok', false);
    }

    public function test_the_callback_stores_the_connection_encrypted(): void
    {
        $counsellor = $this->user();
        Http::fake(['oauth2.googleapis.com/token' => Http::response([
            'access_token' => 'ya29.first', 'expires_in' => 3599, 'refresh_token' => '1//the-refresh-token',
            'scope' => 'openid https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/calendar.events',
            'id_token' => $this->idToken('Priya.Counsellor@gmail.com'),
        ])]);

        $this->as($counsellor)->withSession(['google_oauth' => ['state' => 'abc123', 'back' => '/crm/students/9/planner#calendar', 'user' => $counsellor->id, 'at' => now()->timestamp]])
            ->get(route('crm.google.callback', ['state' => 'abc123', 'code' => '4/the-code']))
            ->assertRedirect('/crm/students/9/planner#calendar')
            ->assertSessionHas('google_status.ok', true);

        $account = $counsellor->fresh()->googleAccount;
        $this->assertSame('priya.counsellor@gmail.com', $account->google_email);
        $this->assertSame('1//the-refresh-token', $account->refresh_token);
        $this->assertTrue($account->works());

        // At rest, neither token is readable.
        $raw = DB::table('crm_google_accounts')->first();
        $this->assertStringNotContainsString('the-refresh-token', $raw->refresh_token);
        $this->assertStringNotContainsString('ya29.first', $raw->access_token);

        Http::assertSent(fn (HttpRequest $r) => $r['grant_type'] === 'authorization_code' && $r['code'] === '4/the-code' && $r['redirect_uri'] === route('crm.google.callback'));
    }

    public function test_the_callback_refuses_a_forged_or_stale_state(): void
    {
        $counsellor = $this->user();
        $this->as($counsellor)->withSession(['google_oauth' => ['state' => 'right', 'back' => '/crm', 'user' => $counsellor->id, 'at' => now()->timestamp]])
            ->get(route('crm.google.callback', ['state' => 'wrong', 'code' => 'x']))
            ->assertRedirect('/crm')->assertSessionHas('google_status.ok', false);

        $this->as($counsellor)->withSession(['google_oauth' => ['state' => 'right', 'back' => '/crm', 'user' => $counsellor->id, 'at' => now()->subHour()->timestamp]])
            ->get(route('crm.google.callback', ['state' => 'right', 'code' => 'x']))
            ->assertSessionHas('google_status.ok', false);

        // Somewhere off the site is never where it sends you back to.
        $this->as($counsellor)->withSession(['google_oauth' => ['state' => 'right', 'back' => '//evil.example/x', 'user' => $counsellor->id, 'at' => now()->timestamp]])
            ->get(route('crm.google.callback', ['state' => 'wrong']))
            ->assertRedirect(route('crm.dashboard'));

        Http::assertNothingSent();
        $this->assertNull($counsellor->fresh()->googleAccount);
    }

    public function test_connecting_without_calendar_access_is_refused(): void
    {
        $counsellor = $this->user();
        Http::fake(['oauth2.googleapis.com/token' => Http::response([
            'access_token' => 'ya29.x', 'expires_in' => 3599, 'refresh_token' => '1//x', 'scope' => 'openid https://www.googleapis.com/auth/userinfo.email',
        ])]);

        $this->as($counsellor)->withSession(['google_oauth' => ['state' => 's', 'back' => '/crm', 'user' => $counsellor->id, 'at' => now()->timestamp]])
            ->get(route('crm.google.callback', ['state' => 's', 'code' => 'c']))
            ->assertSessionHas('google_status.ok', false);
        $this->assertNull($counsellor->fresh()->googleAccount);
    }

    public function test_reconnecting_never_throws_away_a_working_refresh_token(): void
    {
        $counsellor = $this->user();
        $this->connected($counsellor, ['needs_reconnect_at' => now(), 'last_error' => 'Google stopped accepting this connection.']);
        // Google leaves the refresh token out of this answer.
        Http::fake(['oauth2.googleapis.com/token' => Http::response([
            'access_token' => 'ya29.again', 'expires_in' => 3599, 'scope' => 'openid email https://www.googleapis.com/auth/calendar.events',
        ])]);

        $this->as($counsellor)->withSession(['google_oauth' => ['state' => 's', 'back' => '/crm', 'user' => $counsellor->id, 'at' => now()->timestamp]])
            ->get(route('crm.google.callback', ['state' => 's', 'code' => 'c']))
            ->assertSessionHas('google_status.ok', true);

        $account = $counsellor->fresh()->googleAccount;
        $this->assertSame('1//refresh-token-that-lasts', $account->refresh_token);
        $this->assertTrue($account->works(), 'Reconnecting clears the problem.');
    }

    /* ------------------------------------------------------------ staying connected */

    public function test_an_expired_access_token_is_renewed_without_the_counsellor_noticing(): void
    {
        $counsellor = $this->user();
        $lead = $this->started($counsellor);
        $this->connected($counsellor, ['access_token_expires_at' => now()->subMinute()]);
        $this->fakeCalendar();

        $this->as($counsellor)->postJson(route('crm.journey.meet-room.store', $lead), ['title' => 'Shortlist', 'date' => '2026-10-10', 'time' => '16:00'])
            ->assertOk()->assertJsonPath('link', 'https://meet.google.com/abc-defg-hij');

        Http::assertSent(fn (HttpRequest $r) => $r->url() === 'https://oauth2.googleapis.com/token' && $r['grant_type'] === 'refresh_token' && $r['refresh_token'] === '1//refresh-token-that-lasts');
        Http::assertSent(fn (HttpRequest $r) => str_starts_with($r->url(), self::CAL) && $r->hasHeader('Authorization', 'Bearer ya29.renewed'));
        $account = $counsellor->googleAccount()->first();
        $this->assertSame('ya29.renewed', $account->access_token);
        $this->assertTrue($account->access_token_expires_at->isAfter(now()->addMinutes(50)));
        $this->assertSame('1//refresh-token-that-lasts', $account->refresh_token, 'The refresh token is kept.');
    }

    public function test_a_token_still_good_is_used_as_it_is(): void
    {
        $counsellor = $this->user();
        $lead = $this->started($counsellor);
        $this->connected($counsellor);
        $this->fakeCalendar();

        $this->as($counsellor)->postJson(route('crm.journey.meet-room.store', $lead), [])->assertOk();
        Http::assertNotSent(fn (HttpRequest $r) => $r->url() === 'https://oauth2.googleapis.com/token');
    }

    public function test_a_refused_refresh_token_asks_the_counsellor_to_reconnect(): void
    {
        $counsellor = $this->user();
        $lead = $this->started($counsellor);
        $this->connected($counsellor, ['access_token_expires_at' => now()->subMinute()]);
        Http::fake(['oauth2.googleapis.com/token' => Http::response(['error' => 'invalid_grant', 'error_description' => 'Token has been expired or revoked.'], 400)]);

        $this->as($counsellor)->postJson(route('crm.journey.meet-room.store', $lead), [])->assertStatus(422);
        $account = $counsellor->googleAccount()->first();
        $this->assertFalse($account->works());
        $this->assertStringContainsString('Connect again', $account->last_error);

        // The page says so, and offers to reconnect.
        $html = $this->as($counsellor)->get(route('crm.journey.show', $lead))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/"google":\{"connected":true,"email":"priya.counsellor@gmail.com","works":false/', $html);
    }

    public function test_the_seven_day_testing_expiry_is_named_for_what_it_is(): void
    {
        $counsellor = $this->user();
        $account = $this->connected($counsellor, ['connected_at' => now()->subDays(7), 'access_token_expires_at' => now()->subMinute()]);
        Http::fake(['oauth2.googleapis.com/token' => Http::response(['error' => 'invalid_grant'], 400)]);

        $this->assertFalse(app(GoogleCalendar::class)->renew($account));
        $this->assertStringContainsString('Testing', $account->fresh()->last_error);
    }

    public function test_google_being_unreachable_does_not_end_the_connection(): void
    {
        $counsellor = $this->user();
        $account = $this->connected($counsellor, ['access_token_expires_at' => now()->subMinute()]);
        Http::fake(['oauth2.googleapis.com/token' => Http::response(['error' => 'internal_failure'], 500)]);

        try {
            app(GoogleCalendar::class)->renew($account);
            $this->fail('Expected a Google error');
        } catch (GoogleCalendarException) {
        }
        $this->assertTrue($account->fresh()->works(), 'A Google outage is not a reason to make anyone reconnect.');
    }

    public function test_keep_alive_renews_idle_connections_and_clears_unused_rooms(): void
    {
        $idle = $this->connected($this->user(), ['last_refreshed_at' => now()->subDays(2), 'pending_rooms' => [
            ['event' => 'oldroom', 'link' => 'https://meet.google.com/old-room-xyz', 'at' => now()->subDay()->toIso8601String()],
            ['event' => 'newroom', 'link' => 'https://meet.google.com/new-room-xyz', 'at' => now()->subHour()->toIso8601String()],
        ]]);
        $fresh = $this->connected($this->user(), ['last_refreshed_at' => now()->subHour()]);
        $lost = $this->connected($this->user(), ['needs_reconnect_at' => now()]);
        $this->fakeCalendar();

        $this->artisan('google:keep-alive')->assertSuccessful();

        $this->assertTrue($idle->fresh()->last_refreshed_at->isAfter(now()->subMinute()));
        $this->assertTrue($fresh->fresh()->last_refreshed_at->isBefore(now()->subMinutes(30)), 'Renewed an hour ago: left alone.');
        $this->assertSame(['newroom'], array_column($idle->fresh()->pending_rooms, 'event'), 'Only the room nobody saved for a day is cleared.');
        Http::assertSent(fn (HttpRequest $r) => $r->method() === 'DELETE' && str_ends_with(parse_url($r->url(), PHP_URL_PATH), '/oldroom'));
        Http::assertSentCount(2); // the idle renewal and the one delete
        $this->assertNotNull($lost->fresh()->needs_reconnect_at, 'A broken connection waits for the counsellor.');
    }

    public function test_the_keep_alive_runs_every_day(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->filter(fn ($e) => str_contains((string) $e->command, 'google:keep-alive'));
        $this->assertCount(1, $events);
        $this->assertSame('15 3 * * *', $events->first()->expression);
    }

    public function test_opening_the_crm_keeps_a_connection_in_use_without_a_scheduler(): void
    {
        $counsellor = $this->user();
        $account = $this->connected($counsellor, ['last_refreshed_at' => now()->subDays(4)]);
        $this->fakeCalendar();

        $this->as($counsellor)->get(route('crm.dashboard'))->assertOk();

        $this->assertTrue($account->fresh()->last_refreshed_at->isAfter(now()->subMinute()));

        // Renewed recently: opening the CRM again asks Google nothing.
        Http::fake();
        $this->as($counsellor)->get(route('crm.dashboard'))->assertOk();
        Http::assertNothingSent();
    }

    /* ------------------------------------------------------------ rooms */

    public function test_a_room_is_made_with_nobody_invited_and_saved_onto_the_meeting(): void
    {
        $counsellor = $this->user();
        $lead = $this->started($counsellor);
        $this->connected($counsellor);
        $this->fakeCalendar('https://meet.google.com/real-room-one');

        $room = $this->as($counsellor)->postJson(route('crm.journey.meet-room.store', $lead), ['title' => 'Shortlist review', 'date' => '2026-10-10', 'time' => '16:00', 'minutes' => 30])
            ->assertOk()->json();
        Http::assertSent(function (HttpRequest $r) {
            if ($r->method() !== 'POST' || ! str_starts_with($r->url(), self::CAL)) {
                return false;
            }
            parse_str((string) parse_url($r->url(), PHP_URL_QUERY), $q);

            return $q['sendUpdates'] === 'none' && $q['conferenceDataVersion'] === '1'
                && ! isset($r['attendees'])
                && $r['conferenceData']['createRequest']['conferenceSolutionKey']['type'] === 'hangoutsMeet'
                && $r['start'] === ['dateTime' => '2026-10-10T16:00:00', 'timeZone' => 'Asia/Kolkata']
                && $r['end']['dateTime'] === '2026-10-10T16:30:00';
        });
        $this->assertCount(1, $counsellor->googleAccount()->first()->pending_rooms);

        $meetings = $this->as($counsellor)->postJson(route('crm.journey.meetings.store', $lead), [
            'title' => 'Shortlist review', 'date' => '2026-10-10', 'time' => '16:30', 'minutes' => 30, 'mode' => 'Google Meet',
            'link' => $room['link'], 'room' => $room['room'], 'emails' => ['ananya@mailbox.test'],
        ])->assertOk()->assertJsonMissingPath('warning')->json('meetings');

        $this->assertSame('https://meet.google.com/real-room-one', $meetings[0]['link']);
        $this->assertTrue($meetings[0]['room']);
        $this->assertSame([], $counsellor->googleAccount()->first()->pending_rooms, 'Saved: no longer waiting to be cleared.');
        // The time moved in the dialog after the room was made; Google is told.
        Http::assertSent(fn (HttpRequest $r) => $r->method() === 'PATCH' && str_contains($r->url(), '/'.$room['room']) && $r['start']['dateTime'] === '2026-10-10T16:30:00');

        // Nothing about the Google event reaches the student's page.
        $stored = $this->planOf($lead)->meetings[0];
        $this->assertSame($room['room'], $stored['google']['event']);
        $this->assertArrayNotHasKey('google', $meetings[0]);
    }

    public function test_a_meet_call_saved_without_a_link_gets_its_room_on_save(): void
    {
        $counsellor = $this->user();
        $lead = $this->started($counsellor);
        $this->connected($counsellor);
        $this->fakeCalendar('https://meet.google.com/made-on-save');

        $this->as($counsellor)->postJson(route('crm.journey.meetings.store', $lead), ['title' => 'Call', 'date' => '2026-10-11', 'mode' => 'Google Meet'])
            ->assertOk()->assertJsonPath('meetings.0.link', 'https://meet.google.com/made-on-save')->assertJsonPath('meetings.0.room', true);
    }

    public function test_google_failing_never_stops_a_meeting_being_saved(): void
    {
        $counsellor = $this->user();
        $lead = $this->started($counsellor);
        $this->connected($counsellor);
        Http::fake(['www.googleapis.com/calendar/*' => Http::response(['error' => ['message' => 'Backend Error']], 503)]);

        $this->as($counsellor)->postJson(route('crm.journey.meetings.store', $lead), ['title' => 'Call', 'date' => '2026-10-11', 'mode' => 'Google Meet'])
            ->assertOk()->assertJsonCount(1, 'meetings')->assertJsonPath('meetings.0.room', false)
            ->assertJsonPath('warning', fn ($w) => str_starts_with($w, 'Saved without a Meet room.'));
    }

    public function test_a_counsellor_without_google_still_books_meetings_as_before(): void
    {
        $counsellor = $this->user();
        $lead = $this->started($counsellor);
        Http::fake();

        $this->as($counsellor)->postJson(route('crm.journey.meetings.store', $lead), ['title' => 'Call', 'date' => '2026-10-11', 'mode' => 'Google Meet', 'link' => 'https://meet.google.com/pasted-by-hand'])
            ->assertOk()->assertJsonPath('meetings.0.link', 'https://meet.google.com/pasted-by-hand')->assertJsonPath('meetings.0.room', false);
        $this->as($counsellor)->postJson(route('crm.journey.meet-room.store', $lead), [])->assertStatus(422)->assertJsonPath('message', 'Connect your Google account first.');
        Http::assertNothingSent();
    }

    public function test_the_calendar_entry_follows_the_meeting_and_goes_when_it_does(): void
    {
        $counsellor = $this->user();
        $lead = $this->started($counsellor);
        $this->connected($counsellor);
        $this->fakeCalendar();

        $key = $this->as($counsellor)->postJson(route('crm.journey.meetings.store', $lead), ['title' => 'Call', 'date' => '2026-10-11', 'time' => '10:00', 'mode' => 'Google Meet'])->json('meeting.key');
        $event = $this->planOf($lead)->meetings[0]['google']['event'];

        // Marking it done touches nothing at Google.
        Http::fake();
        $this->fakeCalendar();
        $this->as($counsellor)->patchJson(route('crm.journey.meetings.update', [$lead, $key]), ['done' => true])->assertOk()->assertJsonPath('meetings.0.room', true);
        Http::assertNothingSent();

        // A new time moves the calendar entry.
        $this->as($counsellor)->patchJson(route('crm.journey.meetings.update', [$lead, $key]), ['time' => '11:00'])->assertOk();
        Http::assertSent(fn (HttpRequest $r) => $r->method() === 'PATCH' && str_contains($r->url(), '/'.$event) && $r['start']['dateTime'] === '2026-10-11T11:00:00');

        // A phone call has no use for the room.
        $this->as($counsellor)->patchJson(route('crm.journey.meetings.update', [$lead, $key]), ['mode' => 'Phone call', 'link' => null, 'phone' => '+91 98290 00000'])
            ->assertOk()->assertJsonPath('meetings.0.room', false);
        Http::assertSent(fn (HttpRequest $r) => $r->method() === 'DELETE' && str_contains($r->url(), '/'.$event));

        // Back to Meet: a new room. Then cancelling the meeting removes it.
        $this->as($counsellor)->patchJson(route('crm.journey.meetings.update', [$lead, $key]), ['mode' => 'Google Meet', 'link' => null])->assertOk()->assertJsonPath('meetings.0.room', true);
        $again = $this->planOf($lead)->meetings[0]['google']['event'];
        $this->as($counsellor)->deleteJson(route('crm.journey.meetings.destroy', [$lead, $key]))->assertOk();
        Http::assertSent(fn (HttpRequest $r) => $r->method() === 'DELETE' && str_contains($r->url(), '/'.$again));
    }

    public function test_a_room_from_a_closed_dialog_is_given_back(): void
    {
        $counsellor = $this->user();
        $lead = $this->started($counsellor);
        $this->connected($counsellor);
        $this->fakeCalendar();

        $room = $this->as($counsellor)->postJson(route('crm.journey.meet-room.store', $lead), [])->json('room');
        $this->as($counsellor)->deleteJson(route('crm.journey.meet-room.release', $lead), ['room' => $room])->assertOk();

        Http::assertSent(fn (HttpRequest $r) => $r->method() === 'DELETE' && str_contains($r->url(), '/'.$room));
        $this->assertSame([], $counsellor->googleAccount()->first()->pending_rooms);
    }

    public function test_a_room_is_only_ever_claimed_by_the_counsellor_who_made_it(): void
    {
        $owner = $this->user();
        $lead = $this->started($owner);
        $this->connected($owner, ['pending_rooms' => [['event' => 'ownersroom', 'link' => 'https://meet.google.com/own-ers-room', 'at' => now()->toIso8601String()]]]);
        $admin = $this->user('super_admin');
        $this->connected($admin, ['google_email' => 'admin@gmail.com']);
        $this->fakeCalendar('https://meet.google.com/admins-room');

        // Offering someone else's room id gets the admin a room of their own.
        $this->as($admin)->postJson(route('crm.journey.meetings.store', $lead), ['title' => 'Call', 'date' => '2026-10-11', 'mode' => 'Google Meet', 'room' => 'ownersroom'])
            ->assertOk()->assertJsonPath('meetings.0.link', 'https://meet.google.com/admins-room');
        $this->assertCount(1, $owner->googleAccount()->first()->pending_rooms);
    }

    /* ------------------------------------------------------------ the page */

    public function test_only_the_counsellor_page_carries_the_connection_and_never_a_token(): void
    {
        $counsellor = $this->user();
        $partner = $this->user('partner');
        $lead = $this->started($counsellor, ['partner_id' => $partner->id]);
        $this->connected($counsellor);
        Http::fake();

        $html = $this->as($counsellor)->get(route('crm.journey.show', $lead))->assertOk()->getContent();
        $this->assertStringContainsString('"email":"priya.counsellor@gmail.com"', $html);
        $this->assertStringNotContainsString('refresh-token-that-lasts', $html);
        $this->assertStringNotContainsString('ya29.current', $html);

        $this->assertStringNotContainsString('priya.counsellor@gmail.com', $this->as($partner)->get(route('crm.journey.show', $lead))->assertOk()->getContent());

        $account = $lead->studentAccount()->firstOrFail();
        $account->forceFill(['must_change_password' => false])->save();
        $this->flushSession();
        $this->assertStringNotContainsString('priya.counsellor@gmail.com', $this->withSession([StudentAuth::SESSION_KEY => $account->id])->get(route('student.dashboard'))->assertOk()->getContent());
    }

    public function test_disconnecting_tells_google_and_forgets_the_tokens(): void
    {
        $counsellor = $this->user();
        $this->connected($counsellor);
        Http::fake(['oauth2.googleapis.com/revoke' => Http::response([])]);

        $this->as($counsellor)->postJson(route('crm.google.disconnect'))->assertOk()->assertJsonPath('google.connected', false);
        Http::assertSent(fn (HttpRequest $r) => $r->url() === 'https://oauth2.googleapis.com/revoke' && $r['token'] === '1//refresh-token-that-lasts');
        $this->assertSame(0, CrmGoogleAccount::query()->count());
    }
}
