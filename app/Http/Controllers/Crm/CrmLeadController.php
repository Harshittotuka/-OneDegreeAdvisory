<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmLead;
use App\Models\CrmLeadActivity;
use App\Models\CrmUser;
use App\Services\CrmNotifier;
use App\Support\CrmOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CrmLeadController extends Controller
{
    public function __construct(private readonly CrmNotifier $notifier) {}

    public function store(Request $request): RedirectResponse
    {
        /** @var CrmUser $user */
        $user = $request->attributes->get('crm_user');
        $data = $this->validatedLead($request, $user, 'leadCreate');
        $data['phone'] = $this->normalisePhone($data['phone']);

        if (CrmLead::withTrashed()->where('phone', $data['phone'])->exists()) {
            return back()->withErrors(['phone' => 'A lead with this phone number already exists.'], 'leadCreate')->withInput();
        }

        $data['created_by'] = $user->id;
        $data['assigned_to'] = $user->isSuperAdmin() ? ($data['assigned_to'] ?? null) : $user->id;
        $data['lead_number'] = 'PENDING-'.str()->uuid();

        $lead = DB::transaction(function () use ($data, $user): CrmLead {
            $lead = CrmLead::query()->create($data);
            $lead->update(['lead_number' => 'OD-'.str_pad((string) (10000 + $lead->id), 5, '0', STR_PAD_LEFT)]);
            $this->activity($lead, $user, 'created', 'Lead created'.($lead->assignee ? ' and assigned to '.$lead->assignee->name : ' without an owner').'.');

            return $lead;
        });

        $this->notifyLead(
            $lead,
            $user,
            'New CRM lead: '.$lead->name,
            'A new lead was created',
            $user->name.' created '.$lead->name.' in the CRM.',
        );

        return redirect()->route('crm.dashboard', ['lead' => $lead->id])->with('status', 'Lead created successfully.');
    }

    public function update(Request $request, CrmLead $lead): RedirectResponse
    {
        /** @var CrmUser $user */
        $user = $request->attributes->get('crm_user');
        $this->guardLead($lead, $user);
        $data = $this->validatedLead($request, $user);
        $data['phone'] = $this->normalisePhone($data['phone']);
        if (! $user->isSuperAdmin()) {
            unset($data['assigned_to']);
        }

        $duplicate = CrmLead::withTrashed()->where('phone', $data['phone'])->whereKeyNot($lead->id)->exists();
        if ($duplicate) {
            return back()->withErrors(['phone' => 'Another lead already uses this phone number.'])->withInput();
        }

        $before = $lead->getAttributes();
        $previousAssignee = $lead->assignee;
        $lead->fill($data);
        if ($lead->isDirty('follow_up_at')) {
            $lead->follow_up_completed_at = null;
        }
        $changes = $lead->getDirty();
        $lead->save();

        $labels = [];
        foreach (array_keys($changes) as $field) {
            if ($field === 'updated_at') {
                continue;
            }
            $labels[] = match ($field) {
                'assigned_to' => 'owner', 'follow_up_at' => 'follow-up', 'course_interest' => 'course',
                'country_interest' => 'country', 'student_stage' => 'student stage', default => str_replace('_', ' ', $field),
            };
        }
        if ($labels !== []) {
            $this->activity($lead, $user, 'updated', 'Updated '.implode(', ', $labels).'.', ['before' => $before, 'changes' => $changes]);
            $lead->unsetRelation('assignee')->load('assignee');
            $this->notifyLead(
                $lead,
                $user,
                'CRM lead updated: '.$lead->name,
                'Lead details were updated',
                $user->name.' updated '.implode(', ', $labels).' for '.$lead->name.'.',
                ['Changed fields' => implode(', ', $labels)],
                $previousAssignee ? [$previousAssignee] : [],
            );
        }

        return back()->with('status', 'Lead details updated.');
    }

    public function comment(Request $request, CrmLead $lead): RedirectResponse|JsonResponse
    {
        /** @var CrmUser $user */
        $user = $request->attributes->get('crm_user');
        $this->guardLead($lead, $user);
        $data = $request->validate(['comment' => ['required', 'string', 'max:3000']]);
        $activity = $this->activity($lead, $user, 'comment', trim($data['comment']));
        $lead->update(['last_contacted_at' => now()]);
        $this->notifyLead(
            $lead,
            $user,
            'New CRM timeline note: '.$lead->name,
            'A timeline note was added',
            $user->name.' added a new interaction note for '.$lead->name.'.',
            ['Timeline note' => trim($data['comment'])],
        );

        if ($request->expectsJson()) {
            $activity->load('user');

            return response()->json([
                'message' => 'Comment added to the timeline.',
                'activity' => [
                    'id' => $activity->id,
                    'actor' => $activity->user?->name ?? 'System',
                    'label' => str_replace('_', ' ', ucfirst($activity->type)),
                    'body' => $activity->body,
                    'created_at' => $activity->created_at->format('d M Y, g:i A'),
                ],
                'total' => $lead->activities()->count(),
            ]);
        }

        return back()->with('status', 'Comment added to the timeline.');
    }

    public function completeFollowUp(Request $request, CrmLead $lead): RedirectResponse
    {
        /** @var CrmUser $user */
        $user = $request->attributes->get('crm_user');
        $this->guardLead($lead, $user);
        $lead->update(['follow_up_completed_at' => now(), 'last_contacted_at' => now()]);
        $this->activity($lead, $user, 'follow_up_done', 'Follow-up marked complete.');
        $this->notifyLead(
            $lead,
            $user,
            'CRM follow-up completed: '.$lead->name,
            'Follow-up marked complete',
            $user->name.' completed the scheduled follow-up for '.$lead->name.'.',
        );

        return back()->with('status', 'Follow-up completed.');
    }

    public function convert(Request $request, CrmLead $lead): RedirectResponse
    {
        /** @var CrmUser $user */
        $user = $request->attributes->get('crm_user');
        $this->guardLead($lead, $user);
        $data = $request->validate([
            'student_category' => ['required', Rule::in(array_keys(CrmOptions::STUDENT_CATEGORIES))],
            'student_stage' => ['required', Rule::in(array_keys(CrmOptions::STUDENT_STAGES))],
            'enrollment_amount' => ['nullable', 'integer', 'min:0'],
            'enrollment_date' => ['nullable', 'date'],
            'payment_reference' => ['nullable', 'string', 'max:150'],
            'conversion_remarks' => ['nullable', 'string', 'max:3000'],
        ]);
        $lead->update($data + ['is_student' => true, 'status' => 'converted']);
        $this->activity($lead, $user, 'converted', 'Converted to an enrolled student at stage “'.CrmOptions::STUDENT_STAGES[$data['student_stage']].'”.');

        $this->notifyLead(
            $lead,
            $user,
            'CRM conversion: '.$lead->name,
            'Lead converted to an enrolled student',
            $user->name.' converted '.$lead->name.' into a student journey.',
            [
                'Student stage' => CrmOptions::STUDENT_STAGES[$data['student_stage']],
                'Enrollment amount' => isset($data['enrollment_amount']) ? 'INR '.number_format((int) $data['enrollment_amount']) : 'Not set',
            ],
        );

        return back()->with('status', 'Lead converted to an enrolled student.');
    }

    public function updateStudentJourney(Request $request, CrmLead $lead): RedirectResponse
    {
        /** @var CrmUser $user */
        $user = $request->attributes->get('crm_user');
        $this->guardLead($lead, $user);
        abort_unless($lead->is_student, 422);

        $data = $request->validate([
            'student_category' => ['required', Rule::in(array_keys(CrmOptions::STUDENT_CATEGORIES))],
            'student_stage' => ['required', Rule::in(array_keys(CrmOptions::STUDENT_STAGES))],
            'enrollment_amount' => ['nullable', 'integer', 'min:0'],
            'enrollment_date' => ['nullable', 'date'],
            'payment_reference' => ['nullable', 'string', 'max:150'],
            'conversion_remarks' => ['nullable', 'string', 'max:3000'],
        ]);

        $before = collect(array_keys($data))->mapWithKeys(fn (string $field): array => [$field => $lead->getRawOriginal($field)])->all();
        $lead->fill($data);
        $dirty = $lead->getDirty();
        $lead->save();
        $changes = array_keys($dirty);

        if ($changes !== []) {
            $stage = CrmOptions::STUDENT_STAGES[$lead->student_stage];
            $body = in_array('student_stage', $changes, true)
                ? 'Student journey advanced to “'.$stage.'”.'
                : 'Updated student enrolment information.';
            $this->activity($lead, $user, 'student_stage', $body, ['before' => $before, 'changes' => $dirty]);
            $this->notifyLead(
                $lead,
                $user,
                'CRM student journey updated: '.$lead->name,
                'Student journey updated',
                $user->name.' updated the student journey for '.$lead->name.'.',
                [
                    'Student stage' => CrmOptions::STUDENT_STAGES[$lead->student_stage],
                    'Changed fields' => implode(', ', array_map(fn (string $field): string => str_replace('_', ' ', $field), $changes)),
                ],
            );
        }

        return back()->with('status', $changes === [] ? 'Student journey is already up to date.' : 'Student journey updated.');
    }

    public function destroy(Request $request, CrmLead $lead): RedirectResponse
    {
        /** @var CrmUser $user */
        $user = $request->attributes->get('crm_user');
        abort_unless($user->isSuperAdmin(), 403);
        $lead->loadMissing('assignee');
        $this->notifier->sendToUsers(
            $this->notifier->leadRecipients($lead, $user),
            'CRM lead moved to trash: '.$lead->name,
            'Lead moved to trash',
            $user->name.' moved '.$lead->name.' to the CRM trash.',
            $this->notifier->leadDetails($lead),
            route('crm.dashboard'),
        );
        $lead->delete();

        return redirect()->route('crm.dashboard')->with('status', 'Lead moved to trash.');
    }

    public function import(Request $request): RedirectResponse
    {
        /** @var CrmUser $user */
        $user = $request->attributes->get('crm_user');
        $data = $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
            'assigned_to' => ['nullable', Rule::exists('crm_users', 'id')->where(fn ($q) => $q->where('role', 'counsellor')->where('is_active', true))],
        ]);

        $handle = fopen($data['file']->getRealPath(), 'r');
        $header = fgetcsv($handle) ?: [];
        $header = array_map(fn ($value) => strtolower(trim((string) $value)), $header);
        $created = 0;
        $skipped = 0;
        $firstCreatedLead = null;
        while (($row = fgetcsv($handle)) !== false) {
            $row = array_pad($row, count($header), null);
            $item = array_combine($header, array_slice($row, 0, count($header))) ?: [];
            $phone = $this->normalisePhone((string) ($item['phone'] ?? $item['mobile'] ?? $item['phone number'] ?? ''));
            $name = trim((string) ($item['name'] ?? $item['full name'] ?? $item['student name'] ?? ''));
            if (strlen($phone) !== 10 || $name === '' || CrmLead::withTrashed()->where('phone', $phone)->exists()) {
                $skipped++;

                continue;
            }

            $lead = CrmLead::query()->create([
                'lead_number' => 'PENDING-'.str()->uuid(), 'name' => mb_substr($name, 0, 150), 'phone' => $phone,
                'email' => $item['email'] ?? null, 'city' => $item['city'] ?? null,
                'course_interest' => $item['course'] ?? $item['course interest'] ?? null,
                'country_interest' => $item['country'] ?? $item['country interest'] ?? null,
                'category' => array_key_exists((string) ($item['category'] ?? ''), CrmOptions::CATEGORIES) ? $item['category'] : null,
                'priority' => array_key_exists((string) ($item['priority'] ?? ''), CrmOptions::PRIORITIES) ? $item['priority'] : 'medium',
                'source' => $item['source'] ?? 'CSV import', 'status' => 'new',
                'assigned_to' => $user->isSuperAdmin() ? ($data['assigned_to'] ?? null) : $user->id, 'created_by' => $user->id,
            ]);
            $lead->update(['lead_number' => 'OD-'.str_pad((string) (10000 + $lead->id), 5, '0', STR_PAD_LEFT)]);
            $this->activity($lead, $user, 'imported', 'Lead imported from CSV.');
            $firstCreatedLead ??= $lead;
            $created++;
        }
        fclose($handle);

        if ($firstCreatedLead) {
            $firstCreatedLead->loadMissing('assignee');
            $this->notifier->sendToUsers(
                $this->notifier->leadRecipients($firstCreatedLead, $user),
                'CRM lead import completed',
                'Lead import completed',
                $user->name.' completed a CRM lead import.',
                [
                    'Imported leads' => $created,
                    'Skipped rows' => $skipped,
                    'Assigned counsellor' => $firstCreatedLead->assignee?->name ?? 'Unassigned',
                ],
                route('crm.dashboard', ['view' => 'leads']),
                'View imported leads',
            );
        }

        return back()->with('status', "Imported {$created} lead(s); skipped {$skipped} invalid or duplicate row(s).");
    }

    private function validatedLead(Request $request, CrmUser $user, ?string $errorBag = null): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['required', 'string', 'regex:/^(?:\+?91[\s-]?)?[6-9][0-9\s-]{8,13}$/'],
            'email' => ['nullable', 'email', 'max:190'], 'city' => ['nullable', 'string', 'max:120'],
            'course_interest' => ['nullable', 'string', 'max:180'], 'country_interest' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', Rule::in(array_keys(CrmOptions::CATEGORIES))],
            'priority' => ['required', Rule::in(array_keys(CrmOptions::PRIORITIES))],
            'source' => ['nullable', 'string', 'max:100'], 'status' => ['required', Rule::in(array_keys(CrmOptions::STATUSES))],
            'assigned_to' => [$user->isSuperAdmin() ? 'nullable' : 'prohibited', Rule::exists('crm_users', 'id')->where(fn ($q) => $q->where('role', 'counsellor')->where('is_active', true))],
            'follow_up_at' => ['nullable', 'date'],
            'student_stage' => ['nullable', Rule::in(array_keys(CrmOptions::STUDENT_STAGES))],
        ];

        if ($errorBag !== null) {
            return Validator::make($request->all(), $rules)->validateWithBag($errorBag);
        }

        return $request->validate($rules);
    }

    private function normalisePhone(string $phone): string
    {
        return substr((string) preg_replace('/\D+/', '', $phone), -10);
    }

    private function guardLead(CrmLead $lead, CrmUser $user): void
    {
        abort_unless($user->isSuperAdmin() || $lead->assigned_to === $user->id, 403);
    }

    private function activity(CrmLead $lead, CrmUser $user, string $type, string $body, array $metadata = []): CrmLeadActivity
    {
        return CrmLeadActivity::query()->create([
            'crm_lead_id' => $lead->id, 'crm_user_id' => $user->id,
            'type' => $type, 'body' => $body, 'metadata' => $metadata ?: null,
        ]);
    }

    /**
     * @param  array<string, mixed>  $details
     * @param  iterable<int, CrmUser>  $extraRecipients
     */
    private function notifyLead(
        CrmLead $lead,
        CrmUser $actor,
        string $subject,
        string $headline,
        string $message,
        array $details = [],
        iterable $extraRecipients = [],
    ): void {
        $lead->loadMissing('assignee');
        $this->notifier->sendToUsers(
            $this->notifier->leadRecipients($lead, $actor, $extraRecipients),
            $subject,
            $headline,
            $message,
            [...$this->notifier->leadDetails($lead), ...$details],
            route('crm.dashboard', ['lead' => $lead->id]),
            'Open lead',
        );
    }
}
