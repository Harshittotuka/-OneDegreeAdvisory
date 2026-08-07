{{-- A filter that takes several values at once.

     Sits in the .filters grid as one child, so the responsive rules that hide
     the later filters on narrow screens keep working unchanged. The trigger
     carries .control so it picks up whichever theme is loaded (classic,
     evergreen, orbit) rather than hardcoding a look that only matches one.

     The checkboxes are real inputs named "<field>[]" inside the filter form, so
     submitting is the browser's job and the server reads an ordinary array. All
     behaviour (open, close, label, clear) is delegated from crm.js — nothing
     here may rely on an inline script, because swapApp() rebuilds this markup
     with DOMParser and inline <script> never runs.

     @param string       $name        query field, e.g. "status"
     @param array        $options     value => label, in the order to show
     @param array        $selected    currently chosen values (strings)
     @param string       $placeholder trigger text when nothing is chosen
     @param string|null  $label       accessible name; defaults to $placeholder
     @param string|null  $noun        pluralised in "3 <noun> selected"
     @param array        $optionClass value => extra class for that row
     @param string|null  $triggerClass extra classes for the trigger button
--}}
@php
    $selected = array_values(array_filter(array_map('strval', (array) ($selected ?? []))));
    $chosen = array_values(array_intersect(array_map('strval', array_keys($options)), $selected));
    $count = count($chosen);
    $noun = $noun ?? 'selected';
    $label = $label ?? $placeholder;
    $optionClass = $optionClass ?? [];

    // One chosen value reads as itself; several read as a count, with the full
    // list on hover so the bar stays narrow but nothing is hidden.
    $triggerText = match (true) {
        $count === 0 => $placeholder,
        $count === 1 => (string) $options[$chosen[0]],
        default => $count.' '.$noun,
    };
    $chosenLabels = array_map(fn ($value) => (string) $options[$value], $chosen);
@endphp
<div @class(['mfilter', 'is-active' => $count > 0]) data-mfilter>
    <button type="button"
            @class(['control', 'mfilter-trigger', $triggerClass ?? '' => ($triggerClass ?? '') !== ''])
            data-mfilter-toggle
            aria-expanded="false"
            aria-haspopup="true"
            aria-label="{{ $label }}"
            @if($count > 1) title="{{ $label }}: {{ implode(', ', $chosenLabels) }}" @endif>
        <span class="mfilter-text" data-mfilter-text data-placeholder="{{ $placeholder }}" data-noun="{{ $noun }}">{{ $triggerText }}</span>
        @if($count > 0)<span class="mfilter-count" data-mfilter-badge>{{ $count }}</span>@endif
        <span class="mfilter-caret" aria-hidden="true"></span>
    </button>

    <div class="mfilter-menu" data-mfilter-menu role="group" aria-label="{{ $label }}" hidden>
        <div class="mfilter-list">
            @foreach($options as $value => $optionLabel)
                <label @class(['mfilter-opt', $optionClass[$value] ?? ''])>
                    <input type="checkbox"
                           name="{{ $name }}[]"
                           value="{{ $value }}"
                           @checked(in_array((string) $value, $selected, true))
                           data-mfilter-option>
                    <span class="mfilter-box" aria-hidden="true"></span>
                    <span class="mfilter-opt-text">{{ $optionLabel }}</span>
                </label>
            @endforeach
        </div>
        <div class="mfilter-foot">
            <span class="mfilter-summary" data-mfilter-summary>{{ $count > 0 ? $count.' '.$noun : 'None selected' }}</span>
            <button type="button" class="mfilter-clear" data-mfilter-clear @disabled($count === 0)>Clear</button>
        </div>
    </div>
</div>
