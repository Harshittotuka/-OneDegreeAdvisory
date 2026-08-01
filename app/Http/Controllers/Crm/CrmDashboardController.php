<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmAuditLog;
use App\Models\CrmLead;
use App\Models\CrmMockInterviewInvite;
use App\Models\CrmSubscriber;
use App\Models\CrmUser;
use App\Models\PaymentAttempt;
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

        if ($request->query('view') === 'audit') {
            abort_unless($user->isSuperAdmin(), 403);
        }
        $allowedViews = ['dashboard', 'leads', 'enrollments', 'followups', 'students', 'shortlisting', 'mock-invites'];
        if ($user->isSuperAdmin()) {
            $allowedViews = [...$allowedViews, 'subscriptions', 'audit'];
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

        $leads = CrmLead::query()->visibleTo($user)->with(['assignee', 'websiteSubmissions', 'latestActivity'])->withCount('activities');
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
            'student_journey_updated' => 'Student journey updated',
            'lead_deleted' => 'Lead moved to trash',
            'leads_imported' => 'Leads imported',
            'team_member_created' => 'Team member created',
            'team_member_updated' => 'Team member updated',
            'team_member_role_changed' => 'Team role changed',
            'team_member_access_changed' => 'Team access changed',
            'crm_login' => 'CRM login',
            'crm_logout' => 'CRM logout',
            'mock_invite_created' => 'Mock interview link issued',
            'mock_invite_revoked' => 'Mock interview link revoked',
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
            if (array_key_exists((string) $request->query('audit_event'), $auditEvents)) {
                $auditQuery->where('event', $request->query('audit_event'));
            }
            if ($request->filled('audit_user')) {
                $auditQuery->where('crm_user_id', $request->integer('audit_user'));
            }
            $auditLogs = $auditQuery->paginate($perPage)->withQueryString();
        }

        $enrollmentQuery = PaymentAttempt::query()->with(['lead.assignee'])->latest();
        if (! $user->isSuperAdmin()) {
            $enrollmentQuery->whereHas('lead', fn (Builder $lead) => $lead->visibleTo($user));
        }
        $enrollmentCount = (clone $enrollmentQuery)->count();
        if ($request->filled('payment_status')) $enrollmentQuery->where('status', $request->query('payment_status'));
        if ($request->filled('enrollment_source')) $enrollmentQuery->where('page_slug', $request->query('enrollment_source'));
        if ($request->filled('enrollment_plan')) $enrollmentQuery->where('item_name', $request->query('enrollment_plan'));
        if ($enrollmentSearch = trim((string) $request->query('search'))) {
            $enrollmentQuery->where(fn (Builder $q) => $q->where('customer_name', 'like', "%{$enrollmentSearch}%")->orWhere('customer_email', 'like', "%{$enrollmentSearch}%")->orWhere('customer_phone', 'like', "%{$enrollmentSearch}%")->orWhere('item_name', 'like', "%{$enrollmentSearch}%")->orWhere('razorpay_payment_id', 'like', "%{$enrollmentSearch}%"));
        }

        $subscriberQuery = CrmSubscriber::query()->latest('subscribed_at');
        if ($user->isSuperAdmin()) {
            CrmSubscriberController::applyFilters($subscriberQuery, $request);
        } else {
            $subscriberQuery->whereRaw('1 = 0');
        }

        // Mock-interview invite links. A counsellor sees the links they issued;
        // a super admin sees every link.
        $mockInviteQuery = CrmMockInterviewInvite::query()->with(['creator', 'attempts'])->latest();
        if (! $user->isSuperAdmin()) {
            $mockInviteQuery->where('created_by', $user->id);
        }
        $mockInviteCount = (clone $mockInviteQuery)->count();
        if ($inviteSearch = trim((string) $request->query('invite_search'))) {
            $mockInviteQuery->where(fn (Builder $q) => $q
                ->where('recipient_name', 'like', "%{$inviteSearch}%")
                ->orWhere('recipient_email', 'like', "%{$inviteSearch}%")
                ->orWhere('recipient_phone', 'like', "%{$inviteSearch}%"));
        }

        $selectedLead = null;
        if ($request->filled('lead')) {
            $selectedLead = CrmLead::query()->visibleTo($user)
                ->with(['assignee', 'activities.user', 'websiteSubmissions'])->find($request->integer('lead'));
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
            'team' => $user->isSuperAdmin() ? CrmUser::query()->orderByDesc('is_active')->orderBy('name')->get() : collect(),
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
            // Suggestions only — the field itself stays free text. Queried just for
            // the open drawer, so the list view does not pay for it.
            'intakeSuggestions' => $selectedLead ? $this->intakeSuggestions($user) : collect(),
            'counsellingShortlisting' => CrmOptions::COUNSELLING_SHORTLISTING,
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
            'mockInviteCounts' => MockInterviewQuestions::INVITE_COUNTS,
            'mockQuestionTotal' => MockInterviewQuestions::total(),
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
        $query = CrmLead::query()->visibleTo($user)->with('assignee')->latest();
        $this->applyFilters($query, $request, $user);
        $rows = $query->get();

        return response()->streamDownload(function () use ($rows): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Lead ID', 'Name', 'Phone', 'Email', 'City', 'Course', 'Country', 'Category', 'Lead type', 'Origin', 'Priority', 'Source', 'Status', 'Counsellor', 'Follow-up', 'Created',
                '10th %', '10th passing year', '12th %', '12th passing year', 'Graduation CGPA / %', 'Graduation passing year', 'Backlogs', 'Intake',
                'Counselling and shortlisting', 'English proficiency tests', 'Aptitude tests',
            ]);
            foreach ($rows as $lead) {
                fputcsv($out, [
                    $lead->lead_number, $lead->name, $lead->phone, $lead->email, $lead->city,
                    $lead->course_interest, $lead->country_interest, $lead->category, $lead->lead_type, $lead->lead_origin, $lead->priority,
                    $lead->source, $lead->status, $lead->assignee?->name,
                    $lead->follow_up_at?->format('Y-m-d H:i'), $lead->created_at->format('Y-m-d H:i'),
                    $lead->tenth_score, $lead->tenth_passing_year, $lead->twelfth_score, $lead->twelfth_passing_year,
                    $lead->graduation_score, $lead->graduation_passing_year, $lead->backlogs, $lead->intake,
                    CrmOptions::COUNSELLING_SHORTLISTING[$lead->counselling_shortlisting] ?? '',
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
        $status = (string) $request->query('status');
        if ($status === CrmOptions::FOLLOW_UP_GROUP) {
            $query->whereIn('status', CrmOptions::FOLLOW_UP_STATUSES);
        } elseif (array_key_exists($status, CrmOptions::STATUSES)) {
            $query->where('status', $status);
        }
        if (array_key_exists((string) $request->query('priority'), CrmOptions::PRIORITIES)) {
            $query->where('priority', $request->query('priority'));
        }
        if (array_key_exists((string) $request->query('category'), CrmOptions::CATEGORIES)) {
            $query->where('category', $request->query('category'));
        }
        if (array_key_exists((string) $request->query('student_stage'), CrmOptions::STUDENT_STAGES)) {
            $query->where('student_stage', $request->query('student_stage'));
        }
        // No dropdown feeds this any more — the "specific source" filter was
        // removed from the bar. Kept so an explicit ?source= link (or a saved
        // export URL) still narrows the way it always did.
        if ($request->filled('source')) {
            $query->where('source', $request->query('source'));
        }
        if (array_key_exists((string) $request->query('lead_origin'), CrmOptions::LEAD_ORIGINS)) {
            $query->where('lead_origin', $request->query('lead_origin'));
        }
        if (array_key_exists((string) $request->query('lead_type'), CrmOptions::LEAD_TYPES)) {
            $leadType = (string) $request->query('lead_type');
            $submissionSource = [
                'student_profiler' => 'profiler',
                'loan_accommodation' => 'loan-acco',
                'statement_of_purpose' => 'sop',
                'visa_mock_interview' => 'visa-mock',
                'career_library' => 'career-library',
            ][$leadType] ?? null;
            $query->where(function (Builder $typeQuery) use ($leadType, $submissionSource): void {
                $typeQuery->where('lead_type', $leadType);
                if ($submissionSource !== null) {
                    $typeQuery->orWhereHas('websiteSubmissions', fn (Builder $submission) => $submission->where('source', $submissionSource));
                }
            });
        }
        if ($user->isSuperAdmin() && $request->filled('assigned_to')) {
            // "unassigned" is a real choice in the owner filter, not an id.
            $request->query('assigned_to') === 'unassigned'
                ? $query->whereNull('assigned_to')
                : $query->where('assigned_to', $request->integer('assigned_to'));
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
