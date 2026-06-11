@php $def = $def ?? \App\Support\BriefSchema::type($block['type']); @endphp
<div class="st-block" data-id="{{ $block['id'] }}" data-type="{{ $block['type'] }}">
  <div class="st-block-tools">
    <span class="st-drag" title="Drag block"><i data-lucide="grip-vertical"></i></span>
    <span class="st-block-tag"><i data-lucide="{{ $def['icon'] ?? 'square' }}"></i> {{ $def['label'] ?? $block['type'] }}</span>
    <span class="st-sp"></span>
    <button type="button" class="st-tbtn" data-st="dup" title="Duplicate"><i data-lucide="copy"></i></button>
    <button type="button" class="st-tbtn" data-st="edit" title="Edit"><i data-lucide="settings-2"></i></button>
    <button type="button" class="st-tbtn st-del" data-st="del" title="Delete"><i data-lucide="trash-2"></i></button>
  </div>
  <div class="st-block-node">
    @include('admin.brief._blocknode', ['type' => $block['type'], 'data' => $block['data'] ?? []])
  </div>
</div>
