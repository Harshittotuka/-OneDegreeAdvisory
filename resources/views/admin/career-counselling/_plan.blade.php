{{-- One plan card in the Career Counselling editor.
     Vars: $i (row token — a real index, or __INDEX__ inside the <template>),
           $plan (existing data, or defaults for a fresh row),
           $stages (current stage list, for the stage picker).

     Features and tiers submit as PARALLEL arrays (feature_title[] /
     feature_text[] / feature_locked[], tier_label[] / tier_price[]) rather than
     nested pairs, so rows survive add / remove / reorder in the browser without
     needing matching array keys. "Locked" is a <select> and not a checkbox for
     the same reason: an unchecked box submits nothing, which would silently
     shift every later feature's flag by one. --}}
@php
  $features = $plan['features'] ?? [];
  $tiers = $plan['tiers'] ?? [['label' => '', 'price' => '']];
  if ($tiers === []) { $tiers = [['label' => '', 'price' => '']]; }
@endphp
<div class="ccc-row" data-ccc-row>
  <div class="ccc-row-top">
    <span class="ccc-grip" data-ccc-grip title="Drag to reorder"><i data-lucide="grip-vertical"></i></span>

    <div class="ccc-row-fields">
      <div class="ccc-line3">
        <label class="ccc-mini">Plan name
          <input type="text" name="plans[{{ $i }}][name]" maxlength="60"
                 value="{{ $plan['name'] ?? '' }}" placeholder="Explore">
        </label>
        <label class="ccc-mini">Stage tab
          <select name="plans[{{ $i }}][stage]" data-ccc-stage-select data-selected="{{ (int) ($plan['stage'] ?? 0) }}">
            @foreach($stages as $key => $stage)
              <option value="{{ $key }}" @selected((int) ($plan['stage'] ?? 0) === $key)>{{ $stage['label'] }}</option>
            @endforeach
          </select>
        </label>
        <label class="ccc-mini">Badge <span>(optional)</span>
          <input type="text" name="plans[{{ $i }}][badge]" maxlength="40"
                 value="{{ $plan['badge'] ?? '' }}" placeholder="Bestselling">
        </label>
      </div>

      <label class="ccc-mini">Subtitle
        <input type="text" name="plans[{{ $i }}][subtitle]" maxlength="140"
               value="{{ $plan['subtitle'] ?? '' }}" placeholder="Stream Assessment + Counselling">
      </label>
    </div>

    <div class="ccc-row-toggles">
      <label class="ccc-check"><input type="checkbox" name="plans[{{ $i }}][visible]" value="1" @checked($plan['visible'] ?? true)> Visible</label>
      <label class="ccc-check"><input type="checkbox" name="plans[{{ $i }}][featured]" value="1" @checked($plan['featured'] ?? false)> Highlight</label>
    </div>

    <div class="ccc-row-actions">
      <button type="button" class="ccc-icon-btn" data-ccc-up title="Move up"><i data-lucide="chevron-up"></i></button>
      <button type="button" class="ccc-icon-btn" data-ccc-down title="Move down"><i data-lucide="chevron-down"></i></button>
      <button type="button" class="ccc-icon-btn ccc-del" data-ccc-del title="Remove plan"><i data-lucide="trash-2"></i></button>
    </div>
  </div>

  {{-- ── Prices ── --}}
  <div class="ccc-sub">
    <p class="ccc-sub-label">Prices <span>— one row per session option. A single row means the card shows no session picker. Set 0 for “fee on request” (shown, but not payable online).</span></p>
    <div data-ccc-tiers>
      @foreach($tiers as $tier)
        <div class="ccc-tier-row" data-ccc-tier-row>
          <input type="text" name="plans[{{ $i }}][tier_label][]" maxlength="40"
                 value="{{ $tier['label'] ?? '' }}" placeholder="Option label (e.g. 3 Sessions)">
          <div class="ccc-rupee">
            <span>₹</span>
            <input type="text" name="plans[{{ $i }}][tier_price][]" maxlength="12" inputmode="numeric"
                   value="{{ $tier['price'] ?? '' }}" placeholder="7000">
          </div>
          <button type="button" class="ccc-icon-btn ccc-del" data-ccc-tier-del title="Remove price"><i data-lucide="x"></i></button>
        </div>
      @endforeach
      <button type="button" class="btn btn-ghost btn-sm" data-ccc-tier-add><i data-lucide="plus"></i> Add price option</button>
    </div>
  </div>

  {{-- ── Features ── --}}
  <div class="ccc-sub">
    <p class="ccc-sub-label">What’s included <span>— the bold lead-in, then the explanation. “Locked” greys the row out with a padlock, for what this plan does <em>not</em> include.</span></p>
    <div data-ccc-features>
      @foreach($features as $feature)
        <div class="ccc-feature-row" data-ccc-feature-row>
          <input type="text" name="plans[{{ $i }}][feature_title][]" maxlength="80"
                 value="{{ $feature['title'] ?? '' }}" placeholder="Stream Assessment">
          <input type="text" name="plans[{{ $i }}][feature_text][]" maxlength="240"
                 value="{{ $feature['text'] ?? '' }}" placeholder="4-dimensional assessment with top stream recommendations.">
          <select name="plans[{{ $i }}][feature_locked][]">
            <option value="included" @selected(! ($feature['locked'] ?? false))>Included</option>
            <option value="locked" @selected($feature['locked'] ?? false)>Locked</option>
          </select>
          <button type="button" class="ccc-icon-btn ccc-del" data-ccc-feature-del title="Remove line"><i data-lucide="x"></i></button>
        </div>
      @endforeach
      <button type="button" class="btn btn-ghost btn-sm" data-ccc-feature-add><i data-lucide="plus"></i> Add line</button>
    </div>
  </div>
</div>
