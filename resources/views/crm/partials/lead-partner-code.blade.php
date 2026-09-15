{{-- The Partner code on a lead: shown, never chosen.

     Since partner accounts and codes were merged a partner IS a company with a
     code, so picking the Partner name beside this field is what sets it. Two
     dropdowns that could name two different companies was how a lead ended up
     credited to one partner and emailed to another.

     It stays a posted value rather than something the server derives, so a lead
     the form never touched keeps exactly the code capture gave it. crm.js swaps
     both halves when the partner changes, and putting the partner back to "No
     partner" restores the captured code rather than dropping it — where a lead
     came from is a record, not a preference.

     Inputs: $code (the code to show now), $captured (the code the lead arrived
     with, restored when the partner is cleared), $fieldErrors/$bag for the
     modal's own error bag. Never $errors: that name is Blade's own, and an
     include that reassigns it breaks every @error on the page. --}}
@php
    $code = $code ?? null;
    $captured = $captured ?? null;
    $bag = $bag ?? null;
    $fieldErrors = $fieldErrors ?? null;
@endphp
<div @class(['field', 'has-error' => $fieldErrors?->has('partner_code_id')])>
    <label for="lead_partner_code">Partner code <span class="label-note">Set by the partner named beside it</span></label>
    <input id="lead_partner_code" class="is-derived" type="text" readonly tabindex="-1"
        data-partner-code-display
        value="{{ $code?->label() ?: 'No partner code' }}"
        aria-describedby="lead_partner_code_note">
    <input type="hidden" name="partner_code_id" value="{{ $code?->id }}"
        data-partner-code-input
        data-captured-id="{{ $captured?->id }}"
        data-captured-label="{{ $captured?->label() }}">
    <span class="field-note" id="lead_partner_code_note">The referral link this lead came through.</span>
    @if($bag)
        @error('partner_code_id', $bag)<span class="field-error">{{ $message }}</span>@enderror
    @endif
</div>
