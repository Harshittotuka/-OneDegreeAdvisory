{{-- One section in the live editor: drag/visibility/fields/delete toolbar + the
     instrumented public partial. Vars: $section, optional $isNew. --}}
@php
  $schema = \App\Support\AboutSchema::type($section['type']);
  $vis = ($section['visible'] ?? true) === true;
  $isNew = $isNew ?? false;
@endphp
<div class="le-sec @if(! $vis) le-hidden @endif @if($isNew) le-new @endif" data-le-sec
     data-ed-id="{{ $section['id'] }}" data-ed-type="{{ $section['type'] }}" data-ed-visible="{{ $vis ? 1 : 0 }}">
  <script type="application/json" class="le-extra">{!! json_encode($section['data'] ?? []) !!}</script>

  <div class="le-sec-bar" contenteditable="false">
    <span class="le-drag" draggable="true" title="Drag to reorder section">
      <i data-lucide="grip-vertical"></i><span class="le-drag-label">{{ $schema['label'] ?? ucfirst($section['type']) }}</span>
    </span>
    <button type="button" class="le-bbtn" data-act="vis" title="Show / hide section"><i data-lucide="{{ $vis ? 'eye' : 'eye-off' }}"></i></button>
    <button type="button" class="le-bbtn" data-act="opts" title="Section options"><i data-lucide="sliders-horizontal"></i></button>
    <button type="button" class="le-bbtn le-del" data-act="del-sec" title="Delete section"><i data-lucide="trash-2"></i></button>
  </div>

  @includeIf('partials.about.'.$section['type'], ['data' => $section['data'] ?? [], 'edit' => true])

  @if(! $vis)<div class="le-hidden-flag" contenteditable="false">Hidden on the live site</div>@endif
</div>
