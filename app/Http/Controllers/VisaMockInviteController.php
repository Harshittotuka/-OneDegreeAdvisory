<?php

namespace App\Http\Controllers;

use App\Models\CrmMockInterviewAttempt;
use App\Models\CrmMockInterviewInvite;
use App\Support\MockInterviewQuestions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * The counsellor-issued mock-interview link.
 *
 * A CRM user generates a link granting an extended round (15/20/39 questions)
 * that may be used a fixed number of times. Landing on the link never spends an
 * attempt — only pressing "Start interview" does, via start() — so a refresh or
 * a second look at the setup screen costs the student nothing.
 */
class VisaMockInviteController extends Controller
{
    public function show(Request $request, string $token): View
    {
        $invite = CrmMockInterviewInvite::query()->where('token', $token)->first();

        // An unknown, spent or revoked link still renders the page: the visitor
        // gets the normal free round plus a banner explaining what happened,
        // which beats a dead end.
        return view('pages.visa-mock-interview', [
            'activeNav' => 'new-tabs',
            'bodyClass' => 'vmi-page-body',
            'pageTitle' => 'Your Visa Mock Interview — One Degree Advisory',
            'pageDescription' => 'Your counsellor has set up an extended visa mock interview with AI feedback.',
            'mainId' => 'main',
            'freeQuestions' => MockInterviewQuestions::freePool(),
            'invite' => $invite,
            'inviteState' => $invite?->state() ?? 'invalid',
        ]);
    }

    /**
     * Spend one attempt and hand back the question queue.
     *
     * The queue is built here rather than in the page so the granted question
     * count is actually enforced. Re-entering an attempt already started in this
     * session returns the same queue without spending another use.
     */
    public function start(Request $request, string $token): JsonResponse
    {
        $invite = CrmMockInterviewInvite::query()->where('token', $token)->first();
        if (! $invite) {
            return response()->json(['ok' => false, 'state' => 'invalid', 'message' => 'This interview link is not valid.'], 404);
        }

        $sessionKey = "vmi_invite_attempt_{$invite->id}";
        $existingId = $request->session()->get($sessionKey);
        if ($existingId) {
            $existing = CrmMockInterviewAttempt::query()
                ->where('invite_id', $invite->id)
                ->whereNull('completed_at')
                ->find($existingId);

            if ($existing) {
                return response()->json([
                    'ok' => true,
                    'resumed' => true,
                    'remaining' => $invite->remainingUses(),
                    'questionCount' => $existing->questions_planned,
                    'questions' => MockInterviewQuestions::queue($existing->questions_planned, true),
                ]);
            }
        }

        // Lock the row so two tabs opened at once cannot both take the last use.
        $attempt = null;
        $state = DB::transaction(function () use ($invite, $request, &$attempt): string {
            $locked = CrmMockInterviewInvite::query()->lockForUpdate()->find($invite->id);
            if (! $locked || ! $locked->isUsable()) {
                return $locked?->state() ?? 'invalid';
            }

            $locked->forceFill([
                'uses_count' => $locked->uses_count + 1,
                'last_used_at' => now(),
            ])->save();

            $attempt = CrmMockInterviewAttempt::query()->create([
                'invite_id' => $locked->id,
                'session_key' => mb_substr((string) $request->session()->getId(), 0, 64),
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
                'questions_planned' => $locked->question_count,
                'started_at' => now(),
            ]);

            $invite->setRawAttributes($locked->getAttributes());

            return 'ok';
        });

        if ($state !== 'ok' || ! $attempt) {
            return response()->json([
                'ok' => false,
                'state' => $state,
                'message' => $this->stateMessage($state),
            ], 403);
        }

        $request->session()->put($sessionKey, $attempt->id);

        return response()->json([
            'ok' => true,
            'resumed' => false,
            'remaining' => $invite->remainingUses(),
            'questionCount' => $attempt->questions_planned,
            'questions' => MockInterviewQuestions::queue($attempt->questions_planned, true),
        ]);
    }

    /**
     * Record the outcome so the CRM row shows how the student actually did.
     * Best-effort: a failure here must never break the student's report.
     */
    public function finish(Request $request, string $token): JsonResponse
    {
        $validated = $request->validate([
            'answered' => 'required|integer|min:0|max:100',
            'score' => 'nullable|numeric|min:0|max:10',
        ]);

        $invite = CrmMockInterviewInvite::query()->where('token', $token)->first();
        if (! $invite) {
            return response()->json(['ok' => false], 404);
        }

        $attemptId = $request->session()->get("vmi_invite_attempt_{$invite->id}");
        $attempt = $attemptId
            ? CrmMockInterviewAttempt::query()->where('invite_id', $invite->id)->find($attemptId)
            : null;

        if (! $attempt) {
            return response()->json(['ok' => false], 404);
        }

        $attempt->forceFill([
            'questions_answered' => $validated['answered'],
            'overall_score' => $validated['score'] ?? null,
            'completed_at' => $attempt->completed_at ?? now(),
        ])->save();

        return response()->json(['ok' => true]);
    }

    private function stateMessage(string $state): string
    {
        return match ($state) {
            'revoked' => 'This interview link has been withdrawn. Please contact your counsellor.',
            'expired' => 'This interview link has expired. Please ask your counsellor for a new one.',
            'exhausted' => 'This link has already been used the maximum number of times.',
            default => 'This interview link is not valid.',
        };
    }
}
