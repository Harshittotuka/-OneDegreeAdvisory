@php
    $initials = static fn (?string $name): string => collect(preg_split('/\s+/', trim((string) $name)))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') ?: '?';
    $titles = ['dashboard' => ['Dashboard', 'Your lead performance at a glance'], 'leads' => ['Leads', 'Website and manually created enquiries in one place'], 'enrollments' => ['In progress Enrollment', 'Payment records classified by program and source page'], 'subscriptions' => ['Subscriptions', 'Manage newsletter subscriptions in one simple list'], 'followups' => ['Follow-up planner', 'Upcoming and overdue conversations'], 'students' => ['Enrolled students', 'Track students through admissions and visa stages'], 'audit' => ['Audit log', 'See every CRM action and who performed it'], 'shortlisting' => ['PDF shortlisting', 'Replace a report PDF\'s last page with a university shortlist'], 'mock-invites' => ['Mock interviews', 'Issue extended mock-interview links and see how students scored']];
    $currentTitle = $titles[$view];
    $isListView = ! in_array($view, ['dashboard', 'enrollments', 'subscriptions', 'audit', 'shortlisting'], true);
    $filterQuery = request()->except(['page', 'lead']);
    $calendarQuery = request()->except(['page', 'lead', 'month']);
    $followUpLayoutQuery = request()->except(['page', 'lead', 'layout', 'month']);
    $leadErrors = $errors->getBag('leadCreate');
    $auditValue = static function (mixed $value): string {
        if ($value === null || $value === '') return 'Not set';
        if (is_bool($value)) return $value ? 'Yes' : 'No';
        if (is_array($value)) return (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return (string) $value;
    };
    // Leads-table milestone cell (derived from status). Content is trusted markup, render with {!! !!}.
    $milestone = static function (bool $done, ?\Carbon\Carbon $date = null): string {
        if (! $done) return '<span class="lead-ms lead-ms-no">—</span>';
        $sub = $date ? '<span class="subtext">'.e($date->format('d M Y')).'</span>' : '';
        return '<span class="lead-ms lead-ms-yes">✓ Yes</span>'.$sub;
    };
@endphp
<!doctype html>
<html lang="en" class="crm-css-pending">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>{{ $currentTitle[0] }} · One Degree CRM</title>
    <style>html.crm-css-pending{background:#17182a}html.crm-css-pending body{visibility:hidden!important;opacity:0!important}html.crm-css-pending:before{content:"Loading CRM…";position:fixed;inset:0;display:grid;place-items:center;color:rgba(255,255,255,.72);font:600 13px/1.4 system-ui,sans-serif;letter-spacing:.08em;text-transform:uppercase}</style>
    <style>.lead-ms{display:inline-flex;align-items:center;gap:.3em;font-weight:600;white-space:nowrap}.lead-ms-yes{color:#0f9d58}.lead-ms-no{opacity:.35}td.lead-remark{max-width:300px;white-space:normal;vertical-align:top}td.lead-remark .subtext{display:block;margin-top:2px}.lead-remark-text{display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;line-height:1.4}</style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Fraunces:opsz,wght@9..144,500..700&family=Inter:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Sora:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link id="crmThemeStylesheet" rel="stylesheet" href="{{ asset('assets/crm/crm.css') }}?v={{ filemtime(public_path('assets/crm/crm.css')) }}" data-classic-href="{{ asset('assets/crm/crm-classic.css') }}?v={{ filemtime(public_path('assets/crm/crm-classic.css')) }}" data-evergreen-href="{{ asset('assets/crm/crm.css') }}?v={{ filemtime(public_path('assets/crm/crm.css')) }}" data-orbit-href="{{ asset('assets/crm/crm-orbit.css') }}?v={{ filemtime(public_path('assets/crm/crm-orbit.css')) }}">
    <script>
        (() => {
            let theme = 'evergreen';
            try {
                const savedTheme = localStorage.getItem('crmTheme');
                theme = ['classic', 'evergreen', 'orbit'].includes(savedTheme) ? savedTheme : 'evergreen';
            } catch (error) {}
            document.documentElement.dataset.crmTheme = theme;
            const stylesheet = document.getElementById('crmThemeStylesheet');
            const selectedHref = theme === 'classic' ? stylesheet.dataset.classicHref : (theme === 'orbit' ? stylesheet.dataset.orbitHref : stylesheet.dataset.evergreenHref);
            if (stylesheet.href !== selectedHref) {
                stylesheet.dataset.crmThemeLoading = 'true';
                const finishThemeLoad = () => delete stylesheet.dataset.crmThemeLoading;
                stylesheet.addEventListener('load', finishThemeLoad, { once: true });
                stylesheet.addEventListener('error', finishThemeLoad, { once: true });
                stylesheet.href = selectedHref;
            }
        })();
    </script>
    <noscript><style>html.crm-css-pending body{visibility:visible!important;opacity:1!important}html.crm-css-pending:before{display:none}</style></noscript>
    <link id="crmThemeSwitcherStylesheet" rel="stylesheet" href="{{ asset('assets/crm/crm-theme-switcher.css') }}?v={{ filemtime(public_path('assets/crm/crm-theme-switcher.css')) }}">
    <link id="crmDashboardStylesheet" rel="stylesheet" href="{{ asset('assets/crm/crm-dashboard.css') }}?v={{ filemtime(public_path('assets/crm/crm-dashboard.css')) }}">
    <link id="crmToastStylesheet" rel="stylesheet" href="{{ asset('assets/crm/crm-toast.css') }}?v={{ filemtime(public_path('assets/crm/crm-toast.css')) }}">
    <script>
        (() => {
            const root = document.documentElement;
            const stylesheets = ['crmThemeStylesheet', 'crmThemeSwitcherStylesheet', 'crmDashboardStylesheet', 'crmToastStylesheet']
                .map(id => document.getElementById(id))
                .filter(Boolean);
            let revealed = false;
            const reveal = () => {
                if (revealed) return;
                revealed = true;
                root.classList.remove('crm-css-pending');
                root.classList.add('crm-css-ready');
            };
            const isReady = link => link.dataset.crmThemeLoading !== 'true' && Boolean(link.sheet);
            const ready = link => isReady(link)
                ? Promise.resolve()
                : new Promise(resolve => {
                    let settled = false;
                    const done = () => {
                        if (settled) return;
                        settled = true;
                        resolve();
                    };
                    link.addEventListener('load', done, { once: true });
                    link.addEventListener('error', done, { once: true });
                    queueMicrotask(() => {
                        if (isReady(link)) done();
                    });
                });

            Promise.all(stylesheets.map(ready)).then(() => requestAnimationFrame(() => requestAnimationFrame(reveal)));
            window.setTimeout(reveal, 5000);
        })();
    </script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="">
</head>
<body>
<div class="crm-app" data-crm-app>
@include('crm.partials.toasts', [
    'successMessage' => session('status'),
    'errorMessage' => $errors->getBag('default')->first(),
])
<div class="shell">
    <aside class="sidebar" id="sidebar">
        <div class="login-brand">
            <span class="brand-mark"><img src="{{ asset('assets/Logo/mark-light.svg') }}" alt=""></span>
            <span class="brand-copy"><strong>One Degree</strong><span>Lead CRM</span></span>
        </div>
        <div class="nav-label">Workspace</div>
        <nav class="nav">
            <a class="nav-link nav-dashboard {{ $view === 'dashboard' ? 'active' : '' }}" href="{{ route('crm.dashboard', ['view' => 'dashboard']) }}"><span class="nav-icon">@include('crm.partials.nav-icon',['name'=>'dashboard'])</span><span class="nav-text">Dashboard</span></a>
            <a class="nav-link nav-leads {{ $view === 'leads' ? 'active' : '' }}" href="{{ route('crm.dashboard', ['view' => 'leads']) }}"><span class="nav-icon">@include('crm.partials.nav-icon',['name'=>'leads'])</span><span class="nav-text">Leads</span><span class="nav-badge">{{ $stats['total'] }}</span></a>
            <a class="nav-link nav-followups {{ $view === 'followups' ? 'active' : '' }}" href="{{ route('crm.dashboard', ['view' => 'followups']) }}"><span class="nav-icon">@include('crm.partials.nav-icon',['name'=>'followups'])</span><span class="nav-text">Follow-ups</span>@if($stats['overdue'])<span class="nav-badge">{{ $stats['overdue'] }}</span>@endif</a>
            <a class="nav-link nav-students {{ $view === 'students' ? 'active' : '' }}" href="{{ route('crm.dashboard', ['view' => 'students']) }}"><span class="nav-icon">@include('crm.partials.nav-icon',['name'=>'students'])</span><span class="nav-text">Enrolled students</span></a>
            <a class="nav-link nav-shortlisting {{ $view === 'shortlisting' ? 'active' : '' }}" href="{{ route('crm.dashboard', ['view' => 'shortlisting']) }}"><span class="nav-icon">@include('crm.partials.nav-icon',['name'=>'shortlisting'])</span><span class="nav-text">PDF shortlisting</span></a>
            <a class="nav-link nav-mock-invites {{ $view === 'mock-invites' ? 'active' : '' }}" href="{{ route('crm.dashboard', ['view' => 'mock-invites']) }}"><span class="nav-icon">@include('crm.partials.nav-icon',['name'=>'mock-invites'])</span><span class="nav-text">Mock interviews</span>@if($mockInviteCount)<span class="nav-badge">{{ $mockInviteCount }}</span>@endif</a>
            <a class="nav-link nav-enrollments {{ $view === 'enrollments' ? 'active' : '' }}" href="{{ route('crm.dashboard', ['view' => 'enrollments']) }}"><span class="nav-icon">@include('crm.partials.nav-icon',['name'=>'enrollments'])</span><span class="nav-text">In progress Enrollment</span><span class="nav-badge">{{ $enrollmentCount }}</span></a>
            @if($crmUser->isSuperAdmin())<a class="nav-link nav-subscriptions {{ $view === 'subscriptions' ? 'active' : '' }}" href="{{ route('crm.dashboard', ['view' => 'subscriptions']) }}"><span class="nav-icon">@include('crm.partials.nav-icon',['name'=>'subscriptions'])</span><span class="nav-text">Subscriptions</span><span class="nav-badge">{{ $subscriberCount }}</span></a>@endif
        </nav>
        <div class="sidebar-bottom">
            <div class="side-user-wrap" data-user-menu>
                <div class="user-menu" data-user-menu-panel role="menu">
                    <div class="user-menu-head">
                        <span class="user-menu-avatar">{{ $initials($crmUser->name) }}</span>
                        <span class="user-menu-id"><strong>{{ $crmUser->name }}</strong><span>{{ $crmUser->isSuperAdmin() ? 'Super admin' : 'Counsellor' }}</span></span>
                    </div>
                    <div class="user-menu-sep"></div>
                    @if($crmUser->isSuperAdmin())
                        <span class="user-menu-label">Administration</span>
                        <a class="user-menu-item {{ $view === 'audit' ? 'is-current' : '' }}" href="{{ route('crm.dashboard', ['view' => 'audit']) }}" role="menuitem"><span class="user-menu-item-icon">@include('crm.partials.nav-icon',['name'=>'audit'])</span>Audit log</a>
                        <button type="button" class="user-menu-item" data-modal-open="teamModal" role="menuitem"><span class="user-menu-item-icon">@include('crm.partials.nav-icon',['name'=>'team'])</span>Team management</button>
                        <div class="user-menu-sep"></div>
                    @endif
                    <span class="user-menu-label">Appearance</span>
                    <div class="theme-slider" role="group" aria-label="CRM theme">
                        <span class="theme-slider-thumb" aria-hidden="true"></span>
                        <button type="button" class="theme-opt" data-theme-option="classic" title="Classic theme">
                            <span class="theme-swatch" style="--a:#21147f;--b:#f6551d"></span>
                            <span class="theme-opt-name">Classic</span>
                        </button>
                        <button type="button" class="theme-opt" data-theme-option="evergreen" title="Evergreen theme">
                            <span class="theme-swatch" style="--a:#1f7a4d;--b:#d9a441"></span>
                            <span class="theme-opt-name">Evergreen</span>
                        </button>
                        <button type="button" class="theme-opt" data-theme-option="orbit" title="Orbit theme">
                            <span class="theme-swatch" style="--a:#6c5ce7;--b:#ff7078"></span>
                            <span class="theme-opt-name">Orbit</span>
                        </button>
                    </div>
                    <div class="user-menu-sep"></div>
                    <form method="post" action="{{ route('crm.logout') }}" data-transition-form data-transition-label="Signing you out securely…">@csrf<button type="submit" class="user-menu-item user-menu-signout" role="menuitem"><span class="user-menu-item-icon">@include('crm.partials.nav-icon',['name'=>'logout'])</span>Sign out</button></form>
                </div>
                <button type="button" class="side-user-trigger" data-user-menu-toggle aria-haspopup="true" aria-expanded="false">
                    <span class="avatar">{{ $initials($crmUser->name) }}</span>
                    <span class="side-user-info"><strong>{{ $crmUser->name }}</strong><span>{{ $crmUser->isSuperAdmin() ? 'Super admin' : 'Counsellor' }}</span></span>
                    <span class="side-user-caret" aria-hidden="true"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 15 6-6 6 6"/></svg></span>
                </button>
            </div>
        </div>
    </aside>

    <main class="main">
        <header class="topbar">
            <div style="display:flex;align-items:center;gap:11px">
                <button class="icon-btn mobile-toggle" id="menuToggle" aria-label="Open menu">☰</button>
                <div class="page-title"><h1>{{ $currentTitle[0] }}</h1><p>{{ $isListView ? $currentTitle[1].' · '.number_format($leads->total()).' record'.($leads->total() === 1 ? '' : 's').' in this view' : $currentTitle[1] }}</p></div>
            </div>
            <div class="top-actions">
                {{-- Soft refresh: re-fetches this view through the CRM's own swap
                     path, so scroll, open drawer and typed filters survive. --}}
                <button class="icon-btn icon-btn-refresh" type="button" data-crm-refresh aria-label="Refresh this view" title="Refresh"><svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.5 12a8.5 8.5 0 1 1-2.6-6.1"/><path d="M20.5 4.2V9h-4.8"/></svg></button>
                <button class="icon-btn" id="notificationToggle" aria-label="Follow-up notifications"><svg viewBox="0 0 24 24" width="19" height="19" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>@if($notifications->count())<span class="count-dot">{{ $notifications->count() }}</span>@endif</button>
                <span class="top-actions-sep" aria-hidden="true"></span>
                @if($view === 'followups')
                    <div class="followup-view-switch" role="group" aria-label="Follow-up display">
                        <a @class(['active' => $followUpLayout === 'table']) href="{{ route('crm.dashboard', array_merge($followUpLayoutQuery, ['view' => 'followups', 'layout' => 'table'])) }}" aria-pressed="{{ $followUpLayout === 'table' ? 'true' : 'false' }}"><span aria-hidden="true">&#9776;</span> Table</a>
                        <a @class(['active' => $followUpLayout === 'calendar']) href="{{ route('crm.dashboard', array_merge($followUpLayoutQuery, ['view' => 'followups', 'layout' => 'calendar', 'month' => request('month', now()->format('Y-m'))])) }}" aria-pressed="{{ $followUpLayout === 'calendar' ? 'true' : 'false' }}"><span aria-hidden="true">&#9638;</span> Calendar</a>
                    </div>
                @endif
                @if($isListView)
                    <button class="btn btn-outline" data-modal-open="importModal">⇧ <span>Import Excel / CSV</span></button>
                    <a class="btn btn-outline" href="{{ route('crm.leads.export', $filterQuery) }}" data-native-navigation>⇩ <span>Export</span></a>
                @endif
                <button class="btn btn-primary" data-modal-open="leadModal">＋ <span>Add lead</span></button>
            </div>
            <div class="popover hidden" id="notificationPopover">
                <div class="popover-head"><strong>Follow-up reminders</strong><span>Next 48 hours</span></div>
                @forelse($notifications as $notice)
                    @php $isToday = $notice->follow_up_at->isToday(); $isOverdue = $notice->follow_up_at->isPast() && !$isToday; @endphp
                    <a class="notice" href="{{ route('crm.dashboard', ['view' => 'followups', 'lead' => $notice->id]) }}">
                        <span class="notice-dot"></span>
                        <span class="notice-main"><strong>{{ $notice->name }}</strong><p>{{ $notice->lead_number }} · {{ $notice->assignee?->name ?? 'Unassigned' }}</p><span>{{ $isOverdue ? 'Overdue · '.$notice->follow_up_at->diffForHumans() : ($isToday ? 'Due today' : 'Advance reminder · due tomorrow') }} at {{ $notice->follow_up_at->format('g:i A') }}</span></span>
                    </a>
                @empty
                    <div class="notice-empty">You have no overdue or upcoming follow-ups.</div>
                @endforelse
            </div>
        </header>

        <div class="content">
            @if($view === 'dashboard')
                <section class="stats" aria-label="Lead summary">
                    <a class="stat" href="{{ route('crm.dashboard', ['view' => 'leads']) }}"><span class="stat-top"><span class="stat-icon">◫</span></span><strong>{{ $stats['total'] }}</strong><span>Total leads</span></a>
                    <a class="stat" href="{{ route('crm.dashboard', ['view' => 'leads', 'status' => 'new']) }}"><span class="stat-top"><span class="stat-icon">＋</span></span><strong>{{ $stats['new'] }}</strong><span>New leads</span></a>
                    <a class="stat hot" href="{{ route('crm.dashboard', ['view' => 'leads', 'status' => 'interested']) }}"><span class="stat-top"><span class="stat-icon">↗</span></span><strong>{{ $stats['interested'] }}</strong><span>Interested</span></a>
                    <a class="stat" href="{{ route('crm.dashboard', ['view' => 'leads', 'status' => 'follow_up']) }}"><span class="stat-top"><span class="stat-icon">◷</span></span><strong>{{ $stats['follow_up'] }}</strong><span>In follow-up</span></a>
                    <a class="stat hot" href="{{ route('crm.dashboard', ['view' => 'students']) }}"><span class="stat-top"><span class="stat-icon">◇</span></span><strong>{{ $stats['converted'] }}</strong><span>Enrolled</span></a>
                    <a class="stat danger" href="{{ route('crm.dashboard', ['view' => 'followups']) }}"><span class="stat-top"><span class="stat-icon">!</span></span><strong>{{ $stats['overdue'] }}</strong><span>Overdue</span></a>
                </section>

                <div class="dashboard-insights" data-dashboard-insights>
                    <section class="dashboard-panel dashboard-map-panel">
                        <div class="dashboard-panel-head">
                            <div><span class="dashboard-kicker">Global demand</span><h2>Where students want to study</h2><p>Preferred destinations captured across visible leads.</p></div>
                            <a class="dashboard-panel-link" href="{{ route('crm.dashboard', ['view' => 'leads']) }}">View leads <span aria-hidden="true">&rarr;</span></a>
                        </div>
                        <div class="world-map-layout">
                            <div class="lead-world-map" data-lead-world-map data-map-points='@json($dashboard['mapPoints'])' aria-label="Interactive map of preferred study destinations">
                                <div class="dashboard-leaflet-map" data-leaflet-canvas><span class="map-loading">Loading geographic map&hellip;</span></div>
                                @if($dashboard['mapPoints']->isEmpty())
                                    <div class="map-empty"><span>&#9678;</span><strong>No mapped destinations yet</strong><small>Add a preferred country to a lead to populate the map.</small></div>
                                @endif
                            </div>
                            <div class="country-ranking">
                                <div class="ranking-head"><span>Top destinations</span><b>{{ $dashboard['countryBreakdown']->sum('total') }} specified</b></div>
                                @forelse($dashboard['countryBreakdown']->take(5) as $index => $country)
                                    <div class="country-row"><span class="country-rank">{{ $index + 1 }}</span><span class="country-name"><strong>{{ $country['label'] }}</strong><small>{{ $country['total'] }} lead{{ $country['total'] === 1 ? '' : 's' }}</small></span><span class="country-share">{{ $stats['total'] ? round(($country['total'] / $stats['total']) * 100) : 0 }}%</span></div>
                                @empty
                                    <div class="dashboard-empty-small">Country interest has not been added yet.</div>
                                @endforelse
                            </div>
                        </div>
                    </section>

                    <section class="dashboard-panel dashboard-pipeline-panel">
                        <div class="dashboard-panel-head"><div><span class="dashboard-kicker">Pipeline health</span><h2>Lead movement</h2><p>Current status mix and six-month enquiry volume.</p></div></div>
                        <div class="dashboard-metrics">
                            <div><span>Conversion</span><strong>{{ $dashboard['conversionRate'] }}%</strong><small>Lead to student</small></div>
                            <div><span>Due today</span><strong>{{ $dashboard['dueToday'] }}</strong><small>Open follow-ups</small></div>
                            <div><span>Unassigned</span><strong>{{ $dashboard['unassigned'] }}</strong><small>Need an owner</small></div>
                        </div>
                        <div class="pipeline-breakdown">
                            @forelse($dashboard['statusBreakdown']->take(6) as $status)
                                <a class="pipeline-breakdown-row" href="{{ route('crm.dashboard', ['view' => 'leads', 'status' => $status['key']]) }}"><span class="pipeline-label"><i class="status-dot status-dot-{{ $status['key'] }}"></i>{{ $status['label'] }}</span><span class="pipeline-track"><i style="width:{{ max(3, $status['percentage']) }}%"></i></span><b>{{ $status['total'] }}</b></a>
                            @empty
                                <div class="dashboard-empty-small">Pipeline data will appear after the first lead is added.</div>
                            @endforelse
                        </div>
                        <div class="monthly-volume">
                            <div class="monthly-volume-head"><span>New enquiries</span><b>Last 6 months</b></div>
                            <div class="monthly-bars">
                                @foreach($dashboard['monthlyLeads'] as $month)
                                    <div class="monthly-bar"><span><i style="height:{{ $month['percentage'] }}%"></i></span><strong>{{ $month['total'] }}</strong><small>{{ $month['label'] }}</small></div>
                                @endforeach
                            </div>
                        </div>
                    </section>

                    <section class="dashboard-panel dashboard-source-panel">
                        <div class="dashboard-panel-head"><div><span class="dashboard-kicker">Acquisition</span><h2>Lead sources</h2><p>Channels bringing enquiries into the CRM.</p></div></div>
                        <div class="source-list">
                            @forelse($dashboard['sourceBreakdown'] as $source)
                                <div class="source-row"><span class="source-icon">{{ mb_strtoupper(mb_substr($source['label'], 0, 1)) }}</span><span class="source-main"><span><strong>{{ $source['label'] }}</strong><b>{{ $source['total'] }}</b></span><i><em style="width:{{ max(3, $source['percentage']) }}%"></em></i></span></div>
                            @empty
                                <div class="dashboard-empty-small">Lead sources have not been recorded yet.</div>
                            @endforelse
                        </div>
                    </section>

                    <section class="dashboard-panel dashboard-action-panel">
                        <div class="dashboard-panel-head"><div><span class="dashboard-kicker">Action queue</span><h2>Follow-ups requiring attention</h2><p>Oldest incomplete conversations appear first.</p></div><a class="dashboard-panel-link" href="{{ route('crm.dashboard', ['view' => 'followups']) }}">Open planner <span aria-hidden="true">&rarr;</span></a></div>
                        <div class="dashboard-list">
                            @forelse($dashboard['actionQueue'] as $followUp)
                                @php $queueOverdue = $followUp->follow_up_at->isPast(); @endphp
                                <a class="dashboard-list-row" href="{{ route('crm.dashboard', ['view' => 'followups', 'lead' => $followUp->id]) }}"><span class="dashboard-list-avatar">{{ $initials($followUp->name) }}</span><span class="dashboard-list-main"><strong>{{ $followUp->name }}</strong><small>{{ $followUp->assignee?->name ?? 'Unassigned' }} &middot; {{ $followUp->lead_number }}</small></span><span class="queue-time {{ $queueOverdue ? 'overdue' : '' }}">{{ $queueOverdue ? 'Overdue' : $followUp->follow_up_at->format('d M') }}<small>{{ $followUp->follow_up_at->format('g:i A') }}</small></span></a>
                            @empty
                                <div class="dashboard-empty"><span>&#10003;</span><strong>Action queue is clear</strong><p>No incomplete follow-ups are scheduled.</p></div>
                            @endforelse
                        </div>
                    </section>

                    <section class="dashboard-panel dashboard-recent-panel">
                        <div class="dashboard-panel-head"><div><span class="dashboard-kicker">Latest activity</span><h2>Recent enquiries</h2><p>The newest leads added to your visible pipeline.</p></div><a class="dashboard-panel-link" href="{{ route('crm.dashboard', ['view' => 'leads']) }}">All leads <span aria-hidden="true">&rarr;</span></a></div>
                        <div class="dashboard-list">
                            @forelse($dashboard['recentLeads'] as $recentLead)
                                <a class="dashboard-list-row" href="{{ route('crm.dashboard', ['view' => 'leads', 'lead' => $recentLead->id]) }}"><span class="dashboard-list-avatar">{{ $initials($recentLead->name) }}</span><span class="dashboard-list-main"><strong>{{ $recentLead->name }}</strong><small>{{ $recentLead->course_interest ?: ($recentLead->country_interest ?: 'Interest not recorded') }}</small></span><span class="recent-meta"><span class="badge status-{{ $recentLead->status }}">{{ $statuses[$recentLead->status] ?? ucfirst($recentLead->status) }}</span><small>{{ $recentLead->created_at->diffForHumans(null, true) }}</small></span></a>
                            @empty
                                <div class="dashboard-empty"><span>&#9671;</span><strong>No enquiries yet</strong><p>New leads will appear here as they arrive.</p></div>
                            @endforelse
                        </div>
                    </section>
                </div>
            @elseif($view === 'enrollments')
                @include('crm.partials.enrollments')
            @elseif($view === 'shortlisting')
                @include('crm.partials.shortlisting')
            @elseif($view === 'mock-invites')
                @include('crm.partials.mock-invites')
            @elseif($view === 'subscriptions')
                @include('crm.partials.subscribers')
            @elseif($view === 'audit' && $auditLogs)
                <section class="workspace audit-workspace">
                    <div class="workspace-head">
                        <div class="workspace-title"><h2>Audit log</h2><p>{{ number_format($auditLogs->total()) }} recorded action{{ $auditLogs->total() === 1 ? '' : 's' }}</p></div>
                        <span class="audit-private-label">Super admin only</span>
                    </div>
                    <form class="filters audit-filters" method="get" action="{{ route('crm.dashboard') }}" data-crm-filter-form>
                        <input type="hidden" name="view" value="audit">
                        <div class="search-wrap"><input class="control" type="search" name="audit_search" value="{{ request('audit_search') }}" placeholder="Search user, action or record"></div>
                        <select class="control" name="audit_event"><option value="">All actions</option>@foreach($auditEvents as $key=>$label)<option value="{{ $key }}" @selected(request('audit_event')===$key)>{{ $label }}</option>@endforeach</select>
                        <select class="control" name="audit_user"><option value="">All users</option>@foreach($team as $member)<option value="{{ $member->id }}" @selected((string)request('audit_user')===(string)$member->id)>{{ $member->name }}</option>@endforeach</select>
                    </form>

                    @if($auditLogs->count())
                        <div class="audit-list">
                            @foreach($auditLogs as $log)
                                <article class="audit-entry">
                                    <div class="audit-entry-main">
                                        <span class="audit-avatar">{{ $initials($log->actor?->name) }}</span>
                                        <span class="audit-actor"><strong>{{ $log->actor?->name ?? 'Deleted user' }}</strong><small>{{ $log->actor?->isSuperAdmin() ? 'Super admin' : ($log->actor ? 'Counsellor' : 'Account removed') }}</small></span>
                                        <span class="audit-action"><span class="audit-event">{{ $auditEvents[$log->event] ?? str($log->event)->replace('_', ' ')->title() }}</span><strong>{{ $log->description }}</strong>@if($log->subject_label)<small>@if($log->lead)<a href="{{ route('crm.dashboard', ['view' => 'leads', 'lead' => $log->lead->id]) }}">{{ $log->subject_label }}</a>@else{{ $log->subject_label }}@endif</small>@endif</span>
                                        <span class="audit-meta"><strong>{{ $log->created_at->format('d M Y') }}</strong><small>{{ $log->created_at->format('g:i:s A') }}</small><small>{{ $log->ip_address ?: 'IP unavailable' }}</small></span>
                                    </div>
                                    @if($log->changes)
                                        <details class="audit-details">
                                            <summary>View recorded details</summary>
                                            <div class="audit-change-groups">
                                                @foreach($log->changes as $group => $values)
                                                    <div class="audit-change-group">
                                                        <strong>{{ str((string)$group)->replace('_', ' ')->title() }}</strong>
                                                        @if(is_array($values))
                                                            @foreach($values as $field => $value)
                                                                <span><b>{{ str((string)$field)->replace('_', ' ')->title() }}</b><em>{{ $auditValue($value) }}</em></span>
                                                            @endforeach
                                                        @else
                                                            <span><em>{{ $auditValue($values) }}</em></span>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </details>
                                    @endif
                                </article>
                            @endforeach
                        </div>
                        @if($auditLogs->hasPages())<div class="pagination-wrap">{{ $auditLogs->onEachSide(1)->links() }}</div>@endif
                    @else
                        <div class="empty"><span class="empty-icon">≣</span><h3>No audit entries found</h3><p>Try changing the filters. New CRM actions will be recorded here.</p></div>
                    @endif
                </section>
            @else
            <section class="workspace">
                <form @class(['filters', 'lead-classification-filters' => $isListView]) method="get" action="{{ route('crm.dashboard') }}" data-crm-filter-form>
                    <input type="hidden" name="view" value="{{ $view }}">
                    @if($view === 'followups')<input type="hidden" name="layout" value="{{ $followUpLayout }}">@endif
                    @if($view === 'followups' && $followUpLayout === 'calendar')<input type="hidden" name="month" value="{{ request('month', now()->format('Y-m')) }}">@endif
                    <div class="search-wrap"><input class="control" type="search" name="search" value="{{ request('search') }}" placeholder="Search name, phone, email or lead ID"></div>
                    <select class="control" name="status"><option value="">All statuses</option>@foreach($statuses as $key=>$label)<option value="{{ $key }}" @selected(request('status')===$key)>{{ $label }}</option>@endforeach</select>
                    <select class="control" name="priority"><option value="">All priorities</option>@foreach($priorities as $key=>$label)<option value="{{ $key }}" @selected(request('priority')===$key)>{{ $label }}</option>@endforeach</select>
                    <select class="control" name="lead_origin"><option value="">All lead origins</option>@foreach($leadOrigins as $key=>$label)<option value="{{ $key }}" @selected(request('lead_origin')===$key)>{{ $label }}</option>@endforeach</select>
                    <select class="control" name="lead_type"><option value="">All enquiry types</option>@foreach($leadTypes as $key=>$label)<option value="{{ $key }}" @selected(request('lead_type')===$key)>{{ $label }}</option>@endforeach</select>
                    <select class="control" name="source"><option value="">All specific sources</option>@foreach($leadSources as $source)<option value="{{ $source }}" @selected(request('source')===$source)>{{ $source }}</option>@endforeach</select>
                    <select class="control" name="category"><option value="">All study categories</option>@foreach($categories as $key=>$label)<option value="{{ $key }}" @selected(request('category')===$key)>{{ $label }}</option>@endforeach</select>
                    @if($view === 'students')<select class="control" name="student_stage"><option value="">All journey stages</option>@foreach($studentStages as $key=>$label)<option value="{{ $key }}" @selected(request('student_stage')===$key)>{{ $label }}</option>@endforeach</select>@endif
                    <label class="followup-date-filter"><span>Next follow-up</span><input class="control" type="date" name="follow_up_date" value="{{ request('follow_up_date') }}"></label>
                    @if($crmUser->isSuperAdmin())<select class="control" name="assigned_to"><option value="">All counsellors</option>@foreach($counsellors as $person)<option value="{{ $person->id }}" @selected((string)request('assigned_to')===(string)$person->id)>{{ $person->name }}</option>@endforeach</select>@else<span></span>@endif
                </form>

                @if($view === 'followups' && $followUpLayout === 'calendar' && $followUpCalendar)
                    @php
                        $calendarMonth = $followUpCalendar['month'];
                        $previousMonthUrl = route('crm.dashboard', array_merge($calendarQuery, ['view' => 'followups', 'month' => $followUpCalendar['previous']]));
                        $nextMonthUrl = route('crm.dashboard', array_merge($calendarQuery, ['view' => 'followups', 'month' => $followUpCalendar['next']]));
                        $todayMonthUrl = route('crm.dashboard', array_merge($calendarQuery, ['view' => 'followups', 'month' => now()->format('Y-m')]));
                    @endphp
                    <section class="followup-calendar" aria-label="Follow-up calendar for {{ $calendarMonth->format('F Y') }}">
                        <div class="followup-calendar-head">
                            <div>
                                <span class="followup-calendar-kicker">Monthly schedule</span>
                                <h3>{{ $calendarMonth->format('F Y') }}</h3>
                                <p>{{ $followUpCalendar['total'] }} scheduled · {{ $followUpCalendar['dueToday'] }} due today · {{ $followUpCalendar['overdue'] }} overdue</p>
                            </div>
                            <div class="followup-calendar-actions">
                                <a href="{{ $previousMonthUrl }}" aria-label="Previous month">&larr;</a>
                                <a class="followup-calendar-today" href="{{ $todayMonthUrl }}">Today</a>
                                <a href="{{ $nextMonthUrl }}" aria-label="Next month">&rarr;</a>
                            </div>
                        </div>
                        <div class="followup-calendar-scroll">
                            <div class="followup-calendar-grid">
                                @foreach(['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $weekday)
                                    <div class="followup-calendar-weekday">{{ $weekday }}</div>
                                @endforeach
                                @foreach($followUpCalendar['weeks'] as $week)
                                    @foreach($week as $day)
                                        <div @class(['followup-calendar-day', 'is-muted' => !$day['inMonth'], 'is-today' => $day['date']->isToday(), 'has-events' => $day['events']->isNotEmpty()])>
                                            <div class="followup-calendar-date">
                                                <span>{{ $day['date']->format('j') }}</span>
                                                @if($day['date']->isToday())<small>Today</small>@endif
                                            </div>
                                            <div class="followup-calendar-events">
                                                @foreach($day['events']->take(3) as $calendarLead)
                                                    @php
                                                        $calendarLeadUrl = request()->fullUrlWithQuery(['lead' => $calendarLead->id]);
                                                        $calendarOverdue = $calendarLead->follow_up_at->isPast();
                                                    @endphp
                                                    <a @class(['followup-calendar-event', 'is-overdue' => $calendarOverdue, 'priority-high' => $calendarLead->priority === 'high']) href="{{ $calendarLeadUrl }}" title="Open {{ $calendarLead->name }} to view and edit lead details">
                                                        <span class="followup-calendar-event-top"><b>{{ $calendarLead->follow_up_at->format('g:i A') }}</b><em>{{ $priorities[$calendarLead->priority] ?? ucfirst($calendarLead->priority) }}</em></span>
                                                        <strong>{{ $calendarLead->name }}</strong>
                                                        <small class="followup-calendar-event-detail">{{ $calendarLead->phone }} · {{ $calendarLead->course_interest ?: ($calendarLead->country_interest ?: ($categories[$calendarLead->category] ?? 'General enquiry')) }}</small>
                                                        <small class="followup-calendar-event-owner">{{ $calendarLead->assignee?->name ?? 'Unassigned' }}<span>View &amp; edit &rarr;</span></small>
                                                    </a>
                                                @endforeach
                                                @if($day['events']->count() > 3)
                                                    <span class="followup-calendar-more">+{{ $day['events']->count() - 3 }} more</span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                @endforeach
                            </div>
                        </div>
                        <div class="followup-calendar-legend"><span><i class="is-scheduled"></i>Scheduled</span><span><i class="is-priority"></i>High priority</span><span><i class="is-overdue"></i>Overdue</span></div>
                    </section>
                @endif

                @if($view !== 'followups' || $followUpLayout === 'table')
                @if($leads->count())
                    <div class="table-wrap">
                        <table>
                        @if($view === 'leads')
                            <thead><tr><th>Serial No</th><th>Lead Received</th><th>Student Name</th><th>Phone number</th><th>Contacted</th><th>Counselling Done</th><th>Shortlisting sent</th><th>Enrolled</th><th>Remarks by One Degree</th><th></th></tr></thead>
                            <tbody>
                            @foreach($leads as $lead)
                                @php
                                    $openUrl = request()->fullUrlWithQuery(['lead' => $lead->id]);
                                    $isContacted = $lead->last_contacted_at || in_array($lead->status, ['call_back','follow_up','interested','not_interested','converted'], true);
                                    $isCounselled = $lead->follow_up_completed_at || $lead->is_student || in_array($lead->status, ['interested','converted'], true);
                                    $isEnrolled = $lead->is_student || $lead->status === 'converted';
                                    $latestActivity = $lead->latestActivity;
                                @endphp
                                <tr data-crm-href="{{ $openUrl }}" tabindex="0">
                                    <td>{{ $leads->firstItem() + $loop->index }}</td>
                                    <td>{{ $lead->created_at->format('d M Y') }}<span class="subtext">{{ $lead->created_at->format('g:i A') }}</span></td>
                                    <td><div class="lead-primary"><span class="lead-avatar">{{ $initials($lead->name) }}</span><span class="lead-name"><strong>{{ $lead->name }}</strong><span>{{ $lead->lead_number }}</span></span></div></td>
                                    <td>{{ $lead->phone ? '+91 '.$lead->phone : '—' }}</td>
                                    <td>{!! $milestone((bool) $isContacted, $lead->last_contacted_at) !!}</td>
                                    <td>{!! $milestone((bool) $isCounselled, $lead->follow_up_completed_at) !!}</td>
                                    <td><span class="lead-ms lead-ms-no" title="Tracked manually — no CRM field for this yet">—</span></td>
                                    <td>{!! $milestone((bool) $isEnrolled, $lead->enrollment_date) !!}</td>
                                    <td @class(['lead-remark', 'has-remark-pop' => $latestActivity])@if($latestActivity) data-remark-full="{{ $latestActivity->body }}" data-remark-time="{{ $latestActivity->created_at->format('d M Y, g:i A') }}"@endif>@if($latestActivity)<span class="lead-remark-text">{{ \Illuminate\Support\Str::limit($latestActivity->body, 90) }}</span><span class="subtext">{{ $latestActivity->created_at->diffForHumans() }}</span>@else<span class="subtext">No activity yet</span>@endif</td>
                                    <td><a class="row-open" href="{{ $openUrl }}" aria-label="Open {{ $lead->name }}">→</a></td>
                                </tr>
                            @endforeach
                            </tbody>
                        @else
                            <thead><tr><th>Lead</th><th>Type &amp; source</th><th>{{ $view === 'students' ? 'Journey stage' : 'Status' }}</th><th>Category</th>@if($crmUser->isSuperAdmin())<th>Owner</th>@endif<th>Follow-up</th><th>Updated</th><th></th></tr></thead>
                            <tbody>
                            @foreach($leads as $lead)
                                @php
                                    $openUrl = request()->fullUrlWithQuery(['lead' => $lead->id]);
                                    $followClass = $lead->follow_up_at && !$lead->follow_up_completed_at ? ($lead->follow_up_at->isPast() ? 'due' : ($lead->follow_up_at->isTomorrow() ? 'soon' : '')) : '';
                                @endphp
                                <tr data-crm-href="{{ $openUrl }}" tabindex="0">
                                    <td><div class="lead-primary"><span class="lead-avatar">{{ $initials($lead->name) }}</span><span class="lead-name"><strong>{{ $lead->name }}</strong><span>{{ $lead->lead_number }}@if($lead->phone) · +91 {{ $lead->phone }}@elseif($lead->email) · {{ $lead->email }}@endif</span></span></div></td>
                                    <td><span class="lead-type-pill">{{ $leadTypes[$lead->lead_type] ?? 'General enquiry' }}</span><span class="subtext">{{ $leadOrigins[$lead->lead_origin] ?? ucfirst($lead->lead_origin) }} · {{ $lead->source ?: 'Source not set' }}</span></td>
                                    @if($view === 'students')
                                        @php $alertStage = in_array($lead->student_stage, ['visa_rejected', 'dropped'], true); @endphp
                                        <td><span class="badge {{ $alertStage ? 'status-dropped' : 'status-converted' }}">{{ $studentStages[$lead->student_stage] ?? 'Journey not started' }}</span><span class="priority {{ $lead->priority }}">{{ $priorities[$lead->priority] ?? $lead->priority }}</span></td>
                                    @else
                                        <td><span class="badge status-{{ $lead->status }}">{{ $lead->status === 'converted' && !$lead->is_student ? 'Conversion incomplete' : ($statuses[$lead->status] ?? ucfirst($lead->status)) }}</span><span class="priority {{ $lead->priority }}">{{ $priorities[$lead->priority] ?? $lead->priority }}</span></td>
                                    @endif
                                    <td>{{ $categories[$lead->category] ?? '—' }}<span class="subtext">{{ $lead->course_interest ?: ($lead->country_interest ?: $lead->city) }}</span></td>
                                    @if($crmUser->isSuperAdmin())<td>@if($lead->assignee)<span class="owner"><span class="avatar">{{ $initials($lead->assignee->name) }}</span>{{ $lead->assignee->name }}</span>@else<span class="subtext">Unassigned</span>@endif</td>@endif
                                    <td><span class="follow-date {{ $followClass }}">{{ $lead->follow_up_at ? $lead->follow_up_at->format('d M, g:i A') : 'Not scheduled' }}</span>@if($lead->follow_up_completed_at)<span class="subtext">Completed</span>@endif</td>
                                    <td>{{ $lead->updated_at->diffForHumans(null, true) }}<span class="subtext">{{ $lead->activities_count }} activities</span></td>
                                    <td><a class="row-open" href="{{ $openUrl }}" aria-label="Open {{ $lead->name }}">→</a></td>
                                </tr>
                            @endforeach
                            </tbody>
                        @endif
                        </table>
                    </div>
                    @if($leads->hasPages())<div class="pagination-wrap">{{ $leads->onEachSide(1)->links() }}</div>@endif
                @else
                    <div class="empty"><span class="empty-icon">⌕</span><h3>No leads found</h3><p>Adjust the filters or add your first lead to this view.</p></div>
                @endif
                @endif
            </section>
            @endif
        </div>
    </main>
</div>

<div class="overlay {{ $leadErrors->any() ? 'open' : '' }}" id="leadModal" aria-hidden="{{ $leadErrors->any() ? 'false' : 'true' }}" @if($leadErrors->any()) data-open-on-load @endif>
    <div class="modal">
        <div class="modal-head"><div><h2>Add a new lead</h2><p>Capture the enquiry and assign the next action.</p></div><button class="close-btn" data-modal-close>×</button></div>
        <form method="post" action="{{ route('crm.leads.store') }}">@csrf
            <div class="modal-body"><div class="form-grid">
                <div @class(['field', 'has-error' => $leadErrors->has('name')])><label for="lead_name">Full name <span class="required">*</span></label><input id="lead_name" name="name" value="{{ old('name') }}" @if($leadErrors->has('name')) aria-invalid="true" aria-describedby="lead_name_error" @endif required>@error('name','leadCreate')<span class="field-error" id="lead_name_error">{{ $message }}</span>@enderror</div>
                <div @class(['field', 'has-error' => $leadErrors->has('phone')])><label for="lead_phone">Mobile number <span class="required">*</span></label><input id="lead_phone" name="phone" value="{{ old('phone') }}" inputmode="tel" placeholder="9876543210" @if($leadErrors->has('phone')) aria-invalid="true" aria-describedby="lead_phone_error" @endif required>@error('phone','leadCreate')<span class="field-error" id="lead_phone_error">{{ $message }}</span>@enderror</div>
                <div @class(['field', 'has-error' => $leadErrors->has('email')])><label for="lead_email">Email</label><input id="lead_email" name="email" type="email" value="{{ old('email') }}" @if($leadErrors->has('email')) aria-invalid="true" aria-describedby="lead_email_error" @endif>@error('email','leadCreate')<span class="field-error" id="lead_email_error">{{ $message }}</span>@enderror</div>
                <div @class(['field', 'has-error' => $leadErrors->has('city')])><label for="lead_city">City</label><input id="lead_city" name="city" value="{{ old('city') }}" @if($leadErrors->has('city')) aria-invalid="true" aria-describedby="lead_city_error" @endif>@error('city','leadCreate')<span class="field-error" id="lead_city_error">{{ $message }}</span>@enderror</div>
                <div @class(['field', 'has-error' => $leadErrors->has('course_interest')])><label for="lead_course">Course / field interest</label><input id="lead_course" name="course_interest" value="{{ old('course_interest') }}" placeholder="e.g. MS Computer Science" @if($leadErrors->has('course_interest')) aria-invalid="true" aria-describedby="lead_course_error" @endif>@error('course_interest','leadCreate')<span class="field-error" id="lead_course_error">{{ $message }}</span>@enderror</div>
                <div @class(['field', 'has-error' => $leadErrors->has('country_interest')])><label for="lead_country">Preferred country</label><input id="lead_country" name="country_interest" value="{{ old('country_interest') }}" @if($leadErrors->has('country_interest')) aria-invalid="true" aria-describedby="lead_country_error" @endif>@error('country_interest','leadCreate')<span class="field-error" id="lead_country_error">{{ $message }}</span>@enderror</div>
                <div @class(['field', 'has-error' => $leadErrors->has('category')])><label for="lead_category">Category</label><select id="lead_category" name="category" @if($leadErrors->has('category')) aria-invalid="true" aria-describedby="lead_category_error" @endif><option value="">Not set</option>@foreach($categories as $key=>$label)<option value="{{ $key }}" @selected(old('category')===$key)>{{ $label }}</option>@endforeach</select>@error('category','leadCreate')<span class="field-error" id="lead_category_error">{{ $message }}</span>@enderror</div>
                <div @class(['field', 'has-error' => $leadErrors->has('lead_type')])><label for="lead_type">Enquiry type</label><select id="lead_type" name="lead_type">@foreach($leadTypes as $key=>$label)<option value="{{ $key }}" @selected(old('lead_type','general')===$key)>{{ $label }}</option>@endforeach</select>@error('lead_type','leadCreate')<span class="field-error">{{ $message }}</span>@enderror</div>
                <div @class(['field', 'has-error' => $leadErrors->has('priority')])><label for="lead_priority">Priority</label><select id="lead_priority" name="priority" @if($leadErrors->has('priority')) aria-invalid="true" aria-describedby="lead_priority_error" @endif>@foreach($priorities as $key=>$label)<option value="{{ $key }}" @selected(old('priority','medium')===$key)>{{ $label }}</option>@endforeach</select>@error('priority','leadCreate')<span class="field-error" id="lead_priority_error">{{ $message }}</span>@enderror</div>
                <div @class(['field', 'has-error' => $leadErrors->has('source')])><label for="lead_source">Lead source</label><input id="lead_source" name="source" value="{{ old('source') }}" placeholder="Website, Instagram, referral…" @if($leadErrors->has('source')) aria-invalid="true" aria-describedby="lead_source_error" @endif>@error('source','leadCreate')<span class="field-error" id="lead_source_error">{{ $message }}</span>@enderror</div>
                <div @class(['field', 'has-error' => $leadErrors->has('status')])><label for="lead_status">Pipeline status</label><select id="lead_status" name="status" @if($leadErrors->has('status')) aria-invalid="true" aria-describedby="lead_status_error" @endif>@foreach($pipelineStatuses as $key=>$label)<option value="{{ $key }}" @selected(old('status','new')===$key)>{{ $label }}</option>@endforeach</select><span class="field-help">Enrollment is completed later from the Student tab.</span>@error('status','leadCreate')<span class="field-error" id="lead_status_error">{{ $message }}</span>@enderror</div>
                @if($crmUser->isSuperAdmin())<div @class(['field', 'has-error' => $leadErrors->has('assigned_to')])><label for="lead_assigned">Assign to counsellor</label><select id="lead_assigned" name="assigned_to" @if($leadErrors->has('assigned_to')) aria-invalid="true" aria-describedby="lead_assigned_error" @endif><option value="">Unassigned</option>@foreach($counsellors as $person)<option value="{{ $person->id }}" @selected((string)old('assigned_to') === (string)$person->id)>{{ $person->name }}</option>@endforeach</select>@error('assigned_to','leadCreate')<span class="field-error" id="lead_assigned_error">{{ $message }}</span>@enderror</div>@endif
                <div @class(['field', 'has-error' => $leadErrors->has('follow_up_at')])><label for="lead_followup">First follow-up</label><input id="lead_followup" type="datetime-local" name="follow_up_at" value="{{ old('follow_up_at') }}" @if($leadErrors->has('follow_up_at')) aria-invalid="true" aria-describedby="lead_followup_error" @endif>@error('follow_up_at','leadCreate')<span class="field-error" id="lead_followup_error">{{ $message }}</span>@enderror</div>
            </div></div>
            <div class="modal-foot"><button class="btn btn-outline" type="button" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit">Create lead</button></div>
        </form>
    </div>
</div>

<div class="overlay" id="importModal" aria-hidden="true">
    <div class="modal sm">
        <div class="modal-head"><div><h2>Import leads</h2><p>Upload an Excel or UTF-8 CSV file, up to 5 MB.</p></div><button class="close-btn" data-modal-close>×</button></div>
        <form method="post" action="{{ route('crm.leads.import') }}" enctype="multipart/form-data" data-import-form>@csrf
            <div class="modal-body">
                <label class="dropzone"><input type="file" name="file" accept=".csv,.xlsx,.xls,text/csv" hidden required><strong>Choose Excel or CSV file</strong><span>Duplicates are safely skipped using the phone number.</span></label>
                <div class="csv-template">name, phone, email, city, course, country, category, priority, source, lead type</div>
                @if($crmUser->isSuperAdmin())<div class="field" style="margin-top:15px"><label>Assign imported leads to</label><select name="assigned_to"><option value="">Leave unassigned</option>@foreach($counsellors as $person)<option value="{{ $person->id }}">{{ $person->name }}</option>@endforeach</select></div>@endif
            </div>
            <div class="modal-foot"><button class="btn btn-outline" type="button" data-modal-close>Cancel</button><button class="btn btn-primary" type="submit">Import leads</button></div>
        </form>
    </div>
</div>

@if($crmUser->isSuperAdmin())
<div class="overlay" id="teamModal" aria-hidden="true">
    <div class="modal team-management-modal">
        <div class="modal-head"><div><h2>Team management</h2><p>Create counsellors or super admins and control their CRM access.</p></div><button class="close-btn" data-modal-close>×</button></div>
        <div class="modal-body">
            @php
                $superAdmins = $team->filter->isSuperAdmin()->values();
                $counsellors = $team->reject->isSuperAdmin()->values();
            @endphp
            <div class="team-toolbar" data-team-toolbar>
                <label class="team-search">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="search" placeholder="Search by name, email or mobile…" data-team-search-input aria-label="Search team members" autocomplete="off">
                </label>
                <div class="team-filter" role="group" aria-label="Filter team members">
                    <button type="button" class="team-filter-chip is-active" data-team-filter="all">All <span class="chip-count">{{ $team->count() }}</span></button>
                    <button type="button" class="team-filter-chip" data-team-filter="super-admin">Super admins <span class="chip-count">{{ $superAdmins->count() }}</span></button>
                    <button type="button" class="team-filter-chip" data-team-filter="counsellor">Counsellors <span class="chip-count">{{ $counsellors->count() }}</span></button>
                </div>
            </div>
            <div class="team-groups" data-team-scroll>
                <section class="team-group" data-role="super-admin" data-team-group>
                    <header class="team-group-head">
                        <span class="team-group-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 5 6v5c0 4.7 2.8 8.1 7 10 4.2-1.9 7-5.3 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4"/></svg></span>
                        <span class="team-group-heading"><strong>Super admins</strong><small>Full CRM, team &amp; audit-log access</small></span>
                        <span class="team-group-count">{{ $superAdmins->count() }}</span>
                    </header>
                    <div class="team-list">
                        @forelse($superAdmins as $member)
                            @include('crm.partials.team-member', ['member' => $member])
                        @empty
                            <p class="team-group-empty">No super admins yet.</p>
                        @endforelse
                    </div>
                </section>
                <section class="team-group" data-role="counsellor" data-team-group>
                    <header class="team-group-head">
                        <span class="team-group-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5.5 20c.6-4 2.7-6 6.5-6s5.9 2 6.5 6"/></svg></span>
                        <span class="team-group-heading"><strong>Counsellors</strong><small>Work only their assigned leads</small></span>
                        <span class="team-group-count">{{ $counsellors->count() }}</span>
                    </header>
                    <div class="team-list">
                        @forelse($counsellors as $member)
                            @include('crm.partials.team-member', ['member' => $member])
                        @empty
                            <p class="team-group-empty">No counsellors yet. Add one below.</p>
                        @endforelse
                    </div>
                </section>
                <p class="team-no-results" data-team-no-results hidden>No team members match your search.</p>
            </div>
            <form class="team-create-form" method="post" action="{{ route('crm.team.store') }}" data-ajax-preserve-modal="teamModal">@csrf
                <div class="team-create-heading"><h3>Add a team member</h3><p>They can sign in with the mobile number or email address below.</p></div>
                <div class="form-grid">
                    <div class="field"><label>Name</label><input name="name" required></div>
                    <div class="field"><label>Mobile number</label><input name="phone" inputmode="tel" required></div>
                    <div class="field full"><label>Email address</label><input type="email" name="email" placeholder="name@example.com" required></div>
                    <div class="field full"><label>Access level</label><select name="role" required><option value="counsellor">Counsellor — assigned leads only</option><option value="super_admin">Super admin — complete CRM access</option></select><div class="team-role-guide"><span class="is-counsellor"><b>● Counsellor</b><small>Manages only leads assigned to them</small></span><span class="is-super-admin"><b>◆ Super admin</b><small>Full team, lead and audit-log access</small></span></div></div>
                </div>
                <button class="btn btn-primary btn-block" type="submit">Create team account</button>
            </form>
        </div>
    </div>
</div>
@endif

@if($selectedLead)
@php
    $statusDetails = [
        'new' => ['symbol' => '✦', 'copy' => 'A fresh enquiry waiting for first contact.', 'phase' => 1],
        'not_answered' => ['symbol' => '⌁', 'copy' => 'Contact was attempted. Plan the next retry.', 'phase' => 2],
        'call_back' => ['symbol' => '↗', 'copy' => 'The lead requested another conversation.', 'phase' => 2],
        'follow_up' => ['symbol' => '◷', 'copy' => 'The conversation is active and needs a scheduled next step.', 'phase' => 2],
        'interested' => ['symbol' => '◆', 'copy' => 'The lead is qualified and showing clear intent.', 'phase' => 3],
        'not_interested' => ['symbol' => '—', 'copy' => 'The lead is not interested at this time.', 'phase' => 2],
        'converted' => ['symbol' => '✓', 'copy' => 'The lead has enrolled and moved into the student journey.', 'phase' => 4],
        'junk' => ['symbol' => '×', 'copy' => 'The enquiry is invalid or not actionable.', 'phase' => 1],
        'dropped' => ['symbol' => '↓', 'copy' => 'The opportunity was closed without enrollment.', 'phase' => 3],
    ];
    $conversionIncomplete = $selectedLead->status === 'converted' && !$selectedLead->is_student;
    $currentStatus = $conversionIncomplete
        ? ['symbol' => '!', 'copy' => 'This legacy status did not create a student journey. Use the Student tab to finish conversion, or choose another pipeline status.', 'phase' => 3]
        : ($statusDetails[$selectedLead->status] ?? ['symbol' => '•', 'copy' => 'Keep the lead information and next action up to date.', 'phase' => 1]);
    $closedStatuses = ['not_interested', 'junk', 'dropped'];
    $pipelineSteps = [1 => 'New', 2 => 'In conversation', 3 => 'Qualified', 4 => 'Enrolled'];
    $followUpTone = 'neutral';
    $followUpTitle = 'Schedule the next conversation';
    $followUpCopy = 'A dated follow-up keeps this lead visible to the assigned counsellor.';
    if ($selectedLead->follow_up_completed_at) {
        $followUpTone = 'done';
        $followUpTitle = 'Latest follow-up completed';
        $followUpCopy = 'Completed '.$selectedLead->follow_up_completed_at->diffForHumans().'. Add the next action when needed.';
    } elseif ($selectedLead->follow_up_at?->isPast()) {
        $followUpTone = 'overdue';
        $followUpTitle = 'Follow-up is overdue';
        $followUpCopy = 'Due '.$selectedLead->follow_up_at->format('d M Y, g:i A').'. Contact the lead or reschedule now.';
    } elseif ($selectedLead->follow_up_at?->isToday()) {
        $followUpTone = 'today';
        $followUpTitle = 'Follow-up is due today';
        $followUpCopy = 'Planned for '.$selectedLead->follow_up_at->format('g:i A').'.';
    } elseif ($selectedLead->follow_up_at) {
        $followUpTone = 'upcoming';
        $followUpTitle = 'Next follow-up planned';
        $followUpCopy = $selectedLead->follow_up_at->format('d M Y, g:i A').' · '.$selectedLead->follow_up_at->diffForHumans().'.';
    }
    $activityViews = [
        'comment' => ['symbol' => '✎', 'group' => 'conversation', 'label' => 'Comment'],
        'updated' => ['symbol' => '↻', 'group' => 'updates', 'label' => 'Lead updated'],
        'created' => ['symbol' => '✦', 'group' => 'system', 'label' => 'Lead created'],
        'imported' => ['symbol' => '⇧', 'group' => 'system', 'label' => 'Lead imported'],
        'follow_up_done' => ['symbol' => '✓', 'group' => 'milestones', 'label' => 'Follow-up completed'],
        'converted' => ['symbol' => '◇', 'group' => 'milestones', 'label' => 'Student enrolled'],
        'student_stage' => ['symbol' => '→', 'group' => 'milestones', 'label' => 'Journey advanced'],
    ];
    $journeyKeys = ['doc_pending', 'doc_complete', 'app_submitted', 'offer_received', 'deposit_paid', 'visa_in_process', 'visa_filed', 'visa_granted'];
    $stageGuidance = [
        'doc_pending' => 'Collect identity, academic and financial documents from the student.',
        'doc_complete' => 'Review the final shortlist and prepare institution applications.',
        'app_submitted' => 'Track institution responses and any additional document requests.',
        'offer_received' => 'Review offer conditions, deadlines and deposit requirements.',
        'deposit_paid' => 'Confirm payment receipt and begin the visa preparation checklist.',
        'visa_in_process' => 'Complete financial evidence, medicals and application documents.',
        'visa_filed' => 'Track the decision and keep the student ready for further requests.',
        'visa_granted' => 'Complete pre-departure guidance and final travel preparation.',
        'visa_rejected' => 'Review the refusal reason and decide whether to refile or close the journey.',
        'dropped' => 'Record the reason and close any outstanding tasks for the student.',
    ];
    $journeyPosition = array_search($selectedLead->student_stage, $journeyKeys, true);
    $journeyPosition = $journeyPosition === false ? -1 : $journeyPosition;
    $nextJourneyKey = $journeyPosition >= 0 && isset($journeyKeys[$journeyPosition + 1]) ? $journeyKeys[$journeyPosition + 1] : null;
    $journeyIsAlert = in_array($selectedLead->student_stage, ['visa_rejected', 'dropped'], true);
    $journeyIsComplete = !$journeyIsAlert && $journeyPosition === count($journeyKeys) - 1;
@endphp
<div class="drawer-overlay" id="leadDrawer">
    <aside class="drawer">
        <div class="drawer-head">
            <div class="drawer-lead"><span class="lead-avatar">{{ $initials($selectedLead->name) }}</span><div><span class="drawer-eyebrow">{{ $selectedLead->is_student ? 'Enrolled student' : 'Lead workspace' }}</span><h2>{{ $selectedLead->name }}</h2><p><span>{{ $selectedLead->lead_number }}</span>@if($selectedLead->phone)<span>+91 {{ $selectedLead->phone }}</span>@elseif($selectedLead->email)<span>{{ $selectedLead->email }}</span>@endif<span class="drawer-status-dot status-{{ $selectedLead->status }}">{{ $conversionIncomplete ? 'Conversion incomplete' : $statuses[$selectedLead->status] }}</span></p></div></div>
            <div class="drawer-head-actions"><button class="drawer-tool-btn" type="button" data-drawer-expand aria-label="Expand lead workspace to full screen" aria-pressed="false" title="Expand full screen"><svg class="expand-view-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M8 3H3v5M16 3h5v5M8 21H3v-5M16 21h5v-5"/></svg><svg class="restore-view-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M3 8h5V3M21 8h-5V3M3 16h5v5M21 16h-5v5"/></svg></button><a class="close-btn" href="{{ request()->fullUrlWithoutQuery('lead') }}" aria-label="Close lead workspace"><svg class="close-view-icon" viewBox="0 0 24 24" aria-hidden="true"><path d="M7 7l10 10M17 7 7 17"/></svg></a></div>
        </div>
        <div class="drawer-body">
            <div class="quick-actions">
                <a class="quick-action {{ $selectedLead->phone ? '' : 'is-disabled' }}" @if($selectedLead->phone) href="tel:+91{{ $selectedLead->phone }}" @endif><span class="quick-action-icon">☎</span><span><b>Call</b><small>{{ $selectedLead->phone ? 'Start conversation' : 'Phone not added' }}</small></span></a>
                <a class="quick-action {{ $selectedLead->phone ? '' : 'is-disabled' }}" @if($selectedLead->phone) target="_blank" rel="noopener" href="https://wa.me/91{{ $selectedLead->phone }}?text={{ urlencode('Hi '.$selectedLead->name.', this is '.$crmUser->name.' from One Degree Advisory.') }}" @endif><span class="quick-action-icon">◉</span><span><b>WhatsApp</b><small>{{ $selectedLead->phone ? 'Send a message' : 'Phone not added' }}</small></span></a>
                <a class="quick-action {{ $selectedLead->email ? '' : 'is-disabled' }}" @if($selectedLead->email) href="mailto:{{ $selectedLead->email }}" @endif><span class="quick-action-icon">✉</span><span><b>Email</b><small>{{ $selectedLead->email ? 'Write an email' : 'Email not added' }}</small></span></a>
            </div>
            <div class="tabs drawer-tabs"><button class="tab active" data-tab="details"><span class="tab-symbol">◫</span><span>Details<small>Profile & status</small></span></button><button class="tab" data-tab="timeline"><span class="tab-symbol">◷</span><span>Timeline<small><span data-timeline-count>{{ $selectedLead->activities->count() }}</span> activities</small></span></button><button class="tab" data-tab="student"><span class="tab-symbol">◇</span><span>Student<small>{{ $selectedLead->is_student ? ($studentStages[$selectedLead->student_stage] ?? 'Journey') : 'Not enrolled' }}</small></span></button></div>

            <div class="tab-panel active" data-panel="details">
                <section class="lead-snapshot {{ in_array($selectedLead->status, $closedStatuses, true) ? 'is-closed' : '' }}">
                    <div class="lead-orbit" style="--lead-progress:{{ $currentStatus['phase'] * 25 }}" aria-hidden="true"><svg viewBox="0 0 100 100"><circle class="orbit-track" cx="50" cy="50" r="42" pathLength="100"/><circle class="orbit-value" cx="50" cy="50" r="42" pathLength="100"/></svg><span>{{ $currentStatus['symbol'] }}</span></div>
                    <div class="lead-snapshot-copy"><span class="section-kicker">Current lead position</span><h3>{{ $conversionIncomplete ? 'Conversion incomplete' : $statuses[$selectedLead->status] }}</h3><p>{{ $currentStatus['copy'] }}</p><div class="snapshot-chips"><span class="priority-chip {{ $selectedLead->priority }}">{{ $priorities[$selectedLead->priority] }} priority</span><span>{{ $selectedLead->assignee?->name ?? 'Unassigned' }}</span><span>{{ $selectedLead->source ?: 'Source not set' }}</span></div></div>
                </section>

                <div class="mini-pipeline {{ in_array($selectedLead->status, $closedStatuses, true) ? 'has-closed-state' : '' }}" aria-label="Lead progress">
                    @foreach($pipelineSteps as $phase=>$phaseLabel)<div class="pipeline-step {{ $phase < $currentStatus['phase'] ? 'complete' : ($phase === $currentStatus['phase'] ? 'current' : '') }}"><span>{{ $phase < $currentStatus['phase'] ? '✓' : $phase }}</span><small>{{ $phaseLabel }}</small></div>@endforeach
                </div>

                <form method="post" action="{{ route('crm.leads.update',$selectedLead) }}" data-lead-details-form data-track-changes>@csrf @method('PUT')
                    <div class="drawer-card section-card">
                        <div class="section-heading"><span class="section-icon">↗</span><div><h3>Pipeline control</h3><p>Keep ownership, intent and the next conversation clear.</p></div></div>
                        <div class="form-grid roomy-grid">
                            <div class="field full"><label>Pipeline status <span class="label-note">Where is this lead now?</span></label>
                                @if($selectedLead->is_student)
                                    <input type="hidden" name="status" value="converted">
                                    <div class="locked-conversion-status"><strong>Enrolled student</strong><span>Status is managed through the student journey below.</span></div>
                                @else
                                    <select name="status" data-status-select>
                                        @if($conversionIncomplete)<option value="converted" data-symbol="!" data-hint="{{ $currentStatus['copy'] }}" selected>Conversion incomplete — finish in Student tab</option>@endif
                                        @foreach($pipelineStatuses as $key=>$label)<option value="{{ $key }}" data-symbol="{{ $statusDetails[$key]['symbol'] ?? '•' }}" data-hint="{{ $statusDetails[$key]['copy'] ?? '' }}" @selected(!$conversionIncomplete && $selectedLead->status===$key)>{{ $label }}</option>@endforeach
                                    </select>
                                @endif
                                <div class="select-guidance"><span data-status-symbol>{{ $currentStatus['symbol'] }}</span><p data-status-help>{{ $currentStatus['copy'] }}</p></div>
                            </div>
                            <div class="field"><label>Priority</label><select name="priority">@foreach($priorities as $key=>$label)<option value="{{ $key }}" @selected($selectedLead->priority===$key)>{{ $label }}</option>@endforeach</select></div>
                            @if($crmUser->isSuperAdmin())<div class="field"><label>Counsellor owner</label><select name="assigned_to"><option value="">Unassigned</option>@foreach($counsellors as $person)<option value="{{ $person->id }}" @selected($selectedLead->assigned_to===$person->id)>{{ $person->name }}</option>@endforeach</select></div>@endif
                            <div class="field {{ $crmUser->isSuperAdmin() ? 'full' : '' }}"><label>Next follow-up <span class="label-note">Optional</span></label><input type="datetime-local" name="follow_up_at" value="{{ $selectedLead->follow_up_at?->format('Y-m-d\TH:i') }}"></div>
                        </div>
                        <div class="next-action-card {{ $followUpTone }}"><span class="next-action-icon">{{ $followUpTone === 'overdue' ? '!' : ($followUpTone === 'done' ? '✓' : '◷') }}</span><div><strong>{{ $followUpTitle }}</strong><p>{{ $followUpCopy }}</p></div>@if($selectedLead->follow_up_at && !$selectedLead->follow_up_completed_at)<button class="btn btn-outline btn-compact" form="completeFollowup" type="submit">Mark completed</button>@endif</div>
                    </div>

                    <div class="drawer-card section-card">
                        <div class="section-heading"><span class="section-icon">◎</span><div><h3>Contact and study profile</h3><p>The information counsellors need before every conversation.</p></div></div>
                        <div class="form-grid roomy-grid">
                            <div class="field"><label>Full name</label><input name="name" value="{{ $selectedLead->name }}" required></div><div class="field"><label>Mobile number <span class="label-note">Optional for email leads</span></label><input name="phone" value="{{ $selectedLead->phone }}"></div>
                            <div class="field"><label>Email address <span class="label-note">Optional</span></label><input type="email" name="email" value="{{ $selectedLead->email }}" placeholder="student@example.com"></div><div class="field"><label>Current city <span class="label-note">Optional</span></label><input name="city" value="{{ $selectedLead->city }}" placeholder="e.g. Jaipur"></div>
                            <div class="field"><label>Course / field of interest</label><input name="course_interest" value="{{ $selectedLead->course_interest }}" placeholder="e.g. MS Computer Science"></div><div class="field"><label>Preferred country</label><input name="country_interest" value="{{ $selectedLead->country_interest }}" placeholder="e.g. United Kingdom"></div>
                            <div class="field"><label>Study category</label><select name="category"><option value="">Not set</option>@foreach($categories as $key=>$label)<option value="{{ $key }}" @selected($selectedLead->category===$key)>{{ $label }}</option>@endforeach</select></div><div class="field"><label>Enquiry type</label><select name="lead_type">@foreach($leadTypes as $key=>$label)<option value="{{ $key }}" @selected($selectedLead->lead_type===$key)>{{ $label }}</option>@endforeach</select></div>
                            <div class="field"><label>Lead origin</label><input value="{{ $leadOrigins[$selectedLead->lead_origin] ?? ucfirst($selectedLead->lead_origin) }}" disabled></div><div class="field"><label>Lead source</label><input name="source" value="{{ $selectedLead->source }}" placeholder="Website, referral, event…"></div>
                        </div>
                    </div>

                    <div class="drawer-card section-card">
                        <div class="section-heading"><span class="section-icon">▤</span><div><h3>Academic background</h3><p>Filled from the website questionnaire where the student answered it — complete the rest here.</p></div></div>
                        <div class="form-grid roomy-grid academic-grid">
                            <div class="field"><label for="lead_tenth_score">10th %</label><input id="lead_tenth_score" name="tenth_score" value="{{ $selectedLead->tenth_score }}" placeholder="e.g. 88.4" maxlength="40"></div>
                            <div class="field"><label for="lead_tenth_year">Passing Year</label><input id="lead_tenth_year" name="tenth_passing_year" type="number" inputmode="numeric" min="1950" max="2100" value="{{ $selectedLead->tenth_passing_year }}" placeholder="e.g. 2019"></div>
                            <div class="field"><label for="lead_twelfth_score">12th %</label><input id="lead_twelfth_score" name="twelfth_score" value="{{ $selectedLead->twelfth_score }}" placeholder="e.g. 91" maxlength="40"></div>
                            <div class="field"><label for="lead_twelfth_year">Passing Year</label><input id="lead_twelfth_year" name="twelfth_passing_year" type="number" inputmode="numeric" min="1950" max="2100" value="{{ $selectedLead->twelfth_passing_year }}" placeholder="e.g. 2021"></div>
                            <div class="field academic-trio"><label for="lead_graduation_score">Graduation CGPA / %</label><input id="lead_graduation_score" name="graduation_score" value="{{ $selectedLead->graduation_score }}" placeholder="e.g. 8.2 CGPA" maxlength="40"></div>
                            <div class="field academic-trio"><label for="lead_graduation_year">Passing Year</label><input id="lead_graduation_year" name="graduation_passing_year" type="number" inputmode="numeric" min="1950" max="2100" value="{{ $selectedLead->graduation_passing_year }}" placeholder="e.g. 2025"></div>
                            <div class="field academic-trio"><label for="lead_backlogs">Backlogs <span class="label-note">If any</span></label><input id="lead_backlogs" name="backlogs" value="{{ $selectedLead->backlogs }}" placeholder="e.g. 0" maxlength="40"></div>
                            @include('crm.partials.test-repeater', [
                                'field' => 'english_tests', 'options' => $englishTests, 'rows' => $selectedLead->english_tests,
                                'heading' => 'English Proficiency Test', 'addLabel' => 'Add English test',
                            ])
                            @include('crm.partials.test-repeater', [
                                'field' => 'aptitude_tests', 'options' => $aptitudeTests, 'rows' => $selectedLead->aptitude_tests,
                                'heading' => 'Aptitude test', 'addLabel' => 'Add aptitude test',
                            ])
                        </div>
                    </div>

                    <div class="lead-save-bar"><span class="save-state" data-form-state><i></i><span>All changes are saved</span></span><button class="btn btn-primary" type="submit"><span>Save lead details</span><b aria-hidden="true">→</b></button></div>
                </form>
                @if($selectedLead->websiteSubmissions->isNotEmpty())
                    <div class="drawer-card section-card">
                        <div class="section-heading"><span class="section-icon">⌁</span><div><h3>Website submission history</h3><p>Original form answers captured from the website.</p></div></div>
                        <div class="crm-submission-history">
                            @foreach($selectedLead->websiteSubmissions as $submission)
                                <details><summary><span><strong>{{ $submission->source_label }}</strong><small>{{ $submission->submitted_at->format('d M Y, g:i A') }}@if($submission->degree) · {{ $submission->degree }}@endif</small></span><a href="{{ route('crm.website.download', $submission) }}" data-native-navigation>Download</a></summary>
                                    @foreach($submission->sections ?: [] as $section)<div class="crm-answer-section"><b>{{ $section['title'] ?? ($section['eyebrow'] ?? 'Answers') }}</b>@foreach($section['answers'] ?? [] as $answer)<p><span>{{ $answer['label'] ?? '' }}</span><strong>{{ implode(', ', (array)($answer['value'] ?? [])) ?: '—' }}</strong></p>@endforeach</div>@endforeach
                                </details>
                            @endforeach
                        </div>
                    </div>
                @endif
                @if($selectedLead->follow_up_at && !$selectedLead->follow_up_completed_at)<form id="completeFollowup" method="post" action="{{ route('crm.leads.follow-up.complete',$selectedLead) }}">@csrf</form>@endif
            </div>

            <div class="tab-panel" data-panel="timeline">
                <section class="timeline-overview">
                    <div><span class="overview-icon">◷</span><strong data-timeline-total>{{ $selectedLead->activities->count() }}</strong><small>Total activities</small></div>
                    <div><span class="overview-icon">✎</span><strong data-timeline-comments>{{ $selectedLead->activities->where('type','comment')->count() }}</strong><small>Team comments</small></div>
                    <div><span class="overview-icon">☎</span><strong data-last-contacted>{{ $selectedLead->last_contacted_at?->diffForHumans(null, true) ?? '—' }}</strong><small>Since contact</small></div>
                </section>

                <div class="drawer-card comment-box enhanced-composer">
                    <div class="section-heading"><span class="section-icon">✎</span><div><h3>Log an interaction</h3><p>Capture what happened and make the next conversation easier.</p></div></div>
                    <form method="post" action="{{ route('crm.leads.comments.store',$selectedLead) }}" data-timeline-form>@csrf
                        <div class="comment-templates"><span>Quick start</span><button type="button" data-comment-template="Call connected — ">Call connected</button><button type="button" data-comment-template="No answer — retry on ">No answer</button><button type="button" data-comment-template="Documents requested — ">Documents requested</button></div>
                        <div class="comment-input-wrap"><textarea name="comment" rows="4" maxlength="3000" placeholder="Write the call outcome, student concern, documents discussed or next action…" required></textarea><span data-comment-count>0 / 3000</span></div>
                        <div class="drawer-actions composer-actions"><button class="btn btn-primary" type="submit"><span data-timeline-submit>Add to timeline</span><b aria-hidden="true">＋</b></button></div>
                    </form>
                </div>

                <div class="timeline-toolbar"><div><span class="section-kicker">Activity history</span><h3>Everything in one place</h3></div><div class="timeline-filters" aria-label="Filter timeline"><button class="active" data-timeline-filter="all">All</button><button data-timeline-filter="conversation">Comments</button><button data-timeline-filter="updates">Updates</button><button data-timeline-filter="milestones">Milestones</button></div></div>
                <ol class="timeline activity-stream" data-timeline-list>
                    @forelse($selectedLead->activities as $activity)
                        @php $activityView = $activityViews[$activity->type] ?? ['symbol' => '•', 'group' => 'system', 'label' => str_replace('_',' ',ucfirst($activity->type))]; @endphp
                        <li class="activity-item activity-{{ $activityView['group'] }}" data-activity-group="{{ $activityView['group'] }}"><span class="activity-marker">{{ $activityView['symbol'] }}</span><article class="activity-card"><div class="activity-head"><div><span class="mini-avatar">{{ $initials($activity->user?->name ?? 'System') }}</span><span><strong>{{ $activityView['label'] }}</strong><small>{{ $activity->user?->name ?? 'System' }}</small></span></div><time datetime="{{ $activity->created_at->toIso8601String() }}">{{ $activity->created_at->format('d M Y') }}<small>{{ $activity->created_at->format('g:i A') }}</small></time></div><p>{{ $activity->body }}</p></article></li>
                    @empty
                        <li class="timeline-empty-state" data-timeline-empty><span>◷</span><strong>No activity yet</strong><p>Comments, updates and milestones will build the complete lead story here.</p></li>
                    @endforelse
                </ol>
            </div>

            <div class="tab-panel" data-panel="student">
                @if($selectedLead->is_student)
                    <section class="journey-hero crm-stage-summary {{ $journeyIsAlert ? 'journey-alert' : '' }} {{ $journeyIsComplete ? 'journey-complete' : '' }}">
                        <div class="journey-stage-mark" aria-hidden="true"><span>{{ $journeyIsAlert ? '!' : ($journeyIsComplete ? '✓' : ($journeyPosition >= 0 ? $journeyPosition + 1 : '•')) }}</span></div>
                        <div class="journey-stage-copy"><span class="section-kicker">Current student stage</span><h3>{{ $studentStages[$selectedLead->student_stage] ?? 'Journey not started' }}</h3><p>{{ $stageGuidance[$selectedLead->student_stage] ?? 'Select the current stage and keep the student record up to date.' }}</p></div>
                        <div class="journey-stage-status"><strong>{{ $journeyIsAlert ? 'Needs attention' : ($journeyIsComplete ? 'Journey complete' : ($journeyPosition >= 0 ? 'Step '.($journeyPosition + 1).' of '.count($journeyKeys) : 'Stage pending')) }}</strong><small>{{ $nextJourneyKey ? 'Next: '.$studentStages[$nextJourneyKey] : ($journeyIsComplete ? 'Pre-departure ready' : 'Review and update') }}</small></div>
                    </section>

                    <div class="journey-track" aria-label="Student journey progress">
                        @foreach($journeyKeys as $stageIndex=>$stageKey)<div class="journey-step {{ $stageIndex < $journeyPosition ? 'complete' : ($stageIndex === $journeyPosition ? 'current' : '') }}"><span>{{ $stageIndex < $journeyPosition ? '✓' : $stageIndex + 1 }}</span><div><strong>{{ $studentStages[$stageKey] }}</strong><small>{{ $stageIndex === $journeyPosition ? 'Current stage' : ($stageIndex < $journeyPosition ? 'Completed' : 'Upcoming') }}</small></div></div>@endforeach
                    </div>

                    <section class="student-metrics">
                        <div><span>Student type</span><strong>{{ $studentCategories[$selectedLead->student_category] ?? 'Not set' }}</strong></div><div><span>Enrollment date</span><strong>{{ $selectedLead->enrollment_date?->format('d M Y') ?? 'Not set' }}</strong></div><div><span>Enrollment value</span><strong>{{ $selectedLead->enrollment_amount ? '₹'.number_format($selectedLead->enrollment_amount) : 'Not set' }}</strong></div>
                    </section>

                    <form method="post" action="{{ route('crm.leads.student-journey.update',$selectedLead) }}" data-track-changes data-student-journey-form>@csrf @method('PATCH')
                        <div class="drawer-card section-card">
                            <div class="section-heading"><span class="section-icon">◇</span><div><h3>Update student journey</h3><p>Advance the stage and maintain enrollment information.</p></div></div>
                            <div class="form-grid roomy-grid">
                                <div class="field full"><label>Current journey stage</label><select name="student_stage" data-stage-select required>@foreach($studentStages as $key=>$label)<option value="{{ $key }}" data-hint="{{ $stageGuidance[$key] ?? '' }}" @selected($selectedLead->student_stage===$key)>{{ $label }}</option>@endforeach</select><div class="select-guidance stage-guidance"><span>→</span><p data-stage-help>{{ $stageGuidance[$selectedLead->student_stage] ?? 'Keep the student journey current.' }}</p></div></div>
                                <div class="field"><label>Student type</label><select name="student_category" required>@foreach($studentCategories as $key=>$label)<option value="{{ $key }}" @selected($selectedLead->student_category===$key)>{{ $label }}</option>@endforeach</select></div>
                                <div class="field"><label>Enrollment date</label><input type="date" name="enrollment_date" value="{{ $selectedLead->enrollment_date?->format('Y-m-d') }}"></div>
                                <div class="field"><label>Enrollment amount</label><div class="money-input"><span>₹</span><input type="number" min="0" name="enrollment_amount" value="{{ $selectedLead->enrollment_amount }}" placeholder="0"></div></div>
                                <div class="field"><label>Payment reference</label><input name="payment_reference" value="{{ $selectedLead->payment_reference }}" placeholder="Receipt or transaction ID"></div>
                                <div class="field full"><label>Counsellor remarks <span class="label-note">Optional</span></label><textarea name="conversion_remarks" rows="3" placeholder="Important enrollment, application or visa notes…">{{ $selectedLead->conversion_remarks }}</textarea></div>
                            </div>
                        </div>
                        <div class="lead-save-bar journey-save-bar"><span class="save-state" data-form-state><i></i><span>Journey information is saved</span></span><button class="btn btn-primary" type="submit"><span>Update journey</span><b aria-hidden="true">→</b></button></div>
                    </form>
                @else
                    <section class="conversion-hero"><div class="conversion-graphic" aria-hidden="true"><span class="student-card-graphic"><i>{{ $initials($selectedLead->name) }}</i><b>{{ $selectedLead->name }}</b><small>Future student</small></span><span class="conversion-arrow">→</span><span class="journey-passport">◇<i>One Degree</i></span></div><span class="section-kicker">Ready for the next chapter?</span><h3>Convert this lead into a student journey</h3><p>Enrollment keeps the complete lead history and unlocks structured document, application, offer and visa tracking.</p><div class="conversion-benefits"><span><i>1</i>Keep the full timeline</span><span><i>2</i>Track every admission stage</span><span><i>3</i>Manage payment information</span></div></section>

                    <form method="post" action="{{ route('crm.leads.convert',$selectedLead) }}" data-track-changes>@csrf
                        <div class="drawer-card section-card conversion-form-card">
                            <div class="section-heading"><span class="section-icon">✦</span><div><h3>Enrollment information</h3><p>Start with the details available today. They can be updated later.</p></div></div>
                            <div class="form-grid roomy-grid">
                                <div class="field"><label>Student type</label><select name="student_category" required>@foreach($studentCategories as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                                <div class="field"><label>Starting stage</label><select name="student_stage" required>@foreach($studentStages as $key=>$label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></div>
                                <div class="field"><label>Enrollment amount <span class="label-note">Optional</span></label><div class="money-input"><span>₹</span><input type="number" min="0" name="enrollment_amount" placeholder="0"></div></div>
                                <div class="field"><label>Enrollment date <span class="label-note">Optional</span></label><input type="date" name="enrollment_date" value="{{ now()->format('Y-m-d') }}"></div>
                                <div class="field full"><label>Payment reference <span class="label-note">Optional</span></label><input name="payment_reference" placeholder="Receipt or transaction ID"></div>
                                <div class="field full"><label>Enrollment remarks <span class="label-note">Optional</span></label><textarea name="conversion_remarks" rows="3" placeholder="Scholarship, payment plan, intake or special notes…"></textarea></div>
                            </div>
                        </div>
                        <div class="lead-save-bar conversion-save-bar"><span class="save-state"><i></i><span>This action starts the student journey</span></span><button class="btn btn-primary" type="submit"><span>Convert to student</span><b aria-hidden="true">→</b></button></div>
                    </form>
                @endif
            </div>
        </div>
    </aside>
</div>
@endif

</div>

<div class="transition-screen" id="transitionScreen" aria-hidden="true">
    <div class="transition-card">
        <span class="transition-logo"><img src="{{ asset('assets/Logo/mark-light.svg') }}" alt=""></span>
        <span class="transition-rings" aria-hidden="true"><i></i><i></i><i></i></span>
        <strong data-transition-copy>Loading your workspace…</strong>
        <small>One Degree Lead CRM</small>
    </div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js" defer></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="" defer></script>
<script src="{{ asset('assets/crm/crm.js') }}?v={{ filemtime(public_path('assets/crm/crm.js')) }}" defer></script>
</body>
</html>
