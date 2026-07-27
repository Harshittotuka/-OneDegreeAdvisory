{{--
    Repeatable test rows (test · score · date) for the lead's academic card.

    @param string $field    form field name, e.g. "english_tests"
    @param array  $options  the test catalog, key => label ("other" reveals a name box)
    @param array  $rows     saved rows: ['test' => …, 'name' => …, 'score' => …, 'date' => …]
    @param string $heading  section heading shown above the rows
    @param string $addLabel button copy for adding another row
--}}
@php
    $savedRows = collect($rows ?: [])->filter(fn ($row): bool => is_array($row))->values();
@endphp
<div class="test-repeater field full" data-test-repeater="{{ $field }}">
    {{-- Marks the group as submitted, so clearing every row actually clears the column. --}}
    <input type="hidden" name="{{ $field }}_present" value="1">
    <div class="test-repeater-head"><label>{{ $heading }}</label><span class="label-note">Add every test the student has taken</span></div>
    <div class="test-repeater-rows" data-test-rows>
        @foreach($savedRows as $index => $row)
            @include('crm.partials.test-row', ['field' => $field, 'options' => $options, 'row' => $row, 'index' => $index])
        @endforeach
    </div>
    <p class="test-repeater-empty" data-test-empty @if($savedRows->isNotEmpty()) hidden @endif>No test recorded yet.</p>
    <button type="button" class="btn btn-outline btn-compact test-repeater-add" data-test-add>＋ <span>{{ $addLabel }}</span></button>
    <template data-test-template>
        @include('crm.partials.test-row', ['field' => $field, 'options' => $options, 'row' => [], 'index' => '__INDEX__'])
    </template>
</div>
