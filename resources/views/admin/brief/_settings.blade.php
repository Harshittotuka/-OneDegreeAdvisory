@php
    $normal = array_filter($def['fields'] ?? [], fn ($f) => ($f['group'] ?? '') !== 'appearance');
    $appearance = array_filter($def['fields'] ?? [], fn ($f) => ($f['group'] ?? '') === 'appearance');
@endphp
<div class="st-form" data-for="{{ $block['id'] }}" data-type="{{ $block['type'] }}">
  <div class="st-form-head"><i data-lucide="{{ $def['icon'] ?? 'square' }}"></i> {{ $def['label'] ?? $block['type'] }}</div>
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
