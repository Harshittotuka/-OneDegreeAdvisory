@php
    // One slug per role, shared by the CSS hook and the filter chips.
    $roleSlug = $member->isSuperAdmin() ? 'super-admin' : ($member->isPartner() ? 'partner' : 'counsellor');
    $isSelected = ($teamMember?->id ?? null) === $member->id;
    // Keep whatever else is on the URL (search, filters) and just change who is open.
    $openUrl = request()->fullUrlWithQuery(['view' => 'team', 'member' => $member->id]);
@endphp
<a class="team-row role-{{ $roleSlug }} {{ $isSelected ? 'is-selected' : '' }} {{ $member->is_active ? '' : 'is-disabled' }}"
    href="{{ $openUrl }}"
    @if($isSelected) aria-current="true" @endif
    data-team-member
    data-team-role="{{ $roleSlug }}"
    data-team-status="{{ $member->is_active ? 'active' : 'disabled' }}"
    data-team-search="{{ strtolower($member->name.' '.$member->email.' '.$member->phone) }}">
    <span class="avatar" aria-hidden="true">{{ $initials($member->name) }}</span>
    <span class="team-row-copy">
        <span class="team-row-name">{{ $member->name }}@if($member->id === $crmUser->id)<em>You</em>@endif</span>
        <span class="team-row-meta">{{ $member->roleLabel() }}@if($member->isPartner()) · {{ $member->partnerAccessLabel() }}@endif</span>
    </span>
    @unless($member->is_active)<span class="team-row-off">Off</span>@endunless
</a>
