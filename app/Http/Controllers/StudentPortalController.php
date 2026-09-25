<?php

namespace App\Http\Controllers;

use App\Http\Middleware\StudentAuth;
use App\Models\CrmJourneyApplication;
use App\Models\CrmJourneyPlan;
use App\Models\CrmLeadActivity;
use App\Models\CrmStudentAccount;
use App\Support\JourneyDocuments;
use App\Support\JourneyPlanner;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * The student portal: an enrolled student signs in with the email and
 * password their counsellor created when starting the journey planner, and
 * sees their own plan in a dashboard.
 *
 * The student reads everything their counsellor has switched on and may move
 * the status of their own activities (the owner names the Student). Which
 * activities apply, the dates and the notes stay with the counsellor. Each
 * change the student makes is written to the lead's timeline in the CRM.
 */
class StudentPortalController extends Controller
{
    public function showLogin(Request $request): View|RedirectResponse
    {
        if ($request->session()->has(StudentAuth::SESSION_KEY)) {
            return redirect()->route('student.dashboard');
        }

        return view('student.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'string', 'max:200'],
        ]);

        $account = CrmStudentAccount::query()->with('lead')
            ->where('email', CrmStudentAccount::normaliseEmail($data['email']))->first();

        // One message for every failure, so the form never confirms which
        // emails have an account.
        if (! $account || ! Hash::check($data['password'], $account->password) || ! $account->canSignIn()) {
            return back()->withInput($request->only('email'))
                ->withErrors(['email' => 'That email and password don\'t match an active student login. Check them, or ask your counsellor to reset your password.']);
        }

        $request->session()->regenerate();
        $request->session()->put(StudentAuth::SESSION_KEY, $account->id);
        $account->forceFill(['last_login_at' => now()])->saveQuietly();

        return $account->must_change_password
            ? redirect()->route('student.password')
            : redirect()->intended(route('student.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget(StudentAuth::SESSION_KEY);
        $request->session()->regenerateToken();

        return redirect()->route('student.login')->with('status', 'You\'ve signed out.');
    }

    public function showPassword(Request $request): View
    {
        return view('student.password', ['account' => $this->account($request)]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $account = $this->account($request);
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'confirmed', 'different:current_password', Password::min(8)->letters()->numbers()],
        ], [
            'password.different' => 'Choose a password that\'s different from the one you were given.',
        ], ['password' => 'new password']);

        if (! Hash::check($data['current_password'], $account->password)) {
            return back()->withErrors(['current_password' => 'Your current password isn\'t right.']);
        }

        $account->forceFill(['password' => $data['password'], 'must_change_password' => false, 'password_changed_at' => now()])->save();
        $request->session()->regenerate();

        return redirect()->route('student.dashboard')->with('status', 'Password updated.');
    }

    public function dashboard(Request $request): View
    {
        $account = $this->account($request);
        $plan = CrmJourneyPlan::query()->where('crm_lead_id', $account->crm_lead_id)
            ->with(['lead.assignee', 'lead.studentAccount', 'applications'])->firstOrFail();

        return view('journey.planner', [
            'title' => 'My journey — One Degree Advisory',
            'payload' => JourneyPlanner::payload($plan, 'student', [
                'activity' => route('student.activity'),
                'documents' => route('student.documents.store'),
                'document' => route('student.documents.update', ['document' => '__ID__']),
                'password' => route('student.password'),
                'logout' => route('student.logout'),
            ]),
        ]);
    }

    public function updateActivity(Request $request): JsonResponse
    {
        $account = $this->account($request);
        $plan = CrmJourneyPlan::query()->where('crm_lead_id', $account->crm_lead_id)->firstOrFail();

        $data = $request->validate([
            'scope' => ['required', Rule::in(['core', 'app'])],
            'application_id' => ['required_if:scope,app', 'nullable', 'integer'],
            'key' => ['required', 'string', 'max:60'],
            'status' => ['required', Rule::in(JourneyPlanner::STUDENT_STATUSES)],
        ]);

        $result = DB::transaction(function () use ($plan, $data): array {
            if ($data['scope'] === 'core') {
                $record = CrmJourneyPlan::query()->lockForUpdate()->find($plan->id);
                $state = $record->coreState();
                $name = $record->coreDefinitions()[$data['key']]['name'] ?? null;
                $where = null;
            } else {
                $record = CrmJourneyApplication::query()->where('plan_id', $plan->id)->lockForUpdate()->find($data['application_id']);
                $state = $record?->activityState() ?? [];
                $name = JourneyPlanner::applicationDefinitions()[$data['key']]['name'] ?? null;
                $where = $record?->university;
            }

            $row = $state[$data['key']] ?? null;
            if (! $record || ! $row || ! $row['inc']) {
                return ['error' => 404];
            }
            if (! JourneyPlanner::studentOwns($row['owner']) || $row['status'] === 'Not Applicable') {
                return ['error' => 403];
            }
            if ($row['status'] === $data['status']) {
                return ['row' => $row];
            }

            $from = $row['status'];
            $state[$data['key']] = JourneyPlanner::apply($row, ['status' => $data['status']], 'student');
            $record->forceFill([$data['scope'] === 'core' ? 'core' : 'activities' => $state])->save();

            CrmLeadActivity::query()->create([
                'crm_lead_id' => $plan->crm_lead_id,
                'crm_user_id' => null,
                'type' => 'journey_student',
                'body' => 'The student marked “'.$name.'”'.($where ? ' for '.$where : '').' as '.$data['status'].' (was '.$from.').',
                'metadata' => ['scope' => $data['scope'], 'application_id' => $data['application_id'] ?? null, 'key' => $data['key'], 'from' => $from, 'to' => $data['status']],
            ]);

            return ['row' => $state[$data['key']]];
        });

        abort_if(isset($result['error']), $result['error'] ?? 200);

        return response()->json(['ok' => true, 'activity' => $result['row']]);
    }

    /* ------------------------------------------------------------ documents & essays */

    public function storeDocument(Request $request): JsonResponse
    {
        $plan = $this->plan($request);
        $data = JourneyDocuments::createInput($request, $plan);
        $document = JourneyDocuments::create($plan, $data, $request->file('file'), null);

        return response()->json(['ok' => true, 'document' => $this->documentArray($document)]);
    }

    public function updateDocument(Request $request, int $document): JsonResponse
    {
        $plan = $this->plan($request);
        $doc = JourneyDocuments::find($plan, $document);
        $data = $request->validate(JourneyDocuments::updateRules($plan, false));
        JourneyDocuments::update($doc, $data, null);

        return response()->json(['ok' => true, 'document' => $this->documentArray($doc->fresh())]);
    }

    public function destroyDocument(Request $request, int $document): JsonResponse
    {
        JourneyDocuments::delete(JourneyDocuments::find($this->plan($request), $document), null);

        return response()->json(['ok' => true]);
    }

    public function downloadDocument(Request $request, int $document)
    {
        return JourneyDocuments::download(JourneyDocuments::find($this->plan($request), $document));
    }

    private function documentArray(\App\Models\CrmJourneyDocument $document): array
    {
        $document->load(['application', 'creator', 'reviewer']);

        return JourneyDocuments::toArray($document, 'student', fn ($d) => route('student.documents.file', $d));
    }

    private function plan(Request $request): CrmJourneyPlan
    {
        return CrmJourneyPlan::query()->where('crm_lead_id', $this->account($request)->crm_lead_id)->firstOrFail();
    }

    private function account(Request $request): CrmStudentAccount
    {
        return $request->attributes->get('student_account');
    }
}
