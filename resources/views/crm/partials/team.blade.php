{{-- Team management: a sidebar list beside a main frame with three states.

     The card grid this replaces made every account a small box holding four
     separate forms — details behind a disclosure, role behind an icon, partner
     access behind its own Apply button, disable and delete behind two more —
     and put the "add someone" form below all of them.

     Now the sidebar is the index and the main frame answers it:

       · nothing chosen   → a card grid of the accounts the sidebar filter is
                            showing, so the chips (All / Admins / Counsellors /
                            Partners) change the main frame as well as the list
       · ?member=<id>     → everything about that account, saved in one form
       · ?add=1           → the create form

     The chips and the search box filter the rows and the cards in one client
     side pass (crm.js applyTeamFilter), so both stay in step with no reload.
     Opening an account travels in the URL instead, which keeps a person
     linkable and leaves the choice of what is on screen out of JavaScript.

     Super admin only, enforced in CrmDashboardController::index. --}}
@php
    $superAdmins = $team->filter->isSuperAdmin()->values();
    // Named apart from the $counsellors the controller passes for the lead
    // owner dropdown further down the page: reusing that name shadowed it.
    $counsellorAccounts = $team->filter(fn ($member) => $member->role === 'counsellor')->values();
    $partnerAccounts = $team->filter->isPartner()->values();
    $isAdding = request()->boolean('add') && ! $teamMember;
    $browseUrl = request()->fullUrlWithQuery(['view' => 'team', 'member' => null, 'add' => null]);
    $addUrl = request()->fullUrlWithQuery(['view' => 'team', 'member' => null, 'add' => 1]);

    // The role chips live in the URL rather than in JavaScript. They have to:
    // a chip is "show me this group", and while the main frame was on the Add
    // form or an account, a purely client-side chip could only narrow the
    // sidebar and left the frame stuck on whatever it was already showing.
    // As a link it clears ?member / ?add too, so the grid always comes back.
    $slugOf = fn ($member): string => $member->isSuperAdmin() ? 'super-admin' : ($member->isPartner() ? 'partner' : 'counsellor');
    $roleFilter = in_array(request('role'), ['super-admin', 'counsellor', 'partner'], true) ? request('role') : 'all';
    $shown = $roleFilter === 'all' ? $team : $team->filter(fn ($member) => $slugOf($member) === $roleFilter)->values();
    $chipUrl = fn (string $slug): string => request()->fullUrlWithQuery([
        'view' => 'team', 'role' => $slug === 'all' ? null : $slug, 'member' => null, 'add' => null,
    ]);
    $chips = [
        'all' => ['All', $team->count()],
        'super-admin' => ['Admins', $superAdmins->count()],
        'counsellor' => ['Counsellors', $counsellorAccounts->count()],
        'partner' => ['Partners', $partnerAccounts->count()],
    ];
@endphp
<section class="workspace team-workspace">
    <div class="workspace-head">
        <div class="workspace-title">
            <h2>Team management</h2>
            <p>{{ $team->count() }} account{{ $team->count() === 1 ? '' : 's' }} · {{ $superAdmins->count() }} super admin{{ $superAdmins->count() === 1 ? '' : 's' }}, {{ $counsellorAccounts->count() }} counsellor{{ $counsellorAccounts->count() === 1 ? '' : 's' }}, {{ $partnerAccounts->count() }} partner{{ $partnerAccounts->count() === 1 ? '' : 's' }}</p>
        </div>
        <div class="workspace-head-actions">
            <span class="audit-private-label">Super admin only</span>
            @unless($isAdding)<a class="btn btn-primary" href="{{ $addUrl }}">＋ <span>Add member</span></a>@endunless
        </div>
    </div>

    <div class="team-panes">
        <aside class="team-list-pane" aria-label="Team accounts">
            <div class="team-toolbar" data-team-toolbar>
                <label class="team-search">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="search" placeholder="Search name, email or mobile…" data-team-search-input aria-label="Search team accounts" autocomplete="off">
                </label>
                <div class="team-filter" role="group" aria-label="Filter by access level">
                    @foreach($chips as $slug => [$label, $count])
                        <a class="team-filter-chip {{ $roleFilter === $slug ? 'is-active' : '' }}"
                           href="{{ $chipUrl($slug) }}"
                           data-team-filter="{{ $slug }}"
                           @if($roleFilter === $slug) aria-current="true" @endif>{{ $label }} <span class="chip-count">{{ $count }}</span></a>
                    @endforeach
                </div>
            </div>
            <div class="team-list">
                @foreach($shown as $member)
                    @include('crm.partials.team-row', ['member' => $member])
                @endforeach
                <p class="team-no-results" data-team-no-results @unless($shown->isEmpty()) hidden @endunless>No account matches this filter.</p>
            </div>
        </aside>

        <div class="team-detail-pane">
            @if($teamMember)
                @include('crm.partials.team-detail', ['member' => $teamMember, 'browseUrl' => $browseUrl])
            @elseif($isAdding)
                <div class="team-detail">
                    <a class="team-back" href="{{ $browseUrl }}">← All accounts</a>
                    <header class="team-detail-head">
                        <span class="avatar is-new" aria-hidden="true">＋</span>
                        <div class="team-detail-id">
                            <h3>Add a team member</h3>
                            <p><span class="team-detail-state">They sign in with the mobile number or email you enter here — no password.</span></p>
                        </div>
                    </header>
                    <form class="team-detail-form" method="post" action="{{ route('crm.team.store') }}">@csrf
                        <div class="form-grid">
                            <div class="field"><label for="new_name">Full name</label><input id="new_name" name="name" value="{{ old('name') }}" required></div>
                            <div class="field"><label for="new_phone">Mobile number</label><input id="new_phone" name="phone" value="{{ old('phone') }}" inputmode="tel" placeholder="98765 43210" required></div>
                            <div class="field full"><label for="new_email">Email address</label><input id="new_email" type="email" name="email" value="{{ old('email') }}" placeholder="name@domain.com" required></div>
                            <div class="field full">
                                <label for="new_role">Access level</label>
                                <select id="new_role" name="role" data-team-role-select required>
                                    <option value="counsellor" @selected(old('role') === 'counsellor')>Counsellor</option>
                                    <option value="super_admin" @selected(old('role') === 'super_admin')>Super admin</option>
                                    <option value="partner" @selected(old('role') === 'partner')>Partner</option>
                                </select>
                                <div class="team-role-guide">
                                    <span class="is-counsellor"><b>Counsellor</b><small>Works only the leads assigned to them</small></span>
                                    <span class="is-super-admin"><b>Super admin</b><small>Full lead, team and audit-log access</small></span>
                                    <span class="is-partner"><b>Partner</b><small>Sees only the students whose Partner field names them</small></span>
                                </div>
                            </div>
                            {{-- Only meaningful on a partner, so it appears with the role.
                                 The server excludes it for any other role rather than
                                 trusting this to be right (CrmUserController::store). --}}
                            <div class="field full" data-partner-access-field @unless(old('role') === 'partner') hidden @endunless>
                                <label for="new_access">Partner access</label>
                                <select id="new_access" name="partner_access">
                                    <option value="read" @selected(old('partner_access') === 'read')>Read only — can follow their students</option>
                                    <option value="edit" @selected(old('partner_access') === 'edit')>Read and edit — can also update their students</option>
                                </select>
                                <span class="field-note">Either way they can never change the Partner field, enrol a student or see the payment log.</span>
                            </div>
                        </div>
                        <div class="team-detail-save">
                            <button class="btn btn-primary" type="submit">Create account</button>
                            <span class="team-detail-hint">They can sign in as soon as the account exists.</span>
                        </div>
                    </form>
                </div>
            @else
                {{-- The browse state. Same accounts as the sidebar, same filter —
                     a chip narrows both at once. --}}
                <div class="team-browse">
                    <div class="team-browse-head">
                        <h3>{{ $roleFilter === 'all' ? 'All accounts' : $chips[$roleFilter][0] }}</h3>
                        <p><strong data-team-visible-count>{{ $shown->count() }}</strong> shown · pick one to edit it</p>
                    </div>
                    <div class="team-cards">
                        @foreach($shown as $member)
                            @include('crm.partials.team-card', ['member' => $member])
                        @endforeach
                        <a class="team-card is-add" href="{{ $addUrl }}">
                            <span class="team-card-add-mark" aria-hidden="true">＋</span>
                            <span class="team-card-name">Add a team member</span>
                            <span class="team-card-contact">Counsellor, super admin or referral partner</span>
                        </a>
                    </div>
                    <p class="team-no-results" data-team-no-results @unless($shown->isEmpty()) hidden @endunless>No account matches this filter.</p>
                </div>
            @endif
        </div>
    </div>
</section>
