<div class="tpc-row" data-tpc-row>
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
    <button type="button" class="tpc-icon-btn" data-tpc-up title="Move up"><i data-lucide="chevron-up"></i></button>
    <button type="button" class="tpc-icon-btn" data-tpc-down title="Move down"><i data-lucide="chevron-down"></i></button>
    <button type="button" class="tpc-icon-btn tpc-del" data-tpc-del title="Remove"><i data-lucide="trash-2"></i></button>
  </div>
</div>
