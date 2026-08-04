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
    /** Human field names used by the timeline story and the audit log. */
    private const FIELD_LABELS = [
        'assigned_to' => 'Owner', 'follow_up_at' => 'Follow-up', 'course_interest' => 'Course',
        'country_interest' => 'Country', 'student_stage' => 'Student stage', 'lead_type' => 'Lead type',
        'status' => 'Status', 'priority' => 'Priority', 'category' => 'Category', 'source' => 'Source',
        'city' => 'City', 'name' => 'Name', 'phone' => 'Phone', 'email' => 'Email',
        'tenth_score' => '10th %', 'tenth_passing_year' => '10th passing year',
        'twelfth_score' => '12th %', 'twelfth_passing_year' => '12th passing year',
        'graduation_score' => 'Graduation CGPA / %', 'graduation_passing_year' => 'Graduation passing year',
        'backlogs' => 'Backlogs', 'intake' => 'Intake', 'english_tests' => 'English proficiency tests',
        'aptitude_tests' => 'Aptitude tests',
        'counselling' => 'Counselling', 'shortlisting' => 'Shortlisting',
    ];

    /** The repeatable test groups on the academic card, and the catalog each validates against. */
    private const TEST_GROUPS = [
        'english_tests' => CrmOptions::ENGLISH_TESTS,
        'aptitude_tests' => CrmOptions::APTITUDE_TESTS,
    ];

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
        // Only a super admin gets this far with a non-converted status on a
        // student (validatedLead limits the list); moving the status out of
        // "Enrolled student" is what takes the lead back out of the journey.
        $reverting = (bool) $before['is_student'] && ($data['status'] ?? null) !== 'converted';
        if ($reverting) {
            $lead->is_student = false;
        }
        if ($lead->isDirty('follow_up_at')) {
            $lead->follow_up_completed_at = null;
        }
        $changes = $lead->getDirty();
        $lead->save();

        if ($reverting) {
            $body = 'Enrollment reverted by a super admin — the record is back in the pipeline as “'
                .(CrmOptions::STATUSES[$lead->status] ?? $lead->status).'”. The enrollment details are kept and reappear if it is converted again.';
            $this->activity($lead, $user, 'enrollment_reverted', $body, ['before' => $before, 'changes' => $changes]);
            $this->auditLead($request, $user, $lead, 'lead_enrollment_reverted', 'Reverted the enrollment for '.$lead->name.'.', [
                'before' => array_intersect_key($before, $changes),
                'after' => $changes,
            ]);
        }

        // A revert has already said its own piece above, status and all, so the
        // generic story covers only whatever else was edited in the same save.
        $storyChanges = array_diff_key($changes, array_flip($reverting ? ['is_student', 'status'] : []));
        $labels = [];
        foreach (array_keys($storyChanges) as $field) {
            if (in_array($field, ['updated_at', 'follow_up_completed_at'], true)) {
                continue;
            }
            $labels[] = match ($field) {
                'assigned_to' => 'owner', 'follow_up_at' => 'follow-up', 'course_interest' => 'course',
                'country_interest' => 'country', 'student_stage' => 'student stage',
                default => mb_strtolower(self::FIELD_LABELS[$field] ?? str_replace('_', ' ', $field)),
            };
        }
        if ($labels !== []) {
            $story = $this->describeChanges($before, $storyChanges);
            $this->activity($lead, $user, 'updated', $story ?: 'Updated '.implode(', ', $labels).'.', ['before' => $before, 'changes' => $storyChanges]);
            $lead->unsetRelation('assignee')->load('assignee');
            $this->auditLead($request, $user, $lead, 'lead_updated', 'Updated '.implode(', ', $labels).' for '.$lead->name.'.', [
                'before' => array_intersect_key($before, $storyChanges),
                'after' => $storyChanges,
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
        // Enrolling twice would overwrite the recorded enrollment with a blank
        // form's worth of data; the button is gone once it is done, but the route
        // is not, and a resubmitted POST would land right here.
        if ($lead->is_student) {
            return back()->withErrors(['student_stage' => 'This lead is already an enrolled student. Update the journey instead.'])->withInput();
        }
        $data = $request->validate($this->enrollmentRules($request), $this->enrollmentMessages());
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

        // The same mandatory set the conversion asked for: what a conversion has
        // to record, an update must not be able to empty back out.
        $data = $request->validate($this->enrollmentRules($request), $this->enrollmentMessages());

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

    /**
     * What an enrollment has to carry, shared by the conversion and by every
     * later journey update so the two can never disagree.
     *
     * Enrollment amount and date are mandatory: a conversion is a financial
     * record, and one made by mistake used to be indistinguishable from a real
     * one with nothing filled in. Zero is still a valid amount — that is what a
     * non-paid student is — but it has to be typed, not left blank. A paid
     * student instead needs a real figure and the receipt it came from.
     *
     * @return array<string, array<int, mixed>>
     */
    private function enrollmentRules(Request $request): array
    {
        $paid = in_array((string) $request->input('student_category'), CrmOptions::PAID_STUDENT_CATEGORIES, true);

        return [
            'student_category' => ['required', Rule::in(array_keys(CrmOptions::STUDENT_CATEGORIES))],
            'student_stage' => ['required', Rule::in(array_keys(CrmOptions::STUDENT_STAGES))],
            'enrollment_amount' => ['required', 'integer', $paid ? 'min:1' : 'min:0'],
            'enrollment_date' => ['required', 'date'],
            'payment_reference' => [Rule::requiredIf($paid), 'nullable', 'string', 'max:150'],
            'conversion_remarks' => ['nullable', 'string', 'max:3000'],
        ];
    }

    /** @return array<string, string> */
    private function enrollmentMessages(): array
    {
        return [
            'enrollment_amount.required' => 'Enter the enrollment amount before enrolling this student — use 0 for a non-paid student.',
            'enrollment_amount.min' => 'A paid student needs an enrollment amount above ₹0.',
            'enrollment_date.required' => 'Pick the enrollment date before enrolling this student.',
            'payment_reference.required' => 'Add the receipt or transaction ID for a paid enrollment.',
        ];
    }

    private function validatedLead(Request $request, CrmUser $user, ?string $errorBag = null, ?CrmLead $lead = null): array
    {
        $allowedStatuses = array_keys(CrmOptions::pipelineStatuses());
        if ($lead?->is_student) {
            // A counsellor can only leave an enrolled student where it is. A super
            // admin can move it back into the pipeline, which is how a conversion
            // made by mistake gets undone.
            $allowedStatuses = $user->isSuperAdmin() ? [...$allowedStatuses, 'converted'] : ['converted'];
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
            // An open follow-up status is a promise to talk again, so it has to carry a date.
            'follow_up_at' => ['nullable', Rule::requiredIf(
                fn (): bool => in_array((string) $request->input('status'), CrmOptions::FOLLOW_UP_STATUSES, true)
            ), 'date'],
            'student_stage' => ['nullable', Rule::in(array_keys(CrmOptions::STUDENT_STAGES))],
            'tenth_score' => ['nullable', 'string', 'max:40'],
            'twelfth_score' => ['nullable', 'string', 'max:40'],
            'graduation_score' => ['nullable', 'string', 'max:40'],
            'tenth_passing_year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'twelfth_passing_year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'graduation_passing_year' => ['nullable', 'integer', 'min:1950', 'max:2100'],
            'backlogs' => ['nullable', 'string', 'max:40'],
            // Free text: intakes are named differently per destination and year.
            'intake' => ['nullable', 'string', 'max:60'],
            // Blank is a real answer for both — see CrmOptions::DONE_STATES.
            'counselling' => ['nullable', Rule::in(array_keys(CrmOptions::DONE_STATES))],
            'shortlisting' => ['nullable', Rule::in(array_keys(CrmOptions::DONE_STATES))],
            'english_tests' => ['nullable', 'array', 'max:12'],
            'english_tests.*.test' => ['nullable', Rule::in(array_keys(CrmOptions::ENGLISH_TESTS))],
            'aptitude_tests' => ['nullable', 'array', 'max:12'],
            'aptitude_tests.*.test' => ['nullable', Rule::in(array_keys(CrmOptions::APTITUDE_TESTS))],
        ];

        $messages = ['follow_up_at.required' => 'Set the next follow-up date and time for this pipeline status.'];
        foreach (self::TEST_GROUPS as $group => $catalog) {
            $rules[$group.'.*.name'] = ['nullable', 'required_if:'.$group.'.*.test,other', 'string', 'max:60'];
            $rules[$group.'.*.score'] = ['nullable', 'string', 'max:40'];
            $rules[$group.'.*.date'] = ['nullable', 'date'];
            $messages[$group.'.*.name.required_if'] = 'Enter the name of the other test.';
        }

        $data = $errorBag !== null
            ? Validator::make($request->all(), $rules, $messages)->validateWithBag($errorBag)
            : $request->validate($rules, $messages);

        return $this->normaliseTestRows($request, $this->defaultFollowUpStatus($data, $lead));
    }

    /**
     * Scheduling a conversation puts the lead back in the planner: a new or changed
     * follow-up date defaults the status to "Follow up", unless the counsellor
     * already picked one of the open follow-up statuses.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function defaultFollowUpStatus(array $data, ?CrmLead $lead): array
    {
        $followUp = $data['follow_up_at'] ?? null;
        $status = (string) ($data['status'] ?? '');
        // An enrolled student stays enrolled — unless this save is the super-admin
        // revert, which puts the record back in the pipeline and so back under the
        // planner's rules.
        if (blank($followUp) || $status === 'converted') {
            return $data;
        }
        if (in_array($status, CrmOptions::FOLLOW_UP_STATUSES, true)) {
            return $data;
        }
        // Leave an untouched date alone — only a freshly picked one moves the status.
        if ($lead?->follow_up_at?->equalTo(\Illuminate\Support\Carbon::parse($followUp))) {
            return $data;
        }
        $data['status'] = 'follow_up';

        return $data;
    }

    /**
     * Tidy the test repeaters: drop rows with no test picked, re-index the array,
     * and keep the free-text name only where "Other" was chosen.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normaliseTestRows(Request $request, array $data): array
    {
        foreach (array_keys(self::TEST_GROUPS) as $group) {
            // A repeater emptied of every row submits no rows at all, so the hidden
            // "_present" marker is what tells us the group was on the form.
            if (! array_key_exists($group, $data) && ! $request->boolean($group.'_present')) {
                continue;
            }

            $rows = [];
            foreach ((array) ($data[$group] ?? []) as $row) {
                $test = trim((string) (is_array($row) ? ($row['test'] ?? '') : ''));
                if ($test === '') {
                    continue;
                }
                $name = trim((string) ($row['name'] ?? ''));
                // "Not taken" is the absence of a result, so it never carries a score or date.
                $score = $test === 'not_taken' ? '' : trim((string) ($row['score'] ?? ''));
                $date = $test === 'not_taken' ? '' : trim((string) ($row['date'] ?? ''));
                $rows[] = [
                    'test' => $test,
                    'name' => $test === 'other' ? ($name ?: null) : null,
                    'score' => $score ?: null,
                    'date' => $date ?: null,
                ];
            }
            $data[$group] = $rows ?: null;
        }

        return $data;
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
        $fields = array_diff(array_keys($changes), ['updated_at', 'follow_up_completed_at', 'is_student']);
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

        $labels = self::FIELD_LABELS;

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
                'counselling', 'shortlisting' => CrmOptions::DONE_STATES[$value] ?? (string) $value,
                'english_tests' => CrmOptions::describeTests($value, CrmOptions::ENGLISH_TESTS),
                'aptitude_tests' => CrmOptions::describeTests($value, CrmOptions::APTITUDE_TESTS),
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
