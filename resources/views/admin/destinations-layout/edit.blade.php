@extends('admin.layout')

@section('title', 'Destinations Menu')

@push('head')
<style>
  .dl-intro { color: var(--muted); font-size: .9rem; margin: -4px 0 22px; max-width: 660px; }
  .dl-layout { display: grid; grid-template-columns: 340px 1fr; gap: 20px; align-items: start; }
  .dl-controls .field:last-child { margin-bottom: 0; }
  .dl-range { display: flex; align-items: center; gap: 12px; }
  .dl-range input[type=range] { flex: 1; accent-color: var(--teal); }
  .dl-range output { min-width: 56px; text-align: right; font-weight: 800; font-variant-numeric: tabular-nums; color: var(--ink); }

  .dl-preview-wrap { position: sticky; top: 90px; }
  .dl-preview-label { font-size: .7rem; font-weight: 800; letter-spacing: .09em; text-transform: uppercase; color: var(--muted); margin-bottom: 10px; }
  /* The preview panel mimics the real cream dropdown surface. */
  .dl-preview { background: #fdf8f1; border: 1px solid #ecdfce; border-radius: 14px; padding: 16px; box-shadow: var(--shadow); overflow-x: auto; }
  .dl-preview-grid { display: grid; margin: 0 auto; }
  .dl-card { display: flex; align-items: center; gap: 8px; padding: 9px 10px; border: 1px solid rgba(19,37,47,.1);
    border-radius: 8px; background: #fff; font-size: .8rem; font-weight: 700; color: #243945; min-width: 0; }
  .dl-card .dot { width: 18px; height: 13px; border-radius: 3px; background: linear-gradient(135deg,#f97316,#fdba74); flex-shrink: 0; }
  .dl-card span:last-child { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
  .dl-actions { margin-top: 24px; display: flex; gap: 10px; }
  @media (max-width: 860px) { .dl-layout { grid-template-columns: 1fr; } .dl-preview-wrap { position: static; } }
</style>
@endpush

@section('content')
<p class="dl-intro">
  Control how the <strong>Destinations</strong> mega-menu arranges its country cards — the number of
  columns, the gap between cards and the overall panel width. Changes apply to both the country-guides and
  MBBS grids inside the dropdown. The preview updates live; click <strong>Save changes</strong> to publish.
</p>

<form method="POST" action="{{ route('admin.destinations-layout.update') }}" id="dl-form">
  @csrf
  <div class="dl-layout">
    <div class="panel dl-controls" style="padding: 22px;">
      <div class="field">
        <label for="dl-columns">Columns</label>
        <div class="dl-range">
          <input id="dl-columns" type="range" name="columns" min="2" max="6" step="1" value="{{ $layout['columns'] }}" data-dl="columns">
          <output for="dl-columns" data-dl-out="columns">{{ $layout['columns'] }}</output>
        </div>
        <p class="hint">How many country cards sit side by side. Default 3.</p>
      </div>

      <div class="field">
        <label for="dl-gap">Gap between cards (px)</label>
        <div class="dl-range">
          <input id="dl-gap" type="range" name="gap" min="2" max="24" step="1" value="{{ $layout['gap'] }}" data-dl="gap">
          <output for="dl-gap" data-dl-out="gap">{{ $layout['gap'] }}px</output>
        </div>
        <p class="hint">Spacing between cards. Default 5.</p>
      </div>

      <div class="field">
        <label for="dl-width">Panel width (px)</label>
        <div class="dl-range">
          <input id="dl-width" type="range" name="width" min="680" max="1280" step="20" value="{{ $layout['width'] }}" data-dl="width">
          <output for="dl-width" data-dl-out="width">{{ $layout['width'] }}px</output>
        </div>
        <p class="hint">Overall width of the dropdown panel. Wider = more room for extra columns. Default 940.</p>
      </div>
    </div>

    <div class="dl-preview-wrap">
      <div class="dl-preview-label">Live preview</div>
      <div class="dl-preview">
        <div class="dl-preview-grid" id="dl-preview-grid">
          @for ($n = 1; $n <= 9; $n++)
            <div class="dl-card"><span class="dot"></span><span>Country {{ $n }}</span></div>
          @endfor
        </div>
      </div>
      <p class="hint" style="margin-top:10px;">Placeholder cards — the live menu uses your real country list.</p>
    </div>
  </div>

  <div class="dl-actions">
    <button type="submit" class="btn btn-primary"><i data-lucide="save" style="width:16px;height:16px;"></i> Save changes</button>
    {{-- Reset submits THIS form to the reset route (formaction); the server saves
         the baseline defaults, so the live menu actually reverts. --}}
    <button type="submit" class="btn btn-ghost" formaction="{{ route('admin.destinations-layout.reset') }}" formnovalidate
            onclick="return confirm('Reset the Destinations menu to the original layout ({{ $defaults['columns'] }} columns, {{ $defaults['gap'] }}px gap, {{ $defaults['width'] }}px wide)?');">
      <i data-lucide="rotate-ccw" style="width:16px;height:16px;"></i> Reset to defaults
    </button>
    <a class="btn btn-ghost" href="{{ route('home') }}" target="_blank"><i data-lucide="external-link" style="width:16px;height:16px;"></i> Preview site</a>
  </div>
</form>
@endsection

@push('scripts')
<script>
  (function () {
    const grid = document.getElementById('dl-preview-grid');
    if (!grid) return;

    const val = (k) => document.querySelector('[data-dl="' + k + '"]').value;

    function apply() {
      const cols = val('columns'), gap = val('gap'), width = val('width');
      grid.style.gridTemplateColumns = 'repeat(' + cols + ', minmax(0, 1fr))';
      grid.style.gap = gap + 'px';
      grid.style.maxWidth = width + 'px';
      document.querySelector('[data-dl-out="columns"]').textContent = cols;
      document.querySelector('[data-dl-out="gap"]').textContent = gap + 'px';
      document.querySelector('[data-dl-out="width"]').textContent = width + 'px';
    }

    document.querySelectorAll('[data-dl]').forEach(c => c.addEventListener('input', apply));
    apply();
  })();
</script>
@endpush
