{{-- The fields that describe a partner, written once.

     A partner is created on the Team screen and edited on that account's own
     pane, and when each spelled its fields out for itself they drifted:
     different labels, required marks on one and not the other. Both include
     this partial, so the two forms cannot disagree about what a partner is.

     Three parts, requested with $parts:

       identity → Full name, Mobile number, Email address (the person)
       access   → the read / edit switch (only meaningful with a login)
       company  → Referral company, Custom code, Company link (the link)

     Values come from the account, and from the code for the company half. The
     create form passes neither, so every value falls back to old input.

     Inputs: $prefix (id namespace), $parts, $account, $code, $hidden (the
     partner-only parts start folded away until the role says partner),
     $companyRequired, $companyLead. --}}
@php
    $prefix = $prefix ?? 'partner';
    $parts = $parts ?? ['identity', 'access', 'company'];
    $account = $account ?? null;
    $code = $code ?? null;
    $hidden = $hidden ?? false;
    $companyRequired = $companyRequired ?? true;
    $companyLead = $companyLead ?? 'The code becomes their tracking link — '.e(url('/profiler')).'?partner=CODE. Every profiler submitted through it is emailed to them as well as to us, and the lead is recorded against them.';

    $fieldId = fn (string $field): string => $prefix.'_'.$field;
    // A hidden required field blocks the submit with a message the browser
    // cannot show anywhere, so the mark only goes on while the fields are up.
    // crm.js puts it back when the role changes (see [data-partner-required]).
    $markCompanyRequired = $companyRequired && ! $hidden;

    $nameValue = old('name', $account?->name);
    $phoneValue = old('phone', $account?->phone);
    $emailValue = old('email', $account?->email);
    $accessValue = old('partner_access', $account?->partner_access ?: 'read');
    $companyValue = old('company_name', $code?->company_name);
    $codeValue = old('code', $code?->code);
    $linkValue = old('company_link', $code?->company_link);
@endphp

@if(in_array('identity', $parts, true))
    <div @class(['field', 'has-error' => $errors->has('name')])>
        <label for="{{ $fieldId('name') }}">Full name <span class="required">*</span></label>
        <input id="{{ $fieldId('name') }}" name="name" value="{{ $nameValue }}" maxlength="120" required>
        @error('name')<span class="field-error">{{ $message }}</span>@enderror
    </div>
    <div @class(['field', 'has-error' => $errors->has('phone')])>
        <label for="{{ $fieldId('phone') }}">Mobile number <span class="required">*</span></label>
        <input id="{{ $fieldId('phone') }}" name="phone" value="{{ $phoneValue }}" inputmode="tel" maxlength="30" placeholder="98765 43210" required>
        @error('phone')<span class="field-error">{{ $message }}</span>@enderror
    </div>
    <div @class(['field', 'full', 'has-error' => $errors->has('email')])>
        <label for="{{ $fieldId('email') }}">Email address <span class="required">*</span></label>
        <input id="{{ $fieldId('email') }}" name="email" type="email" value="{{ $emailValue }}" maxlength="190" placeholder="name@domain.com" required>
        <span class="field-note">They sign in with this address, and every referral notice for their code is sent to it.</span>
        @error('email')<span class="field-error">{{ $message }}</span>@enderror
    </div>
@endif

@if(in_array('access', $parts, true))
    {{-- Only meaningful on a partner, so it appears with the role and is cleared
         away with it. The server excludes the value for any other role rather
         than trusting this to be right (CrmUserController::store). --}}
    <div @class(['field', 'full', 'has-error' => $errors->has('partner_access')])
         data-partner-access-field @if($hidden) hidden @endif>
        <label for="{{ $fieldId('access') }}">Partner access <span class="required">*</span></label>
        <select id="{{ $fieldId('access') }}" name="partner_access">
            @foreach(\App\Support\CrmOptions::PARTNER_ACCESS as $key => $label)
                <option value="{{ $key }}" @selected($accessValue === $key)>{{ $label }} — {{ $key === 'read' ? 'can follow their students' : 'can also update their students' }}</option>
            @endforeach
        </select>
        <span class="field-note">Either way they can never change the Partner field, enrol a student or see the payment log.</span>
        @error('partner_access')<span class="field-error">{{ $message }}</span>@enderror
    </div>
@endif

@if(in_array('company', $parts, true))
    <div class="field full team-partner-lead-in" data-partner-fields @if($hidden) hidden @endif>
        <h4>Referral company</h4>
        <p>{!! $companyLead !!}</p>
    </div>
    <div @class(['field', 'has-error' => $errors->has('company_name')]) data-partner-fields @if($hidden) hidden @endif>
        <label for="{{ $fieldId('company') }}">Company name @if($companyRequired)<span class="required">*</span>@else<span class="field-optional">optional</span>@endif</label>
        <input id="{{ $fieldId('company') }}" name="company_name" value="{{ $companyValue }}" maxlength="150" data-partner-required @required($markCompanyRequired)>
        @error('company_name')<span class="field-error">{{ $message }}</span>@enderror
    </div>
    <div @class(['field', 'has-error' => $errors->has('code')]) data-partner-fields @if($hidden) hidden @endif>
        <label for="{{ $fieldId('code') }}">Custom code @if($companyRequired)<span class="required">*</span>@else<span class="field-optional">optional</span>@endif</label>
        <input id="{{ $fieldId('code') }}" name="code" value="{{ $codeValue }}" maxlength="40" placeholder="ACME10" autocapitalize="characters" spellcheck="false" data-partner-required @required($markCompanyRequired)>
        <span class="field-note">{{ $code
            ? 'Changing this retires the old link — anything already shared with it stops being credited.'
            : 'Letters, numbers, hyphens and underscores. Stored in capitals and matched either way, so acme10 and ACME10 are one code.' }}</span>
        @error('code')<span class="field-error">{{ $message }}</span>@enderror
    </div>
    <div @class(['field', 'full', 'has-error' => $errors->has('company_link')]) data-partner-fields @if($hidden) hidden @endif>
        <label for="{{ $fieldId('link') }}">Company link <span class="field-optional">optional</span></label>
        <input id="{{ $fieldId('link') }}" name="company_link" type="url" value="{{ $linkValue }}" maxlength="255" placeholder="https://company.com">
        <span class="field-note">Their website, shown beside the company wherever the partner is listed.</span>
        @error('company_link')<span class="field-error">{{ $message }}</span>@enderror
    </div>

    @if($code)
        {{-- The link itself, wherever the partner is open: copying it is the most
             common reason anyone comes to one of these forms. --}}
        <div class="field full team-partner-link" data-partner-fields @if($hidden) hidden @endif>
            <span class="field-label-plain">Their link</span>
            <div class="team-partner-link-row">
                <input class="control" type="text" readonly value="{{ $code->profilerUrl() }}" aria-label="Partner profiler link" onfocus="this.select()">
                <button class="btn btn-outline" type="button" data-copy-link="{{ $code->profilerUrl() }}">Copy link</button>
            </div>
            <span class="field-note team-partner-link-state">
                {{-- The same chip the Partner codes tab uses, so "is this link
                     working?" reads identically on both screens. --}}
                <span class="partner-code-status is-{{ $code->is_active ? 'ok' : 'paused' }}">{{ $code->is_active ? 'Live' : 'Paused' }}</span>
                {{-- Counted by the query that loaded the code, never here: a
                     view that counts is a view that queries once per row. --}}
                <span>{{ $code->leads_count ?? 0 }} lead{{ ($code->leads_count ?? 0) === 1 ? '' : 's' }} through it</span>
                {{-- A directive glued to a word is not a directive: Blade needs a
                     non-word character before the @, so this one gets its own tag. --}}
                @unless($code->is_active)<span>New submissions carrying {{ $code->code }} are recorded without a partner.</span>@endunless
            </span>
        </div>
    @endif
@endif
