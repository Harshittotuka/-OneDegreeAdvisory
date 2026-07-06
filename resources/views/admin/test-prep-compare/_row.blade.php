@php
    $details = $program['details'] ?? [];
    $hasDetails = trim((string) ($details['title'] ?? '')) !== '' || trim((string) ($details['tagline'] ?? '')) !== '';
@endphp
<div class="tpc-row" data-tpc-row>
  <div class="tpc-row-top">
    <div class="tpc-row-grip" title="Drag to reorder" data-tpc-grip><i data-lucide="grip-vertical"></i></div>

    <div class="tpc-row-fields">
      <div class="tpc-row-line1">
        <input type="text" name="programs[{{ $i }}][name]" value="{{ $program['name'] ?? '' }}"
               class="tpc-in-name" placeholder="Program name (e.g. IELTS)">
        <input type="text" name="programs[{{ $i }}][badge]" value="{{ $program['badge'] ?? '' }}"
               class="tpc-in-badge" placeholder="Badge (optional, e.g. Popular)">
      </div>
      <div class="tpc-row-line2">
        <label class="tpc-mini">₹ Price
          <input type="text" inputmode="numeric" name="programs[{{ $i }}][price]" value="{{ $program['price'] ?? '' }}"
                 placeholder="0 = on request">
        </label>
        <label class="tpc-mini">Duration (months)
          <input type="text" inputmode="decimal" name="programs[{{ $i }}][months]" value="{{ $program['months'] ?? '' }}"
                 placeholder="e.g. 1.5">
        </label>
      </div>
    </div>

    <label class="tpc-vis" title="Show on the site">
      <input type="hidden" name="programs[{{ $i }}][visible]" value="0">
      <input type="checkbox" name="programs[{{ $i }}][visible]" value="1" @checked($program['visible'] ?? true)>
      <span>Show</span>
    </label>

    <div class="tpc-row-actions">
      <button type="button" class="tpc-icon-btn @if($hasDetails) tpc-icon-btn--on @endif" data-tpc-details-toggle title="Popup details (the chip shown on the public payment card)"><i data-lucide="message-square-text"></i></button>
      <button type="button" class="tpc-icon-btn" data-tpc-up title="Move up"><i data-lucide="chevron-up"></i></button>
      <button type="button" class="tpc-icon-btn" data-tpc-down title="Move down"><i data-lucide="chevron-down"></i></button>
      <button type="button" class="tpc-icon-btn tpc-del" data-tpc-del title="Remove"><i data-lucide="trash-2"></i></button>
    </div>
  </div>

  {{-- Popup ("exam info") details — entirely optional. A program only shows
       a chip in the public payment card's strip once title/tagline here are
       filled in. Collapsed by default unless already populated. --}}
  <div class="tpc-details" data-tpc-details @if(! $hasDetails) hidden @endif>
    <p class="tpc-details-hint">Shown as a chip on the public payment card. Tapping the chip opens this as a popup with exam facts. Leave title &amp; tagline blank to hide the chip for this program.</p>

    <div class="tpc-row-line1">
      <input type="text" name="programs[{{ $i }}][details][eyebrow]" value="{{ $details['eyebrow'] ?? '' }}"
             placeholder="Eyebrow (e.g. IELTS)">
      <input type="text" name="programs[{{ $i }}][details][title]" value="{{ $details['title'] ?? '' }}"
             placeholder="Title (e.g. IELTS - International English Language Testing System)">
    </div>
    <div class="field" style="margin:8px 0 0;">
      <textarea name="programs[{{ $i }}][details][tagline]" rows="2" placeholder="One-line tagline shown under the title">{{ $details['tagline'] ?? '' }}</textarea>
    </div>

    <div class="tpc-facts" data-tpc-facts>
      <p class="tpc-details-label">Quick facts <span>(shown as a small grid, e.g. “Score” / “Band 0–9”)</span></p>
      @foreach(($details['facts'] ?? []) as $fact)
        <div class="tpc-fact-row" data-tpc-fact-row>
          <input type="text" name="programs[{{ $i }}][details][fact_label][]" value="{{ $fact[0] ?? '' }}" placeholder="Label (e.g. Score)">
          <input type="text" name="programs[{{ $i }}][details][fact_value][]" value="{{ $fact[1] ?? '' }}" placeholder="Value (e.g. Band 0-9)">
          <button type="button" class="tpc-icon-btn tpc-del" data-tpc-fact-del title="Remove fact"><i data-lucide="x"></i></button>
        </div>
      @endforeach
      <button type="button" class="btn btn-ghost btn-sm" data-tpc-fact-add><i data-lucide="plus"></i> Add fact</button>
    </div>

    <div class="field" style="margin:12px 0 0;">
      <label>Why take it</label>
      <textarea name="programs[{{ $i }}][details][advantage]" rows="3" placeholder="Why this test/language is worth taking...">{{ $details['advantage'] ?? '' }}</textarea>
    </div>

    <div class="tpc-syllabus" data-tpc-syllabus>
      <p class="tpc-details-label">Format &amp; syllabus <span>(one bullet point per line)</span></p>
      @foreach(($details['syllabus'] ?? []) as $line)
        <div class="tpc-syllabus-row" data-tpc-syllabus-row>
          <input type="text" name="programs[{{ $i }}][details][syllabus][]" value="{{ $line }}" placeholder="e.g. Reading - academic passages and questions">
          <button type="button" class="tpc-icon-btn tpc-del" data-tpc-syllabus-del title="Remove line"><i data-lucide="x"></i></button>
        </div>
      @endforeach
      <button type="button" class="btn btn-ghost btn-sm" data-tpc-syllabus-add><i data-lucide="plus"></i> Add line</button>
    </div>

    <div class="field" style="margin:12px 0 0;">
      <label>Source / disclaimer note</label>
      <input type="text" name="programs[{{ $i }}][details][source]" value="{{ $details['source'] ?? '' }}"
             placeholder="e.g. General exam-format information - verify with the official test body.">
    </div>
  </div>
</div>
