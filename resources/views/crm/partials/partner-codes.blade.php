{{-- Partner codes: the referral companies whose code travels in a public link.

     One screen, three parts, in the order the job is done: the create form,
     then the summary, then the list. Each row shows the link to hand out and the
     number of leads that came in through it, so "is this partner working?" is
     answered where the code is managed rather than on the leads list.

     Editing happens in place — ?edit=<id> swaps that row's cells for the same
     fields as the create form. It travels in the URL for the same reason the
     Team screen's detail pane does: it keeps a code linkable and leaves the
     choice of what is open out of JavaScript.

     Super admin only, enforced in CrmDashboardController::index and again in
     CrmPartnerCodeController::guard. --}}
@php
    $editingId = request()->integer('edit') ?: null;
    $editing = $editingId ? $partnerCodes->firstWhere('id', $editingId) : null;
    $listUrl = request()->fullUrlWithQuery(['view' => 'partner-codes', 'edit' => null]);
    // Old input is only ever meant for the form that was submitted: without this
    // a failed create would also refill the row being edited, and vice versa.
    $creating = ! $editing;
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
        </div>
    </div>

    @if($newPartnerCode)
        {{-- Straight after a create: the link is the whole point of the record,
             so it is put in front of the person who just made it. --}}
        <div class="partner-code-fresh">
            <div class="partner-code-fresh-copy">
                <strong>{{ $newPartnerCode->company_name }} is set up</strong>
                <span>Code {{ $newPartnerCode->code }} · notices go to {{ $newPartnerCode->email }}</span>
            </div>
            <input class="control partner-code-fresh-url" type="text" readonly value="{{ $newPartnerCode->profilerUrl() }}" aria-label="Partner profiler link" onfocus="this.select()">
            <button class="btn btn-primary" type="button" data-copy-link="{{ $newPartnerCode->profilerUrl() }}">Copy link</button>
        </div>
    @endif

    @unless($editing)
        <form class="partner-code-create" method="post" action="{{ route('crm.partner-codes.store') }}">@csrf
            <div class="partner-code-create-head">
                <h3>Add a partner</h3>
                <p>The code becomes their tracking link — <code>{{ url('/profiler') }}?partner=CODE</code>. Every profiler submitted through it is emailed to the company as well as to us, and the lead is recorded against them.</p>
            </div>
            <div class="form-grid">
                <div @class(['field', 'has-error' => $creating && $errors->has('company_name')])>
                    <label for="pc_company">Company name <span class="required">*</span></label>
                    <input id="pc_company" name="company_name" value="{{ $creating ? old('company_name') : '' }}" maxlength="150" required>
                    @if($creating)@error('company_name')<span class="field-error">{{ $message }}</span>@enderror @endif
                </div>
                <div @class(['field', 'has-error' => $creating && $errors->has('code')])>
                    <label for="pc_code">Custom code <span class="required">*</span></label>
                    <input id="pc_code" name="code" value="{{ $creating ? old('code') : '' }}" maxlength="40" placeholder="ACME10" autocapitalize="characters" spellcheck="false" required>
                    <span class="field-help">Letters, numbers, hyphens and underscores. Stored in capitals and matched either way, so <code>acme10</code> and <code>ACME10</code> are one code.</span>
                    @if($creating)@error('code')<span class="field-error">{{ $message }}</span>@enderror @endif
                </div>
                <div @class(['field', 'has-error' => $creating && $errors->has('email')])>
                    <label for="pc_email">Email <span class="required">*</span></label>
                    <input id="pc_email" name="email" type="email" value="{{ $creating ? old('email') : '' }}" maxlength="190" placeholder="referrals@company.com" required>
                    <span class="field-help">Where every referral notice for this code is sent.</span>
                    @if($creating)@error('email')<span class="field-error">{{ $message }}</span>@enderror @endif
                </div>
                <div @class(['field', 'has-error' => $creating && $errors->has('contact_name')])>
                    <label for="pc_contact">Contact name <span class="field-optional">optional</span></label>
                    <input id="pc_contact" name="contact_name" value="{{ $creating ? old('contact_name') : '' }}" maxlength="120">
                    @if($creating)@error('contact_name')<span class="field-error">{{ $message }}</span>@enderror @endif
                </div>
                <div @class(['field', 'has-error' => $creating && $errors->has('phone')])>
                    <label for="pc_phone">Phone number <span class="field-optional">optional</span></label>
                    <input id="pc_phone" name="phone" value="{{ $creating ? old('phone') : '' }}" inputmode="tel" maxlength="30" placeholder="+91 98765 43210">
                    @if($creating)@error('phone')<span class="field-error">{{ $message }}</span>@enderror @endif
                </div>
                <div @class(['field', 'has-error' => $creating && $errors->has('company_link')])>
                    <label for="pc_link">Company link <span class="field-optional">optional</span></label>
                    <input id="pc_link" name="company_link" type="url" value="{{ $creating ? old('company_link') : '' }}" maxlength="255" placeholder="https://company.com">
                    @if($creating)@error('company_link')<span class="field-error">{{ $message }}</span>@enderror @endif
                </div>
            </div>
            <div class="partner-code-create-foot">
                <span class="field-help">Nothing is emailed to the company when you add them — the first notice they get is a real referral.</span>
                <button class="btn btn-primary" type="submit">Add partner code</button>
            </div>
        </form>
    @endunless

    <div class="partner-code-summary" aria-label="Partner code summary">
        <div><strong>{{ number_format($partnerCodeCount) }}</strong><span>Partner codes</span></div>
        <div><strong>{{ number_format($partnerCodeActiveCount) }}</strong><span>Live links</span></div>
        <div><strong>{{ number_format($partnerCodes->sum('leads_count')) }}</strong><span>Leads on this page</span></div>
    </div>

    <form id="crmPartnerCodeFilters" class="filters crm-partner-code-filters" method="get" action="{{ route('crm.dashboard') }}" data-crm-filter-form>
        <input type="hidden" name="view" value="partner-codes">
        <div class="search-wrap"><input class="control" type="search" name="partner_code_search" value="{{ request('partner_code_search') }}" placeholder="Search company, code, contact or email"></div>
        <button class="btn btn-outline" type="submit">Filter</button>
    </form>

    @if($partnerCodes->count())
        @include('crm.partials.list-count', ['paginator' => $partnerCodes, 'filterForm' => 'crmPartnerCodeFilters', 'noun' => 'partner code'])
        <div class="table-wrap"><table class="partner-code-table">
            <thead><tr><th class="col-serial">Serial No</th><th>Company</th><th>Code &amp; link</th><th>Contact</th><th>Leads</th><th>Status</th><th>Manage</th></tr></thead>
            <tbody>
            @foreach($partnerCodes as $code)
                @if($editing && $editing->id === $code->id)
                    <tr class="is-editing">
                        <td class="col-serial">{{ $partnerCodes->firstItem() + $loop->index }}</td>
                        <td colspan="6">
                            <form class="partner-code-edit" method="post" action="{{ route('crm.partner-codes.update', $code) }}">@csrf @method('PUT')
                                <div class="partner-code-edit-head">
                                    <strong>Editing {{ $code->company_name }}</strong>
                                    <a class="partner-code-cancel" href="{{ $listUrl }}">Cancel</a>
                                </div>
                                <div class="form-grid">
                                    <div @class(['field', 'has-error' => $errors->has('company_name')])>
                                        <label for="pc_edit_company">Company name <span class="required">*</span></label>
                                        <input id="pc_edit_company" name="company_name" value="{{ old('company_name', $code->company_name) }}" maxlength="150" required>
                                        @error('company_name')<span class="field-error">{{ $message }}</span>@enderror
                                    </div>
                                    <div @class(['field', 'has-error' => $errors->has('code')])>
                                        <label for="pc_edit_code">Custom code <span class="required">*</span></label>
                                        <input id="pc_edit_code" name="code" value="{{ old('code', $code->code) }}" maxlength="40" autocapitalize="characters" spellcheck="false" required>
                                        <span class="field-help">Changing this retires the old link — anything already shared with it stops being credited.</span>
                                        @error('code')<span class="field-error">{{ $message }}</span>@enderror
                                    </div>
                                    <div @class(['field', 'has-error' => $errors->has('email')])>
                                        <label for="pc_edit_email">Email <span class="required">*</span></label>
                                        <input id="pc_edit_email" name="email" type="email" value="{{ old('email', $code->email) }}" maxlength="190" required>
                                        @error('email')<span class="field-error">{{ $message }}</span>@enderror
                                    </div>
                                    <div @class(['field', 'has-error' => $errors->has('contact_name')])>
                                        <label for="pc_edit_contact">Contact name <span class="field-optional">optional</span></label>
                                        <input id="pc_edit_contact" name="contact_name" value="{{ old('contact_name', $code->contact_name) }}" maxlength="120">
                                        @error('contact_name')<span class="field-error">{{ $message }}</span>@enderror
                                    </div>
                                    <div @class(['field', 'has-error' => $errors->has('phone')])>
                                        <label for="pc_edit_phone">Phone number <span class="field-optional">optional</span></label>
                                        <input id="pc_edit_phone" name="phone" value="{{ old('phone', $code->phone) }}" inputmode="tel" maxlength="30">
                                        @error('phone')<span class="field-error">{{ $message }}</span>@enderror
                                    </div>
                                    <div @class(['field', 'has-error' => $errors->has('company_link')])>
                                        <label for="pc_edit_link">Company link <span class="field-optional">optional</span></label>
                                        <input id="pc_edit_link" name="company_link" type="url" value="{{ old('company_link', $code->company_link) }}" maxlength="255" placeholder="https://company.com">
                                        @error('company_link')<span class="field-error">{{ $message }}</span>@enderror
                                    </div>
                                </div>
                                <div class="partner-code-edit-foot">
                                    <button class="btn btn-primary" type="submit">Save changes</button>
                                    <a class="btn btn-outline" href="{{ $listUrl }}">Discard</a>
                                </div>
                            </form>
                        </td>
                    </tr>
                @else
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
                            <a class="btn btn-outline btn-compact" href="{{ request()->fullUrlWithQuery(['view' => 'partner-codes', 'edit' => $code->id]) }}">Edit</a>
                            <form method="post" action="{{ route('crm.partner-codes.toggle', $code) }}">@csrf @method('PATCH')<button class="btn btn-outline btn-compact" type="submit">{{ $code->is_active ? 'Pause' : 'Resume' }}</button></form>
                            {{-- Deleting is the one destructive action here, so it asks
                                 through the CRM's own confirm modal rather than the
                                 browser's, and says what happens to the leads. --}}
                            <form method="post" action="{{ route('crm.partner-codes.destroy', $code) }}"
                                  data-confirm-submit
                                  data-confirm-title="Remove {{ $code->company_name }}?"
                                  data-confirm-body="The {{ $code->leads_count }} lead{{ $code->leads_count === 1 ? '' : 's' }} this code brought in stay in the CRM, but stop naming a partner code — and the link stops working. Pause it instead to keep the record."
                                  data-confirm-accept="Yes, remove this partner">@csrf @method('DELETE')<button class="btn btn-danger btn-compact" type="submit">Delete</button></form>
                        </div></td>
                    </tr>
                @endif
            @endforeach
            </tbody>
        </table></div>
        @if($partnerCodes->hasPages())<div class="pagination-wrap">{{ $partnerCodes->onEachSide(1)->links('pagination::crm') }}</div>@endif
    @else
        <div class="empty">
            <span class="empty-icon">◈</span>
            <h3>{{ request('partner_code_search') ? 'No partner code matches that search' : 'No partner codes yet' }}</h3>
            <p>{{ request('partner_code_search') ? 'Try a different company name, code or email.' : 'Add a company above and share the link it gives you. Their referrals then arrive tagged, and they are emailed each one.' }}</p>
        </div>
    @endif
</section>

{{-- Copy-link clicks are handled by the delegated [data-copy-link] listener in
     crm.js. An inline <script> here would never run: the CRM swaps views in via
     DOMParser, whose scripts are inert. --}}
