{{-- The Partner code on a lead: shown, never chosen.

     Since partner accounts and codes were merged a partner IS a company with a
     code, so picking the Partner name beside this field is what sets it. Two
     dropdowns that could name two different companies was how a lead ended up
     credited to one partner and emailed to another.

     What it shows is a convenience for the person looking at it: the value is
     decided on the server, in CrmLeadController::partnerCodeFromPartner, so a
     save lands the two in step whether or not the browser kept up. crm.js moves
     both halves as the partner is picked, and empties them when the name is
     cleared.

     Inputs: $code (the code to show), $fieldErrors/$bag for the modal's own
     error bag. Never $errors: that name is Blade's own, and an include that
     reassigns it breaks every @error on the page. --}}
@php
    $code = $code ?? null;
    $bag = $bag ?? null;
    $fieldErrors = $fieldErrors ?? null;
@endphp
<div @class(['field', 'has-error' => $fieldErrors?->has('partner_code_id')])>
    <label for="lead_partner_code">Partner code <span class="label-note">Set by the partner named beside it</span></label>
    <input id="lead_partner_code" class="is-derived" type="text" readonly tabindex="-1"
        data-partner-code-display
        value="{{ $code?->label() ?: 'No partner code' }}"
        aria-describedby="lead_partner_code_note">
    <input type="hidden" name="partner_code_id" value="{{ $code?->id }}" data-partner-code-input>
    <span class="field-note" id="lead_partner_code_note">The referral link this lead came through.</span>
    @if($bag)
        @error('partner_code_id', $bag)<span class="field-error">{{ $message }}</span>@enderror
    @endif
</div>
