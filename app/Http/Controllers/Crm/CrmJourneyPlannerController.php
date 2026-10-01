<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Mail\JourneyMeetingMail;
use App\Models\CrmJourneyApplication;
use App\Models\CrmJourneyDocument;
use App\Models\CrmJourneyPlan;
use App\Models\CrmLead;
use App\Models\CrmStudentAccount;
use App\Models\CrmUser;
use App\Services\CrmAuditLogger;
use App\Support\JourneyDocuments;
use App\Support\JourneyPlanner;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The counsellor's side of the student journey planner.
 *
 * Who may see a planner follows the lead: a super admin, the assigned
 * counsellor, or the partner named on the lead. Only the team edits it — like
 * the student journey stage, the plan is One Degree's to maintain, so a partner
 * gets the same page read-only. Every edit is a small JSON request so the page
 * never reloads while a counsellor works down a checklist.
 *
 * Starting the planner also creates the student's sign-in (their email and a
 * temporary password); see App\Http\Controllers\StudentPortalController.
 */
class CrmJourneyPlannerController extends Controller
{
    public function show(Request $request, CrmLead $lead): View|RedirectResponse
    {
        $user = $this->user($request);
        $this->guardView($lead, $user);

        $plan = $lead->journeyPlan;
        if (! $plan) {
            return $user->isPartner()
                ? view('journey.not-started', ['lead' => $lead])
                : redirect()->route('crm.dashboard', ['view' => 'students', 'lead' => $lead->id])
                    ->withErrors(['planner' => 'Start the journey planner from the Student tab first.']);
        }

        $plan->load(['lead.assignee', 'lead.studentAccount', 'applications']);
        $mode = $user->isPartner() ? 'partner' : 'counsellor';
        $back = route('crm.dashboard', ['view' => 'students', 'lead' => $lead->id]);

        return view('journey.planner', [
            'title' => $lead->name.' — Journey planner',
            'payload' => JourneyPlanner::payload($plan, $mode, $mode === 'counsellor' ? [
                'details' => route('crm.journey.details', $lead),
                'activity' => route('crm.journey.activity', $lead),
                'applications' => route('crm.journey.applications.store', $lead),
                'application' => route('crm.journey.applications.update', [$lead, '__ID__']),
                'resetPassword' => route('crm.journey.login.reset', $lead),
                'toggleLogin' => route('crm.journey.login.toggle', $lead),
                'adminPassword' => route('crm.journey.login.admin', $lead),
                'documents' => route('crm.journey.documents.store', $lead),
                'tasks' => route('crm.journey.tasks.store', $lead),
                'task' => route('crm.journey.tasks.update', [$lead, '__KEY__']),
                'stages' => route('crm.journey.stages.store', $lead),
                'stage' => route('crm.journey.stages.update', [$lead, '__KEY__']),
                'document' => route('crm.journey.documents.update', [$lead, '__ID__']),
                'documentEdits' => route('crm.journey.documents.edits.store', [$lead, '__ID__']),
                'team' => route('crm.journey.team.store', $lead),
                'member' => route('crm.journey.team.update', [$lead, '__KEY__']),
                'deadlines' => route('crm.journey.deadlines.store', $lead),
                'deadline' => route('crm.journey.deadlines.update', [$lead, '__KEY__']),
                'meetings' => route('crm.journey.meetings.store', $lead),
                'meeting' => route('crm.journey.meetings.update', [$lead, '__KEY__']),
                'meetingNotify' => route('crm.journey.meetings.notify', [$lead, '__KEY__']),
                'back' => $back,
            ] : ['back' => $back], $mode === 'counsellor' ? session('journey_credentials') : null),
        ]);
    }

    /**
     * "Start journey planner" on the Student tab: build the plan from the
     * template and give the student their sign-in. The temporary password is
     * shown to the counsellor once, on the page this redirects to.
     */
    public function start(Request $request, CrmLead $lead, CrmAuditLogger $audit): RedirectResponse
    {
        $user = $this->user($request);
        $this->guardView($lead, $user);
        abort_if($user->isPartner(), 403);

        $back = redirect()->route('crm.dashboard', ['view' => 'students', 'lead' => $lead->id]);
        $email = CrmStudentAccount::normaliseEmail((string) $lead->email);
        if ($lead->studentAccount === null) {
            if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return $back->withErrors(['planner' => "Add the student's email address on the Details tab first. It becomes their sign-in."]);
            }
            if (CrmStudentAccount::query()->where('email', $email)->exists()) {
                return $back->withErrors(['planner' => "Another student already signs in with {$email}. Give this student their own email address first."]);
            }
        }

        $credentials = DB::transaction(function () use ($lead, $user, $email): ?array {
            CrmJourneyPlan::forLead($lead, $user);
            if ($lead->studentAccount()->exists()) {
                return null;
            }
            $password = CrmStudentAccount::temporaryPassword();
            $lead->studentAccount()->create(['email' => $email, 'password' => $password, 'must_change_password' => true, 'created_by' => $user->id]);

            return ['email' => $email, 'password' => $password];
        });

        $redirect = redirect()->route('crm.journey.show', $lead);
        if ($credentials) {
            $redirect->with('journey_credentials', $credentials);
            $audit->record($request, $user, 'journey_login_created', "Started the journey planner and created a student login for {$lead->name}", [
                'crm_lead_id' => $lead->id, 'subject_type' => CrmStudentAccount::class, 'subject_label' => $lead->name,
            ]);
        }

        return $redirect;
    }

    public function updateDetails(Request $request, CrmLead $lead): JsonResponse
    {
        $plan = $this->editablePlan($request, $lead);
        $data = $request->validate([
            'level' => ['nullable', Rule::in(JourneyPlanner::LEVELS)],
            'intake' => ['nullable', 'string', 'max:60'],
            'focus' => ['nullable', 'string', 'max:150'],
        ]);
        $plan->fill(array_map(fn ($v) => is_string($v) ? (trim($v) ?: null) : $v, $data))->save();

        return response()->json(['ok' => true]);
    }

    public function updateActivity(Request $request, CrmLead $lead): JsonResponse
    {
        $plan = $this->editablePlan($request, $lead);
        $data = $request->validate([
            'scope' => ['required', Rule::in(['core', 'app'])],
            'application_id' => ['required_if:scope,app', 'nullable', 'integer'],
            'key' => ['required', 'string', 'max:60'],
            'inc' => ['sometimes', 'boolean'],
            'status' => ['sometimes', Rule::in(JourneyPlanner::STATUSES)],
            'owner' => ['sometimes', Rule::in(JourneyPlanner::OWNERS)],
            'target' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'done' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ]);
        $changes = collect($data)->except(['scope', 'application_id', 'key'])->all();
        $by = 'team:'.$this->user($request)->id;

        $row = DB::transaction(function () use ($plan, $data, $changes, $by): ?array {
            if ($data['scope'] === 'core') {
                $locked = CrmJourneyPlan::query()->lockForUpdate()->find($plan->id);
                $state = $locked->coreState();
                if (! isset($state[$data['key']])) {
                    return null;
                }
                $state[$data['key']] = JourneyPlanner::apply($state[$data['key']], $changes, $by);
                $locked->forceFill(['core' => $state])->save();

                return $state[$data['key']];
            }

            $application = CrmJourneyApplication::query()->where('plan_id', $plan->id)->lockForUpdate()->find($data['application_id']);
            $state = $application?->activityState();
            if (! $application || ! isset($state[$data['key']])) {
                return null;
            }
            $state[$data['key']] = JourneyPlanner::apply($state[$data['key']], $changes, $by);
            $application->forceFill(['activities' => $state])->save();

            return $state[$data['key']];
        });

        abort_if($row === null, 404);

        return response()->json(['ok' => true, 'activity' => $row]);
    }

    public function storeApplication(Request $request, CrmLead $lead, CrmAuditLogger $audit): JsonResponse
    {
        $plan = $this->editablePlan($request, $lead);
        $data = $request->validate($this->applicationRules());

        $application = $plan->applications()->create([
            'position' => (int) $plan->applications()->max('position') + 1,
            'university' => trim($data['university']),
            'country' => trim((string) ($data['country'] ?? '')) ?: null,
            'program' => trim((string) ($data['program'] ?? '')) ?: null,
            'fit' => $data['fit'] ?? null,
            'tests_required' => trim((string) ($data['tests_required'] ?? '')) ?: null,
            'documents_required' => trim((string) ($data['documents_required'] ?? '')) ?: null,
            'requirements' => trim((string) ($data['requirements'] ?? '')) ?: null,
            'deadline' => $data['deadline'] ?? null,
            'activities' => JourneyPlanner::freshApplication(),
        ]);
        if ($application->fit) {
            $this->markFitConfirmed($application, $this->user($request));
        }

        $audit->record($request, $this->user($request), 'journey_university_added', "Added {$application->university} to {$lead->name}'s journey planner", [
            'crm_lead_id' => $lead->id, 'subject_type' => CrmJourneyApplication::class, 'subject_id' => $application->id, 'subject_label' => $application->university,
        ]);

        return response()->json(['ok' => true, 'application' => $application->fresh()->toPlannerArray()]);
    }

    public function updateApplication(Request $request, CrmLead $lead, CrmJourneyApplication $application): JsonResponse
    {
        $plan = $this->editablePlan($request, $lead);
        abort_unless($application->plan_id === $plan->id, 404);

        $data = $request->validate(array_merge($this->applicationRules(partial: true), [
            'offer_type' => ['sometimes', 'nullable', Rule::in(JourneyPlanner::OFFER_TYPES)],
        ]));
        foreach (['university', 'country', 'program', 'fit', 'offer_type', 'tests_required', 'documents_required', 'requirements', 'deadline'] as $field) {
            if (array_key_exists($field, $data)) {
                $application->{$field} = is_string($data[$field]) ? (trim($data[$field]) ?: null) : $data[$field];
            }
        }
        $application->save();

        // Choosing a fit is the first activity on the checklist; recording an
        // offer type is the "Offer received" activity. Do the obvious tick.
        $user = $this->user($request);
        if (array_key_exists('fit', $data) && $application->fit) {
            $this->markFitConfirmed($application, $user);
        }
        if (array_key_exists('offer_type', $data) && $application->offer_type) {
            $state = $application->activityState();
            if (! in_array($state['offer']['status'], ['Completed', 'Not Applicable'], true)) {
                $state['offer'] = JourneyPlanner::apply($state['offer'], ['status' => 'Completed'], 'team:'.$user->id);
                $application->forceFill(['activities' => $state])->save();
            }
        }

        return response()->json(['ok' => true, 'application' => $application->fresh()->toPlannerArray()]);
    }

    public function destroyApplication(Request $request, CrmLead $lead, CrmJourneyApplication $application, CrmAuditLogger $audit): JsonResponse
    {
        $plan = $this->editablePlan($request, $lead);
        abort_unless($application->plan_id === $plan->id, 404);

        $audit->record($request, $this->user($request), 'journey_university_removed', "Removed {$application->university} from {$lead->name}'s journey planner", [
            'crm_lead_id' => $lead->id, 'subject_type' => CrmJourneyApplication::class, 'subject_id' => $application->id, 'subject_label' => $application->university,
        ]);
        $application->delete();

        return response()->json(['ok' => true]);
    }

    /** A new temporary password, shown once. The old one stops working at once. */
    public function resetPassword(Request $request, CrmLead $lead, CrmAuditLogger $audit): JsonResponse
    {
        $this->editablePlan($request, $lead);
        $account = $lead->studentAccount ?? abort(404);
        $password = CrmStudentAccount::temporaryPassword();
        $account->forceFill(['password' => $password, 'must_change_password' => true])->save();

        $audit->record($request, $this->user($request), 'journey_password_reset', "Issued a new journey planner password for {$lead->name}", [
            'crm_lead_id' => $lead->id, 'subject_type' => CrmStudentAccount::class, 'subject_id' => $account->id, 'subject_label' => $lead->name,
        ]);

        return response()->json(['ok' => true, 'credentials' => ['email' => $account->email, 'password' => $password]]);
    }

    /** A new admin password for this student. The old one stops working at once. */
    public function regenerateAdminPassword(Request $request, CrmLead $lead, CrmAuditLogger $audit): JsonResponse
    {
        $this->editablePlan($request, $lead);
        $account = $lead->studentAccount ?? abort(404);
        $account->forceFill(['admin_password' => CrmStudentAccount::temporaryPassword(14)])->save();

        $audit->record($request, $this->user($request), 'journey_admin_password_reset', "Issued a new admin password for {$lead->name}'s student login", [
            'crm_lead_id' => $lead->id, 'subject_type' => CrmStudentAccount::class, 'subject_id' => $account->id, 'subject_label' => $lead->name,
        ]);

        return response()->json(['ok' => true, 'adminPassword' => $account->admin_password]);
    }

    /** Switch the student's sign-in off (or back on) without losing the plan. */
    public function toggleLogin(Request $request, CrmLead $lead, CrmAuditLogger $audit): JsonResponse
    {
        $this->editablePlan($request, $lead);
        $account = $lead->studentAccount ?? abort(404);
        $active = (bool) $request->validate(['active' => ['required', 'boolean']])['active'];
        $account->forceFill(['is_active' => $active])->save();

        $audit->record($request, $this->user($request), $active ? 'journey_login_enabled' : 'journey_login_disabled', ($active ? 'Switched on' : 'Switched off')." the journey planner login for {$lead->name}", [
            'crm_lead_id' => $lead->id, 'subject_type' => CrmStudentAccount::class, 'subject_id' => $account->id, 'subject_label' => $lead->name,
        ]);

        return response()->json(['ok' => true, 'active' => $account->is_active]);
    }

    /* ------------------------------------------------------------ the counsellor's own tasks */

    /** Most tasks a counsellor can add to one student's journey. */
    private const MAX_CUSTOM_TASKS = 60;

    /**
     * Add a task of the counsellor's own to one stage of the core journey. It
     * behaves like the standard ones: it counts toward progress, the student
     * sees it, and the student can tick it off when the owner names them.
     */
    public function storeTask(Request $request, CrmLead $lead, CrmAuditLogger $audit): JsonResponse
    {
        $plan = $this->editablePlan($request, $lead);
        $data = $request->validate([
            'phase' => ['required', Rule::in($plan->phaseKeys())],
            'name' => ['required', 'string', 'max:150'],
            'desc' => ['nullable', 'string', 'max:500'],
            'docs' => ['nullable', 'string', 'max:190'],
            'owner' => ['required', Rule::in(JourneyPlanner::OWNERS)],
            'target' => ['nullable', 'date_format:Y-m-d'],
        ], [], ['name' => 'task name', 'desc' => 'description', 'docs' => 'documents']);
        $user = $this->user($request);

        $result = DB::transaction(function () use ($plan, $data, $user): array {
            $locked = CrmJourneyPlan::query()->lockForUpdate()->find($plan->id);
            $tasks = $locked->custom_tasks ?? [];
            if (count($tasks) >= self::MAX_CUSTOM_TASKS) {
                throw ValidationException::withMessages(['name' => 'This plan already has '.self::MAX_CUSTOM_TASKS.' added tasks, the most it can hold. Remove one you no longer need first.']);
            }
            $key = 'c-'.Str::lower(Str::random(10));
            $tasks[] = [
                'key' => $key, 'phase' => $data['phase'], 'name' => trim($data['name']),
                'desc' => trim((string) ($data['desc'] ?? '')), 'docs' => trim((string) ($data['docs'] ?? '')),
                'owner' => $data['owner'], 'created_by' => $user->id, 'created_at' => now()->toIso8601String(),
            ];
            $locked->custom_tasks = $tasks;
            $state = $locked->coreState(); // the new key appears here with its defaults
            $state[$key] = JourneyPlanner::apply($state[$key], ['owner' => $data['owner'], 'target' => $data['target'] ?? null], 'team:'.$user->id);
            $locked->core = $state;
            $locked->save();

            return ['task' => $locked->customDefinitions()[$key], 'activity' => $state[$key]];
        });

        $audit->record($request, $user, 'journey_task_added', "Added the task “{$result['task']['name']}” to {$lead->name}'s journey planner", [
            'crm_lead_id' => $lead->id, 'subject_type' => CrmJourneyPlan::class, 'subject_id' => $plan->id, 'subject_label' => $result['task']['name'],
        ]);

        return response()->json(['ok' => true] + $result);
    }

    /** Rename an added task or change its description or documents. Status, owner and dates go through updateActivity. */
    public function updateTask(Request $request, CrmLead $lead, string $task): JsonResponse
    {
        $plan = $this->editablePlan($request, $lead);
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:150'],
            'desc' => ['sometimes', 'nullable', 'string', 'max:500'],
            'docs' => ['sometimes', 'nullable', 'string', 'max:190'],
        ], [], ['name' => 'task name', 'desc' => 'description', 'docs' => 'documents']);

        $def = DB::transaction(function () use ($plan, $task, $data): ?array {
            $locked = CrmJourneyPlan::query()->lockForUpdate()->find($plan->id);
            $tasks = $locked->custom_tasks ?? [];
            $found = false;
            foreach ($tasks as &$t) {
                if (($t['key'] ?? null) === $task) {
                    foreach (['name', 'desc', 'docs'] as $f) {
                        if (array_key_exists($f, $data)) {
                            $t[$f] = trim((string) $data[$f]);
                        }
                    }
                    $found = true;
                }
            }
            unset($t);
            if (! $found) {
                return null;
            }
            $locked->forceFill(['custom_tasks' => $tasks])->save();

            return $locked->customDefinitions()[$task] ?? null;
        });
        abort_if($def === null, 404);

        return response()->json(['ok' => true, 'task' => $def]);
    }

    /** Remove an added task and everything recorded on it. ODA's standard tasks can only be switched off, never removed. */
    public function destroyTask(Request $request, CrmLead $lead, string $task, CrmAuditLogger $audit): JsonResponse
    {
        $plan = $this->editablePlan($request, $lead);

        $name = DB::transaction(function () use ($plan, $task): ?string {
            $locked = CrmJourneyPlan::query()->lockForUpdate()->find($plan->id);
            $tasks = $locked->custom_tasks ?? [];
            $gone = collect($tasks)->firstWhere('key', $task);
            if (! $gone) {
                return null;
            }
            $core = $locked->core ?? [];
            unset($core[$task]);
            $locked->forceFill([
                'custom_tasks' => array_values(array_filter($tasks, fn ($t) => ($t['key'] ?? null) !== $task)),
                'core' => $core,
            ])->save();

            return $gone['name'] ?? 'Task';
        });
        abort_if($name === null, 404);

        $audit->record($request, $this->user($request), 'journey_task_removed', "Removed the task “{$name}” from {$lead->name}'s journey planner", [
            'crm_lead_id' => $lead->id, 'subject_type' => CrmJourneyPlan::class, 'subject_id' => $plan->id, 'subject_label' => $name,
        ]);

        return response()->json(['ok' => true]);
    }

    /* ------------------------------------------------------------ the counsellor's own stages */

    /** Most stages a counsellor can add to one student's journey. */
    private const MAX_CUSTOM_STAGES = 12;

    /**
     * Add a whole stage to this student's journey, placed after one of ODA's
     * seven stages or at the end. Its tasks are added with storeTask.
     */
    public function storeStage(Request $request, CrmLead $lead, CrmAuditLogger $audit): JsonResponse
    {
        $plan = $this->editablePlan($request, $lead);
        $data = $request->validate($this->stageRules(), [], ['name' => 'stage name', 'timeline' => 'timing']);

        $stage = DB::transaction(function () use ($plan, $data): array {
            $locked = CrmJourneyPlan::query()->lockForUpdate()->find($plan->id);
            $stages = $locked->custom_stages ?? [];
            if (count($stages) >= self::MAX_CUSTOM_STAGES) {
                throw ValidationException::withMessages(['name' => 'This plan already has '.self::MAX_CUSTOM_STAGES.' added stages, the most it can hold.']);
            }
            $stage = [
                'key' => 's-'.Str::lower(Str::random(10)), 'name' => trim($data['name']),
                'timeline' => trim((string) ($data['timeline'] ?? '')), 'after' => $data['after'] ?? null,
            ];
            $stages[] = $stage;
            $locked->forceFill(['custom_stages' => $stages])->save();

            return collect($locked->orderedPhases())->firstWhere('key', $stage['key']);
        });

        $audit->record($request, $this->user($request), 'journey_stage_added', "Added the stage “{$stage['name']}” to {$lead->name}'s journey planner", [
            'crm_lead_id' => $lead->id, 'subject_type' => CrmJourneyPlan::class, 'subject_id' => $plan->id, 'subject_label' => $stage['name'],
        ]);

        return response()->json(['ok' => true, 'stage' => $stage, 'order' => array_column($plan->fresh()->orderedPhases(), 'key')]);
    }

    /** Rename an added stage, change its timing, or move it. */
    public function updateStage(Request $request, CrmLead $lead, string $stage): JsonResponse
    {
        $plan = $this->editablePlan($request, $lead);
        $data = $request->validate(array_map(fn ($r) => array_merge(['sometimes'], $r), $this->stageRules()), [], ['name' => 'stage name', 'timeline' => 'timing']);

        $found = DB::transaction(function () use ($plan, $stage, $data): bool {
            $locked = CrmJourneyPlan::query()->lockForUpdate()->find($plan->id);
            $stages = $locked->custom_stages ?? [];
            $found = false;
            foreach ($stages as &$st) {
                if (($st['key'] ?? null) === $stage) {
                    foreach (['name', 'timeline', 'after'] as $f) {
                        if (array_key_exists($f, $data)) {
                            $st[$f] = $f === 'after' ? ($data[$f] ?: null) : trim((string) $data[$f]);
                        }
                    }
                    $found = true;
                }
            }
            unset($st);
            if ($found) {
                $locked->forceFill(['custom_stages' => $stages])->save();
            }

            return $found;
        });
        abort_unless($found, 404);

        $fresh = $plan->fresh();

        return response()->json(['ok' => true, 'stage' => collect($fresh->orderedPhases())->firstWhere('key', $stage), 'order' => array_column($fresh->orderedPhases(), 'key')]);
    }

    /** Remove an added stage together with every task in it. ODA's seven stages stay. */
    public function destroyStage(Request $request, CrmLead $lead, string $stage, CrmAuditLogger $audit): JsonResponse
    {
        $plan = $this->editablePlan($request, $lead);

        $name = DB::transaction(function () use ($plan, $stage): ?string {
            $locked = CrmJourneyPlan::query()->lockForUpdate()->find($plan->id);
            $stages = $locked->custom_stages ?? [];
            $gone = collect($stages)->firstWhere('key', $stage);
            if (! $gone) {
                return null;
            }
            $tasks = $locked->custom_tasks ?? [];
            $core = $locked->core ?? [];
            foreach ($tasks as $t) {
                if (($t['phase'] ?? null) === $stage) {
                    unset($core[$t['key']]);
                }
            }
            $locked->forceFill([
                'custom_stages' => array_values(array_filter($stages, fn ($s) => ($s['key'] ?? null) !== $stage)),
                'custom_tasks' => array_values(array_filter($tasks, fn ($t) => ($t['phase'] ?? null) !== $stage)),
                'core' => $core,
            ])->save();

            return $gone['name'] ?? 'Stage';
        });
        abort_if($name === null, 404);

        $audit->record($request, $this->user($request), 'journey_stage_removed', "Removed the stage “{$name}” from {$lead->name}'s journey planner", [
            'crm_lead_id' => $lead->id, 'subject_type' => CrmJourneyPlan::class, 'subject_id' => $plan->id, 'subject_label' => $name,
        ]);

        return response()->json(['ok' => true]);
    }

    private function stageRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:80'],
            'timeline' => ['nullable', 'string', 'max:120'],
            'after' => ['nullable', Rule::in(array_column(JourneyPlanner::phases(), 'key'))],
        ];
    }

    /* ------------------------------------------------------------ team, deadlines and meetings */

    /** Most rows each of the plan's three lists can hold. */
    private const MAX_TEAM = 24;

    private const MAX_ROLES = 20;

    private const MAX_DEADLINES = 80;

    private const MAX_MEETINGS = 120;

    /**
     * Add someone to this student's file. The designation can be one ODA
     * already uses or a new one the counsellor types; a new one is kept on the
     * plan so it appears in the dropdown from then on.
     */
    public function storeMember(Request $request, CrmLead $lead, CrmAuditLogger $audit): JsonResponse
    {
        $plan = $this->editablePlan($request, $lead);
        $data = $request->validate($this->memberRules(), [], ['role' => 'designation']);

        $result = $this->withList($plan, 'team', function (array &$team) use ($data, $plan): array {
            if (count($team) >= self::MAX_TEAM) {
                throw ValidationException::withMessages(['name' => 'This plan already names '.self::MAX_TEAM.' people, the most it can hold.']);
            }
            $member = [
                'key' => 't-'.Str::lower(Str::random(10)),
                'role' => $this->resolveRole($plan, trim($data['role'])),
                'name' => trim($data['name']),
                'contact' => trim((string) ($data['contact'] ?? '')),
                'external' => (bool) ($data['external'] ?? false),
            ];
            $team[] = $member;

            return $member;
        });

        $audit->record($request, $this->user($request), 'journey_team_added', "Added {$result['name']} ({$result['role']}) to {$lead->name}'s journey planner", [
            'crm_lead_id' => $lead->id, 'subject_type' => CrmJourneyPlan::class, 'subject_id' => $plan->id, 'subject_label' => $result['name'],
        ]);

        return $this->teamResponse($plan, ['member' => $result]);
    }

    /** Change someone's designation, name or contact details. */
    public function updateMember(Request $request, CrmLead $lead, string $member): JsonResponse
    {
        $plan = $this->editablePlan($request, $lead);
        $data = $request->validate($this->memberRules(partial: true), [], ['role' => 'designation']);

        $found = $this->withList($plan, 'team', function (array &$team) use ($data, $member, $plan): bool {
            foreach ($team as &$m) {
                if (($m['key'] ?? null) !== $member) {
                    continue;
                }
                if (array_key_exists('role', $data)) {
                    $m['role'] = $this->resolveRole($plan, trim($data['role']));
                }
                foreach (['name', 'contact'] as $f) {
                    if (array_key_exists($f, $data)) {
                        $m[$f] = trim((string) $data[$f]);
                    }
                }
                if (array_key_exists('external', $data)) {
                    $m['external'] = (bool) $data['external'];
                }

                return true;
            }

            return false;
        });
        abort_unless($found, 404);

        return $this->teamResponse($plan);
    }

    public function destroyMember(Request $request, CrmLead $lead, string $member, CrmAuditLogger $audit): JsonResponse
    {
        $plan = $this->editablePlan($request, $lead);

        $gone = $this->withList($plan, 'team', function (array &$team) use ($member): ?array {
            $match = collect($team)->firstWhere('key', $member);
            $team = array_values(array_filter($team, fn ($m) => ($m['key'] ?? null) !== $member));

            return $match;
        });
        abort_if($gone === null, 404);

        $audit->record($request, $this->user($request), 'journey_team_removed', "Removed {$gone['name']} from {$lead->name}'s journey planner", [
            'crm_lead_id' => $lead->id, 'subject_type' => CrmJourneyPlan::class, 'subject_id' => $plan->id, 'subject_label' => $gone['name'],
        ]);

        return $this->teamResponse($plan);
    }

    /** A date this plan has to hit: the university's own, or one ODA set itself. */
    public function storeDeadline(Request $request, CrmLead $lead): JsonResponse
    {
        $plan = $this->editablePlan($request, $lead);
        $data = $request->validate($this->deadlineRules(), [], ['what' => 'deadline']);

        $this->withList($plan, 'deadlines', function (array &$list) use ($data): void {
            if (count($list) >= self::MAX_DEADLINES) {
                throw ValidationException::withMessages(['what' => 'This plan already holds '.self::MAX_DEADLINES.' deadlines, the most it can hold.']);
            }
            $list[] = [
                'key' => 'd-'.Str::lower(Str::random(10)),
                'kind' => $data['kind'],
                'what' => trim($data['what']),
                'who' => trim((string) ($data['who'] ?? '')),
                'date' => $data['date'],
            ];
        });

        return $this->deadlineResponse($plan);
    }

    public function updateDeadline(Request $request, CrmLead $lead, string $deadline): JsonResponse
    {
        $plan = $this->editablePlan($request, $lead);
        $data = $request->validate($this->deadlineRules(partial: true), [], ['what' => 'deadline']);

        $found = $this->withList($plan, 'deadlines', function (array &$list) use ($data, $deadline): bool {
            foreach ($list as &$d) {
                if (($d['key'] ?? null) !== $deadline) {
                    continue;
                }
                foreach (['kind', 'what', 'who', 'date'] as $f) {
                    if (array_key_exists($f, $data)) {
                        $d[$f] = is_string($data[$f]) ? trim($data[$f]) : $data[$f];
                    }
                }

                return true;
            }

            return false;
        });
        abort_unless($found, 404);

        return $this->deadlineResponse($plan);
    }

    public function destroyDeadline(Request $request, CrmLead $lead, string $deadline): JsonResponse
    {
        $plan = $this->editablePlan($request, $lead);

        $found = $this->withList($plan, 'deadlines', function (array &$list) use ($deadline): bool {
            $before = count($list);
            $list = array_values(array_filter($list, fn ($d) => ($d['key'] ?? null) !== $deadline));

            return count($list) < $before;
        });
        abort_unless($found, 404);

        return $this->deadlineResponse($plan);
    }

    /**
     * Book a meeting on this plan. The planner can't create a Google Meet room
     * itself — that belongs to the counsellor's own Google account — so the
     * page sends them to Google Calendar with the details filled in, and the
     * join link it hands back is stored here where the student will find it.
     */
    public function storeMeeting(Request $request, CrmLead $lead, CrmAuditLogger $audit): JsonResponse
    {
        $plan = $this->editablePlan($request, $lead);
        $data = $request->validate($this->meetingRules(), [], ['title' => 'meeting', 'who' => 'attendees']);

        $meeting = $this->withList($plan, 'meetings', function (array &$list) use ($data): array {
            if (count($list) >= self::MAX_MEETINGS) {
                throw ValidationException::withMessages(['title' => 'This plan already holds '.self::MAX_MEETINGS.' meetings, the most it can hold.']);
            }
            $meeting = [
                'key' => 'm-'.Str::lower(Str::random(10)),
                'title' => trim($data['title']),
                'date' => $data['date'],
                'time' => (string) ($data['time'] ?? ''),
                'minutes' => (int) ($data['minutes'] ?? 45),
                'mode' => $data['mode'],
                'who' => trim((string) ($data['who'] ?? '')),
                'emails' => CrmJourneyPlan::emailList($data['emails'] ?? null),
                'phone' => trim((string) ($data['phone'] ?? '')),
                'link' => trim((string) ($data['link'] ?? '')),
                'notes' => trim((string) ($data['notes'] ?? '')),
                'done' => false,
                'sentAt' => null,
            ];
            $list[] = $meeting;

            return $meeting;
        });

        $sent = ($data['notify'] ?? true) ? $this->sendMeeting($plan, $lead, $meeting['key'], false) : 0;

        $audit->record($request, $this->user($request), 'journey_meeting_added', "Booked “{$meeting['title']}” with {$lead->name} for {$meeting['date']}", [
            'crm_lead_id' => $lead->id, 'subject_type' => CrmJourneyPlan::class, 'subject_id' => $plan->id, 'subject_label' => $meeting['title'],
        ]);

        return $this->meetingResponse($plan, ['meeting' => $meeting, 'sent' => $sent]);
    }

    public function updateMeeting(Request $request, CrmLead $lead, string $meeting): JsonResponse
    {
        $plan = $this->editablePlan($request, $lead);
        $data = $request->validate(array_merge($this->meetingRules(partial: true), [
            'done' => ['sometimes', 'boolean'],
        ]), [], ['title' => 'meeting', 'who' => 'attendees']);

        $found = $this->withList($plan, 'meetings', function (array &$list) use ($data, $meeting): bool {
            foreach ($list as &$m) {
                if (($m['key'] ?? null) !== $meeting) {
                    continue;
                }
                foreach (['title', 'date', 'time', 'mode', 'who', 'phone', 'link', 'notes'] as $f) {
                    if (array_key_exists($f, $data)) {
                        $m[$f] = trim((string) $data[$f]);
                    }
                }
                if (array_key_exists('emails', $data)) {
                    $m['emails'] = CrmJourneyPlan::emailList($data['emails']);
                }
                if (array_key_exists('minutes', $data)) {
                    $m['minutes'] = (int) $data['minutes'];
                }
                if (array_key_exists('done', $data)) {
                    $m['done'] = (bool) $data['done'];
                }

                return true;
            }

            return false;
        });
        abort_unless($found, 404);

        $sent = ($data['notify'] ?? false) ? $this->sendMeeting($plan, $lead, $meeting, true) : 0;

        return $this->meetingResponse($plan, ['sent' => $sent]);
    }

    /**
     * Send the joining details to the people listed on a meeting. Not a
     * calendar invitation — just the when, the who and the way in, so nobody
     * has to go hunting for the link.
     */
    public function notifyMeeting(Request $request, CrmLead $lead, string $meeting): JsonResponse
    {
        $plan = $this->editablePlan($request, $lead);
        $row = collect($plan->meetingRows())->firstWhere('key', $meeting) ?? abort(404);
        abort_if($row['emails'] === [], 422);

        $sent = $this->sendMeeting($plan, $lead, $meeting, true);

        return $this->meetingResponse($plan, ['sent' => $sent]);
    }

    public function destroyMeeting(Request $request, CrmLead $lead, string $meeting, CrmAuditLogger $audit): JsonResponse
    {
        $plan = $this->editablePlan($request, $lead);

        $gone = $this->withList($plan, 'meetings', function (array &$list) use ($meeting): ?array {
            $match = collect($list)->firstWhere('key', $meeting);
            $list = array_values(array_filter($list, fn ($m) => ($m['key'] ?? null) !== $meeting));

            return $match;
        });
        abort_if($gone === null, 404);

        $audit->record($request, $this->user($request), 'journey_meeting_removed', "Cancelled “{$gone['title']}” with {$lead->name}", [
            'crm_lead_id' => $lead->id, 'subject_type' => CrmJourneyPlan::class, 'subject_id' => $plan->id, 'subject_label' => $gone['title'],
        ]);

        return $this->meetingResponse($plan);
    }

    /** @return array<string, array<int, mixed>> */
    private function memberRules(bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return [
            'role' => [$required, 'string', 'max:60'],
            'name' => [$required, 'string', 'max:120'],
            'contact' => ['sometimes', 'nullable', 'string', 'max:190'],
            'external' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function deadlineRules(bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return [
            'kind' => [$required, Rule::in(array_keys(JourneyPlanner::DEADLINE_KINDS))],
            'what' => [$required, 'string', 'max:150'],
            'who' => ['sometimes', 'nullable', 'string', 'max:120'],
            'date' => [$required, 'date_format:Y-m-d'],
        ];
    }

    /** @return array<string, array<int, mixed>> */
    private function meetingRules(bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return [
            'title' => [$required, 'string', 'max:150'],
            'date' => [$required, 'date_format:Y-m-d'],
            'time' => ['sometimes', 'nullable', 'date_format:H:i'],
            'minutes' => ['sometimes', 'nullable', 'integer', 'min:5', 'max:480'],
            'mode' => [$required, Rule::in(JourneyPlanner::MEETING_MODES)],
            'who' => ['sometimes', 'nullable', 'string', 'max:190'],
            'emails' => ['sometimes', 'nullable', 'array', 'max:20'],
            'emails.*' => ['email', 'max:190'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:40'],
            'link' => ['sometimes', 'nullable', 'url:https', 'max:300'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'notify' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * The designation to store. One ODA already uses is taken as it is; a new
     * one is added to this plan's own list so it joins the dropdown.
     */
    private function resolveRole(CrmJourneyPlan $plan, string $role): string
    {
        $existing = collect($plan->teamRoles())->first(fn (string $r) => mb_strtolower($r) === mb_strtolower($role));
        if ($existing !== null) {
            return $existing;
        }
        $roles = array_values(array_filter($plan->roles ?? [], 'is_string'));
        if (count($roles) >= self::MAX_ROLES) {
            throw ValidationException::withMessages(['role' => 'This plan already has '.self::MAX_ROLES.' designations of its own. Pick one from the list instead.']);
        }
        $roles[] = $role;
        $plan->forceFill(['roles' => $roles])->save();

        return $role;
    }

    /** @param  array<string, mixed>  $extra */
    private function teamResponse(CrmJourneyPlan $plan, array $extra = []): JsonResponse
    {
        $fresh = $plan->fresh();

        return response()->json(['ok' => true, 'team' => $fresh->teamMembers(), 'roles' => $fresh->teamRoles()] + $extra);
    }

    private function deadlineResponse(CrmJourneyPlan $plan): JsonResponse
    {
        return response()->json(['ok' => true, 'deadlines' => $plan->fresh()->deadlineRows()]);
    }

    /** @param  array<string, mixed>  $extra */
    /**
     * Mail one meeting's joining details to everyone listed on it and stamp
     * when that happened. A mail that bounces off a bad address must not lose
     * the meeting, so a failure is logged and the count reflects what left.
     */
    private function sendMeeting(CrmJourneyPlan $plan, CrmLead $lead, string $key, bool $isUpdate): int
    {
        if (! config('crm.email.enabled')) {
            return 0;
        }
        $meeting = collect($plan->fresh()->meetingRows())->firstWhere('key', $key);
        if (! $meeting || $meeting['emails'] === []) {
            return 0;
        }

        $mailer = (string) config('crm.email.mailer');
        $mail = new JourneyMeetingMail($meeting, $lead->name, $lead->assignee?->name, $isUpdate);
        $sent = 0;
        foreach ($meeting['emails'] as $address) {
            try {
                Mail::mailer($mailer)->to($address)->send($mail);
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('Journey meeting mail failed', [
                    'plan' => $plan->id, 'meeting' => $key, 'to' => $address, 'error' => $e->getMessage(),
                ]);
            }
        }

        if ($sent > 0) {
            $this->withList($plan, 'meetings', function (array &$list) use ($key): void {
                foreach ($list as &$m) {
                    if (($m['key'] ?? null) === $key) {
                        $m['sentAt'] = now()->toIso8601String();
                    }
                }
            });
        }

        return $sent;
    }

    private function meetingResponse(CrmJourneyPlan $plan, array $extra = []): JsonResponse
    {
        return response()->json(['ok' => true, 'meetings' => $plan->fresh()->meetingRows()] + $extra);
    }

    /**
     * Change one of the plan's JSON lists under a lock, so two counsellors
     * editing the same plan can't write over each other's row.
     *
     * @param  \Closure(array): mixed  $fn  receives the list by reference
     */
    private function withList(CrmJourneyPlan $plan, string $column, \Closure $fn): mixed
    {
        return DB::transaction(function () use ($plan, $column, $fn): mixed {
            $locked = CrmJourneyPlan::query()->lockForUpdate()->find($plan->id);
            $list = $locked->{$column} ?? [];
            $out = $fn($list);
            $locked->forceFill([$column => array_values($list)])->save();

            return $out;
        });
    }

    /* ------------------------------------------------------------ documents & essays */

    public function storeDocument(Request $request, CrmLead $lead): JsonResponse
    {
        $plan = $this->editablePlan($request, $lead);
        $data = JourneyDocuments::createInput($request, $plan);
        $document = JourneyDocuments::create($plan, $data, $request->file('file'), $this->user($request));

        return response()->json(['ok' => true, 'document' => $this->documentArray($lead, $document)]);
    }

    public function updateDocument(Request $request, CrmLead $lead, int $document): JsonResponse
    {
        $plan = $this->editablePlan($request, $lead);
        $doc = JourneyDocuments::find($plan, $document);
        $data = $request->validate(JourneyDocuments::updateRules($plan, true));
        JourneyDocuments::update($doc, $data, $this->user($request));

        return response()->json(['ok' => true, 'document' => $this->documentArray($lead, $doc->fresh())]);
    }

    /**
     * Record a change made to a document. The planner writes its own entries
     * as drafts move; this is for a change made outside it — a file re-sent by
     * email, a paragraph rewritten with the student on a call.
     */
    public function storeDocumentEdit(Request $request, CrmLead $lead, int $document): JsonResponse
    {
        $plan = $this->editablePlan($request, $lead);
        $doc = JourneyDocuments::find($plan, $document);
        $data = $request->validate([
            'note' => ['required', 'string', 'max:290'],
            'new_version' => ['sometimes', 'boolean'],
        ], [], ['note' => 'what changed']);

        JourneyDocuments::noteEdit($doc, $data['note'], $this->user($request), (bool) ($data['new_version'] ?? false));

        return response()->json(['ok' => true, 'document' => $this->documentArray($lead, $doc->fresh())]);
    }

    public function destroyDocument(Request $request, CrmLead $lead, int $document): JsonResponse
    {
        $plan = $this->editablePlan($request, $lead);
        JourneyDocuments::delete(JourneyDocuments::find($plan, $document), $this->user($request));

        return response()->json(['ok' => true]);
    }

    /** Partners can read what the plan holds, so downloads only need the lead to be visible. */
    public function downloadDocument(Request $request, CrmLead $lead, int $document)
    {
        $this->guardView($lead, $this->user($request));
        $plan = $lead->journeyPlan ?? abort(404);

        return JourneyDocuments::download(JourneyDocuments::find($plan, $document));
    }

    private function documentArray(CrmLead $lead, CrmJourneyDocument $document): array
    {
        $document->load(['application', 'creator', 'reviewer', 'edits.author']);

        return JourneyDocuments::toArray($document, 'counsellor', fn ($d) => route('crm.journey.documents.file', [$lead, $d]));
    }

    private function markFitConfirmed(CrmJourneyApplication $application, CrmUser $user): void
    {
        $state = $application->activityState();
        if ($state['fit']['status'] !== 'Completed') {
            $state['fit'] = JourneyPlanner::apply($state['fit'], ['status' => 'Completed'], 'team:'.$user->id);
            $application->forceFill(['activities' => $state])->save();
        }
    }

    private function applicationRules(bool $partial = false): array
    {
        $required = $partial ? 'sometimes' : 'required';

        return [
            'university' => [$required, 'string', 'max:150'],
            'country' => ['sometimes', 'nullable', 'string', 'max:80'],
            'program' => ['sometimes', 'nullable', 'string', 'max:190'],
            'fit' => ['sometimes', 'nullable', Rule::in(array_keys(JourneyPlanner::FITS))],
            // What this university asks for: the brief beside the checklist.
            'tests_required' => ['sometimes', 'nullable', 'string', 'max:190'],
            'documents_required' => ['sometimes', 'nullable', 'string', 'max:300'],
            'requirements' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'deadline' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
        ];
    }

    private function user(Request $request): CrmUser
    {
        return $request->attributes->get('crm_user');
    }

    /** The same reach as the lead drawer, and only for someone who has been enrolled. */
    private function guardView(CrmLead $lead, CrmUser $user): void
    {
        abort_unless(match (true) {
            $user->isSuperAdmin() => true,
            $user->isPartner() => $lead->partner_id === $user->id,
            default => $lead->assigned_to === $user->id,
        }, 403);
        abort_unless($lead->is_student, 404);
    }

    private function editablePlan(Request $request, CrmLead $lead): CrmJourneyPlan
    {
        $user = $this->user($request);
        $this->guardView($lead, $user);
        abort_if($user->isPartner(), 403);

        return $lead->journeyPlan ?? abort(404);
    }
}
