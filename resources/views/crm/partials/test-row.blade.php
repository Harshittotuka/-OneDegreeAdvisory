@php
    $rowTest = (string) ($row['test'] ?? '');
    $rowName = (string) ($row['name'] ?? '');
    $rowScore = (string) ($row['score'] ?? '');
    $rowDate = (string) ($row['date'] ?? '');
    $prefix = $field.'['.$index.']';
    // "Not taken" records the absence of a result, so the score and date boxes go away.
    $notTaken = $rowTest === 'not_taken';
@endphp
<div class="test-row" data-test-row>
    <div class="field test-row-field">
        <span class="test-row-caption">Test</span>
        <select class="test-row-select" name="{{ $prefix }}[test]" data-test-select aria-label="Test">
            <option value="">Select a test</option>
            @foreach($options as $key=>$label)<option value="{{ $key }}" @selected($rowTest === $key)>{{ $label }}</option>@endforeach
        </select>
    </div>
    <div class="field test-row-field test-row-other" data-test-other @if($rowTest !== 'other') hidden @endif>
        <span class="test-row-caption">Test name</span>
        <input name="{{ $prefix }}[name]" value="{{ $rowName }}" maxlength="60" placeholder="e.g. CLAT" aria-label="Other test name">
    </div>
    <div class="field test-row-field test-row-result" data-test-result @if($notTaken) hidden @endif>
        <span class="test-row-caption">Score</span>
        <input name="{{ $prefix }}[score]" value="{{ $notTaken ? '' : $rowScore }}" maxlength="40" placeholder="e.g. 7.5" aria-label="Score">
    </div>
    <div class="field test-row-field test-row-result" data-test-result @if($notTaken) hidden @endif>
        <span class="test-row-caption">Test date</span>
        <input type="date" name="{{ $prefix }}[date]" value="{{ $notTaken ? '' : $rowDate }}" aria-label="Test date">
    </div>
    <span class="test-row-flag" data-test-flag @unless($notTaken) hidden @endunless>Student has not taken this test</span>
    <button type="button" class="test-row-remove" data-test-remove aria-label="Remove this test">×</button>
</div>
