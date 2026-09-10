@php
    $roleSlug = $member->isSuperAdmin() ? 'super-admin' : ($member->isPartner() ? 'partner' : 'counsellor');
    $partnerLeadCount = $member->isPartner() ? ($member->partner_leads_count ?? $member->partnerLeads()->count()) : 0;
    $openUrl = request()->fullUrlWithQuery(['view' => 'team', 'member' => $member->id, 'add' => null]);
@endphp
{{-- A card is for scanning, not editing: it opens the detail pane, where all
     the controls live. It carries the same data-team-role / data-team-search
     hooks as its sidebar row so one filter pass drives both. --}}
<a class="team-card role-{{ $roleSlug }} {{ $member->is_active ? '' : 'is-disabled' }}"
    href="{{ $openUrl }}"
    data-team-card
    data-team-role="{{ $roleSlug }}"
    data-team-search="{{ strtolower($member->name.' '.$member->email.' '.$member->phone) }}">
    <span class="team-card-top">
        <span class="avatar" aria-hidden="true">{{ $initials($member->name) }}</span>
        <span class="team-role-tag is-{{ $roleSlug }}">{{ $member->roleLabel() }}</span>
    </span>
    <span class="team-card-name">{{ $member->name }}@if($member->id === $crmUser->id)<em>You</em>@endif</span>
    <span class="team-card-contact">{{ $member->email ?: 'No email' }}</span>
    <span class="team-card-contact">+91 {{ $member->phone }}</span>
    <span class="team-card-foot">
        <span class="state {{ $member->is_active ? '' : 'off' }}">{{ $member->is_active ? 'Active' : 'Disabled' }}</span>
        @if($member->isPartner())<span class="team-card-note">{{ $member->partnerAccessLabel() }} · {{ $partnerLeadCount }} student{{ $partnerLeadCount === 1 ? '' : 's' }}</span>@endif
        <span class="team-card-open" aria-hidden="true">Open →</span>
    </span>
</a>
