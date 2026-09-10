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
            <div class="field"><label for="team_name_{{ $member->id }}">Full name</label><input id="team_name_{{ $member->id }}" name="name" value="{{ $member->name }}" required></div>
            <div class="field"><label for="team_phone_{{ $member->id }}">Mobile number</label><input id="team_phone_{{ $member->id }}" name="phone" value="{{ $member->phone }}" inputmode="tel" placeholder="98765 43210" required></div>
            <div class="field full"><label for="team_email_{{ $member->id }}">Email address</label><input id="team_email_{{ $member->id }}" type="email" name="email" value="{{ $member->email }}" placeholder="name@domain.com" required></div>

            <div class="field {{ $member->isPartner() ? '' : 'full' }}">
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
                <div class="field" data-partner-access-field>
                    <label for="team_access_{{ $member->id }}">Partner access</label>
                    <select id="team_access_{{ $member->id }}" name="partner_access">
                        @foreach(\App\Support\CrmOptions::PARTNER_ACCESS as $key => $label)<option value="{{ $key }}" @selected(($member->partner_access ?: 'read') === $key)>{{ $label }}</option>@endforeach
                    </select>
                    <span class="field-note">Either way they can never change the Partner field, enrol a student or see the payment log.</span>
                </div>
            @endif
        </div>
        <div class="team-detail-save">
            <button class="btn btn-primary" type="submit">Save changes</button>
            <span class="team-detail-hint">Name, access level and partner access all save together.</span>
        </div>
    </form>

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
                        ? 'Their students stay in the CRM and simply stop naming a partner.'
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
