<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmJourneyApplication;
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
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
                'documents' => route('crm.journey.documents.store', $lead),
                'tasks' => route('crm.journey.tasks.store', $lead),
                'task' => route('crm.journey.tasks.update', [$lead, '__KEY__']),
                'stages' => route('crm.journey.stages.store', $lead),
                'stage' => route('crm.journey.stages.update', [$lead, '__KEY__']),
                'document' => route('crm.journey.documents.update', [$lead, '__ID__']),
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
        foreach (['university', 'country', 'program', 'fit', 'offer_type'] as $field) {
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
                throw \Illuminate\Validation\ValidationException::withMessages(['name' => 'This plan already has '.self::MAX_CUSTOM_TASKS.' added tasks, the most it can hold. Remove one you no longer need first.']);
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
                throw \Illuminate\Validation\ValidationException::withMessages(['name' => 'This plan already has '.self::MAX_CUSTOM_STAGES.' added stages, the most it can hold.']);
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

    private function documentArray(CrmLead $lead, \App\Models\CrmJourneyDocument $document): array
    {
        $document->load(['application', 'creator', 'reviewer']);

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
