@php
    $vis = $block['visible'] ?? true;
    $normal = array_filter($def['fields'] ?? [], fn ($f) => ($f['group'] ?? '') !== 'appearance');
    $appearance = array_filter($def['fields'] ?? [], fn ($f) => ($f['group'] ?? '') === 'appearance');
@endphp
<div class="bp-block @if(! $vis) is-hidden @endif" data-id="{{ $block['id'] }}" data-type="{{ $block['type'] }}" data-visible="{{ $vis ? '1' : '0' }}">
  <div class="bp-block-bar">
    <span class="bp-grip" title="Drag block to reorder"><i data-lucide="grip-vertical"></i></span>
    <span class="bp-block-ic"><i data-lucide="{{ $def['icon'] ?? 'square' }}"></i></span>
    <strong class="bp-block-name">{{ $def['label'] ?? $block['type'] }}</strong>
    <span class="bp-block-sp"></span>
    <button type="button" class="bp-icbtn" data-act="vis" title="Show / hide on the page"><i data-lucide="{{ $vis ? 'eye' : 'eye-off' }}"></i></button>
    <button type="button" class="bp-icbtn" data-act="collapse" title="Collapse"><i data-lucide="chevron-up"></i></button>
    <button type="button" class="bp-icbtn bp-del" data-act="del" title="Delete block"><i data-lucide="trash-2"></i></button>
  </div>
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
