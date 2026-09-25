{{-- Journey planners: every enrolled student whose planner has started, with
     their progress. Super admins see everyone; counsellors their own students.
     Built from the CRM's own tiles, filters and table so it follows the theme. --}}
@php
    $isAdmin = $crmUser->isSuperAdmin();
    $showOptions = ['' => 'All planners', 'attention' => 'Needs attention', 'not_signed_in' => 'Student not signed in yet', 'complete' => 'Complete'];
    $sortOptions = ['' => 'Recently updated', 'progress_asc' => 'Least progress first', 'progress_desc' => 'Most progress first', 'late' => 'Most late tasks', 'name' => 'Name A–Z'];
@endphp
<style>
    /* Drawn in the text colour so the bar reads in every CRM theme. */
    .jp-crm-progress{display:flex;align-items:center;gap:10px;min-width:170px}
    .jp-crm-progress b{min-width:40px;text-align:right;font-variant-numeric:tabular-nums}
    .jp-crm-bar{flex:1;height:8px;border-radius:99px;overflow:hidden;background:rgba(127,127,127,.2)}
    .jp-crm-bar i{display:block;height:100%;border-radius:99px;background:currentColor;opacity:.75}
    .jp-crm-split{display:flex;gap:14px;margin-top:6px}
    .jp-crm-split span{white-space:nowrap}
    .jp-crm-table td{vertical-align:middle}
</style>

<section class="stats" aria-label="Journey planner summary">
    <div class="stat"><span class="stat-top"><span class="stat-icon">◈</span></span><strong>{{ $journeyStats['total'] }}</strong><span>{{ $isAdmin ? 'Planners started' : 'Your students with a planner' }}</span></div>
    <div class="stat"><span class="stat-top"><span class="stat-icon">%</span></span><strong>{{ $journeyStats['average'] }}%</strong><span>Average progress</span></div>
    <a @class(['stat', 'danger' => $journeyStats['late'] > 0]) href="{{ route('crm.dashboard', ['view' => 'journeys', 'journey_show' => 'attention']) }}"><span class="stat-top"><span class="stat-icon">!</span></span><strong>{{ $journeyStats['late'] }}</strong><span>Students with late tasks</span></a>
    <a @class(['stat', 'hot' => $journeyStats['essays'] > 0]) href="{{ route('crm.dashboard', ['view' => 'journeys', 'journey_show' => 'attention']) }}"><span class="stat-top"><span class="stat-icon">✎</span></span><strong>{{ $journeyStats['essays'] }}</strong><span>Essays waiting for review</span></a>
    <a class="stat" href="{{ route('crm.dashboard', ['view' => 'journeys', 'journey_show' => 'not_signed_in']) }}"><span class="stat-top"><span class="stat-icon">○</span></span><strong>{{ $journeyStats['neverSignedIn'] }}</strong><span>Students not signed in yet</span></a>
</section>

<section class="workspace">
    <div class="workspace-head">
        <div class="workspace-title">
            <h2>Journey planners</h2>
            <p>{{ number_format($journeyPlans->total()) }} {{ Str::plural('student', $journeyPlans->total()) }} · {{ $isAdmin ? 'every counsellor’s students' : 'the students assigned to you' }}</p>
        </div>
        <a class="btn btn-outline" href="{{ route('crm.dashboard', ['view' => 'students']) }}">Enrolled students</a>
    </div>

    <form class="filters" method="get" action="{{ route('crm.dashboard') }}">
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

    @if($journeyPlans->isEmpty())
        <div class="empty-state" style="padding:48px 24px;text-align:center">
            <strong>{{ $journeyStats['total'] ? 'No planners match these filters' : 'No journey planners yet' }}</strong>
            <p class="subtext">{{ $journeyStats['total'] ? 'Change or clear the filters above.' : 'Open an enrolled student, go to the Student tab and click “Start journey planner”.' }}</p>
        </div>
    @else
        <div class="table-wrap"><table class="jp-crm-table">
            <thead><tr><th class="col-serial">Serial No</th><th>Student</th>@if($isAdmin)<th>Counsellor</th>@endif<th>Progress</th><th>Current stage</th><th>Needs attention</th><th>Next date</th><th>Student login</th><th></th></tr></thead>
            <tbody>@foreach($journeyPlans as $row)
                @php
                    $plan = $row['plan'];
                    $lead = $plan->lead;
                    $account = $lead->studentAccount;
                @endphp
                <tr>
                    <td class="col-serial">{{ $journeyPlans->firstItem() + $loop->index }}</td>
                    <td>
                        <a href="{{ route('crm.journey.show', $lead) }}" target="_blank" rel="noopener" data-native-navigation><strong>{{ $lead->name }}</strong></a>
                        <span class="subtext">{{ $lead->lead_number }} · {{ collect([$plan->level, $plan->intake])->filter()->implode(' · ') ?: 'Plan details not set' }}</span>
                    </td>
                    @if($isAdmin)<td>{{ $lead->assignee?->name ?? 'Unassigned' }}</td>@endif
                    <td>
                        <div class="jp-crm-progress"><span class="jp-crm-bar"><i style="width:{{ $row['all']['percent'] }}%"></i></span><b>{{ $row['all']['percent'] }}%</b></div>
                        <div class="jp-crm-split subtext"><span>Journey {{ $row['core']['percent'] }}%</span><span>{{ $row['universities'] }} {{ Str::plural('university', $row['universities']) }}{{ $row['universities'] ? ' · '.$row['apps']['percent'].'%' : '' }}</span></div>
                    </td>
                    <td>
                        @if($row['stage'])
                            <strong>{{ $row['stage']['name'] }}</strong><span class="subtext">Stage {{ $row['stage']['number'] }} of {{ $row['stage']['of'] }}</span>
                        @else
                            <span class="badge status-converted">All stages done</span>
                        @endif
                    </td>
                    <td>
                        @if($row['overdue'] || $plan->essays_waiting)
                            @if($row['overdue'])<span class="badge status-dropped">{{ $row['overdue'] }} late</span>@endif
                            @if($plan->essays_waiting)<span class="badge status-follow_up">{{ $plan->essays_waiting }} {{ Str::plural('essay', $plan->essays_waiting) }} to review</span>@endif
                        @else
                            <span class="subtext">Nothing</span>
                        @endif
                    </td>
                    <td>
                        @if($row['next'])
                            <strong>{{ \Illuminate\Support\Carbon::parse($row['next']['date'])->format('d M Y') }}</strong><span class="subtext">{{ Str::limit($row['next']['name'], 40) }} · {{ $row['next']['where'] }}</span>
                        @else
                            <span class="subtext">No dates set</span>
                        @endif
                    </td>
                    <td>
                        @if(! $account)
                            <span class="subtext">No login</span>
                        @elseif(! $account->is_active)
                            <span class="badge status-dropped">Switched off</span>
                        @else
                            {{ $account->last_login_at ? 'Signed in '.$account->last_login_at->diffForHumans() : 'Not signed in yet' }}
                            <span class="subtext">{{ $account->must_change_password ? 'Temporary password' : 'Own password set' }}</span>
                        @endif
                    </td>
                    <td><a class="btn btn-outline" href="{{ route('crm.journey.show', $lead) }}" target="_blank" rel="noopener" data-native-navigation>Open planner</a></td>
                </tr>
            @endforeach</tbody>
        </table></div>
        @if($journeyPlans->hasPages())<div class="pagination-wrap">{{ $journeyPlans->onEachSide(1)->links('pagination::crm') }}</div>@endif
    @endif
</section>
