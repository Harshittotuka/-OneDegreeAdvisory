@extends('admin.layout')
@section('title', $mode === 'create' ? 'Add section' : 'Edit section')

@push('head')
<style>
  .cms-wrap { max-width: none; }
  .form-grid { display: grid; grid-template-columns: 1fr 320px; gap: 24px; align-items: start; }
  @media (max-width: 920px) { .form-grid { grid-template-columns: 1fr; } }
  .form-side { position: sticky; top: 84px; display: flex; flex-direction: column; gap: 18px; }
  @media (max-width: 920px) { .form-side { position: static; } }
  .panel-pad { padding: 22px; }
  .panel-head { display: flex; align-items: center; gap: 9px; margin: 0 0 18px; padding-bottom: 13px;
    border-bottom: 1px solid var(--line); font-size: 1.02rem; font-weight: 800; letter-spacing: -.01em; }
  .panel-head i { width: 18px; height: 18px; color: var(--teal); }
  .field { margin-bottom: 15px; }

  .icon-btn { cursor: pointer; border: 1px solid var(--line); background: #fff; border-radius: 7px;
    min-width: 30px; height: 28px; font-size: .82rem; line-height: 1; color: var(--muted); padding: 0 7px; }
  .icon-btn:hover { border-color: var(--teal); color: var(--teal); }
  .icon-btn.del:hover { border-color: var(--danger); color: var(--danger); }

  /* Repeaters */
  .rep { border: 1px solid #dbe6f2; background: #f7faff; border-radius: 12px; padding: 14px; margin-bottom: 16px; }
  .rep-label { font-weight: 800; font-size: .8rem; text-transform: uppercase; letter-spacing: .06em; color: #1d3a6b; margin-bottom: 11px; }
  .rep-count { color: #6a89bd; font-weight: 700; }
  .rep-rows { display: flex; flex-direction: column; gap: 12px; }
  .rep-row { border: 1px solid var(--line); border-radius: 10px; background: #fff; box-shadow: 0 2px 8px rgba(13,33,42,.04); }
  .rep-row-head { display: flex; align-items: center; justify-content: space-between; gap: 10px;
    padding: 8px 11px; border-bottom: 1px solid var(--line); background: #f8f5f1; border-radius: 10px 10px 0 0; }
  .rep-row-title { display: inline-flex; align-items: center; gap: 6px; font-weight: 800; font-size: .72rem;
    text-transform: uppercase; letter-spacing: .06em; color: var(--teal-dark); }
  .rep-row-ctl { display: flex; gap: 4px; }
  .rep-row-body { padding: 13px; }
  .rep-row-body .field:last-child { margin-bottom: 0; }
  .rep-add { margin-top: 12px; }
  /* nested repeaters indent slightly */
  .rep .rep { background: #fff; border-color: #e7eef7; }

  .check-row { display: flex; align-items: center; gap: 9px; cursor: pointer; background: #fafbfc;
    border: 1px solid var(--line); border-radius: 10px; padding: 10px 12px; margin-bottom: 15px; font-weight: 700; font-size: .9rem; }
  .check-row input[type=checkbox] { width: auto; }

  /* Icon field */
  .icon-row { display: flex; align-items: center; gap: 9px; }
  .icon-prev { width: 40px; height: 40px; flex-shrink: 0; border: 1px solid var(--line); border-radius: 9px;
    display: inline-flex; align-items: center; justify-content: center; background: #fff; color: var(--ink); }
  .icon-prev svg, .icon-prev i { width: 20px; height: 20px; }

  /* Image field */
  .img-row { display: flex; gap: 11px; align-items: flex-start; }
  .img-prevwrap { position: relative; width: 88px; height: 66px; flex-shrink: 0; }
  .img-prev { width: 88px; height: 66px; object-fit: cover; border-radius: 9px; border: 1px solid var(--line); display: none; background: #eef1f4; }
  .img-prev[src] { display: block; }
  .img-ph { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;
    border: 1px dashed var(--line); border-radius: 9px; color: #aab4bf; background: #fafbfc; }
  .img-controls { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 8px; }
  .img-buttons { display: flex; align-items: center; gap: 8px; }
  .filedrop { display: inline-flex; align-items: center; gap: 6px; border: 1px dashed #cfd8e0; border-radius: 9px;
    padding: 7px 12px; background: #fafbfc; font-size: .82rem; color: var(--muted); cursor: pointer; font-weight: 700; }
  .filedrop:hover { border-color: var(--teal); color: var(--teal); }
  .uploading { font-size: .8rem; color: var(--teal); font-weight: 700; }

  .hint { font-weight: 500; color: var(--muted); font-size: .78rem; margin-top: 5px; }
  .hint code { background: #eef1f4; padding: 1px 6px; border-radius: 5px; font-size: .92em; }
  .save-row { display: flex; gap: 10px; }
  .status-toggle { display: flex; align-items: flex-start; gap: 10px; cursor: pointer; background: #fafbfc;
    border: 1px solid var(--line); border-radius: 10px; padding: 11px 12px; font-weight: 700; font-size: .9rem; }
  .status-toggle input { width: auto; margin-top: 2px; }
</style>
@endpush

@section('content')
  <div style="display:flex;align-items:center;justify-content:space-between;gap:16px;margin-bottom:20px;">
    <div>
      <h1 style="margin:0;font-size:1.45rem;letter-spacing:-.01em;">
        {{ $mode === 'create' ? 'Add section' : 'Edit section' }} · {{ $schema['label'] }}
      </h1>
      <p style="margin:3px 0 0;color:var(--muted);font-size:.85rem;">{{ $schema['desc'] }}</p>
    </div>
    <a class="btn btn-ghost" href="{{ route('admin.about.index') }}"><i data-lucide="arrow-left" style="width:15px;height:15px;"></i> All sections</a>
  </div>

  <form method="POST" id="section-form"
        action="{{ $mode === 'create' ? route('admin.about.store') : route('admin.about.update', $section['id']) }}">
    @csrf
    @if($mode === 'edit') @method('PUT') @endif
    <input type="hidden" name="type" value="{{ $section['type'] }}">

    <div class="form-grid">
      {{-- Fields --}}
      <div class="panel panel-pad">
        <h2 class="panel-head"><i data-lucide="{{ $schema['icon'] }}"></i> Section content</h2>
        @foreach($schema['fields'] as $field)
          @include('admin.about._field', [
            'field' => $field,
            'name' => 'data['.$field['key'].']',
            'value' => $section['data'][$field['key']] ?? null,
          ])
        @endforeach
      </div>

      {{-- Sidebar --}}
      <div class="form-side">
        <div class="panel panel-pad">
          <div class="save-row">
            <button class="btn btn-primary" type="submit" style="flex:1;justify-content:center;">
              <i data-lucide="check" style="width:16px;height:16px;"></i> {{ $mode === 'create' ? 'Add section' : 'Save changes' }}
            </button>
            @if($mode === 'edit')
              <a class="btn btn-ghost" href="{{ route('about') }}#{{ $section['id'] }}" target="_blank" title="Preview"><i data-lucide="external-link" style="width:16px;height:16px;"></i></a>
            @endif
          </div>
        </div>

        <div class="panel panel-pad">
          <h2 class="panel-head"><i data-lucide="settings-2"></i> Section</h2>
          <label class="status-toggle">
            <input type="hidden" name="visible" value="0">
            <input type="checkbox" name="visible" value="1" @checked($section['visible'] ?? true)>
            <span>Visible on the page<br><span class="hint">Turn off to hide this section without deleting it.</span></span>
          </label>
          @if($mode === 'edit')
            <p class="hint" style="margin-top:12px;">Section id: <code>{{ $section['id'] }}</code></p>
            {{-- Submits the SEPARATE delete form below via the HTML form= attribute.
                 Must NOT be a nested <form> — that would inject _method=DELETE into
                 the editor form and turn every Save into a delete. --}}
            <button type="submit" form="about-delete-form" class="btn btn-danger btn-sm"
                    style="width:100%;justify-content:center;margin-top:14px;"
                    onclick="return confirm('Delete this section? This cannot be undone.');">
              <i data-lucide="trash-2" style="width:14px;height:14px;"></i> Delete section
            </button>
          @endif
        </div>
      </div>
    </div>
  </form>

  @if($mode === 'edit')
    {{-- Standalone delete form (a sibling of the editor form, never nested). --}}
    <form id="about-delete-form" method="POST" action="{{ route('admin.about.destroy', $section['id']) }}">
      @csrf @method('DELETE')
    </form>
  @endif

  {{-- Suggestions for icon fields --}}
  <datalist id="lucide-suggest">
    @foreach(['sparkles','sparkle','star','award','trophy','target','users','users-round','user-check','user',
              'graduation-cap','book-open','briefcase','globe-2','map-pin','building-2','compass','telescope',
              'badge-check','shield-check','check-circle-2','heart-handshake','hand-coins','life-buoy','lock',
              'route','layers','plane-takeoff','arrow-up-right','arrow-right','phone','mail','linkedin','sparkle',
              'lightbulb','rocket','gem','flag','calendar','clock','bar-chart-3','trending-up','message-circle'] as $ic)
      <option value="{{ $ic }}">
    @endforeach
  </datalist>
@endsection

@push('scripts')
<script>
(function () {
  const UPLOAD_URL = @json(route('admin.about.upload'));
  const CSRF = document.querySelector('meta[name=csrf-token]').content;
  const form = document.getElementById('section-form');
  let NEWIDX = 1000; // unique array indices for newly-added rows

  const refreshIcons = () => { if (window.lucide) lucide.createIcons(); };

  /* ── Direct-child helpers (so nested repeaters don't leak) ── */
  const childRows = (rep) => rep.querySelector(':scope > .rep-rows');
  const childTpl = (rep) => rep.querySelector(':scope > template[data-rep-tpl]');
  const childCount = (rep) => rep.querySelector(':scope > .rep-label .rep-count');

  function updateCount(rep) {
    const c = childCount(rep);
    if (c) c.textContent = '(' + childRows(rep).querySelectorAll(':scope > .rep-row').length + ')';
  }

  /* ── Repeater controls (delegated, so cloned rows work too) ── */
  form.addEventListener('click', (e) => {
    const add = e.target.closest('[data-rep-add]');
    if (add) {
      const rep = add.closest('.rep');
      const token = rep.dataset.token;
      const html = childTpl(rep).innerHTML.split(token).join('n' + (NEWIDX++));
      const tmp = document.createElement('div');
      tmp.innerHTML = html;
      const rows = childRows(rep);
      while (tmp.firstElementChild) rows.appendChild(tmp.firstElementChild);
      updateCount(rep);
      refreshIcons();
      return;
    }
    const del = e.target.closest('[data-rep-del]');
    if (del) {
      const row = del.closest('.rep-row');
      const rep = row.closest('.rep');
      row.remove();
      updateCount(rep);
      return;
    }
    const up = e.target.closest('[data-rep-up]');
    if (up) {
      const row = up.closest('.rep-row');
      const prev = row.previousElementSibling;
      if (prev) row.parentNode.insertBefore(row, prev);
      return;
    }
    const down = e.target.closest('[data-rep-down]');
    if (down) {
      const row = down.closest('.rep-row');
      const next = row.nextElementSibling;
      if (next) row.parentNode.insertBefore(next, row);
      return;
    }
    const clear = e.target.closest('[data-img-clear]');
    if (clear) {
      const f = clear.closest('[data-img-field]');
      f.querySelector('[data-img-url]').value = '';
      const prev = f.querySelector('[data-img-preview]');
      prev.removeAttribute('src');
      f.querySelector('[data-img-ph]').style.display = '';
      const file = f.querySelector('[data-img-file]');
      if (file) file.value = '';
      return;
    }
  });

  /* ── Icon live preview (delegated) ── */
  function renderIconPreview(input) {
    const prev = input.closest('.icon-field').querySelector('[data-icon-preview]');
    const v = input.value.trim().toLowerCase().replace(/[^a-z0-9-]/g, '');
    prev.innerHTML = v ? '<i data-lucide="' + v + '"></i>' : '';
    refreshIcons();
  }

  /* ── Image: live URL preview + upload (delegated) ── */
  function setImg(field, url) {
    field.querySelector('[data-img-url]').value = url;
    const prev = field.querySelector('[data-img-preview]');
    const ph = field.querySelector('[data-img-ph]');
    if (url) { prev.src = url; ph.style.display = 'none'; }
    else { prev.removeAttribute('src'); ph.style.display = ''; }
  }

  form.addEventListener('input', (e) => {
    if (e.target.matches('[data-icon-input]')) renderIconPreview(e.target);
    else if (e.target.matches('[data-img-url]')) {
      const field = e.target.closest('[data-img-field]');
      const v = e.target.value.trim();
      const prev = field.querySelector('[data-img-preview]');
      const ph = field.querySelector('[data-img-ph]');
      if (v) { prev.src = v; ph.style.display = 'none'; } else { prev.removeAttribute('src'); ph.style.display = ''; }
    }
  });

  form.addEventListener('change', (e) => {
    if (!e.target.matches('[data-img-file]')) return;
    const field = e.target.closest('[data-img-field]');
    const file = e.target.files[0];
    if (!file) return;
    const up = field.querySelector('[data-img-uploading]');
    up.hidden = false;
    const fd = new FormData();
    fd.append('file', file);
    fetch(UPLOAD_URL, { method: 'POST', headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' }, body: fd })
      .then(r => r.ok ? r.json() : Promise.reject())
      .then(d => { up.hidden = true; setImg(field, d.url); })
      .catch(() => { up.hidden = true; alert('Image upload failed. Use a JPG/PNG/WebP under 5 MB.'); });
  });

  refreshIcons();
})();
</script>
@endpush
