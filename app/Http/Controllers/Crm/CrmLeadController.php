<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmLead;
use App\Models\CrmLeadActivity;
use App\Models\CrmUser;
use App\Services\CrmAuditLogger;
use App\Support\CrmOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CrmLeadController extends Controller
{
    public function __construct(
        private readonly CrmAuditLogger $auditLogger,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        /** @var CrmUser $user */
        $user = $request->attributes->get('crm_user');
        if ($request->input('status') === 'converted') {
            return back()->withErrors(['status' => 'Use “Convert to Student” from the Student tab to enroll this lead.'], 'leadCreate')->withInput();
        }
        $data = $this->validatedLead($request, $user, 'leadCreate');
        $data['phone'] = $this->normalisePhone((string) ($data['phone'] ?? '')) ?: null;
        $data['email'] = $this->normaliseEmail((string) ($data['email'] ?? '')) ?: null;

        if ($data['phone'] && CrmLead::withTrashed()->where('phone', $data['phone'])->exists()) {
            return back()->withErrors(['phone' => 'A lead with this phone number already exists.'], 'leadCreate')->withInput();
        }
        if ($data['email'] && CrmLead::withTrashed()->whereRaw('LOWER(email) = ?', [$data['email']])->exists()) {
            return back()->withErrors(['email' => 'A lead with this email address already exists.'], 'leadCreate')->withInput();
        }

        $data['created_by'] = $user->id;
        $data['lead_origin'] = 'manual';
        $data['lead_type'] = $data['lead_type'] ?? 'general';
        $data['source'] = trim((string) ($data['source'] ?? '')) ?: 'Manual creation';
        $data['assigned_to'] = $user->isSuperAdmin() ? ($data['assigned_to'] ?? null) : $user->id;
        $data['lead_number'] = 'PENDING-'.str()->random(11); // fits varchar(20); overwritten with OD-##### below

        $lead = DB::transaction(function () use ($data, $user): CrmLead {
            $lead = CrmLead::query()->create($data);
            $lead->update(['lead_number' => 'OD-'.str_pad((string) (10000 + $lead->id), 5, '0', STR_PAD_LEFT)]);
            $this->activity($lead, $user, 'created', 'Lead created'.($lead->assignee ? ' and assigned to '.$lead->assignee->name : ' without an owner').'.');

            return $lead;
        });

        $this->auditLead($request, $user, $lead, 'lead_created', 'Created lead '.$lead->name.'.', [
            'after' => $lead->only(['lead_number', 'name', 'phone', 'email', 'status', 'priority', 'assigned_to', 'follow_up_at']),
        ]);

        return redirect()->route('crm.dashboard', ['lead' => $lead->id])->with('status', 'Lead created successfully.');
    }

    public function update(Request $request, CrmLead $lead): RedirectResponse
    {
        /** @var CrmUser $user */
        $user = $request->attributes->get('crm_user');
        $this->guardLead($lead, $user);
        if (! $lead->is_student && $lead->status !== 'converted' && $request->input('status') === 'converted') {
            return back()->withErrors(['status' => 'Use “Convert to Student” from the Student tab to enroll this lead.'])->withInput();
        }
        $data = $this->validatedLead($request, $user, null, $lead);
        $data['phone'] = $this->normalisePhone((string) ($data['phone'] ?? '')) ?: null;
        $data['email'] = $this->normaliseEmail((string) ($data['email'] ?? '')) ?: null;
        if (! $user->isSuperAdmin()) {
            unset($data['assigned_to']);
        }

        $phoneChanged = $data['phone'] !== ($this->normalisePhone((string) $lead->phone) ?: null);
        $duplicate = $phoneChanged && $data['phone'] && CrmLead::withTrashed()->where('phone', $data['phone'])->whereKeyNot($lead->id)->exists();
        if ($duplicate) {
            return back()->withErrors(['phone' => 'Another lead already uses this phone number.'])->withInput();
        }
        $emailChanged = $data['email'] !== ($this->normaliseEmail((string) $lead->email) ?: null);
        $duplicateEmail = $emailChanged && $data['email'] && CrmLead::withTrashed()
            ->whereRaw('LOWER(email) = ?', [$data['email']])
            ->whereKeyNot($lead->id)
            ->exists();
        if ($duplicateEmail) {
            return back()->withErrors(['email' => 'Another lead already uses this email address.'])->withInput();
        }

        $before = $lead->getAttributes();
        $lead->fill($data);
        if ($lead->isDirty('follow_up_at')) {
            $lead->follow_up_completed_at = null;
        }
        $changes = $lead->getDirty();
        $lead->save();

        $labels = [];
        foreach (array_keys($changes) as $field) {
            if (in_array($field, ['updated_at', 'follow_up_completed_at'], true)) {
                continue;
            }
            $labels[] = match ($field) {
                'assigned_to' => 'owner', 'follow_up_at' => 'follow-up', 'course_interest' => 'course',
                'country_interest' => 'country', 'student_stage' => 'student stage', default => str_replace('_', ' ', $field),
            };
        }
        if ($labels !== []) {
            $story = $this->describeChanges($before, $changes);
            $this->activity($lead, $user, 'updated', $story ?: 'Updated '.implode(', ', $labels).'.', ['before' => $before, 'changes' => $changes]);
            $lead->unsetRelation('assignee')->load('assignee');
            $this->auditLead($request, $user, $lead, 'lead_updated', 'Updated '.implode(', ', $labels).' for '.$lead->name.'.', [
                'before' => array_intersect_key($before, $changes),
                'after' => $changes,
            ]);
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
        $this->auditLead($request, $user, $lead, 'timeline_comment_added', 'Added a timeline comment for '.$lead->name.'.', [
            'comment' => trim($data['comment']),
        ]);

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
        $before = ['follow_up_completed_at' => $lead->getRawOriginal('follow_up_completed_at')];
        $lead->update(['follow_up_completed_at' => now(), 'last_contacted_at' => now()]);
        $this->activity($lead, $user, 'follow_up_done', 'Follow-up marked complete.');
        $this->auditLead($request, $user, $lead, 'follow_up_completed', 'Completed the scheduled follow-up for '.$lead->name.'.', [
            'before' => $before,
            'after' => ['follow_up_completed_at' => $lead->follow_up_completed_at?->toDateTimeString()],
        ]);

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
        $before = $lead->only(['is_student', 'status', 'student_category', 'student_stage', 'enrollment_amount', 'enrollment_date', 'payment_reference', 'conversion_remarks']);
        $lead->update($data + ['is_student' => true, 'status' => 'converted']);
        $this->activity($lead, $user, 'converted', 'Converted to an enrolled student at stage “'.CrmOptions::STUDENT_STAGES[$data['student_stage']].'”.');

        $this->auditLead($request, $user, $lead, 'lead_converted', 'Converted '.$lead->name.' into an enrolled student.', [
            'before' => $before,
            'after' => $lead->only(array_keys($before)),
        ]);

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
            if (in_array('student_stage', $changes, true)) {
                $fromStage = CrmOptions::STUDENT_STAGES[$before['student_stage']] ?? null;
                $toStage = CrmOptions::STUDENT_STAGES[$lead->student_stage];
                $body = $fromStage
                    ? 'Student stage changed from “'.$fromStage.'” to “'.$toStage.'”.'
                    : 'Student stage set to “'.$toStage.'”.';
            } else {
                $body = 'Updated student enrolment information.';
            }
            $this->activity($lead, $user, 'student_stage', $body, ['before' => $before, 'changes' => $dirty]);
            $this->auditLead($request, $user, $lead, 'student_journey_updated', 'Updated the student journey for '.$lead->name.'.', [
                'before' => array_intersect_key($before, $dirty),
                'after' => $dirty,
            ]);
        }

        return back()->with('status', $changes === [] ? 'Student journey is already up to date.' : 'Student journey updated.');
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
        while (($row = fgetcsv($handle)) !== false) {
            $row = array_pad($row, count($header), null);
            $item = array_combine($header, array_slice($row, 0, count($header))) ?: [];
            $phone = $this->normalisePhone((string) ($item['phone'] ?? $item['mobile'] ?? $item['phone number'] ?? ''));
            $name = trim((string) ($item['name'] ?? $item['full name'] ?? $item['student name'] ?? ''));
            $email = $this->normaliseEmail((string) ($item['email'] ?? ''));
            $duplicateEmail = $email !== '' && CrmLead::withTrashed()->whereRaw('LOWER(email) = ?', [$email])->exists();
            if (strlen($phone) !== 10 || $name === '' || CrmLead::withTrashed()->where('phone', $phone)->exists() || $duplicateEmail) {
                $skipped++;

                continue;
            }

            $lead = CrmLead::query()->create([
                'lead_number' => 'PENDING-'.str()->random(11), 'name' => mb_substr($name, 0, 150), 'phone' => $phone,
                'email' => $email ?: null, 'city' => $item['city'] ?? null,
                'course_interest' => $item['course'] ?? $item['course interest'] ?? null,
                'country_interest' => $item['country'] ?? $item['country interest'] ?? null,
                'category' => array_key_exists((string) ($item['category'] ?? ''), CrmOptions::CATEGORIES) ? $item['category'] : null,
                'priority' => array_key_exists((string) ($item['priority'] ?? ''), CrmOptions::PRIORITIES) ? $item['priority'] : 'medium',
                'source' => $item['source'] ?? 'CSV import', 'lead_origin' => 'import',
                'lead_type' => array_key_exists((string) ($item['lead type'] ?? $item['type'] ?? ''), CrmOptions::LEAD_TYPES) ? ($item['lead type'] ?? $item['type']) : 'general',
                'status' => 'new',
                'assigned_to' => $user->isSuperAdmin() ? ($data['assigned_to'] ?? null) : $user->id, 'created_by' => $user->id,
            ]);
            $lead->update(['lead_number' => 'OD-'.str_pad((string) (10000 + $lead->id), 5, '0', STR_PAD_LEFT)]);
            $this->activity($lead, $user, 'imported', 'Lead imported from CSV.');
            $created++;
        }
        fclose($handle);

        $this->auditLogger->record($request, $user, 'leads_imported', 'Imported '.$created.' lead(s) and skipped '.$skipped.' row(s).', [
            'subject_type' => 'lead_import',
            'subject_label' => $data['file']->getClientOriginalName(),
            'changes' => ['imported' => $created, 'skipped' => $skipped, 'assigned_to' => $data['assigned_to'] ?? $user->id],
        ]);

        return back()->with('status', "Imported {$created} lead(s); skipped {$skipped} invalid or duplicate row(s).");
    }

    private function validatedLead(Request $request, CrmUser $user, ?string $errorBag = null, ?CrmLead $lead = null): array
    {
        $allowedStatuses = array_keys(CrmOptions::pipelineStatuses());
        if ($lead?->is_student) {
            $allowedStatuses = ['converted'];
        } elseif ($lead?->status === 'converted') {
            $allowedStatuses[] = 'converted';
        }
        $rules = [
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'required_without:email', 'string', 'max:30'],
            'email' => ['nullable', 'required_without:phone', 'email', 'max:190'], 'city' => ['nullable', 'string', 'max:120'],
            'course_interest' => ['nullable', 'string', 'max:180'], 'country_interest' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', Rule::in(array_keys(CrmOptions::CATEGORIES))],
            'lead_type' => ['nullable', Rule::in(array_keys(CrmOptions::LEAD_TYPES))],
            'priority' => ['required', Rule::in(array_keys(CrmOptions::PRIORITIES))],
            'source' => ['nullable', 'string', 'max:100'], 'status' => ['required', Rule::in($allowedStatuses)],
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

    private function normaliseEmail(string $email): string
    {
        return strtolower(trim($email));
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
     * Turn a set of dirty lead fields into a human sentence for the timeline,
     * e.g. "Status changed from “New lead” to “Interested”. Owner changed from “Unassigned” to “Priya”."
     *
     * @param  array<string, mixed>  $before   Full original attributes
     * @param  array<string, mixed>  $changes  Dirty fields (field => new value)
     */
    private function describeChanges(array $before, array $changes): string
    {
        $fields = array_diff(array_keys($changes), ['updated_at', 'follow_up_completed_at']);
        if ($fields === []) {
            return '';
        }

        // Resolve counsellor names for any owner change in one query.
        $userNames = [];
        if (in_array('assigned_to', $fields, true)) {
            $ids = array_filter([$before['assigned_to'] ?? null, $changes['assigned_to'] ?? null]);
            $userNames = $ids === []
                ? []
                : CrmUser::query()->whereKey($ids)->pluck('name', 'id')->all();
        }

        $labels = [
            'assigned_to' => 'Owner', 'follow_up_at' => 'Follow-up', 'course_interest' => 'Course',
            'country_interest' => 'Country', 'student_stage' => 'Student stage', 'lead_type' => 'Lead type',
            'status' => 'Status', 'priority' => 'Priority', 'category' => 'Category', 'source' => 'Source',
            'city' => 'City', 'name' => 'Name', 'phone' => 'Phone', 'email' => 'Email',
        ];

        $format = function (string $field, $value) use ($userNames): string {
            if ($value === null || $value === '') {
                return '';
            }
            return match ($field) {
                'status' => CrmOptions::STATUSES[$value] ?? (string) $value,
                'priority' => CrmOptions::PRIORITIES[$value] ?? (string) $value,
                'category' => CrmOptions::CATEGORIES[$value] ?? (string) $value,
                'lead_type' => CrmOptions::LEAD_TYPES[$value] ?? (string) $value,
                'student_stage' => CrmOptions::STUDENT_STAGES[$value] ?? (string) $value,
                'assigned_to' => $userNames[$value] ?? 'counsellor #'.$value,
                'follow_up_at' => \Illuminate\Support\Carbon::parse($value)->format('d M Y, g:i A'),
                'phone' => '+91 '.$value,
                default => (string) $value,
            };
        };

        $sentences = [];
        foreach ($fields as $field) {
            $label = $labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
            $old = $format($field, $before[$field] ?? null);
            $new = $format($field, $changes[$field] ?? null);

            if ($new === '' && $old === '') {
                continue;
            }
            $sentences[] = match (true) {
                $old === '' => $field === 'assigned_to'
                    ? 'Assigned to “'.$new.'”.'
                    : $label.' set to “'.$new.'”.',
                $new === '' => $label.' cleared (was “'.$old.'”).',
                default => $label.' changed from “'.$old.'” to “'.$new.'”.',
            };
        }

        return implode(' ', $sentences);
    }

    /** @param array<string, mixed> $changes */
    private function auditLead(Request $request, CrmUser $actor, CrmLead $lead, string $event, string $description, array $changes = []): void
    {
        $this->auditLogger->record($request, $actor, $event, $description, [
            'crm_lead_id' => $lead->id,
            'subject_type' => 'lead',
            'subject_id' => $lead->id,
            'subject_label' => $lead->lead_number.' · '.$lead->name,
            'changes' => $changes,
        ]);
    }
}
