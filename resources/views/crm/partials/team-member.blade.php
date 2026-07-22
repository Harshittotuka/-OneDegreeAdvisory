<div class="team-member {{ $member->isSuperAdmin() ? 'role-super-admin' : 'role-counsellor' }} {{ $member->is_active ? '' : 'is-disabled' }}"
    data-team-member
    data-team-role="{{ $member->isSuperAdmin() ? 'super-admin' : 'counsellor' }}"
    data-team-status="{{ $member->is_active ? 'active' : 'disabled' }}"
    data-team-search="{{ strtolower($member->name.' '.$member->email.' '.$member->phone) }}">
    <span class="avatar">{{ $initials($member->name) }}</span>
    <span class="team-member-info">
        <span class="team-member-title"><strong>{{ $member->name }}</strong>@if($member->id === $crmUser->id)<em>You</em>@endif</span>
        <span class="team-member-role {{ $member->isSuperAdmin() ? 'is-super-admin' : 'is-counsellor' }}">
            @if($member->isSuperAdmin())
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 5 6v5c0 4.7 2.8 8.1 7 10 4.2-1.9 7-5.3 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4"/></svg>
                Super admin
            @else
                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"/><path d="M5.5 20c.6-4 2.7-6 6.5-6s5.9 2 6.5 6"/></svg>
                Counsellor
            @endif
        </span>
        <span class="team-member-contact">{{ $member->email ?: 'Email not added' }}</span>
        <span class="team-member-contact">+91 {{ $member->phone }}</span>
    </span>
    <span class="state {{ $member->is_active ? '' : 'off' }}">{{ $member->is_active ? 'Active' : 'Disabled' }}</span>
    @if($member->id !== $crmUser->id)
        <div class="team-member-actions">
            <form method="post" action="{{ route('crm.team.toggle',$member) }}" data-ajax-preserve-modal="teamModal">@csrf @method('PATCH')<button class="team-action-btn" type="submit" title="{{ $member->is_active ? 'Disable access' : 'Restore access' }}" aria-label="{{ $member->is_active ? 'Disable access' : 'Restore access' }}">{{ $member->is_active ? '⊘' : '↻' }}</button></form>
            <form method="post" action="{{ route('crm.team.destroy',$member) }}" data-ajax-preserve-modal="teamModal" onsubmit="return confirm('Permanently delete {{ addslashes($member->name) }} from the CRM? Any leads they own will become unassigned. This cannot be undone.')">@csrf @method('DELETE')<button class="team-action-btn is-danger" type="submit" title="Delete team member" aria-label="Delete team member"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 7h16M9 7V5a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2m2 0v12a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V7m4 4v6m4-6v6"/></svg></button></form>
        </div>
    @endif
    <details class="team-member-edit">
        <summary>Edit name or email</summary>
        <form method="post" action="{{ route('crm.team.update',$member) }}" data-ajax-preserve-modal="teamModal">@csrf @method('PATCH')
            <label><span>Name</span><input name="name" value="{{ $member->name }}" required></label>
            <label><span>Email address</span><input type="email" name="email" value="{{ $member->email }}" placeholder="name@example.com" required></label>
            <button class="btn btn-outline" type="submit">Save changes</button>
        </form>
    </details>
</div>
