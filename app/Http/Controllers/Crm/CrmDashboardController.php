<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmAuditLog;
use App\Models\CrmLead;
use App\Models\CrmMockInterviewInvite;
use App\Models\CrmPartnerCode;
use App\Models\CrmSpamAttempt;
use App\Models\CrmSubscriber;
use App\Models\CrmUser;
use App\Models\PaymentAttempt;
use App\Support\CrmFilter;
use App\Support\CrmOptions;
use App\Support\MockInterviewQuestions;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CrmDashboardController extends Controller
{
    /**
     * Rows per page, offered above every paginated list in the workspace and
     * shared by all of them — leads, enrollments, subscriptions, mock-interview
     * links and the audit log — so the choice means the same thing everywhere.
     *
     * The default sits well above a typical day's intake: a workspace holding a
     * couple of dozen records reads as "everything is here", which is what
     * people expect of a list this size. Larger sizes are there for bulk review.
     */
    public const PER_PAGE_OPTIONS = [25, 50, 100, 200];

    private const DEFAULT_PER_PAGE = 50;

    public function index(Request $request): View
    {
        /** @var CrmUser $user */
        $user = $request->attributes->get('crm_user');
        $base = CrmLead::query()->visibleTo($user);
        $now = now();
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();
        $tomorrowEnd = $now->copy()->addDay()->endOfDay();

        // The super-admin-only screens say so out loud rather than bouncing back
        // to the dashboard: asking for them is deliberate, and silently landing
        // somewhere else reads as a broken link.
        if (in_array($request->query('view'), ['audit', 'team', 'partner-codes'], true)) {
            abort_unless($user->isSuperAdmin(), 403);
        }
        // A partner's workspace is their own students and nothing else: the
        // follow-up planner, the report builder, the mock-interview links and the
        // payment log are all in-house tools.
        $allowedViews = $user->isPartner()
            ? ['dashboard', 'leads', 'students']
            : ['dashboard', 'leads', 'enrollments', 'followups', 'students', 'journeys', 'shortlisting', 'mock-invites'];
        if ($user->isSuperAdmin()) {
            $allowedViews = [...$allowedViews, 'subscriptions', 'audit', 'spam', 'team', 'partner-codes'];
        }
        $requestedView = match ($request->query('view')) {
            'website' => 'leads',
            'subscribers' => 'subscriptions',
            default => $request->query('view'),
        };
        $view = in_array($requestedView, $allowedViews, true) ? $requestedView : 'dashboard';
        $perPage = in_array((int) $request->query('per_page'), self::PER_PAGE_OPTIONS, true)
            ? (int) $request->query('per_page')
            : self::DEFAULT_PER_PAGE;
        $followUpLayout = $view === 'followups' && in_array($request->query('layout'), ['table', 'calendar'], true)
            ? $request->query('layout') : 'table';

        $stats = [
            'total' => (clone $base)->count(),
            'new' => (clone $base)->where('status', 'new')->count(),
            'interested' => (clone $base)->where('status', 'interested')->count(),
            'converted' => (clone $base)->where('is_student', true)->count(),
            'overdue' => (clone $base)->whereNull('follow_up_completed_at')->where('follow_up_at', '<', $todayStart)->count(),
            // What the Follow-up planner actually lists, and the single source for
            // both the "In follow-up" card and the sidebar badge. They each used to
            // count something narrower than the planner — the badge counted overdue
            // only, the card counted open statuses and so missed a lead parked on
            // another status with a follow-up date booked. Both read lower than the
            // list they opened.
            'open_conversations' => (clone $base)->openConversation()->count(),
        ];

        $notifications = (clone $base)
            ->with('assignee')
            ->whereNull('follow_up_completed_at')
            ->whereNotNull('follow_up_at')
            ->where('follow_up_at', '<=', $tomorrowEnd)
            ->orderBy('follow_up_at')
            ->limit(20)
            ->get();

        $dashboard = $this->dashboardData($base, $stats, $todayStart, $todayEnd, $view === 'dashboard');

        $leads = CrmLead::query()->visibleTo($user)->with(['assignee', 'partner', 'partnerCode', 'websiteSubmissions', 'latestActivity'])->withCount('activities');
        if ($view === 'followups') {
            // Dated follow-ups first, oldest first; undated open conversations last.
            $leads->openConversation()->orderByRaw('follow_up_at is null')->orderBy('follow_up_at');
        } elseif ($view === 'students') {
            $leads->where('is_student', true)->latest('updated_at');
        } else {
            $leads->latest('updated_at');
        }
        $this->applyFilters($leads, $request, $user);
        $followUpCalendar = $this->followUpCalendar($base, $request, $user, $view === 'followups' && $followUpLayout === 'calendar');

        $auditEvents = [
            'lead_created' => 'Lead created',
            'lead_updated' => 'Lead updated',
            'timeline_comment_added' => 'Timeline comment added',
            'follow_up_completed' => 'Follow-up completed',
            'lead_converted' => 'Lead converted',
            'lead_enrollment_reverted' => 'Enrollment reverted',
            'student_journey_updated' => 'Student journey updated',
            'lead_deleted' => 'Lead moved to trash',
            'leads_imported' => 'Leads imported',
            'team_member_created' => 'Team member created',
            'team_member_updated' => 'Team member updated',
            'team_member_role_changed' => 'Team role changed',
            'partner_access_changed' => 'Partner access changed',
            'partner_code_created' => 'Partner code created',
            'partner_code_updated' => 'Partner code updated',
            'partner_code_access_changed' => 'Partner code paused or resumed',
            'partner_code_deleted' => 'Partner code deleted',
            'team_member_access_changed' => 'Team access changed',
            'crm_login' => 'CRM login',
            'crm_logout' => 'CRM logout',
            'mock_invite_created' => 'Mock interview link issued',
            'mock_invite_revoked' => 'Mock interview link revoked',
            'journey_university_added' => 'Journey planner: university added',
            'journey_university_removed' => 'Journey planner: university removed',
            'journey_login_created' => 'Journey planner started, student login created',
            'journey_password_reset' => 'Student password reset',
            'journey_admin_password_reset' => 'Student admin password reset',
            'journey_task_added' => 'Journey planner: task added',
            'journey_stage_added' => 'Journey planner: stage added',
            'journey_stage_removed' => 'Journey planner: stage removed',
            'journey_task_removed' => 'Journey planner: task removed',
            'journey_login_enabled' => 'Student login switched on',
            'journey_login_disabled' => 'Student login switched off',
        ];
        $auditLogs = null;
        if ($view === 'audit') {
            $auditQuery = CrmAuditLog::query()->with(['actor', 'lead'])->latest();
            if ($search = trim((string) $request->query('audit_search'))) {
                $auditQuery->where(function (Builder $query) use ($search): void {
                    $query->where('description', 'like', "%{$search}%")
                        ->orWhere('subject_label', 'like', "%{$search}%")
                        ->orWhereHas('actor', fn (Builder $actor) => $actor->where('name', 'like', "%{$search}%"));
                });
            }
            if ($events = CrmFilter::values($request, 'audit_event', $auditEvents)) {
                $auditQuery->whereIn('event', $events);
            }
            if ($actors = CrmFilter::ids($request, 'audit_user')) {
                $auditQuery->whereIn('crm_user_id', $actors);
            }
            $auditLogs = $auditQuery->paginate($perPage)->withQueryString();
        }

        $enrollmentQuery = PaymentAttempt::query()->with(['lead.assignee'])->latest();
        if ($user->isPartner()) {
            // What a student paid us is in-house information, so the whole log is
            // closed to a partner rather than narrowed to their own students.
            $enrollmentQuery->whereRaw('1 = 0');
        } elseif (! $user->isSuperAdmin()) {
            $enrollmentQuery->whereHas('lead', fn (Builder $lead) => $lead->visibleTo($user));
        }
        $enrollmentCount = (clone $enrollmentQuery)->count();
        if ($statusFilter = CrmFilter::values($request, 'payment_status', CrmEnrollmentController::STATUSES)) $enrollmentQuery->whereIn('status', $statusFilter);
        if ($pageFilter = CrmFilter::raw($request, 'enrollment_source')) $enrollmentQuery->whereIn('page_slug', $pageFilter);
        if ($planFilter = CrmFilter::raw($request, 'enrollment_plan')) $enrollmentQuery->whereIn('item_name', $planFilter);
        if ($enrollmentSearch = trim((string) $request->query('search'))) {
            $enrollmentQuery->where(fn (Builder $q) => $q->where('customer_name', 'like', "%{$enrollmentSearch}%")->orWhere('customer_email', 'like', "%{$enrollmentSearch}%")->orWhere('customer_phone', 'like', "%{$enrollmentSearch}%")->orWhere('item_name', 'like', "%{$enrollmentSearch}%")->orWhere('razorpay_payment_id', 'like', "%{$enrollmentSearch}%"));
        }

        $spamQuery = CrmSpamAttempt::query()->latest();
        if ($user->isSuperAdmin()) {
            if ($spamSearch = trim((string) $request->query('spam_search'))) {
                $spamQuery->where(fn (Builder $q) => $q->where('ip_address', 'like', "%{$spamSearch}%")->orWhere('source', 'like', "%{$spamSearch}%"));
            }
        } else {
            $spamQuery->whereRaw('1 = 0');
        }

        // Partner codes — the referral companies whose code travels in a public
        // link. Super admin only, like the Team screen that creates them: a code
        // decides which outside address gets a student's enquiry.
        //
        // The account is loaded with each code because the tab shows both halves
        // of a partner on one row — the company and the login that goes with it.
        $partnerCodeQuery = CrmPartnerCode::query()->with('account')->withCount('leads')
            ->orderByDesc('is_active')->orderBy('company_name');
        if (! $user->isSuperAdmin()) {
            $partnerCodeQuery->whereRaw('1 = 0');
        } elseif ($partnerCodeSearch = trim((string) $request->query('partner_code_search'))) {
            $partnerCodeQuery->where(fn (Builder $q) => $q
                ->where('company_name', 'like', "%{$partnerCodeSearch}%")
                ->orWhere('code', 'like', "%{$partnerCodeSearch}%")
                ->orWhere('email', 'like', "%{$partnerCodeSearch}%")
                ->orWhere('contact_name', 'like', "%{$partnerCodeSearch}%")
                // A partner is looked for by the person as readily as by the
                // company, and since the merge the tab knows both.
                ->orWhereHas('account', fn (Builder $account) => $account->where('name', 'like', "%{$partnerCodeSearch}%")));
        }

        $subscriberQuery = CrmSubscriber::query()->latest('subscribed_at');
        if ($user->isSuperAdmin()) {
            CrmSubscriberController::applyFilters($subscriberQuery, $request);
        } else {
            $subscriberQuery->whereRaw('1 = 0');
        }

        // Journey planners: every enrolled student whose planner has started. A
        // counsellor sees their own students (the leads assigned to them); a
        // super admin sees everyone. Partners have no such page.
        $journeyPlans = null;
        $journeyStats = null;
        $journeyCount = 0;
        if (! $user->isPartner()) {
            $journeyQuery = \App\Models\CrmJourneyPlan::query()
                ->whereHas('lead', fn (Builder $lead) => $lead->visibleTo($user)->where('is_student', true));
            $journeyCount = (clone $journeyQuery)->count();

            if ($view === 'journeys') {
                $rows = $journeyQuery
                    ->with(['lead.assignee', 'lead.studentAccount', 'applications'])
                    ->withCount(['documents as essays_waiting' => fn (Builder $d) => $d->where('kind', 'essay')->where('status', 'Submitted')])
                    ->get()
                    ->map(fn ($plan) => ['plan' => $plan] + \App\Support\JourneyPlanner::summary($plan));

                // The tiles count everyone this person can see, before any filter.
                $journeyStats = [
                    'total' => $rows->count(),
                    'average' => $rows->count() ? (int) round($rows->avg(fn ($r) => $r['all']['percent'])) : 0,
                    'late' => $rows->filter(fn ($r) => $r['overdue'] > 0)->count(),
                    'essays' => (int) $rows->sum(fn ($r) => $r['plan']->essays_waiting),
                    'neverSignedIn' => $rows->filter(fn ($r) => ! $r['plan']->lead->studentAccount?->last_login_at)->count(),
                    'complete' => $rows->filter(fn ($r) => $r['all']['percent'] === 100)->count(),
                ];

                if ($search = mb_strtolower(trim((string) $request->query('journey_search')))) {
                    $rows = $rows->filter(fn ($r) => str_contains(mb_strtolower($r['plan']->lead->name.' '.$r['plan']->lead->lead_number.' '.$r['plan']->lead->email), $search));
                }
                if ($user->isSuperAdmin() && ($counsellor = $request->integer('journey_counsellor'))) {
                    $rows = $rows->filter(fn ($r) => (int) $r['plan']->lead->assigned_to === $counsellor);
                }
                $rows = match ($request->query('journey_show')) {
                    'attention' => $rows->filter(fn ($r) => $r['overdue'] > 0 || $r['plan']->essays_waiting > 0),
                    'not_signed_in' => $rows->filter(fn ($r) => ! $r['plan']->lead->studentAccount?->last_login_at),
                    'complete' => $rows->filter(fn ($r) => $r['all']['percent'] === 100),
                    default => $rows,
                };
                $rows = match ($request->query('journey_sort')) {
                    'progress_asc' => $rows->sortBy(fn ($r) => $r['all']['percent']),
                    'progress_desc' => $rows->sortByDesc(fn ($r) => $r['all']['percent']),
                    'name' => $rows->sortBy(fn ($r) => mb_strtolower($r['plan']->lead->name)),
                    'late' => $rows->sortByDesc(fn ($r) => $r['overdue']),
                    default => $rows->sortByDesc(fn ($r) => $r['plan']->updated_at),
                };

                $page = max(1, $request->integer('journey_page', 1));
                $journeyPlans = new \Illuminate\Pagination\LengthAwarePaginator(
                    $rows->values()->forPage($page, $perPage), $rows->count(), $perPage, $page,
                    ['path' => $request->url(), 'pageName' => 'journey_page', 'query' => $request->except('journey_page')],
                );
            }
        }

        // Mock-interview invite links. A counsellor sees the links they issued;
        // a super admin sees every link.
        $mockInviteQuery = CrmMockInterviewInvite::query()->with(['creator', 'attempts'])->latest();
        if ($user->isPartner()) {
            $mockInviteQuery->whereRaw('1 = 0');
        } elseif (! $user->isSuperAdmin()) {
            $mockInviteQuery->where('created_by', $user->id);
        }
        $mockInviteCount = (clone $mockInviteQuery)->count();
        if ($inviteSearch = trim((string) $request->query('invite_search'))) {
            $mockInviteQuery->where(fn (Builder $q) => $q
                ->where('recipient_name', 'like', "%{$inviteSearch}%")
                ->orWhere('recipient_email', 'like', "%{$inviteSearch}%")
                ->orWhere('recipient_phone', 'like', "%{$inviteSearch}%"));
        }

        // partnerCode comes along because the Team screen edits a partner's
        // company and code in the same form as their account, and shows how many
        // leads its link has brought in — counted here rather than in the view.
        $team = $user->isSuperAdmin()
            ? CrmUser::query()
                ->with(['partnerCode' => fn ($query) => $query->withCount('leads')])
                ->withCount('partnerLeads')->orderByDesc('is_active')->orderBy('name')->get()
            : collect();

        $selectedLead = null;
        if ($request->filled('lead')) {
            $selectedLead = CrmLead::query()->visibleTo($user)
                ->with(['assignee', 'partner', 'partnerCode', 'activities.user', 'websiteSubmissions', 'journeyPlan.applications', 'studentAccount'])->find($request->integer('lead'));
        }

        return view('crm.dashboard', [
            'stats' => $stats,
            'dashboard' => $dashboard,
            'notifications' => $notifications,
            'leads' => $leads->paginate($perPage)->withQueryString(),
            'perPage' => $perPage,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'followUpCalendar' => $followUpCalendar,
            'selectedLead' => $selectedLead,
            'counsellors' => CrmUser::query()->where('role', 'counsellor')->where('is_active', true)->orderBy('name')->get(),
            'partners' => $this->partnerOptions($user),
            'team' => $team,
            // Which account the Team view's detail pane is showing. Picked out of
            // the collection already loaded rather than queried again, so an id
            // that is not on the list simply selects nothing.
            'teamMember' => $team->firstWhere('id', $request->integer('member')),
            'auditLogs' => $auditLogs,
            'auditEvents' => $auditEvents,
            'view' => $view,
            'followUpLayout' => $followUpLayout,
            'statuses' => CrmOptions::STATUSES,
            'pipelineStatuses' => CrmOptions::pipelineStatuses(),
            'followUpStatuses' => CrmOptions::FOLLOW_UP_STATUSES,
            'priorities' => CrmOptions::PRIORITIES,
            'categories' => CrmOptions::CATEGORIES,
            'leadOrigins' => CrmOptions::LEAD_ORIGINS,
            'leadTypes' => CrmOptions::LEAD_TYPES,
            'counsellorFilter' => $this->counsellorFilter($base, $user),
            'partnerCodes' => $partnerCodeQuery->paginate($perPage, ['*'], 'partner_code_page')->withQueryString(),
            'partnerCodeCount' => $user->isSuperAdmin() ? CrmPartnerCode::query()->count() : 0,
            'partnerCodeActiveCount' => $user->isSuperAdmin() ? CrmPartnerCode::query()->active()->count() : 0,
            'partnerCodeOptions' => $this->partnerCodeOptions($user),
            'newPartnerCode' => $user->isSuperAdmin() && session('new_partner_code')
                ? CrmPartnerCode::query()->find(session('new_partner_code'))
                : null,
            // Suggestions only — the field itself stays free text. Queried just for
            // the open drawer, so the list view does not pay for it.
            'intakeSuggestions' => $selectedLead ? $this->intakeSuggestions($user) : collect(),
            'doneStates' => CrmOptions::DONE_STATES,
            'doneFields' => CrmOptions::DONE_FIELDS,
            'notRecorded' => CrmOptions::NOT_RECORDED,
            'studentStages' => CrmOptions::STUDENT_STAGES,
            'studentCategories' => CrmOptions::STUDENT_CATEGORIES,
            'englishTests' => CrmOptions::ENGLISH_TESTS,
            'aptitudeTests' => CrmOptions::APTITUDE_TESTS,
            'enrollments' => $enrollmentQuery->paginate($perPage, ['*'], 'enrollment_page')->withQueryString(),
            'enrollmentCount' => $enrollmentCount,
            'enrollmentSources' => PaymentAttempt::query()->distinct()->orderBy('page_slug')->pluck('page_slug'),
            'enrollmentPlans' => collect(app(\App\Support\TestPrepCompareStore::class)->get()['programs'] ?? [])
                ->pluck('name')->merge(PaymentAttempt::query()->whereNotNull('crm_lead_id')->pluck('item_name'))
                ->map(fn ($name) => trim((string) $name))->filter()->unique(fn ($name) => mb_strtolower($name))->sort()->values(),
            'paymentStatuses' => \App\Http\Controllers\Crm\CrmEnrollmentController::STATUSES,
            'subscribers' => $subscriberQuery->paginate($perPage, ['*'], 'subscriber_page')->withQueryString(),
            'subscriberCount' => $user->isSuperAdmin() ? CrmSubscriber::query()->count() : 0,
            'subscriberActiveCount' => $user->isSuperAdmin() ? CrmSubscriber::query()->where('status', 'active')->count() : 0,
            'subscriberSources' => $user->isSuperAdmin()
                ? CrmSubscriber::query()->whereNotNull('source')->distinct()->orderBy('source')->pluck('source')
                : collect(),
            'mockInvites' => $mockInviteQuery->paginate($perPage, ['*'], 'invite_page')->withQueryString(),
            'mockInviteCount' => $mockInviteCount,
            'journeyPlans' => $journeyPlans,
            'journeyStats' => $journeyStats,
            'journeyCount' => $journeyCount,
            'journeyCounsellors' => $user->isSuperAdmin() && $view === 'journeys'
                ? CrmUser::query()->where('role', 'counsellor')->orderBy('name')->get(['id', 'name'])
                : collect(),
            'mockInviteCounts' => MockInterviewQuestions::INVITE_COUNTS,
            'mockQuestionTotal' => MockInterviewQuestions::total(),
            'spamAttempts' => $spamQuery->paginate($perPage, ['*'], 'spam_page')->withQueryString(),
            'spamCount' => $user->isSuperAdmin() ? CrmSpamAttempt::query()->count() : 0,
        ]);
    }

    /**
     * Options for the "owner" filter, which is a different list from the one you
     * can ASSIGN to ($counsellors, active counsellors only).
     *
     * Two things the plain list got wrong: there was no way to find leads nobody
     * owns, and deactivating a counsellor hid their still-open leads from the
     * filter entirely — their name kept showing in the Owner column while being
     * unselectable. Anyone actually holding a visible lead is listed, active or
     * not, with a count so a super admin can see the spread at a glance.
     *
     * One flat list on purpose: grouping it read as clutter in a filter bar.
     *
     * @return array{unassigned: int, people: \Illuminate\Support\Collection<int, array>}
     */
    /**
     * The names offered by the "Partner name" field and its filter.
     *
     * Active partners, plus anyone already named on a lead: a partner who has
     * since been switched off has to stay listed, or the next save of one of
     * their leads would silently blank the field (the dropdown could not
     * re-select a name it was not offering). CrmLeadController's validation
     * allows exactly this set.
     *
     * A partner gets an empty list — their own view is already narrowed to them,
     * and they have nothing to choose here.
     *
     * @return \Illuminate\Support\Collection<int, CrmUser>
     */
    private function partnerOptions(CrmUser $user): \Illuminate\Support\Collection
    {
        if ($user->isPartner()) {
            return collect();
        }

        // With their code, because the lead form no longer asks for one: picking
        // the partner is what sets it, so each option has to carry it.
        return CrmUser::query()
            ->with('partnerCode')
            ->where('role', 'partner')
            ->where(fn (Builder $offered) => $offered
                ->where('is_active', true)
                ->orWhereIn('id', CrmLead::query()->whereNotNull('partner_id')->distinct()->pluck('partner_id')))
            ->orderBy('name')
            ->get();
    }

    /**
     * The companies offered by the "Partner code" field and its filter.
     *
     * Same rule as the partner accounts above: active codes, plus any code a
     * lead already carries. A paused code has to stay listed, or the next save
     * of one of its leads would silently drop the attribution the dropdown could
     * no longer re-select — and the filter could not find those leads at all.
     * CrmLeadController's validation allows exactly this set.
     *
     * A partner sees nothing here — attribution is the team's record of where a
     * lead came from, not something a partner reads or sets.
     *
     * @return \Illuminate\Support\Collection<int, CrmPartnerCode>
     */
    private function partnerCodeOptions(CrmUser $user): \Illuminate\Support\Collection
    {
        if ($user->isPartner()) {
            return collect();
        }

        return CrmPartnerCode::query()
            ->where(fn (Builder $offered) => $offered
                ->where('is_active', true)
                ->orWhereIn('id', CrmLead::query()->whereNotNull('partner_code_id')->distinct()->pluck('partner_code_id')))
            ->orderBy('company_name')
            ->get();
    }

    private function counsellorFilter(Builder $base, CrmUser $user): array
    {
        if (! $user->isSuperAdmin()) {
            return ['unassigned' => 0, 'people' => collect()];
        }

        $counts = (clone $base)->selectRaw('assigned_to, COUNT(*) as total')->groupBy('assigned_to')->get();
        $byOwner = $counts->filter(fn ($row): bool => $row->assigned_to !== null)
            ->mapWithKeys(fn ($row): array => [(int) $row->assigned_to => (int) $row->total]);

        $people = CrmUser::query()
            ->where(fn (Builder $q) => $q
                ->where(fn (Builder $assignable) => $assignable->where('role', 'counsellor')->where('is_active', true))
                ->orWhereIn('id', $byOwner->keys()))
            ->orderBy('name')
            ->get()
            ->map(fn (CrmUser $person): array => [
                'id' => $person->id,
                'name' => $person->name,
                'total' => $byOwner[$person->id] ?? 0,
            ]);

        return [
            'unassigned' => (int) ($counts->firstWhere('assigned_to', null)->total ?? 0),
            'people' => $people->values(),
        ];
    }

    /**
     * Common intakes to offer as datalist hints on the academic card. The field
     * accepts anything; these just save typing for the usual terms, and any
     * intake a counsellor has already recorded joins the list.
     *
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function intakeSuggestions(CrmUser $user): \Illuminate\Support\Collection
    {
        $year = (int) now()->year;
        $standard = collect([$year, $year + 1, $year + 2])
            ->crossJoin(['January', 'May', 'September'])
            ->map(fn (array $pair): string => $pair[1].' '.$pair[0]);

        return CrmLead::query()->visibleTo($user)
            ->whereNotNull('intake')->where('intake', '!=', '')
            ->distinct()->orderBy('intake')->pluck('intake')
            ->merge($standard)
            ->map(fn ($intake): string => trim((string) $intake))
            ->filter()
            ->unique(fn (string $intake): string => mb_strtolower($intake))
            ->values();
    }

    private function followUpCalendar(Builder $base, Request $request, CrmUser $user, bool $include): ?array
    {
        if (!$include) {
            return null;
        }

        $month = now()->startOfMonth();
        $requestedMonth = trim((string) $request->query('month'));
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $requestedMonth)) {
            try {
                $month = Carbon::createFromFormat('!Y-m', $requestedMonth)->startOfMonth();
            } catch (\Throwable) {
                $month = now()->startOfMonth();
            }
        }

        $monthEnd = $month->copy()->endOfMonth();
        $calendarQuery = (clone $base)
            ->with('assignee')
            ->whereNull('follow_up_completed_at')
            ->whereBetween('follow_up_at', [$month->copy()->startOfDay(), $monthEnd->copy()->endOfDay()])
            ->orderBy('follow_up_at');
        $this->applyFilters($calendarQuery, $request, $user);

        $events = $calendarQuery->get();
        $eventsByDate = $events->groupBy(fn (CrmLead $lead): string => $lead->follow_up_at->format('Y-m-d'));
        $gridStart = $month->copy()->startOfWeek(Carbon::MONDAY);
        $gridEnd = $monthEnd->copy()->endOfWeek(Carbon::SUNDAY);
        $days = [];

        for ($day = $gridStart->copy(); $day->lte($gridEnd); $day->addDay()) {
            $days[] = [
                'date' => $day->copy(),
                'events' => $eventsByDate->get($day->format('Y-m-d'), collect()),
                'inMonth' => $day->month === $month->month && $day->year === $month->year,
            ];
        }

        return [
            'month' => $month,
            'previous' => $month->copy()->subMonth()->format('Y-m'),
            'next' => $month->copy()->addMonth()->format('Y-m'),
            'weeks' => array_chunk($days, 7),
            'total' => $events->count(),
            'dueToday' => $events->filter(fn (CrmLead $lead): bool => $lead->follow_up_at->isToday())->count(),
            'overdue' => $events->filter(fn (CrmLead $lead): bool => $lead->follow_up_at->isPast())->count(),
        ];
    }

    private function dashboardData(Builder $base, array $stats, mixed $todayStart, mixed $todayEnd, bool $includeDetails): array
    {
        $empty = [
            'statusBreakdown' => collect(),
            'countryBreakdown' => collect(),
            'mapPoints' => collect(),
            'sourceBreakdown' => collect(),
            'monthlyLeads' => collect(),
            'recentLeads' => collect(),
            'actionQueue' => collect(),
            'conversionRate' => $stats['total'] ? (int) round(($stats['converted'] / $stats['total']) * 100) : 0,
            'unassigned' => 0,
            'dueToday' => 0,
        ];

        if (!$includeDetails) {
            return $empty;
        }

        $statusBreakdown = (clone $base)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->orderByDesc('total')
            ->get()
            ->map(fn (CrmLead $row): array => [
                'key' => $row->status,
                'label' => CrmOptions::STATUSES[$row->status] ?? ucfirst(str_replace('_', ' ', $row->status)),
                'total' => (int) $row->total,
                'percentage' => $stats['total'] ? (int) round(((int) $row->total / $stats['total']) * 100) : 0,
            ]);

        $countryBreakdown = (clone $base)
            ->whereNotNull('country_interest')
            ->where('country_interest', '!=', '')
            ->selectRaw('country_interest as label, COUNT(*) as total')
            ->groupBy('country_interest')
            ->orderByDesc('total')
            ->limit(8)
            ->get()
            ->map(fn (CrmLead $row): array => ['label' => $row->label, 'total' => (int) $row->total]);

        $coordinates = $this->countryCoordinates();
        $mapPoints = $countryBreakdown->map(function (array $country) use ($coordinates): ?array {
            $key = preg_replace('/[^a-z]/', '', strtolower($country['label']));
            if (!isset($coordinates[$key])) {
                return null;
            }

            return [...$country, ...$coordinates[$key]];
        })->filter()->values();

        $sourceBreakdown = (clone $base)
            ->selectRaw("COALESCE(NULLIF(source, ''), 'Direct / unknown') as label, COUNT(*) as total")
            ->groupBy('label')
            ->orderByDesc('total')
            ->limit(6)
            ->get()
            ->map(fn (CrmLead $row): array => [
                'label' => $row->label,
                'total' => (int) $row->total,
                'percentage' => $stats['total'] ? (int) round(((int) $row->total / $stats['total']) * 100) : 0,
            ]);

        $monthStart = now()->subMonths(5)->startOfMonth();
        $monthCounts = (clone $base)
            ->where('created_at', '>=', $monthStart)
            ->get(['created_at'])
            ->groupBy(fn (CrmLead $lead): string => $lead->created_at->format('Y-m'))
            ->map->count();
        $monthlyLeads = collect(range(0, 5))->map(function (int $offset) use ($monthStart, $monthCounts): array {
            $month = $monthStart->copy()->addMonths($offset);

            return [
                'label' => $month->format('M'),
                'total' => (int) ($monthCounts[$month->format('Y-m')] ?? 0),
            ];
        });
        $monthlyMax = max(1, (int) $monthlyLeads->max('total'));
        $monthlyLeads = $monthlyLeads->map(fn (array $month): array => [
            ...$month,
            'percentage' => max(6, (int) round(($month['total'] / $monthlyMax) * 100)),
        ]);

        return [
            'statusBreakdown' => $statusBreakdown,
            'countryBreakdown' => $countryBreakdown,
            'mapPoints' => $mapPoints,
            'sourceBreakdown' => $sourceBreakdown,
            'monthlyLeads' => $monthlyLeads,
            'recentLeads' => (clone $base)->with('assignee')->latest()->limit(5)->get(),
            'actionQueue' => (clone $base)->with('assignee')
                ->whereNull('follow_up_completed_at')
                ->whereNotNull('follow_up_at')
                ->orderBy('follow_up_at')
                ->limit(5)
                ->get(),
            'conversionRate' => $stats['total'] ? (int) round(($stats['converted'] / $stats['total']) * 100) : 0,
            'unassigned' => (clone $base)->whereNull('assigned_to')->count(),
            'dueToday' => (clone $base)->whereNull('follow_up_completed_at')->whereBetween('follow_up_at', [$todayStart, $todayEnd])->count(),
        ];
    }

    private function countryCoordinates(): array
    {
        return [
            'canada' => ['lat' => 56.13, 'lng' => -106.35],
            'unitedstates' => ['lat' => 37.09, 'lng' => -95.71], 'usa' => ['lat' => 37.09, 'lng' => -95.71], 'us' => ['lat' => 37.09, 'lng' => -95.71],
            'unitedkingdom' => ['lat' => 55.38, 'lng' => -3.44], 'uk' => ['lat' => 55.38, 'lng' => -3.44],
            'ireland' => ['lat' => 53.14, 'lng' => -7.69], 'france' => ['lat' => 46.23, 'lng' => 2.21],
            'germany' => ['lat' => 51.17, 'lng' => 10.45], 'netherlands' => ['lat' => 52.13, 'lng' => 5.29],
            'spain' => ['lat' => 40.46, 'lng' => -3.75], 'italy' => ['lat' => 41.87, 'lng' => 12.57],
            'switzerland' => ['lat' => 46.82, 'lng' => 8.23], 'sweden' => ['lat' => 60.13, 'lng' => 18.64],
            'finland' => ['lat' => 61.92, 'lng' => 25.75], 'poland' => ['lat' => 51.92, 'lng' => 19.15],
            'portugal' => ['lat' => 39.40, 'lng' => -8.22], 'unitedarabemirates' => ['lat' => 23.42, 'lng' => 53.85],
            'uae' => ['lat' => 23.42, 'lng' => 53.85], 'india' => ['lat' => 20.59, 'lng' => 78.96],
            'china' => ['lat' => 35.86, 'lng' => 104.20], 'singapore' => ['lat' => 1.35, 'lng' => 103.82],
            'malaysia' => ['lat' => 4.21, 'lng' => 101.98], 'japan' => ['lat' => 36.20, 'lng' => 138.25],
            'southkorea' => ['lat' => 35.91, 'lng' => 127.77], 'australia' => ['lat' => -25.27, 'lng' => 133.78],
            'newzealand' => ['lat' => -40.90, 'lng' => 174.89],
        ];
    }

    public function export(Request $request): StreamedResponse
    {
        /** @var CrmUser $user */
        $user = $request->attributes->get('crm_user');
        $query = CrmLead::query()->visibleTo($user)->with(['assignee', 'partner', 'partnerCode'])->latest();
        $this->applyFilters($query, $request, $user);
        $rows = $query->get();

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Lead ID', 'Name', 'Phone', 'Email', 'City', 'Course', 'Country', 'Category', 'Lead type', 'Origin', 'Priority', 'Source', 'Status', 'Counsellor', 'Partner', 'Partner code', 'Referral company', 'Follow-up', 'Created',
                '10th %', '10th passing year', '12th %', '12th passing year', 'Graduation CGPA / %', 'Graduation passing year', 'Backlogs', 'Intake',
                'Counselling', 'Shortlisting', 'English proficiency tests', 'Aptitude tests',
            ]);
            foreach ($rows as $lead) {
                fputcsv($out, [
                    $lead->lead_number, $lead->name, $lead->phone, $lead->email, $lead->city,
                    $lead->course_interest, $lead->country_interest, $lead->category, $lead->lead_type, $lead->lead_origin, $lead->priority,
                    $lead->source, $lead->status, $lead->assignee?->name, $lead->partner?->name,
                    $lead->partnerCode?->code, $lead->partnerCode?->company_name,
                    $lead->follow_up_at?->format('Y-m-d H:i'), $lead->created_at->format('Y-m-d H:i'),
                    $lead->tenth_score, $lead->tenth_passing_year, $lead->twelfth_score, $lead->twelfth_passing_year,
                    $lead->graduation_score, $lead->graduation_passing_year, $lead->backlogs, $lead->intake,
                    CrmOptions::DONE_STATES[$lead->counselling] ?? '',
                    CrmOptions::DONE_STATES[$lead->shortlisting] ?? '',
                    CrmOptions::describeTests($lead->english_tests, CrmOptions::ENGLISH_TESTS),
                    CrmOptions::describeTests($lead->aptitude_tests, CrmOptions::APTITUDE_TESTS),
                ]);
            }
            fclose($out);
        }, 'one-degree-crm-leads-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function applyFilters(Builder $query, Request $request, CrmUser $user): void
    {
        if ($search = trim((string) $request->query('search'))) {
            $query->where(function (Builder $q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('lead_number', 'like', "%{$search}%");
            });
        }
        // Every dropdown below takes more than one value; two statuses ticked
        // means "either", not "both", so each is a whereIn over what was chosen.
        $statuses = CrmFilter::values($request, 'status', array_merge(
            [CrmOptions::FOLLOW_UP_GROUP => 'Any follow-up status'],
            CrmOptions::STATUSES,
        ));
        if ($statuses !== []) {
            // "Any follow-up status" is a set, not a status. Ticking it next to
            // named statuses widens the list to the union of the two.
            $query->whereIn('status', in_array(CrmOptions::FOLLOW_UP_GROUP, $statuses, true)
                ? array_values(array_unique(array_merge(
                    array_diff($statuses, [CrmOptions::FOLLOW_UP_GROUP]),
                    CrmOptions::FOLLOW_UP_STATUSES,
                )))
                : $statuses);
        }
        if ($priorities = CrmFilter::values($request, 'priority', CrmOptions::PRIORITIES)) {
            $query->whereIn('priority', $priorities);
        }
        if ($categories = CrmFilter::values($request, 'category', CrmOptions::CATEGORIES)) {
            $query->whereIn('category', $categories);
        }
        if ($stages = CrmFilter::values($request, 'student_stage', CrmOptions::STUDENT_STAGES)) {
            $query->whereIn('student_stage', $stages);
        }
        // No dropdown feeds this any more — the "specific source" filter was
        // removed from the bar. Kept so an explicit ?source= link (or a saved
        // export URL) still narrows the way it always did.
        if ($sources = CrmFilter::raw($request, 'source')) {
            $query->whereIn('source', $sources);
        }
        if ($origins = CrmFilter::values($request, 'lead_origin', CrmOptions::LEAD_ORIGINS)) {
            $query->whereIn('lead_origin', $origins);
        }
        if ($leadTypes = CrmFilter::values($request, 'lead_type', CrmOptions::LEAD_TYPES)) {
            // Some enquiry types also exist as a website submission source, and a
            // lead counts as that type either way. With several ticked this stays
            // one OR group so it cannot swallow the filters applied around it.
            $submissionSources = array_values(array_filter(array_map(static fn (string $type): ?string => [
                'student_profiler' => 'profiler',
                'loan_accommodation' => 'loan-acco',
                'statement_of_purpose' => 'sop',
                'visa_mock_interview' => 'visa-mock',
                'career_library' => 'career-library',
                'career_counselling' => 'career-counselling',
                'referral' => 'referral',
            ][$type] ?? null, $leadTypes)));
            $query->where(function (Builder $typeQuery) use ($leadTypes, $submissionSources): void {
                $typeQuery->whereIn('lead_type', $leadTypes);
                if ($submissionSources !== []) {
                    $typeQuery->orWhereHas('websiteSubmissions', fn (Builder $submission) => $submission->whereIn('source', $submissionSources));
                }
            });
        }
        // Counselling and shortlisting each filter to Yes, No, or the blank that
        // means nobody has recorded it — the state most leads are in, and the one
        // worth listing on its own. "Not recorded" is a null check rather than a
        // value, so a mixed selection becomes one OR group.
        foreach (array_keys(CrmOptions::DONE_FIELDS) as $field) {
            $selected = CrmFilter::values($request, $field, array_merge(
                CrmOptions::DONE_STATES,
                [CrmOptions::NOT_RECORDED => 'Not recorded'],
            ));
            if ($selected === []) {
                continue;
            }
            $wantsBlank = in_array(CrmOptions::NOT_RECORDED, $selected, true);
            $recorded = array_values(array_diff($selected, [CrmOptions::NOT_RECORDED]));
            $query->where(function (Builder $doneQuery) use ($field, $recorded, $wantsBlank): void {
                if ($recorded !== []) {
                    $doneQuery->whereIn($field, $recorded);
                }
                if ($wantsBlank) {
                    $recorded === [] ? $doneQuery->whereNull($field) : $doneQuery->orWhereNull($field);
                }
            });
        }
        // The Partner field, for the team. A partner's own list is already narrowed
        // to them, so there is nothing here for them to choose. "none" is a real
        // choice rather than an id — the leads no partner referred.
        if (! $user->isPartner()) {
            $wantsNoPartner = in_array('none', CrmFilter::raw($request, 'partner_id'), true);
            $partnerIds = CrmFilter::ids($request, 'partner_id');
            if ($partnerIds !== [] || $wantsNoPartner) {
                $query->where(function (Builder $partnerQuery) use ($partnerIds, $wantsNoPartner): void {
                    if ($partnerIds !== []) {
                        $partnerQuery->whereIn('partner_id', $partnerIds);
                    }
                    if ($wantsNoPartner) {
                        $partnerIds === [] ? $partnerQuery->whereNull('partner_id') : $partnerQuery->orWhereNull('partner_id');
                    }
                });
            }
        }
        // The Partner code field, same shape as the Partner filter above: "none"
        // is the leads that arrived without a referral code on the URL.
        if (! $user->isPartner()) {
            $wantsNoCode = in_array('none', CrmFilter::raw($request, 'partner_code_id'), true);
            $codeIds = CrmFilter::ids($request, 'partner_code_id');
            if ($codeIds !== [] || $wantsNoCode) {
                $query->where(function (Builder $codeQuery) use ($codeIds, $wantsNoCode): void {
                    if ($codeIds !== []) {
                        $codeQuery->whereIn('partner_code_id', $codeIds);
                    }
                    if ($wantsNoCode) {
                        $codeIds === [] ? $codeQuery->whereNull('partner_code_id') : $codeQuery->orWhereNull('partner_code_id');
                    }
                });
            }
        }
        if ($user->isSuperAdmin()) {
            // "unassigned" is a real choice in the owner filter, not an id, and it
            // can be ticked alongside named owners.
            $wantsUnassigned = in_array('unassigned', CrmFilter::raw($request, 'assigned_to'), true);
            $ownerIds = CrmFilter::ids($request, 'assigned_to');
            if ($ownerIds !== [] || $wantsUnassigned) {
                $query->where(function (Builder $ownerQuery) use ($ownerIds, $wantsUnassigned): void {
                    if ($ownerIds !== []) {
                        $ownerQuery->whereIn('assigned_to', $ownerIds);
                    }
                    if ($wantsUnassigned) {
                        $ownerIds === [] ? $ownerQuery->whereNull('assigned_to') : $ownerQuery->orWhereNull('assigned_to');
                    }
                });
            }
        }
        $this->applyFollowUpDateFilter($query, $request);
        $this->applyDueFilter($query, $request);
    }

    /**
     * Narrow the planner to what is actually due.
     *
     * The Overdue card on the dashboard used to link at the planner unfiltered:
     * it read "3" and opened a list of every open conversation, so the number
     * and the page it led to disagreed. It now links here, and "overdue" matches
     * the stat's own definition exactly — incomplete, and dated before today.
     */
    private function applyDueFilter(Builder $query, Request $request): void
    {
        $due = (string) $request->query('due');
        if (! in_array($due, ['overdue', 'today', 'week'], true)) {
            return;
        }

        $query->whereNull('follow_up_completed_at');

        match ($due) {
            'overdue' => $query->where('follow_up_at', '<', now()->startOfDay()),
            'today' => $query->whereBetween('follow_up_at', [now()->startOfDay(), now()->endOfDay()]),
            'week' => $query->whereBetween('follow_up_at', [now()->startOfDay(), now()->addWeek()->endOfDay()]),
        };
    }

    /** Filter on the lead's "Next follow-up" date — leads scheduled on the chosen day. */
    private function applyFollowUpDateFilter(Builder $query, Request $request): void
    {
        $value = trim((string) $request->query('follow_up_date'));
        if ($value === '') {
            return;
        }

        try {
            $date = Carbon::createFromFormat('!Y-m-d', $value);
        } catch (\Throwable) {
            return;
        }

        $query->whereBetween('follow_up_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()]);
    }
}
