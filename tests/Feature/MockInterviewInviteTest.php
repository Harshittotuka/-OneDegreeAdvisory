<?php

namespace Tests\Feature;

use App\Models\CrmMockInterviewAttempt;
use App\Models\CrmMockInterviewInvite;
use App\Models\CrmUser;
use App\Support\MockInterviewQuestions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MockInterviewInviteTest extends TestCase
{
    use RefreshDatabase;

    private ?CrmUser $counsellor = null;

    /** Memoised: several tests need both an issuer and an invite it created. */
    private function counsellor(): CrmUser
    {
        return $this->counsellor ??= CrmUser::query()->create([
            'name' => 'Priya Counsellor',
            'phone' => '9800000001',
            'email' => 'priya@example.com',
            'role' => 'super_admin',
            'is_active' => true,
        ]);
    }

    private function invite(array $overrides = []): CrmMockInterviewInvite
    {
        return CrmMockInterviewInvite::query()->create(array_merge([
            'token' => CrmMockInterviewInvite::freshToken(),
            'recipient_name' => 'Rahul Student',
            'question_count' => 15,
            'max_uses' => 3,
            'created_by' => $this->counsellor()->id,
            'expires_at' => now()->addDays(30),
        ], $overrides));
    }

    /* ---------------------------------------------------------------- bank */

    public function test_the_bank_holds_all_thirty_nine_questions_with_ten_recorded(): void
    {
        $all = MockInterviewQuestions::all();

        $this->assertCount(39, $all);
        $this->assertSame(39, MockInterviewQuestions::total());

        $recorded = array_values(array_filter($all, fn (array $q) => $q['audio'] !== ''));
        $this->assertCount(10, $recorded, 'Exactly ten questions have a recorded clip.');

        // Recordings must line up with the free pool, since the free page plays
        // them and nothing else.
        $this->assertSame(
            array_column($recorded, 'id'),
            array_column(MockInterviewQuestions::freePool(), 'id')
        );
    }

    public function test_recorded_clips_stay_pinned_to_their_own_question(): void
    {
        $pool = MockInterviewQuestions::freePool();

        // The clip numbering is what silently broke when the bank was widened
        // before: q2 belongs to "Why did you choose this course?", which sits at
        // position 3 of the full 39.
        $this->assertStringEndsWith('/q1.mp3', $pool[0]['audio']);
        $this->assertSame('Tell me about yourself.', $pool[0]['q']);
        $this->assertStringEndsWith('/q2.mp3', $pool[1]['audio']);
        $this->assertSame('Why did you choose this course?', $pool[1]['q']);
        $this->assertStringEndsWith('/q10.mp3', $pool[9]['audio']);
        $this->assertSame('Will you return to your home country after your studies?', $pool[9]['q']);
    }

    public function test_a_free_round_never_reaches_beyond_the_recorded_questions(): void
    {
        foreach (MockInterviewQuestions::FREE_COUNTS as $count) {
            $queue = MockInterviewQuestions::queue($count);
            $this->assertCount($count, $queue);
            foreach ($queue as $question) {
                $this->assertNotSame('', $question['audio'], 'Every free question keeps its voiceover.');
            }
        }
    }

    public function test_an_extended_queue_matches_the_granted_count_and_spans_all_categories(): void
    {
        foreach (MockInterviewQuestions::INVITE_COUNTS as $count) {
            $queue = MockInterviewQuestions::queue($count, true);
            $this->assertCount($count, $queue);
            $this->assertCount(
                6,
                array_unique(array_column($queue, 'cat')),
                "A {$count}-question round still covers all six categories."
            );
        }
    }

    /* -------------------------------------------------------------- landing */

    public function test_the_invite_page_unlocks_only_the_granted_length(): void
    {
        $invite = $this->invite(['question_count' => 20]);

        $this->get($invite->shareUrl())
            ->assertOk()
            ->assertSee('"count":20', false)
            ->assertSee('"remaining":3', false)
            ->assertSee('Rahul Student', false)
            ->assertSee('mock-interview\\/i\\/'.$invite->token.'\\/start', false);
    }

    public function test_opening_the_link_does_not_spend_an_attempt(): void
    {
        $invite = $this->invite();

        $this->get($invite->shareUrl())->assertOk();
        $this->get($invite->shareUrl())->assertOk();

        $this->assertSame(0, $invite->fresh()->uses_count);
        $this->assertSame(0, CrmMockInterviewAttempt::query()->count());
    }

    public function test_the_extended_questions_are_never_inlined_for_a_visitor(): void
    {
        // "What did you study in your previous education?" is bank-only: it has no
        // recording, so it must not appear until an attempt is claimed.
        $this->get(route('visa-mock'))
            ->assertOk()
            ->assertDontSee('What did you study in your previous education?', false)
            ->assertSee('Tell me about yourself.', false);

        $this->get($this->invite()->shareUrl())
            ->assertOk()
            ->assertDontSee('Do you have an education loan?', false);
    }

    /* ---------------------------------------------------------------- start */

    public function test_starting_spends_one_attempt_and_returns_the_granted_queue(): void
    {
        $invite = $this->invite(['question_count' => 15]);

        $response = $this->postJson(route('visa-mock.invite.start', ['token' => $invite->token]));

        $response->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('resumed', false)
            ->assertJsonPath('remaining', 2)
            ->assertJsonPath('questionCount', 15)
            ->assertJsonCount(15, 'questions');

        $this->assertSame(1, $invite->fresh()->uses_count);
        $this->assertNotNull($invite->fresh()->last_used_at);
        $this->assertDatabaseHas('crm_mock_interview_attempts', [
            'invite_id' => $invite->id,
            'questions_planned' => 15,
            'completed_at' => null,
        ]);
    }

    public function test_re_entering_the_same_session_resumes_instead_of_spending_again(): void
    {
        $invite = $this->invite();

        $this->postJson(route('visa-mock.invite.start', ['token' => $invite->token]))->assertOk();
        $this->postJson(route('visa-mock.invite.start', ['token' => $invite->token]))
            ->assertOk()
            ->assertJsonPath('resumed', true)
            ->assertJsonPath('remaining', 2);

        $this->assertSame(1, $invite->fresh()->uses_count, 'A refresh must not burn a second attempt.');
        $this->assertSame(1, CrmMockInterviewAttempt::query()->count());
    }

    public function test_a_link_stops_working_after_three_attempts(): void
    {
        $invite = $this->invite(['uses_count' => 3]);

        $this->postJson(route('visa-mock.invite.start', ['token' => $invite->token]))
            ->assertForbidden()
            ->assertJsonPath('state', 'exhausted');

        $this->assertSame(3, $invite->fresh()->uses_count);
    }

    public function test_revoked_and_expired_links_cannot_start_a_round(): void
    {
        $revoked = $this->invite(['revoked_at' => now()->subMinute()]);
        $this->postJson(route('visa-mock.invite.start', ['token' => $revoked->token]))
            ->assertForbidden()
            ->assertJsonPath('state', 'revoked');

        $expired = $this->invite(['expires_at' => now()->subDay()]);
        $this->postJson(route('visa-mock.invite.start', ['token' => $expired->token]))
            ->assertForbidden()
            ->assertJsonPath('state', 'expired');
    }

    public function test_an_unusable_link_still_renders_the_free_round_with_a_reason(): void
    {
        $invite = $this->invite(['uses_count' => 3]);

        $this->get($invite->shareUrl())
            ->assertOk()
            ->assertSee('invite: null', false)
            ->assertSee('inviteError: "exhausted"', false)
            ->assertSee('Tell me about yourself.', false);
    }

    public function test_an_unknown_token_is_not_treated_as_an_invite(): void
    {
        $this->get(route('visa-mock.invite', ['token' => 'nosuchtokenatall']))
            ->assertOk()
            ->assertSee('invite: null', false)
            ->assertSee('inviteError: "invalid"', false);

        $this->postJson(route('visa-mock.invite.start', ['token' => 'nosuchtokenatall']))
            ->assertNotFound()
            ->assertJsonPath('state', 'invalid');
    }

    /* --------------------------------------------------------------- finish */

    public function test_finishing_records_the_score_against_the_attempt(): void
    {
        $invite = $this->invite();
        $this->postJson(route('visa-mock.invite.start', ['token' => $invite->token]))->assertOk();

        $this->postJson(route('visa-mock.invite.finish', ['token' => $invite->token]), [
            'answered' => 14,
            'score' => 7.4,
        ])->assertOk()->assertJsonPath('ok', true);

        $attempt = CrmMockInterviewAttempt::query()->firstOrFail();
        $this->assertSame(14, $attempt->questions_answered);
        $this->assertSame(7.4, $attempt->overall_score);
        $this->assertNotNull($attempt->completed_at);
    }

    public function test_finishing_without_a_started_attempt_is_rejected(): void
    {
        $invite = $this->invite();

        $this->postJson(route('visa-mock.invite.finish', ['token' => $invite->token]), ['answered' => 5])
            ->assertNotFound();
    }

    /* ------------------------------------------------------------------ crm */

    public function test_a_crm_user_can_generate_a_link(): void
    {
        $user = $this->counsellor();

        $this->withSession(['crm_user_id' => $user->id])
            ->post(route('crm.mock-invites.store'), [
                'recipient_name' => 'Aisha Khan',
                'question_count' => 39,
                'recipient_email' => 'aisha@example.com',
                'destination' => 'Canada',
                'expires_in_days' => 14,
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $invite = CrmMockInterviewInvite::query()->firstOrFail();
        $this->assertSame('Aisha Khan', $invite->recipient_name);
        $this->assertSame(39, $invite->question_count);
        $this->assertSame(3, $invite->max_uses, 'Every link is good for three runs.');
        $this->assertSame($user->id, $invite->created_by);
        $this->assertTrue($invite->expires_at->isFuture());
        $this->assertDatabaseHas('crm_audit_logs', ['event' => 'mock_invite_created']);
    }

    public function test_a_question_count_outside_the_offered_tiers_is_rejected(): void
    {
        $user = $this->counsellor();

        $this->withSession(['crm_user_id' => $user->id])
            ->post(route('crm.mock-invites.store'), ['recipient_name' => 'Nope', 'question_count' => 100])
            ->assertSessionHasErrors('question_count');

        $this->assertSame(0, CrmMockInterviewInvite::query()->count());
    }

    public function test_revoking_a_link_kills_it_immediately(): void
    {
        $user = $this->counsellor();
        $invite = $this->invite(['created_by' => $user->id]);

        $this->withSession(['crm_user_id' => $user->id])
            ->patch(route('crm.mock-invites.revoke', $invite))
            ->assertRedirect();

        $this->assertNotNull($invite->fresh()->revoked_at);
        $this->assertFalse($invite->fresh()->isUsable());
        $this->assertDatabaseHas('crm_audit_logs', ['event' => 'mock_invite_revoked']);

        $this->postJson(route('visa-mock.invite.start', ['token' => $invite->token]))->assertForbidden();
    }

    public function test_the_tab_lists_issued_links_and_requires_a_login(): void
    {
        $user = $this->counsellor();
        $invite = $this->invite(['created_by' => $user->id, 'recipient_name' => 'Listed Student']);

        $this->get(route('crm.dashboard', ['view' => 'mock-invites']))->assertRedirect(route('crm.login'));

        $this->withSession(['crm_user_id' => $user->id])
            ->get(route('crm.dashboard', ['view' => 'mock-invites']))
            ->assertOk()
            ->assertSee('Mock interview links')
            ->assertSee('Listed Student')
            ->assertSee($invite->token, false)
            ->assertSee('0 of 3');
    }

    public function test_a_counsellor_only_sees_their_own_links(): void
    {
        $mine = CrmUser::query()->create([
            'name' => 'Counsellor One', 'phone' => '9800000011', 'email' => 'one@example.com',
            'role' => 'counsellor', 'is_active' => true,
        ]);
        $other = CrmUser::query()->create([
            'name' => 'Counsellor Two', 'phone' => '9800000012', 'email' => 'two@example.com',
            'role' => 'counsellor', 'is_active' => true,
        ]);

        $this->invite(['created_by' => $mine->id, 'recipient_name' => 'Mine Student']);
        $this->invite(['created_by' => $other->id, 'recipient_name' => 'Their Student']);

        $this->withSession(['crm_user_id' => $mine->id])
            ->get(route('crm.dashboard', ['view' => 'mock-invites']))
            ->assertOk()
            ->assertSee('Mine Student')
            ->assertDontSee('Their Student');
    }

    public function test_a_counsellor_cannot_revoke_someone_elses_link(): void
    {
        $mine = CrmUser::query()->create([
            'name' => 'Counsellor One', 'phone' => '9800000021', 'email' => 'one2@example.com',
            'role' => 'counsellor', 'is_active' => true,
        ]);
        $invite = $this->invite();

        $this->withSession(['crm_user_id' => $mine->id])
            ->patch(route('crm.mock-invites.revoke', $invite))
            ->assertForbidden();

        $this->assertNull($invite->fresh()->revoked_at);
    }
}
