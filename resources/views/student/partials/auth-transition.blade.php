{{-- The hand-off screen crm.js shows while a sign-in form submits
     (data-transition-form). crm.js looks it up by #transitionScreen and
     writes the form's label into [data-transition-copy]. --}}
<div class="transition-screen" id="transitionScreen" aria-hidden="true">
    <div class="transition-card">
        <span class="transition-rings" aria-hidden="true"><i></i><i></i><i></i></span>
        <span class="transition-logo"><img src="{{ asset('assets/Logo/mark-light.svg') }}" alt=""></span>
        <strong data-transition-copy>{{ $label }}</strong>
        <small>One Degree student portal</small>
    </div>
</div>
<script src="{{ asset('assets/crm/crm.js') }}?v={{ filemtime(public_path('assets/crm/crm.js')) }}" defer></script>
