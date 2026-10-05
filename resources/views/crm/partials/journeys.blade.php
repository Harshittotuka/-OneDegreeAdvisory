{{-- Journey planners: one card per enrolled student whose planner has started.
     Super admins see everyone; counsellors their own students. The cards use
     the shared --dash-* tokens (crm-dashboard.css) so they follow the theme. --}}
@php
    $isAdmin = $crmUser->isSuperAdmin();
    $showOptions = ['' => 'All planners', 'attention' => 'Needs attention', 'not_signed_in' => 'Student not signed in yet', 'complete' => 'Complete'];
    $sortOptions = ['' => 'Recently updated', 'progress_asc' => 'Least progress first', 'progress_desc' => 'Most progress first', 'late' => 'Most late tasks', 'name' => 'Name A–Z'];
@endphp

<section class="stats journey-stats" aria-label="Journey planner summary">
    <div class="stat"><span class="stat-top"><span class="stat-icon">◈</span></span><strong>{{ $journeyStats['total'] }}</strong><span>{{ $isAdmin ? 'Planners started' : 'Your students with a planner' }}</span></div>
    <div class="stat"><span class="stat-top"><span class="stat-icon">◷</span></span><strong>{{ number_format($journeyStats['done']) }} / {{ number_format($journeyStats['steps']) }}</strong><span>Steps done across them</span></div>
    <a @class(['stat', 'danger' => $journeyStats['late'] > 0]) href="{{ route('crm.dashboard', ['view' => 'journeys', 'journey_show' => 'attention']) }}"><span class="stat-top"><span class="stat-icon">!</span></span><strong>{{ $journeyStats['late'] }}</strong><span>Students with late tasks</span></a>
    <a @class(['stat', 'hot' => $journeyStats['essays'] > 0]) href="{{ route('crm.dashboard', ['view' => 'journeys', 'journey_show' => 'attention']) }}"><span class="stat-top"><span class="stat-icon">✎</span></span><strong>{{ $journeyStats['essays'] }}</strong><span>Essays waiting for review</span></a>
    <a class="stat" href="{{ route('crm.dashboard', ['view' => 'journeys', 'journey_show' => 'not_signed_in']) }}"><span class="stat-top"><span class="stat-icon">○</span></span><strong>{{ $journeyStats['neverSignedIn'] }}</strong><span>Students not signed in yet</span></a>
    <a class="stat" href="{{ route('crm.dashboard', ['view' => 'journeys', 'journey_show' => 'complete']) }}"><span class="stat-top"><span class="stat-icon">✓</span></span><strong>{{ $journeyStats['complete'] }}</strong><span>Plans complete</span></a>
</section>

<section class="workspace crm-journey-workspace">
    {{-- One bar: how many, then the filters. The page title already says
         "Journey planners", and Enrolled students is in the sidebar. --}}
    <div class="journey-toolbar">
        <p class="journey-count"><b>{{ number_format($journeyPlans->total()) }}</b> {{ Str::plural('student', $journeyPlans->total()) }} <span>· {{ $isAdmin ? 'all counsellors' : 'yours' }}</span></p>
    <form class="journey-filters" method="get" action="{{ route('crm.dashboard') }}">
        <input type="hidden" name="view" value="journeys">
        <div class="search-wrap"><input class="control" type="search" name="journey_search" value="{{ request('journey_search') }}" placeholder="Search name, email or lead ID"></div>
        @if($isAdmin)
            <select class="control" name="journey_counsellor" aria-label="Counsellor">
                <option value="">All counsellors</option>
                @foreach($journeyCounsellors as $counsellor)
                    <option value="{{ $counsellor->id }}" @selected((int) request('journey_counsellor') === $counsellor->id)>{{ $counsellor->name }}</option>
                @endforeach
            </select>
        @endif
        <select class="control" name="journey_show" aria-label="Show">
            @foreach($showOptions as $key => $label)<option value="{{ $key }}" @selected(request('journey_show', '') === $key)>{{ $label }}</option>@endforeach
        </select>
        <select class="control" name="journey_sort" aria-label="Sort">
            @foreach($sortOptions as $key => $label)<option value="{{ $key }}" @selected(request('journey_sort', '') === $key)>{{ $label }}</option>@endforeach
        </select>
        <button class="btn btn-primary" type="submit">Apply</button>
        @if(request()->hasAny(['journey_search', 'journey_counsellor', 'journey_show', 'journey_sort']))
            <a class="btn btn-outline" href="{{ route('crm.dashboard', ['view' => 'journeys']) }}">Clear</a>
        @endif
    </form>
    </div>

    @if($journeyPlans->isEmpty())
        <div class="empty-state" style="padding:48px 24px;text-align:center">
            <strong>{{ $journeyStats['total'] ? 'No planners match these filters' : 'No journey planners yet' }}</strong>
            <p class="subtext">{{ $journeyStats['total'] ? 'Change or clear the filters above.' : 'Open an enrolled student, go to the Student tab and click “Start journey planner”.' }}</p>
        </div>
    @else
        <div class="journey-cards">
            @foreach($journeyPlans as $row)
                @php
                    $plan = $row['plan'];
                    $lead = $plan->lead;
                    $account = $lead->studentAccount;
                    $essays = $plan->essays_waiting;
                    $percent = $row['all']['percent']; // the stripe's width only; the card shows counts
                    $done = $row['all']['completed'];
                    $steps = $row['all']['included'] - $row['all']['not_applicable'];
                    // The stripe says the one thing to notice first.
                    $state = $row['overdue'] ? 'is-late' : ($essays ? 'is-review' : ($percent === 100 ? 'is-complete' : ''));
                    $loginNote = match (true) {
                        ! $account => 'No student login',
                        ! $account->is_active => 'Login switched off',
                        (bool) $account->last_login_at => 'Signed in '.$account->last_login_at->diffForHumans(),
                        default => 'Not signed in yet',
                    };
                @endphp
                <a class="journey-card {{ $state }}" href="{{ route('crm.journey.show', $lead) }}" target="_blank" rel="noopener" data-native-navigation>
                    <span class="journey-card-top">
                        <span>
                            <span class="journey-card-name">{{ $lead->name }}</span>
                            <span class="journey-card-sub">{{ $lead->lead_number }} · {{ collect([$plan->level, $plan->intake])->filter()->implode(' · ') ?: 'Plan details not set' }}@if($isAdmin) · {{ $lead->assignee?->name ?? 'Unassigned' }}@endif</span>
                        </span>
                        <span class="journey-card-pct">{{ $done }}<small>of {{ $steps }} done</small></span>
                    </span>

                    <span class="journey-card-bar" aria-hidden="true"><i style="width:{{ $percent }}%"></i></span>
                    <span class="journey-card-split">
                        <span>Journey {{ $row['core']['completed'] }}/{{ max(0, $row['core']['included'] - $row['core']['not_applicable']) }}</span>
                        <span>{{ $row['universities'] }} {{ Str::plural('university', $row['universities']) }}{{ $row['universities'] ? ' · '.$row['apps']['completed'].'/'.max(0, $row['apps']['included'] - $row['apps']['not_applicable']) : '' }}</span>
                    </span>

                    <span class="journey-card-stage">
                        @if($row['stage'])
                            <span class="journey-card-step">{{ $row['stage']['number'] }}/{{ $row['stage']['of'] }}</span>
                            <span><b>{{ $row['stage']['name'] }}</b><span>Current stage</span></span>
                        @else
                            <span class="journey-card-step">✓</span>
                            <span><b>All stages done</b><span>Nothing left on the plan</span></span>
                        @endif
                    </span>

                    <span class="journey-card-flags">
                        @if($row['overdue'])<span class="journey-flag is-late">{{ $row['overdue'] }} late</span>@endif
                        @if($essays)<span class="journey-flag is-review">{{ $essays }} {{ Str::plural('essay', $essays) }} to review</span>@endif
                        @if(! $row['overdue'] && ! $essays)<span class="journey-flag is-ok">Nothing needs attention</span>@endif
                        <span class="journey-flag">{{ $loginNote }}</span>
                    </span>

                    <span class="journey-card-foot">
                        <span>
                            @if($row['next'])
                                Next · {{ \Illuminate\Support\Carbon::parse($row['next']['date'])->format('d M Y') }}
                                <b>{{ Str::limit($row['next']['name'], 34) }}</b>
                            @else
                                <b>No dates set</b>
                            @endif
                        </span>
                        <span class="journey-card-open" aria-hidden="true">Open →</span>
                    </span>
                </a>
            @endforeach
        </div>
        @if($journeyPlans->hasPages())<div class="pagination-wrap">{{ $journeyPlans->onEachSide(1)->links('pagination::crm') }}</div>@endif
    @endif
</section>
