@php
    // One slug per role, shared by the CSS hook and the filter chips.
    $roleSlug = $member->isSuperAdmin() ? 'super-admin' : ($member->isPartner() ? 'partner' : 'counsellor');
    $isSelected = ($teamMember?->id ?? null) === $member->id;
    // Keep whatever else is on the URL (search, filters) and just change who is open.
    $openUrl = request()->fullUrlWithQuery(['view' => 'team', 'member' => $member->id]);
    // A partner's link is half of what they are, so its state belongs in the list
    // rather than only inside the pane: a paused link is easy to forget about.
    $partnerCode = $member->isPartner() ? $member->partnerCode : null;
    // Built here rather than by stringing @if directives together: Blade skips a
    // directive glued to a word character, and the filter keeps the separators
    // right whichever of these a member actually has.
    $meta = collect([
        $member->roleLabel(),
        $member->isPartner() ? $member->partnerAccessLabel() : null,
        $partnerCode?->code,
    ])->filter()->implode(' · ');
    // A partner is found by their code or company as readily as by their name.
    $search = strtolower(collect([
        $member->name, $member->email, $member->phone, $partnerCode?->code, $partnerCode?->company_name,
    ])->filter()->implode(' '));
@endphp
<a class="team-row role-{{ $roleSlug }} {{ $isSelected ? 'is-selected' : '' }} {{ $member->is_active ? '' : 'is-disabled' }}"
    href="{{ $openUrl }}"
    @if($isSelected) aria-current="true" @endif
    data-team-member
    data-team-role="{{ $roleSlug }}"
    data-team-status="{{ $member->is_active ? 'active' : 'disabled' }}"
    data-team-search="{{ $search }}">
    <span class="avatar" aria-hidden="true">{{ $initials($member->name) }}</span>
    <span class="team-row-copy">
        <span class="team-row-name">{{ $member->name }}@if($member->id === $crmUser->id)<em>You</em>@endif</span>
        <span class="team-row-meta">{{ $meta }}</span>
    </span>
    @if($partnerCode && ! $partnerCode->is_active)<span class="team-row-off is-link">Link off</span>@endif
    @unless($member->is_active)<span class="team-row-off">Off</span>@endunless
</a>
