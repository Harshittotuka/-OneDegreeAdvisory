@php
    $roleSlug = $member->isSuperAdmin() ? 'super-admin' : ($member->isPartner() ? 'partner' : 'counsellor');
    $isSelf = $member->id === $crmUser->id;
    $partnerLeadCount = $member->isPartner() ? ($member->partner_leads_count ?? $member->partnerLeads()->count()) : 0;
    // Partner never appears alongside the in-house roles: an account cannot cross
    // between them (CrmUserController::roleChangeError explains why), so offering
    // the move in the dropdown would only produce an error on save.
    $roleChoices = $member->isPartner()
        ? ['partner' => \App\Support\CrmOptions::ROLES['partner']]
        : ['counsellor' => \App\Support\CrmOptions::ROLES['counsellor'], 'super_admin' => \App\Support\CrmOptions::ROLES['super_admin']];
    // The company half of a partner, edited in the same form as the account so
    // there is one place to keep a partner right. Null on a partner created
    // before the two were joined: the fields show empty, and entering a company
    // and code is what creates the record (CrmUserController::syncPartnerCode).
    $partnerCode = $member->isPartner() ? $member->partnerCode : null;
    // Counted once for the pane: the controller loads it with the relation.
    $linkLeads = $partnerCode?->leads_count ?? 0;
@endphp
<div class="team-detail">
    <a class="team-back" href="{{ $browseUrl ?? request()->fullUrlWithQuery(['view' => 'team', 'member' => null, 'add' => null]) }}">← All accounts</a>
    <header class="team-detail-head">
        <span class="avatar role-{{ $roleSlug }}" aria-hidden="true">{{ $initials($member->name) }}</span>
        <div class="team-detail-id">
            <h3>{{ $member->name }}@if($isSelf)<em>You</em>@endif</h3>
            <p>
                <span class="team-role-tag is-{{ $roleSlug }}">{{ $member->roleLabel() }}</span>
                <span class="team-detail-state {{ $member->is_active ? '' : 'is-off' }}">{{ $member->is_active ? 'Can sign in' : 'Sign-in disabled' }}</span>
                @if($member->isPartner())<span class="team-detail-count">{{ $partnerLeadCount }} student{{ $partnerLeadCount === 1 ? '' : 's' }}</span>@endif
            </p>
        </div>
    </header>

    {{-- One form, one Save. Name, role and partner access used to be three
         separate posts with three separate buttons on the same card. --}}
    <form class="team-detail-form" method="post" action="{{ route('crm.team.update', $member) }}">@csrf @method('PATCH')
        <div class="form-grid">
            @include('crm.partials.partner-fields', [
                'prefix' => 'team'.$member->id, 'parts' => ['identity'], 'account' => $member,
            ])

            <div class="field full">
                <label for="team_role_{{ $member->id }}">Access level</label>
                <select id="team_role_{{ $member->id }}" name="role" data-team-role-select @disabled($isSelf) required>
                    @foreach($roleChoices as $key => $label)<option value="{{ $key }}" @selected($member->role === $key)>{{ $label }}</option>@endforeach
                </select>
                @if($isSelf)
                    <span class="field-note">You cannot change your own access level. Another super admin can.</span>
                    <input type="hidden" name="role" value="{{ $member->role }}">
                @elseif($member->isPartner())
                    <span class="field-note">A partner cannot be moved to an in-house role.</span>
                @endif
            </div>

            @if($member->isPartner())
                @include('crm.partials.partner-fields', [
                    'prefix' => 'team'.$member->id,
                    'parts' => ['access', 'company'],
                    'account' => $member,
                    'code' => $partnerCode,
                    // A partner created before accounts and codes were joined has
                    // no company yet; entering one is what creates it, so nothing
                    // here is required until it exists.
                    'companyRequired' => (bool) $partnerCode,
                    'companyLead' => $partnerCode
                        ? 'Their tracking link. Referral notices go to the email address above — change it there and this follows.'
                        : 'This partner has no tracking link yet. Enter a company and a code to give them one; until then their referrals arrive without naming them.',
                ])
            @endif
        </div>
        <div class="team-detail-save">
            <button class="btn btn-primary" type="submit">Save changes</button>
            <span class="team-detail-hint">{{ $member->isPartner()
                ? 'Name, access, company and code all save together.'
                : 'Name and access level save together.' }}</span>
        </div>
    </form>

    @if($partnerCode)
        {{-- The link's own controls. They sit apart from the save above for the
             same reason the account's do — pausing a link is a decision about the
             arrangement, not an edit to a company name — and apart from the
             account's because they are two different things to end: a partner can
             keep their workspace after we stop crediting their referrals, and can
             keep referring after we close their login. Same endpoints the Partner
             codes tab posts to, so the two screens cannot behave differently. --}}
        <div class="team-detail-danger team-detail-link-actions">
            <div class="team-danger-row">
                <div>
                    <strong>{{ $partnerCode->is_active ? 'Pause their link' : 'Resume their link' }}</strong>
                    <small>{{ $partnerCode->is_active
                        ? 'Submissions carrying '.$partnerCode->code.' stop naming '.$partnerCode->company_name.' and stop emailing them. The leads it already brought in keep their attribution.'
                        : $partnerCode->company_name.' is credited again for anything arriving through '.$partnerCode->code.', and is emailed each one.' }}</small>
                </div>
                <form method="post" action="{{ route('crm.partner-codes.toggle', $partnerCode) }}">@csrf @method('PATCH')
                    <button class="btn btn-outline" type="submit">{{ $partnerCode->is_active ? 'Pause link' : 'Resume link' }}</button>
                </form>
            </div>
            <div class="team-danger-row">
                <div>
                    <strong>Remove their link</strong>
                    <small>{{ $member->name }} keeps their account and can still sign in — only the tracking link goes. Pausing keeps the record; this is the clean removal for a company we never worked with.</small>
                </div>
                <form method="post" action="{{ route('crm.partner-codes.destroy', $partnerCode) }}"
                    data-confirm-submit
                    data-confirm-title="Remove {{ $partnerCode->company_name }}&rsquo;s link?"
                    data-confirm-body="The {{ $linkLeads }} lead(s) this code brought in stay in the CRM, but stop naming a partner code — and the link stops working. {{ $member->name }} keeps their account and can still sign in. Pause it instead to keep the record."
                    data-confirm-accept="Yes, remove this link">@csrf @method('DELETE')
                    <button class="btn btn-danger" type="submit">Delete link</button>
                </form>
            </div>
        </div>
    @endif

    @unless($isSelf)
        {{-- Destructive actions stay out of the save above: they should never
             ride along with a rename. --}}
        <div class="team-detail-danger">
            <div class="team-danger-row">
                <div>
                    <strong>{{ $member->is_active ? 'Disable sign-in' : 'Restore sign-in' }}</strong>
                    <small>{{ $member->is_active
                        ? 'Keeps the account and everything it owns, but blocks the OTP login.'
                        : 'Lets '.$member->name.' sign in again with their mobile or email.' }}</small>
                </div>
                <form method="post" action="{{ route('crm.team.toggle', $member) }}">@csrf @method('PATCH')
                    <button class="btn btn-outline" type="submit">{{ $member->is_active ? 'Disable' : 'Restore' }}</button>
                </form>
            </div>
            <div class="team-danger-row">
                <div>
                    <strong>Delete account</strong>
                    <small>{{ $member->isPartner()
                        ? 'Their students stay in the CRM and simply stop naming a partner.'.($partnerCode ? ' Their link is paused rather than deleted, so its referrals keep their attribution.' : '')
                        : 'Any leads they own become unassigned.' }} This cannot be undone.</small>
                </div>
                <form method="post" action="{{ route('crm.team.destroy', $member) }}"
                    onsubmit="return confirm('Permanently delete {{ addslashes($member->name) }} from the CRM? {{ $member->isPartner() ? 'Their students stay in the CRM and simply stop naming a partner.' : 'Any leads they own will become unassigned.' }} This cannot be undone.')">@csrf @method('DELETE')
                    <button class="btn btn-danger" type="submit">Delete</button>
                </form>
            </div>
        </div>
    @endunless
</div>
