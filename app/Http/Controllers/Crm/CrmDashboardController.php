<?php

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmLead;
use App\Models\CrmUser;
use App\Support\CrmOptions;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CrmDashboardController extends Controller
{
    public function index(Request $request): View
    {
        /** @var CrmUser $user */
        $user = $request->attributes->get('crm_user');
        $base = CrmLead::query()->visibleTo($user);
        $now = now();
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();
        $tomorrowEnd = $now->copy()->addDay()->endOfDay();

        $view = in_array($request->query('view'), ['dashboard', 'leads', 'followups', 'students'], true)
            ? $request->query('view') : 'dashboard';
        $followUpLayout = $view === 'followups' && in_array($request->query('layout'), ['table', 'calendar'], true)
            ? $request->query('layout') : 'table';

        $stats = [
            'total' => (clone $base)->count(),
            'new' => (clone $base)->where('status', 'new')->count(),
            'interested' => (clone $base)->where('status', 'interested')->count(),
            'follow_up' => (clone $base)->where('status', 'follow_up')->count(),
            'converted' => (clone $base)->where('is_student', true)->count(),
            'overdue' => (clone $base)->whereNull('follow_up_completed_at')->where('follow_up_at', '<', $todayStart)->count(),
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

        $leads = CrmLead::query()->visibleTo($user)->with('assignee')->withCount('activities');
        if ($view === 'followups') {
            $leads->whereNotNull('follow_up_at')->whereNull('follow_up_completed_at')->orderBy('follow_up_at');
        } elseif ($view === 'students') {
            $leads->where('is_student', true)->latest('updated_at');
        } else {
            $leads->latest('updated_at');
        }
        $this->applyFilters($leads, $request, $user);
        $followUpCalendar = $this->followUpCalendar($base, $request, $user, $view === 'followups' && $followUpLayout === 'calendar');

        $selectedLead = null;
        if ($request->filled('lead')) {
            $selectedLead = CrmLead::query()->visibleTo($user)
                ->with(['assignee', 'activities.user'])->find($request->integer('lead'));
        }

        return view('crm.dashboard', [
            'stats' => $stats,
            'dashboard' => $dashboard,
            'notifications' => $notifications,
            'leads' => $leads->paginate(20)->withQueryString(),
            'followUpCalendar' => $followUpCalendar,
            'selectedLead' => $selectedLead,
            'counsellors' => CrmUser::query()->where('role', 'counsellor')->where('is_active', true)->orderBy('name')->get(),
            'team' => $user->isSuperAdmin() ? CrmUser::query()->orderByDesc('is_active')->orderBy('name')->get() : collect(),
            'view' => $view,
            'followUpLayout' => $followUpLayout,
            'statuses' => CrmOptions::STATUSES,
            'priorities' => CrmOptions::PRIORITIES,
            'categories' => CrmOptions::CATEGORIES,
            'studentStages' => CrmOptions::STUDENT_STAGES,
            'studentCategories' => CrmOptions::STUDENT_CATEGORIES,
        ]);
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
            fputcsv($out, ['Lead ID', 'Name', 'Phone', 'Email', 'City', 'Course', 'Country', 'Category', 'Priority', 'Source', 'Status', 'Counsellor', 'Follow-up', 'Created']);
            foreach ($rows as $lead) {
                fputcsv($out, [
                    $lead->lead_number, $lead->name, $lead->phone, $lead->email, $lead->city,
                    $lead->course_interest, $lead->country_interest, $lead->category, $lead->priority,
                    $lead->source, $lead->status, $lead->assignee?->name,
                    $lead->follow_up_at?->format('Y-m-d H:i'), $lead->created_at->format('Y-m-d H:i'),
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
        if (array_key_exists((string) $request->query('status'), CrmOptions::STATUSES)) {
            $query->where('status', $request->query('status'));
        }
        if (array_key_exists((string) $request->query('priority'), CrmOptions::PRIORITIES)) {
            $query->where('priority', $request->query('priority'));
        }
        if (array_key_exists((string) $request->query('category'), CrmOptions::CATEGORIES)) {
            $query->where('category', $request->query('category'));
        }
        if ($user->isSuperAdmin() && $request->filled('assigned_to')) {
            $query->where('assigned_to', $request->integer('assigned_to'));
        }
    }
}
