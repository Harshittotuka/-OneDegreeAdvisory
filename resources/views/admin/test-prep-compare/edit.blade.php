@extends('admin.layout')

@section('title', 'Test Prep · Compare & Enrol')

@push('head')
<style>
  .tpc-intro{color:var(--muted); font-size:.9rem; margin:-4px 0 22px; max-width:680px;}
  .tpc-sec-title{margin:0 0 4px; font-size:1.02rem; font-weight:800;}
  .tpc-sec-sub{margin:0 0 16px; color:var(--muted); font-size:.82rem;}

  /* Style-variant picker */
  .tpc-styles{display:grid; grid-template-columns:repeat(auto-fit,minmax(210px,1fr)); gap:12px;}
  .tpc-style{position:relative; display:block; border:1.5px solid var(--line); border-radius:12px; padding:14px 14px 14px 42px;
    cursor:pointer; background:#fafbfc; transition:border-color .15s, background .15s;}
  .tpc-style:hover{border-color:var(--teal);}
  .tpc-style input{position:absolute; left:14px; top:16px; width:16px; height:16px; accent-color:var(--teal); cursor:pointer;}
  .tpc-style.is-on{border-color:var(--teal); background:var(--teal-soft);}
  .tpc-style b{display:block; font-size:.9rem; margin-bottom:3px;}
  .tpc-style span{display:block; font-size:.76rem; color:var(--muted); line-height:1.45;}

  .tpc-grid2{display:grid; grid-template-columns:1fr 1fr; gap:16px;}
  .tpc-grid3{display:grid; grid-template-columns:repeat(3,1fr); gap:16px;}

  /* Program rows */
  .tpc-row{display:grid; grid-template-columns:auto 1fr auto auto; gap:12px; align-items:center;
    padding:12px; border:1px solid var(--line); border-radius:12px; background:#fafbfc; margin-bottom:10px;}
  .tpc-row.tpc-dragging{opacity:.5;}
  .tpc-row.tpc-dragover{border-color:var(--teal); box-shadow:0 0 0 2px var(--teal-soft);}
  .tpc-row-grip{display:flex; align-items:center; color:#bcb9c9; cursor:grab;}
  .tpc-row-grip i{width:17px; height:17px;}
  .tpc-row-fields{display:grid; gap:8px; min-width:0;}
  .tpc-row-line1{display:grid; grid-template-columns:1.6fr 1fr; gap:8px;}
  .tpc-row-line2{display:grid; grid-template-columns:repeat(2,1fr); gap:8px;}
  .tpc-mini{display:flex; flex-direction:column; gap:3px; font-size:.68rem; font-weight:700; color:var(--muted);
    text-transform:uppercase; letter-spacing:.03em; margin:0;}
  .tpc-mini input{padding:8px 10px; font-size:.86rem;}
  .tpc-row input, .tpc-row select{padding:9px 11px; font-size:.88rem;}
  .tpc-vis{display:flex; align-items:center; gap:6px; margin:0; font-size:.78rem; font-weight:700; color:var(--muted); white-space:nowrap;}
  .tpc-vis input[type=checkbox]{width:16px; height:16px; accent-color:var(--teal); cursor:pointer;}
  .tpc-row-actions{display:flex; gap:4px;}
  .tpc-icon-btn{display:inline-flex; align-items:center; justify-content:center; width:32px; height:32px;
    border:1px solid var(--line); background:#fff; border-radius:8px; cursor:pointer; color:var(--muted);
    transition:border-color .15s, color .15s;}
  .tpc-icon-btn:hover{border-color:var(--teal); color:var(--teal);}
  .tpc-icon-btn.tpc-del:hover{border-color:var(--danger); color:var(--danger);}
  .tpc-icon-btn i{width:16px; height:16px;}
  /* Floating save bar — a self-contained rounded card centred in the content
     column (past the 260px sidebar), lifted off the bottom edge so it reads as
     a floating pill, not a docked strip. Escapes .cms-wrap's transform (it's
     emitted at body level). */
  .tpc-actions{position:fixed; left:var(--sidebar-w,260px); right:0; bottom:22px; z-index:40;
    display:flex; justify-content:center; padding:0 24px; pointer-events:none;
    animation:tpcBarUp .45s cubic-bezier(.2,.9,.3,1.2) both;}
  @keyframes tpcBarUp{from{opacity:0; transform:translateY(20px);} to{opacity:1; transform:none;}}
  .tpc-actions-inner{pointer-events:auto; display:flex; align-items:center; gap:14px;
    background:rgba(255,255,255,.86); backdrop-filter:blur(16px) saturate(1.4);
    border:1px solid rgba(255,255,255,.7); border-radius:16px;
    padding:11px 12px 11px 20px;
    box-shadow:0 2px 6px rgba(43,44,64,.06), 0 18px 44px -14px rgba(43,44,64,.4);}
  .tpc-actions-hint{display:flex; align-items:center; gap:9px; font-size:.82rem; font-weight:600;
    color:var(--muted); white-space:nowrap; padding-right:6px;}
  .tpc-actions-hint .dot{width:9px; height:9px; border-radius:50%; background:var(--teal);
    box-shadow:0 0 0 4px var(--teal-soft); flex-shrink:0;}
  .tpc-actions-sep{width:1px; height:26px; background:var(--line); flex-shrink:0;}
  /* Buttons inside the floating bar — refined over the base .btn look. */
  .tpc-actions .btn{border-radius:11px;}
  .tpc-actions .btn-save{padding:12px 22px; font-size:.92rem;
    box-shadow:0 8px 20px -8px rgba(102,108,255,.7);}
  .tpc-actions .btn-save:hover{transform:translateY(-2px); box-shadow:0 12px 26px -8px rgba(102,108,255,.75);}
  .tpc-actions .btn-preview{background:transparent; border-color:transparent; color:var(--muted); padding:12px 14px;}
  .tpc-actions .btn-preview:hover{background:var(--teal-soft); color:var(--teal-dark);}
  body.portal-admin .tpc-actions .btn-save{box-shadow:0 8px 20px -8px rgba(249,115,22,.7);}
  body.portal-admin .tpc-actions .btn-save:hover{box-shadow:0 12px 26px -8px rgba(249,115,22,.75);}
  /* Extra room so the last fields clear the floating bar. */
  #tpc-form{padding-bottom:52px;}
  @media (max-width:880px){
    .tpc-actions{left:0; bottom:14px; padding:0 14px;}
    .tpc-actions-inner{width:100%; justify-content:flex-end;}
    .tpc-actions-hint{display:none;}
  }
  @media (prefers-reduced-motion: reduce){ .tpc-actions{animation:none;} }
  .tpc-warn{display:flex; gap:8px; align-items:flex-start; background:#fff8e6; border:1px solid #f5e0a8; color:#8a6400;
    padding:11px 14px; border-radius:10px; font-size:.82rem; margin-bottom:18px;}
  .tpc-warn i{width:16px; height:16px; margin-top:1px; flex-shrink:0;}

  @media (max-width:820px){
    .tpc-grid2, .tpc-grid3{grid-template-columns:1fr;}
    .tpc-row{grid-template-columns:1fr;}
    .tpc-row-line1, .tpc-row-line2{grid-template-columns:1fr;}
    .tpc-row-grip{display:none;}
    .tpc-row-actions{justify-content:flex-end;}
  }
</style>
@endpush

@section('content')
<p class="tpc-intro">
  Manage the <strong>“{{ $compare['heading']['title'] ?? 'Compare' }}”</strong> section on the
  <a href="{{ route('services.test-prep') }}" target="_blank">Test Preparation page</a> — the program list,
  each program’s price &amp; course duration, the payment block copy, and how the whole thing looks.
  A single program row drives <em>both</em> the comparison chart and the online payment options, so a price
  here is exactly what a student is charged.
</p>

@unless(config('services.razorpay.key_id'))
  <div class="tpc-warn">
    <i data-lucide="alert-triangle"></i>
    <span>Razorpay isn’t configured (no <code>RAZORPAY_KEY_ID</code>), so the public “Pay securely” button
    falls back to an enquiry link. Prices still display. Add the keys to enable live checkout.</span>
  </div>
@endunless

<form method="POST" action="{{ route('admin.test-prep-compare.update') }}" id="tpc-form">
  @csrf

  {{-- ── Style ── --}}
  <div class="panel" style="padding:22px; margin-bottom:20px;">
    <h2 class="tpc-sec-title">Section style</h2>
    <p class="tpc-sec-sub">Pick how the comparison is displayed. Variant&nbsp;1 is the current default look.</p>
    <div class="tpc-styles">
      @foreach($styles as $key => $meta)
        <label class="tpc-style @if(($compare['style'] ?? 'bars') === $key) is-on @endif" data-tpc-style-opt>
          <input type="radio" name="style" value="{{ $key }}" @checked(($compare['style'] ?? 'bars') === $key)>
          <b>{{ $meta['label'] }}</b>
          <span>{{ $meta['desc'] }}</span>
        </label>
      @endforeach
    </div>
  </div>

  {{-- ── Heading ── --}}
  <div class="panel" style="padding:22px; margin-bottom:20px;">
    <h2 class="tpc-sec-title">Heading</h2>
    <p class="tpc-sec-sub">The title shown above the comparison.</p>
    <div class="tpc-grid3">
      <div class="field" style="margin:0;">
        <label>Eyebrow</label>
        <input type="text" name="heading[eyebrow]" maxlength="60" value="{{ $compare['heading']['eyebrow'] ?? '' }}" placeholder="Compare">
      </div>
      <div class="field" style="margin:0; grid-column:span 2;">
        <label>Title</label>
        <input type="text" name="heading[title]" maxlength="140" value="{{ $compare['heading']['title'] ?? '' }}" placeholder="See the commitment at a glance">
      </div>
    </div>
    <div class="field" style="margin:14px 0 0;">
      <label>Subtitle</label>
      <input type="text" name="heading[subtitle]" maxlength="240" value="{{ $compare['heading']['subtitle'] ?? '' }}" placeholder="Toggle to compare every program by price or by course duration…">
    </div>
  </div>

  {{-- ── Programs ── --}}
  <div class="panel" style="padding:22px; margin-bottom:20px;">
    <div style="display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:14px;">
      <div>
        <h2 class="tpc-sec-title">Programs</h2>
        <p class="tpc-sec-sub" style="margin:0;">Add, remove, reorder (drag the handle or use the arrows). Set price to <strong>0</strong> for “fee on request” (that program shows but can’t be paid online). A row with no name is dropped on save.</p>
      </div>
      <button type="button" class="btn btn-ghost btn-sm" data-tpc-add><i data-lucide="plus"></i> Add program</button>
    </div>

    <div data-tpc-list>
      @foreach($compare['programs'] ?? [] as $i => $program)
        @include('admin.test-prep-compare._row', ['i' => $i, 'program' => $program])
      @endforeach
    </div>

    <p class="hint" data-tpc-empty @if(! empty($compare['programs'])) style="display:none" @endif>
      No programs yet — click “Add program”.
    </p>
  </div>

  {{-- ── Payment block ── --}}
  <div class="panel" style="padding:22px;">
    <h2 class="tpc-sec-title">Payment block</h2>
    <p class="tpc-sec-sub">The copy and accent colour of the “enrol &amp; pay” card below the comparison.</p>

    <div class="tpc-grid3">
      <div class="field" style="margin:0;">
        <label>Eyebrow</label>
        <input type="text" name="payment[eyebrow]" maxlength="60" value="{{ $compare['payment']['eyebrow'] ?? '' }}" placeholder="Enrol now">
      </div>
      <div class="field" style="margin:0; grid-column:span 2;">
        <label>Title</label>
        <input type="text" name="payment[title]" maxlength="140" value="{{ $compare['payment']['title'] ?? '' }}" placeholder="Reserve your seat online">
      </div>
    </div>

    <div class="field" style="margin:14px 0 0;">
      <label>Description</label>
      <textarea name="payment[description]" maxlength="400" rows="2" placeholder="Pick your program and pay securely…">{{ $compare['payment']['description'] ?? '' }}</textarea>
    </div>

    <div class="tpc-grid3" style="margin-top:14px;">
      <div class="field" style="margin:0;">
        <label>Button label</label>
        <input type="text" name="payment[button_label]" maxlength="40" value="{{ $compare['payment']['button_label'] ?? '' }}" placeholder="Pay securely">
      </div>
      <div class="field" style="margin:0;">
        <label>Accent colour</label>
        <input type="color" name="payment[accent]" value="{{ $compare['payment']['accent'] ?? '#ff5a2e' }}"
               style="width:100%; height:42px; padding:4px; border:1px solid var(--line); border-radius:8px; background:#fff; cursor:pointer;">
      </div>
      <div class="field" style="margin:0;">
        <label>Small print / note</label>
        <input type="text" name="payment[note]" maxlength="300" value="{{ $compare['payment']['note'] ?? '' }}" placeholder="Payments are processed by Razorpay…">
      </div>
    </div>
  </div>

</form>

{{-- Floating save bar — rendered at BODY level (see @push('scripts') below) so
     it escapes .cms-wrap's transform animation, which would otherwise trap a
     position:fixed child. The buttons target the form via form="tpc-form". --}}

{{-- Hidden template cloned by "Add program". __INDEX__ becomes a unique token. --}}
<template id="tpc-row-tpl">
  @include('admin.test-prep-compare._row', ['i' => '__INDEX__', 'program' => ['visible' => true]])
</template>
@endsection

@push('scripts')
<script>
(function () {
  const list = document.querySelector('[data-tpc-list]');
  const tpl = document.getElementById('tpc-row-tpl');
  const empty = document.querySelector('[data-tpc-empty]');
  const addBtn = document.querySelector('[data-tpc-add]');
  if (!list || !tpl) return;

  let uid = 1000000;

  function refresh() {
    if (empty) empty.style.display = list.children.length ? 'none' : '';
    if (window.lucide) lucide.createIcons();
  }

  addBtn && addBtn.addEventListener('click', function () {
    const html = tpl.innerHTML.replace(/__INDEX__/g, String(uid++));
    const tmp = document.createElement('div');
    tmp.innerHTML = html.trim();
    const row = tmp.firstElementChild;
    list.appendChild(row);
    refresh();
    const first = row.querySelector('input[type=text]');
    if (first) first.focus();
  });

  // Up / down / delete
  list.addEventListener('click', function (e) {
    const row = e.target.closest('[data-tpc-row]');
    if (!row) return;
    if (e.target.closest('[data-tpc-del]')) { row.remove(); refresh(); }
    else if (e.target.closest('[data-tpc-up]') && row.previousElementSibling) { row.parentNode.insertBefore(row, row.previousElementSibling); }
    else if (e.target.closest('[data-tpc-down]') && row.nextElementSibling) { row.parentNode.insertBefore(row.nextElementSibling, row); }
  });

  // Drag to reorder (via the grip handle).
  let dragEl = null;
  list.addEventListener('mousedown', function (e) {
    const grip = e.target.closest('[data-tpc-grip]');
    if (!grip) return;
    const row = grip.closest('[data-tpc-row]');
    if (row) row.setAttribute('draggable', 'true');
  });
  list.addEventListener('dragstart', function (e) {
    dragEl = e.target.closest('[data-tpc-row]');
    if (dragEl) { dragEl.classList.add('tpc-dragging'); e.dataTransfer.effectAllowed = 'move'; }
  });
  list.addEventListener('dragover', function (e) {
    e.preventDefault();
    const over = e.target.closest('[data-tpc-row]');
    if (!over || over === dragEl) return;
    list.querySelectorAll('.tpc-dragover').forEach(function (r) { r.classList.remove('tpc-dragover'); });
    over.classList.add('tpc-dragover');
    const rect = over.getBoundingClientRect();
    const after = (e.clientY - rect.top) > rect.height / 2;
    list.insertBefore(dragEl, after ? over.nextElementSibling : over);
  });
  list.addEventListener('dragend', function () {
    if (dragEl) { dragEl.classList.remove('tpc-dragging'); dragEl.removeAttribute('draggable'); }
    list.querySelectorAll('.tpc-dragover').forEach(function (r) { r.classList.remove('tpc-dragover'); });
    dragEl = null;
  });

  // Highlight the chosen style card.
  const styleOpts = document.querySelectorAll('[data-tpc-style-opt]');
  styleOpts.forEach(function (opt) {
    const radio = opt.querySelector('input[type=radio]');
    radio && radio.addEventListener('change', function () {
      styleOpts.forEach(function (o) { o.classList.remove('is-on'); });
      opt.classList.add('is-on');
    });
  });

  refresh();
})();
</script>

{{-- The floating save bar itself — placed here so Blade emits it at &lt;body&gt;
     level (this stack renders just before &lt;/body&gt;), clear of .cms-wrap's
     transform animation. --}}
<div class="tpc-actions">
  <div class="tpc-actions-inner">
    <span class="tpc-actions-hint"><span class="dot"></span> Changes go live once saved</span>
    <span class="tpc-actions-sep"></span>
    <a class="btn btn-preview" href="{{ route('services.test-prep') }}" target="_blank"><i data-lucide="external-link" style="width:16px;height:16px;"></i> Preview</a>
    <button type="submit" form="tpc-form" class="btn btn-primary btn-save"><i data-lucide="save" style="width:16px;height:16px;"></i> Save changes</button>
  </div>
</div>
{{-- Draw the bar's icons: the layout's createIcons() already ran before this
     markup existed in the DOM. --}}
<script>if (window.lucide) lucide.createIcons();</script>
@endpush
