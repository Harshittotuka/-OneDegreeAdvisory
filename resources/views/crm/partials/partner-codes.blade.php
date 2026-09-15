{{-- Partner codes: the referral companies whose code travels in a public link.

     The register of partners, not the place they are edited. A partner is one
     thing — a login plus a company plus a code — and the Team screen holds the
     whole of it, so Add and Edit here both go there. This screen owns what is
     true of the link rather than the partner: pause it, resume it, remove it,
     copy it, export the lot.

     Each row shows the link to hand out, who signs in for it, and the number of
     leads that came through it, so "is this partner working?" is answered where
     the partners are listed rather than on the leads list.

     An orphaned code — one whose account was deleted, or issued before accounts
     and codes were joined — has no partner to open, so it offers no Edit. It
     exists to keep the attribution of the leads it brought in; pause or remove
     are the only things left to decide about it.

     Super admin only, enforced in CrmDashboardController::index and again in
     CrmPartnerCodeController::guard. --}}
@php
    // Both live on the Team screen, with the partner role already chosen so the
    // company fields are showing when the page arrives.
    $addUrl = route('crm.dashboard', ['view' => 'team', 'add' => 1, 'role' => 'partner']);
    $accountUrl = fn ($account) => route('crm.dashboard', ['view' => 'team', 'role' => 'partner', 'member' => $account->id]);
@endphp
<section class="workspace crm-partner-code-workspace">
    <div class="workspace-head">
        <div class="workspace-title">
            <h2>Partner codes</h2>
            <p>{{ number_format($partnerCodeCount) }} {{ \Illuminate\Support\Str::plural('company', $partnerCodeCount) }} · each code tracks the leads that arrive through its own link</p>
        </div>
        <div class="workspace-head-actions">
            <span class="audit-private-label">Super admin only</span>
            <a class="btn btn-outline" href="{{ route('crm.partner-codes.export') }}" data-native-navigation>⇩ <span>Export CSV</span></a>
            <a class="btn btn-primary" href="{{ $addUrl }}">＋ <span>Add new partner</span></a>
        </div>
    </div>

    @if($newPartnerCode)
        {{-- Straight after a create: the link is the whole point of the record,
             so it is put in front of the person who just made it. --}}
        <div class="partner-code-fresh">
            <div class="partner-code-fresh-copy">
                <strong>{{ $newPartnerCode->company_name }} is set up</strong>
                <span>Code {{ $newPartnerCode->code }} · notices go to {{ $newPartnerCode->email }}@if($newPartnerCode->account) · {{ $newPartnerCode->account->name }} can sign in with that address @endif</span>
            </div>
            <input class="control partner-code-fresh-url" type="text" readonly value="{{ $newPartnerCode->profilerUrl() }}" aria-label="Partner profiler link" onfocus="this.select()">
            <button class="btn btn-primary" type="button" data-copy-link="{{ $newPartnerCode->profilerUrl() }}">Copy link</button>
        </div>
    @endif

    <div class="partner-code-summary" aria-label="Partner code summary">
        <div><strong>{{ number_format($partnerCodeCount) }}</strong><span>Partner codes</span></div>
        <div><strong>{{ number_format($partnerCodeActiveCount) }}</strong><span>Live links</span></div>
        <div><strong>{{ number_format($partnerCodes->sum('leads_count')) }}</strong><span>Leads on this page</span></div>
    </div>

    <form id="crmPartnerCodeFilters" class="filters crm-partner-code-filters" method="get" action="{{ route('crm.dashboard') }}" data-crm-filter-form>
        <input type="hidden" name="view" value="partner-codes">
        <div class="search-wrap"><input class="control" type="search" name="partner_code_search" value="{{ request('partner_code_search') }}" placeholder="Search company, code, contact, email or partner"></div>
        <button class="btn btn-outline" type="submit">Filter</button>
    </form>

    @if($partnerCodes->count())
        @include('crm.partials.list-count', ['paginator' => $partnerCodes, 'filterForm' => 'crmPartnerCodeFilters', 'noun' => 'partner code'])
        <div class="table-wrap"><table class="partner-code-table">
            <thead><tr><th class="col-serial">Serial No</th><th>Company</th><th>Code &amp; link</th><th>Contact</th><th>Leads</th><th>Status</th><th>Manage</th></tr></thead>
            <tbody>
            @foreach($partnerCodes as $code)
                    <tr @class(['is-paused' => ! $code->is_active])>
                        <td class="col-serial">{{ $partnerCodes->firstItem() + $loop->index }}</td>
                        <td>
                            <strong>{{ $code->company_name }}</strong>
                            @if($code->company_link)
                                <span class="subtext"><a href="{{ $code->company_link }}" target="_blank" rel="noopener noreferrer">{{ \Illuminate\Support\Str::of($code->company_link)->after('//')->rtrim('/') }}</a></span>
                            @else
                                <span class="subtext">No company link</span>
                            @endif
                        </td>
                        <td>
                            <span class="partner-code-chip">{{ $code->code }}</span>
                            <span class="subtext partner-code-url">{{ $code->profilerUrl() }}</span>
                        </td>
                        <td>
                            <a href="mailto:{{ $code->email }}">{{ $code->email }}</a>
                            <span class="subtext">{{ collect([$code->contact_name, $code->phone])->filter()->implode(' · ') ?: 'No contact recorded' }}</span>
                            {{-- Which half of the partner exists. A code with an account
                                 behind it is a partner who can sign in and watch their
                                 own students; one without is a company we only track. --}}
                            @if($code->account)
                                <span class="subtext partner-code-account">
                                    <a href="{{ $accountUrl($code->account) }}">Signs in as {{ $code->account->name }}</a>
                                    · {{ $code->account->partnerAccessLabel() }}{{ $code->account->is_active ? '' : ' · sign-in disabled' }}
                                </span>
                            @else
                                <span class="subtext partner-code-account is-none">No workspace account</span>
                            @endif
                        </td>
                        <td>
                            @if($code->leads_count)
                                <a class="partner-code-leads" href="{{ route('crm.dashboard', ['view' => 'leads', 'partner_code_id' => $code->id]) }}">{{ number_format($code->leads_count) }} {{ \Illuminate\Support\Str::plural('lead', $code->leads_count) }}</a>
                            @else
                                <span class="subtext">None yet</span>
                            @endif
                        </td>
                        <td>
                            <span class="partner-code-status is-{{ $code->is_active ? 'ok' : 'paused' }}">{{ $code->is_active ? 'Live' : 'Paused' }}</span>
                            <span class="subtext">Added {{ $code->created_at->format('d M Y') }}</span>
                        </td>
                        <td><div class="partner-code-actions">
                            <button class="btn btn-outline btn-compact" type="button" data-copy-link="{{ $code->profilerUrl() }}">Copy link</button>
                            @if($code->account)<a class="btn btn-outline btn-compact" href="{{ $accountUrl($code->account) }}">Edit</a>@endif
                            <form method="post" action="{{ route('crm.partner-codes.toggle', $code) }}">@csrf @method('PATCH')<button class="btn btn-outline btn-compact" type="submit">{{ $code->is_active ? 'Pause' : 'Resume' }}</button></form>
                            {{-- Deleting is the one destructive action here, so it asks
                                 through the CRM's own confirm modal rather than the
                                 browser's, and says what happens to the leads — and, for
                                 a partner with a login, that the login is not touched. --}}
                            <form method="post" action="{{ route('crm.partner-codes.destroy', $code) }}"
                                  data-confirm-submit
                                  data-confirm-title="Remove {{ $code->company_name }}?"
                                  data-confirm-body="The {{ $code->leads_count }} lead{{ $code->leads_count === 1 ? '' : 's' }} this code brought in stay in the CRM, but stop naming a partner code — and the link stops working.@if($code->account) {{ $code->account->name }} keeps their account and can still sign in; close it on the Team screen.@endif Pause it instead to keep the record."
                                  data-confirm-accept="Yes, remove this partner">@csrf @method('DELETE')<button class="btn btn-danger btn-compact" type="submit">Delete</button></form>
                        </div></td>
                    </tr>
            @endforeach
            </tbody>
        </table></div>
        @if($partnerCodes->hasPages())<div class="pagination-wrap">{{ $partnerCodes->onEachSide(1)->links('pagination::crm') }}</div>@endif
    @else
        <div class="empty">
            <span class="empty-icon">◈</span>
            <h3>{{ request('partner_code_search') ? 'No partner code matches that search' : 'No partners yet' }}</h3>
            <p>{{ request('partner_code_search') ? 'Try a different company name, code, email or partner name.' : 'Add a partner and share the link it gives you. Their referrals then arrive tagged, they are emailed each one, and they can sign in to follow the students they sent.' }}</p>
            @unless(request('partner_code_search'))<a class="btn btn-primary" href="{{ $addUrl }}">＋ <span>Add new partner</span></a>@endunless
        </div>
    @endif
</section>

{{-- Copy-link clicks are handled by the delegated [data-copy-link] listener in
     crm.js. An inline <script> here would never run: the CRM swaps views in via
     DOMParser, whose scripts are inert. --}}
