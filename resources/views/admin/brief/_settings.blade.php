@php
    $normal = array_filter($def['fields'] ?? [], fn ($f) => ($f['group'] ?? '') !== 'appearance');
    $appearance = array_filter($def['fields'] ?? [], fn ($f) => ($f['group'] ?? '') === 'appearance');
@endphp
<div class="st-form" data-for="{{ $block['id'] }}" data-type="{{ $block['type'] }}">
  <div class="st-form-head"><i data-lucide="{{ $def['icon'] ?? 'square' }}"></i> {{ $def['label'] ?? $block['type'] }}</div>
  @if($block['type'] === 'payment')
    <button type="button" class="bp-paysec-ai" data-paysec-ai data-block="{{ $block['id'] }}"
            style="width:100%;display:inline-flex;align-items:center;justify-content:center;gap:8px;margin:0 0 14px;padding:11px 14px;border:0;border-radius:10px;background:linear-gradient(135deg,#2B1FA8,#F05A28);color:#fff;font:800 12.5px/1 'Poppins',sans-serif;cursor:pointer;box-shadow:0 8px 18px rgba(43,31,168,.22);">
      <i data-lucide="sparkles"></i> Design this payment section with AI
    </button>
  @endif
  <div class="bp-fields">
    @foreach($normal as $f)
      @include('admin.brief._field', ['field' => $f, 'value' => $block['data'][$f['key']] ?? ($f['type'] === 'repeater' ? [] : '')])
    @endforeach

    @if(count($appearance))
      <details class="bp-appear">
        <summary><i data-lucide="palette"></i> Appearance &amp; colour</summary>
        <div class="bp-appear-body">
          @foreach($appearance as $f)
            @include('admin.brief._field', ['field' => $f, 'value' => $block['data'][$f['key']] ?? ''])
          @endforeach
        </div>
      </details>
    @endif
  </div>
</div>
