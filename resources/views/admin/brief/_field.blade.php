@php
    $type = $field['type'];
    $key = $field['key'];
    $label = $field['label'] ?? $key;
@endphp

@if($type === 'repeater')
  <div class="bp-field bp-field--rep">
    <label>{{ $label }}</label>
    <div class="bp-rep" data-rep="{{ $key }}">
      <div class="bp-rep-items">
        @foreach((is_array($value) ? $value : []) as $row)
          @include('admin.brief._repitem', ['fields' => $field['fields'], 'row' => is_array($row) ? $row : []])
        @endforeach
      </div>
      <template class="bp-rep-tpl">
        @include('admin.brief._repitem', ['fields' => $field['fields'], 'row' => \App\Support\BriefSchema::blankRow($field['fields'])])
      </template>
      <button type="button" class="bp-rep-add"><i data-lucide="plus"></i> Add {{ $field['item'] ?? 'item' }}</button>
    </div>
  </div>

@elseif($type === 'textarea')
  <div class="bp-field">
    <label>{{ $label }}</label>
    <textarea data-field="{{ $key }}" rows="3">{{ $value }}</textarea>
  </div>

@elseif($type === 'richtext')
  <div class="bp-field">
    <label>{{ $label }} <span class="bp-hint">basic HTML allowed</span></label>
    <textarea data-field="{{ $key }}" rows="5" class="bp-mono">{{ $value }}</textarea>
  </div>

@elseif($type === 'code')
  <div class="bp-field">
    <label>{{ $label }} <span class="bp-hint">full HTML / CSS / JS</span></label>
    <textarea data-field="{{ $key }}" rows="12" class="bp-mono" spellcheck="false">{{ $value }}</textarea>
  </div>

@elseif($type === 'select')
  <div class="bp-field">
    <label>{{ $label }}</label>
    <select data-field="{{ $key }}">
      @foreach($field['options'] ?? [] as $ov => $ol)
        <option value="{{ $ov }}" @selected((string) $value === (string) $ov)>{{ $ol }}</option>
      @endforeach
    </select>
  </div>

@elseif($type === 'checkbox')
  <div class="bp-field bp-field--check">
    <label class="bp-check"><input type="checkbox" data-field="{{ $key }}" @checked($value)> <span>{{ $label }}</span></label>
  </div>

@elseif($type === 'color')
  <div class="bp-field">
    <label>{{ $label }}</label>
    <div class="bp-color">
      <input type="color" class="bp-color-pick" value="{{ $value ?: '#2b1fa8' }}">
      <input type="text" data-field="{{ $key }}" class="bp-color-hex" value="{{ $value }}" placeholder="auto" spellcheck="false">
      <button type="button" class="bp-color-clear" title="Use default">✕</button>
    </div>
  </div>

@elseif($type === 'icon')
  <div class="bp-field">
    <label>{{ $label }} <span class="bp-hint">lucide icon name</span></label>
    <div class="bp-icon">
      <span class="bp-icon-prev">@if($value)<i data-lucide="{{ $value }}"></i>@endif</span>
      <input type="text" data-field="{{ $key }}" class="bp-icon-name" value="{{ $value }}" placeholder="e.g. sparkles" spellcheck="false">
    </div>
  </div>

@elseif($type === 'image')
  <div class="bp-field">
    <label>{{ $label }}</label>
    <div class="bp-image">
      <div class="bp-image-prev">@if($value)<img src="{{ $value }}" alt="">@else<i data-lucide="image"></i>@endif</div>
      <div class="bp-image-ctrls">
        <input type="text" data-field="{{ $key }}" class="bp-image-url" value="{{ $value }}" placeholder="https://… or upload">
        <div class="bp-image-btns">
          <label class="bp-mini"><i data-lucide="upload"></i> Upload<input type="file" accept="image/*" hidden class="bp-image-file"></label>
          <button type="button" class="bp-mini bp-image-clear"><i data-lucide="x"></i> Clear</button>
        </div>
      </div>
    </div>
  </div>

@else
  <div class="bp-field">
    <label>{{ $label }}</label>
    <input type="text" data-field="{{ $key }}" value="{{ $value }}">
  </div>
@endif
