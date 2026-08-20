<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmMockInterviewInvite;
use App\Models\CrmUser;
use App\Services\CrmAuditLogger;
use App\Support\MockInterviewQuestions;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;

class CrmMockInviteController extends Controller
{
    public function store(Request $request, CrmAuditLogger $auditLogger): RedirectResponse
    {
        /** @var CrmUser $user */
        $user = $request->attributes->get('crm_user');

        $validated = $request->validate([
            'recipient_name' => 'required|string|max:150',
            'recipient_email' => 'nullable|email|max:190|real_email',
            'recipient_phone' => 'nullable|string|max:20',
            'question_count' => ['required', 'integer', Rule::in(MockInterviewQuestions::INVITE_COUNTS)],
            'destination' => 'nullable|string|max:120',
            'notes' => 'nullable|string|max:500',
            'expires_in_days' => 'nullable|integer|min:1|max:365',
        ], [], ['question_count' => 'question count']);

        $invite = CrmMockInterviewInvite::query()->create([
            'token' => CrmMockInterviewInvite::freshToken(),
            'recipient_name' => trim($validated['recipient_name']),
            'recipient_email' => trim((string) ($validated['recipient_email'] ?? '')) ?: null,
            'recipient_phone' => trim((string) ($validated['recipient_phone'] ?? '')) ?: null,
            'question_count' => (int) $validated['question_count'],
            'destination' => trim((string) ($validated['destination'] ?? '')) ?: null,
            'notes' => trim((string) ($validated['notes'] ?? '')) ?: null,
            'created_by' => $user->id,
            'expires_at' => now()->addDays((int) ($validated['expires_in_days'] ?? 30)),
        ]);

        $auditLogger->record(
            $request,
            $user,
            'mock_invite_created',
            "Issued a {$invite->question_count}-question mock interview link for {$invite->recipient_name}",
            ['subject_type' => CrmMockInterviewInvite::class, 'subject_id' => $invite->id, 'subject_label' => $invite->recipient_name]
        );

        return back()
            ->with('status', "Interview link ready for {$invite->recipient_name} — copy it below.")
            ->with('new_invite', $invite->id);
    }

    public function revoke(Request $request, CrmMockInterviewInvite $invite, CrmAuditLogger $auditLogger): RedirectResponse
    {
        /** @var CrmUser $user */
        $user = $request->attributes->get('crm_user');
        abort_unless($user->isSuperAdmin() || $invite->created_by === $user->id, 403);

        if (! $invite->isRevoked()) {
            $invite->forceFill(['revoked_at' => now()])->save();

            $auditLogger->record(
                $request,
                $user,
                'mock_invite_revoked',
                "Revoked the mock interview link for {$invite->recipient_name}",
                ['subject_type' => CrmMockInterviewInvite::class, 'subject_id' => $invite->id, 'subject_label' => $invite->recipient_name]
            );
        }

        return back()->with('status', "Link for {$invite->recipient_name} is no longer usable.");
    }
}
