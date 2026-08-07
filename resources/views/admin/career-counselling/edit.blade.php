@extends('admin.layout')

@section('title', 'Career Counselling · Plans & Pricing')

@push('head')
<style>
  .ccc-intro{color:var(--muted); font-size:.9rem; margin:-4px 0 22px; max-width:720px;}
  .ccc-sec-title{margin:0 0 4px; font-size:1.02rem; font-weight:800;}
  .ccc-sec-sub{margin:0 0 16px; color:var(--muted); font-size:.82rem;}
  .ccc-grid3{display:grid; grid-template-columns:repeat(3,1fr); gap:16px;}

  /* Stage tabs */
  .ccc-stage-row{display:grid; grid-template-columns:auto 1fr auto; gap:10px; align-items:center; margin-bottom:8px;}
  .ccc-stage-row .ccc-num{width:26px; height:26px; border-radius:7px; background:var(--teal-soft); color:var(--teal-dark);
    display:inline-flex; align-items:center; justify-content:center; font-size:.76rem; font-weight:800; flex-shrink:0;}

  /* Plan rows */
  .ccc-row{padding:14px; border:1px solid var(--line); border-radius:12px; background:#fafbfc; margin-bottom:12px;}
  .ccc-row.ccc-dragging{opacity:.5;}
  .ccc-row.ccc-dragover{border-color:var(--teal); box-shadow:0 0 0 2px var(--teal-soft);}
  .ccc-row-top{display:grid; grid-template-columns:auto 1fr auto auto; gap:12px; align-items:start;}
  .ccc-grip{display:flex; align-items:center; color:#bcb9c9; cursor:grab; padding-top:22px;}
  .ccc-grip i{width:17px; height:17px;}
  .ccc-row-fields{display:grid; gap:9px; min-width:0;}
  .ccc-line3{display:grid; grid-template-columns:1.3fr 1fr 1fr; gap:9px;}
  .ccc-mini{display:flex; flex-direction:column; gap:4px; font-size:.68rem; font-weight:700; color:var(--muted);
    text-transform:uppercase; letter-spacing:.03em; margin:0;}
  .ccc-mini span{text-transform:none; font-weight:500; letter-spacing:0;}
  .ccc-row input, .ccc-row select{padding:9px 11px; font-size:.88rem; width:100%;}
  .ccc-row-toggles{display:flex; flex-direction:column; gap:7px; padding-top:20px;}
  .ccc-check{display:flex; align-items:center; gap:6px; margin:0; font-size:.78rem; font-weight:700;
    color:var(--muted); white-space:nowrap;}
  .ccc-check input[type=checkbox]{width:16px; height:16px; accent-color:var(--teal); cursor:pointer; padding:0;}
  .ccc-row-actions{display:flex; gap:4px; padding-top:18px;}
  .ccc-icon-btn{display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px;
    border:1px solid var(--line); background:#fff; border-radius:8px; cursor:pointer; color:var(--muted);
    transition:border-color .15s, color .15s; flex-shrink:0;}
  .ccc-icon-btn:hover{border-color:var(--teal); color:var(--teal);}
  .ccc-icon-btn.ccc-del:hover{border-color:var(--danger); color:var(--danger);}
  .ccc-icon-btn i{width:16px; height:16px;}

  .ccc-sub{margin-top:14px; padding-top:12px; border-top:1px dashed var(--line);}
  .ccc-sub-label{margin:0 0 9px; font-size:.72rem; font-weight:700; text-transform:uppercase;
    letter-spacing:.03em; color:var(--muted);}
  .ccc-sub-label span{text-transform:none; font-weight:500; letter-spacing:0;}
  .ccc-tier-row{display:grid; grid-template-columns:1fr 160px auto; gap:8px; margin-bottom:7px;}
  .ccc-feature-row{display:grid; grid-template-columns:1fr 1.6fr 130px auto; gap:8px; margin-bottom:7px;}
  .ccc-rupee{display:flex; align-items:stretch; border:1px solid var(--line); border-radius:8px; background:#fff; overflow:hidden;}
  .ccc-rupee span{display:inline-flex; align-items:center; padding:0 11px; background:#f6f5fb; color:var(--muted);
    font-weight:800; font-size:.88rem; border-right:1px solid var(--line);}
  .ccc-rupee input{border:none !important; border-radius:0 !important; background:transparent;}
  .ccc-rupee input:focus{box-shadow:none !important;}

  /* Floating save bar — same treatment as the Test Prep editor: a rounded card
     centred in the content column, emitted at body level so it escapes
     .cms-wrap's transform (which would trap a position:fixed child). */
  .ccc-actions{position:fixed; left:var(--sidebar-w,260px); right:0; bottom:22px; z-index:40;
    display:flex; justify-content:center; padding:0 24px; pointer-events:none;
    animation:cccBarUp .45s cubic-bezier(.2,.9,.3,1.2) both;}
  @keyframes cccBarUp{from{opacity:0; transform:translateY(20px);} to{opacity:1; transform:none;}}
  .ccc-actions-inner{pointer-events:auto; display:flex; align-items:center; gap:14px;
    background:rgba(255,255,255,.86); backdrop-filter:blur(16px) saturate(1.4);
    border:1px solid rgba(255,255,255,.7); border-radius:16px; padding:11px 12px 11px 20px;
    box-shadow:0 2px 6px rgba(43,44,64,.06), 0 18px 44px -14px rgba(43,44,64,.4);}
  .ccc-actions-hint{display:flex; align-items:center; gap:9px; font-size:.82rem; font-weight:600;
    color:var(--muted); white-space:nowrap; padding-right:6px;}
  .ccc-actions-hint .dot{width:9px; height:9px; border-radius:50%; background:var(--teal);
    box-shadow:0 0 0 4px var(--teal-soft); flex-shrink:0;}
  .ccc-actions-sep{width:1px; height:26px; background:var(--line); flex-shrink:0;}
  .ccc-actions .btn{border-radius:11px;}
  .ccc-actions .btn-save{padding:12px 22px; font-size:.92rem; box-shadow:0 8px 20px -8px rgba(102,108,255,.7);}
  .ccc-actions .btn-save:hover{transform:translateY(-2px); box-shadow:0 12px 26px -8px rgba(102,108,255,.75);}
  .ccc-actions .btn-preview{background:transparent; border-color:transparent; color:var(--muted); padding:12px 14px;}
  .ccc-actions .btn-preview:hover{background:var(--teal-soft); color:var(--teal-dark);}
  #ccc-form{padding-bottom:52px;}

  .ccc-warn{display:flex; gap:8px; align-items:flex-start; background:#fff8e6; border:1px solid #f5e0a8; color:#8a6400;
    padding:11px 14px; border-radius:10px; font-size:.82rem; margin-bottom:18px;}
  .ccc-warn i{width:16px; height:16px; margin-top:1px; flex-shrink:0;}

  @media (max-width:1080px){
    .ccc-feature-row{grid-template-columns:1fr 1fr 120px auto;}
  }
  @media (max-width:880px){
    .ccc-actions{left:0; bottom:14px; padding:0 14px;}
    .ccc-actions-inner{width:100%; justify-content:flex-end;}
    .ccc-actions-hint{display:none;}
  }
  @media (max-width:820px){
    .ccc-grid3, .ccc-line3, .ccc-tier-row, .ccc-feature-row{grid-template-columns:1fr;}
    .ccc-row-top{grid-template-columns:1fr;}
    .ccc-grip{display:none;}
    .ccc-row-toggles{flex-direction:row; padding-top:0;}
    .ccc-row-actions{justify-content:flex-end; padding-top:0;}
  }
  @media (prefers-reduced-motion: reduce){ .ccc-actions{animation:none;} }
</style>
@endpush

@section('content')
<p class="ccc-intro">
  Manage the <strong>“{{ $plans['heading']['title'] ?? 'Plans & Pricing' }}”</strong> section on the
  <a href="{{ route('career-counselling') }}" target="_blank">Career Counselling page</a> — the stage tabs, the plan
  cards, what each plan includes, and every price. A price row here is <em>exactly</em> what a parent is charged:
  the public page only ever sends back which option was picked, and the amount is re-read from this file at
  checkout, so a tampered browser cannot change the fee.
</p>

@unless(config('services.razorpay.key_id'))
  <div class="ccc-warn">
    <i data-lucide="alert-triangle"></i>
    <span>Razorpay isn’t configured (no <code>RAZORPAY_KEY_ID</code>), so the public plan buttons fall back to the
    “request a callback” form. Prices still display. Add the keys to enable live checkout.</span>
  </div>
@endunless

<form method="POST" action="{{ route('admin.career-counselling.update') }}" id="ccc-form">
  @csrf

  {{-- ── Heading ── --}}
  <div class="panel" style="padding:22px; margin-bottom:20px;">
    <h2 class="ccc-sec-title">Section heading</h2>
    <p class="ccc-sec-sub">Shown above the plan cards. Leave a field empty to hide that line.</p>
    <div class="ccc-grid3">
      <div class="field" style="margin:0;">
        <label>Eyebrow</label>
        <input type="text" name="heading[eyebrow]" maxlength="60" value="{{ $plans['heading']['eyebrow'] ?? '' }}" placeholder="Plans">
      </div>
      <div class="field" style="margin:0; grid-column:span 2;">
        <label>Title</label>
        <input type="text" name="heading[title]" maxlength="140" value="{{ $plans['heading']['title'] ?? '' }}" placeholder="Plans &amp; Pricing">
      </div>
    </div>
    <div class="field" style="margin:14px 0 0;">
      <label>Subtitle</label>
      <input type="text" name="heading[subtitle]" maxlength="240" value="{{ $plans['heading']['subtitle'] ?? '' }}" placeholder="Choose the right guidance program for your child's stage of school.">
    </div>
  </div>

  {{-- ── Stage tabs ── --}}
  <div class="panel" style="padding:22px; margin-bottom:20px;">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px;">
      <div>
        <h2 class="ccc-sec-title">Stage tabs</h2>
        <p class="ccc-sec-sub" style="margin:0;">The tab strip above the cards (up to {{ \App\Support\CareerCounsellingStore::MAX_STAGES }}). Every plan is assigned to one tab. With a single tab the strip is hidden. Clearing a label removes that tab on save.</p>
      </div>
      <button type="button" class="btn btn-ghost btn-sm" data-ccc-stage-add><i data-lucide="plus"></i> Add tab</button>
    </div>

    <div data-ccc-stages>
      @foreach($plans['stages'] as $index => $stage)
        <div class="ccc-stage-row" data-ccc-stage-row>
          <span class="ccc-num">{{ $index + 1 }}</span>
          <input type="text" name="stages[{{ $index }}][label]" maxlength="60" value="{{ $stage['label'] }}" placeholder="Class 8–9" data-ccc-stage-label>
          <button type="button" class="ccc-icon-btn ccc-del" data-ccc-stage-del title="Remove tab"><i data-lucide="x"></i></button>
        </div>
      @endforeach
    </div>
  </div>

  {{-- ── Plans ── --}}
  <div class="panel" style="padding:22px; margin-bottom:20px;">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px;">
      <div>
        <h2 class="ccc-sec-title">Plan cards</h2>
        <p class="ccc-sec-sub" style="margin:0;">Add, remove and reorder (drag the handle or use the arrows). Cards render in this order within their stage tab. A plan with no name is dropped on save.</p>
      </div>
      <button type="button" class="btn btn-ghost btn-sm" data-ccc-add><i data-lucide="plus"></i> Add plan</button>
    </div>

    <div data-ccc-list>
      @foreach($plans['plans'] as $i => $plan)
        @include('admin.career-counselling._plan', ['i' => $i, 'plan' => $plan, 'stages' => $plans['stages']])
      @endforeach
    </div>

    <p class="hint" data-ccc-empty @if(! empty($plans['plans'])) style="display:none" @endif>
      No plans yet — click “Add plan”.
    </p>
  </div>

  {{-- ── Checkout copy ── --}}
  <div class="panel" style="padding:22px;">
    <h2 class="ccc-sec-title">Checkout dialog</h2>
    <p class="ccc-sec-sub">The copy in the pay-now dialog that opens when a parent picks a plan.</p>

    <div class="ccc-grid3">
      <div class="field" style="margin:0; grid-column:span 2;">
        <label>Title</label>
        <input type="text" name="payment[title]" maxlength="140" value="{{ $plans['payment']['title'] ?? '' }}" placeholder="Confirm your counselling plan">
      </div>
      <div class="field" style="margin:0;">
        <label>Accent colour</label>
        <input type="color" name="payment[accent]" value="{{ $plans['payment']['accent'] ?? '#ff5e32' }}"
               style="width:100%; height:42px; padding:4px; border:1px solid var(--line); border-radius:8px; background:#fff; cursor:pointer;">
      </div>
    </div>

    <div class="field" style="margin:14px 0 0;">
      <label>Description</label>
      <textarea name="payment[description]" maxlength="400" rows="2" placeholder="Tell us who the session is for and pay securely…">{{ $plans['payment']['description'] ?? '' }}</textarea>
    </div>

    <div class="ccc-grid3" style="margin-top:14px;">
      <div class="field" style="margin:0;">
        <label>Pay button label</label>
        <input type="text" name="payment[button_label]" maxlength="40" value="{{ $plans['payment']['button_label'] ?? '' }}" placeholder="Pay securely">
      </div>
      <div class="field" style="margin:0;">
        <label>Fallback button label</label>
        <input type="text" name="payment[enquiry_label]" maxlength="40" value="{{ $plans['payment']['enquiry_label'] ?? '' }}" placeholder="Request a callback">
      </div>
      <div class="field" style="margin:0;">
        <label>Small print / note</label>
        <input type="text" name="payment[note]" maxlength="300" value="{{ $plans['payment']['note'] ?? '' }}" placeholder="Payments are processed by Razorpay…">
      </div>
    </div>
  </div>

</form>

{{-- Hidden templates cloned by the "Add" buttons. __INDEX__ becomes a unique token. --}}
<template id="ccc-plan-tpl">
  @include('admin.career-counselling._plan', [
    'i' => '__INDEX__',
    'plan' => ['visible' => true, 'features' => [], 'tiers' => [['label' => '', 'price' => '']]],
    'stages' => $plans['stages'],
  ])
</template>
@endsection

@push('scripts')
<script>
(function () {
  const form = document.getElementById('ccc-form');
  const list = document.querySelector('[data-ccc-list]');
  const tpl = document.getElementById('ccc-plan-tpl');
  const empty = document.querySelector('[data-ccc-empty]');
  const stageList = document.querySelector('[data-ccc-stages]');
  if (!form || !list || !tpl || !stageList) return;

  const MAX_STAGES = @json(\App\Support\CareerCounsellingStore::MAX_STAGES);
  let uid = 1000000;

  function icons() { if (window.lucide) lucide.createIcons(); }

  /* ── Stage tabs ───────────────────────────────────────────────
     Stage rows are renumbered on every change so `stages[n][label]` stays a
     dense 0..n-1 list — a plan's stage value is an INDEX into it, so a gap
     would silently repoint plans at the wrong tab. Every plan's stage <select>
     is rebuilt from the live labels so a tab renamed or added here is
     assignable immediately, without a save-and-reload round trip. --> */
  function syncStages() {
    const rows = Array.from(stageList.querySelectorAll('[data-ccc-stage-row]'));
    rows.forEach((row, index) => {
      const num = row.querySelector('.ccc-num');
      const input = row.querySelector('[data-ccc-stage-label]');
      if (num) num.textContent = String(index + 1);
      if (input) input.name = 'stages[' + index + '][label]';
      // The last remaining tab can't be removed: the strip needs one.
      const del = row.querySelector('[data-ccc-stage-del]');
      if (del) del.disabled = rows.length <= 1;
    });

    const labels = rows.map((row, index) => {
      const input = row.querySelector('[data-ccc-stage-label]');
      const value = input ? input.value.trim() : '';
      return value || 'Tab ' + (index + 1);
    });

    document.querySelectorAll('[data-ccc-stage-select]').forEach((select) => {
      // Remember the intended stage even while it is temporarily out of range
      // (e.g. a tab was removed and is about to be re-added).
      const wanted = Number(select.dataset.selected || select.value || 0);
      select.innerHTML = labels
        .map((label, index) => '<option value="' + index + '">' + label.replace(/[<>&]/g, '') + '</option>')
        .join('');
      select.value = String(Math.min(Math.max(wanted, 0), Math.max(labels.length - 1, 0)));
    });

    const addBtn = document.querySelector('[data-ccc-stage-add]');
    if (addBtn) addBtn.disabled = rows.length >= MAX_STAGES;
  }

  document.querySelector('[data-ccc-stage-add]')?.addEventListener('click', function () {
    if (stageList.querySelectorAll('[data-ccc-stage-row]').length >= MAX_STAGES) return;
    const row = document.createElement('div');
    row.className = 'ccc-stage-row';
    row.setAttribute('data-ccc-stage-row', '');
    row.innerHTML =
      '<span class="ccc-num"></span>' +
      '<input type="text" maxlength="60" placeholder="Class 8–9" data-ccc-stage-label>' +
      '<button type="button" class="ccc-icon-btn ccc-del" data-ccc-stage-del title="Remove tab"><i data-lucide="x"></i></button>';
    stageList.appendChild(row);
    syncStages();
    icons();
    row.querySelector('input').focus();
  });

  stageList.addEventListener('click', function (event) {
    if (!event.target.closest('[data-ccc-stage-del]')) return;
    const rows = stageList.querySelectorAll('[data-ccc-stage-row]');
    if (rows.length <= 1) return;
    event.target.closest('[data-ccc-stage-row]').remove();
    syncStages();
  });

  stageList.addEventListener('input', function (event) {
    if (event.target.matches('[data-ccc-stage-label]')) syncStages();
  });

  // Keep dataset.selected in step with a deliberate pick, so a later stage
  // rename / add doesn't reset it.
  list.addEventListener('change', function (event) {
    if (event.target.matches('[data-ccc-stage-select]')) event.target.dataset.selected = event.target.value;
  });

  /* ── Plan rows ── */
  function refresh() {
    if (empty) empty.style.display = list.children.length ? 'none' : '';
    icons();
  }

  document.querySelector('[data-ccc-add]')?.addEventListener('click', function () {
    const html = tpl.innerHTML.replace(/__INDEX__/g, String(uid++));
    const tmp = document.createElement('div');
    tmp.innerHTML = html.trim();
    const row = tmp.firstElementChild;
    list.appendChild(row);
    syncStages();
    refresh();
    const first = row.querySelector('input[type=text]');
    if (first) first.focus();
  });

  // The row token in any input name — used to name newly added tier / feature
  // inputs so they land in the right plan.
  function rowToken(row) {
    const named = row.querySelector('[name^="plans["]');
    const match = named && named.name.match(/^plans\[([^\]]+)\]/);
    return match ? match[1] : '0';
  }

  list.addEventListener('click', function (event) {
    const row = event.target.closest('[data-ccc-row]');
    if (!row) return;

    if (event.target.closest('[data-ccc-del]')) { row.remove(); refresh(); return; }
    if (event.target.closest('[data-ccc-up]') && row.previousElementSibling) {
      row.parentNode.insertBefore(row, row.previousElementSibling);
      return;
    }
    if (event.target.closest('[data-ccc-down]') && row.nextElementSibling) {
      row.parentNode.insertBefore(row.nextElementSibling, row);
      return;
    }

    if (event.target.closest('[data-ccc-tier-add]')) {
      const wrap = row.querySelector('[data-ccc-tiers]');
      const addBtn = event.target.closest('[data-ccc-tier-add]');
      const token = rowToken(row);
      const tier = document.createElement('div');
      tier.className = 'ccc-tier-row';
      tier.setAttribute('data-ccc-tier-row', '');
      tier.innerHTML =
        '<input type="text" name="plans[' + token + '][tier_label][]" maxlength="40" placeholder="Option label (e.g. 3 Sessions)">' +
        '<div class="ccc-rupee"><span>₹</span>' +
        '<input type="text" name="plans[' + token + '][tier_price][]" maxlength="12" inputmode="numeric" placeholder="7000"></div>' +
        '<button type="button" class="ccc-icon-btn ccc-del" data-ccc-tier-del title="Remove price"><i data-lucide="x"></i></button>';
      wrap.insertBefore(tier, addBtn);
      icons();
      tier.querySelector('input').focus();
      return;
    }
    if (event.target.closest('[data-ccc-tier-del]')) {
      const tiers = row.querySelectorAll('[data-ccc-tier-row]');
      if (tiers.length <= 1) return; // a plan always needs one price box
      event.target.closest('[data-ccc-tier-row]').remove();
      return;
    }

    if (event.target.closest('[data-ccc-feature-add]')) {
      const wrap = row.querySelector('[data-ccc-features]');
      const addBtn = event.target.closest('[data-ccc-feature-add]');
      const token = rowToken(row);
      const feature = document.createElement('div');
      feature.className = 'ccc-feature-row';
      feature.setAttribute('data-ccc-feature-row', '');
      feature.innerHTML =
        '<input type="text" name="plans[' + token + '][feature_title][]" maxlength="80" placeholder="Stream Assessment">' +
        '<input type="text" name="plans[' + token + '][feature_text][]" maxlength="240" placeholder="What this includes.">' +
        '<select name="plans[' + token + '][feature_locked][]"><option value="included">Included</option><option value="locked">Locked</option></select>' +
        '<button type="button" class="ccc-icon-btn ccc-del" data-ccc-feature-del title="Remove line"><i data-lucide="x"></i></button>';
      wrap.insertBefore(feature, addBtn);
      icons();
      feature.querySelector('input').focus();
      return;
    }
    if (event.target.closest('[data-ccc-feature-del]')) {
      event.target.closest('[data-ccc-feature-row]').remove();
      return;
    }
  });

  /* ── Drag to reorder (via the grip handle) ── */
  let dragEl = null;
  list.addEventListener('mousedown', function (event) {
    const grip = event.target.closest('[data-ccc-grip]');
    if (grip) grip.closest('[data-ccc-row]').setAttribute('draggable', 'true');
  });
  list.addEventListener('dragstart', function (event) {
    dragEl = event.target.closest('[data-ccc-row]');
    if (dragEl) { dragEl.classList.add('ccc-dragging'); event.dataTransfer.effectAllowed = 'move'; }
  });
  list.addEventListener('dragover', function (event) {
    event.preventDefault();
    const over = event.target.closest('[data-ccc-row]');
    if (!over || over === dragEl || !dragEl) return;
    list.querySelectorAll('.ccc-dragover').forEach((r) => r.classList.remove('ccc-dragover'));
    over.classList.add('ccc-dragover');
    const rect = over.getBoundingClientRect();
    const after = (event.clientY - rect.top) > rect.height / 2;
    list.insertBefore(dragEl, after ? over.nextElementSibling : over);
  });
  list.addEventListener('dragend', function () {
    if (dragEl) { dragEl.classList.remove('ccc-dragging'); dragEl.removeAttribute('draggable'); }
    list.querySelectorAll('.ccc-dragover').forEach((r) => r.classList.remove('ccc-dragover'));
    dragEl = null;
  });

  syncStages();
  refresh();
})();
</script>

{{-- The floating save bar — emitted at &lt;body&gt; level (this stack renders just
     before &lt;/body&gt;), clear of .cms-wrap's transform animation. --}}
<div class="ccc-actions">
  <div class="ccc-actions-inner">
    <span class="ccc-actions-hint"><span class="dot"></span> Changes go live once saved</span>
    <span class="ccc-actions-sep"></span>
    <a class="btn btn-preview" href="{{ route('career-counselling') }}" target="_blank"><i data-lucide="external-link" style="width:16px;height:16px;"></i> Preview</a>
    <button type="submit" form="ccc-form" class="btn btn-primary btn-save"><i data-lucide="save" style="width:16px;height:16px;"></i> Save changes</button>
  </div>
</div>
<script>if (window.lucide) lucide.createIcons();</script>
@endpush
