@php
    $successMessage = $successMessage ?? null;
    $errorMessage = $errorMessage ?? null;
    $infoMessage = $infoMessage ?? null;
@endphp
<div class="crm-toast-stack" data-crm-toast-stack aria-live="polite" aria-atomic="false">
    @if($successMessage)
        <div class="crm-toast is-success" data-toast role="status">
            <span class="crm-toast-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="m7 12 3 3 7-7"/></svg></span>
            <span class="crm-toast-copy"><strong>Update complete</strong><span>{{ $successMessage }}</span></span>
            <button type="button" data-toast-close aria-label="Dismiss notification">×</button>
            <i class="crm-toast-progress" aria-hidden="true"></i>
        </div>
    @endif
    @if($errorMessage)
        <div class="crm-toast is-error" data-toast role="alert">
            <span class="crm-toast-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 8v5M12 17h.01"/><circle cx="12" cy="12" r="9"/></svg></span>
            <span class="crm-toast-copy"><strong>Action needs attention</strong><span>{{ $errorMessage }}</span></span>
            <button type="button" data-toast-close aria-label="Dismiss notification">×</button>
            <i class="crm-toast-progress" aria-hidden="true"></i>
        </div>
    @endif
    @if($infoMessage)
        <div class="crm-toast is-info" data-toast role="status">
            <span class="crm-toast-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M12 11v6M12 7h.01"/><circle cx="12" cy="12" r="9"/></svg></span>
            <span class="crm-toast-copy"><strong>For your information</strong><span>{{ $infoMessage }}</span></span>
            <button type="button" data-toast-close aria-label="Dismiss notification">×</button>
            <i class="crm-toast-progress" aria-hidden="true"></i>
        </div>
    @endif
</div>
