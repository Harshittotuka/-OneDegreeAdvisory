{{--
  Renders one editable field from the AboutSchema.
  Vars: $field (schema def), $name (input name, e.g. data[heading]), $value (current value).
  Recurses for repeaters via admin.about._rep_row.
--}}
@php
  $type = $field['type'];
  $value = $value ?? null;
@endphp

@if($type === 'repeater')
  @php
    $token = '__R'.\Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(8)).'__';
    $rows = is_array($value) ? array_values($value) : [];
    $itemLabel = $field['item'] ?? 'Item';
  @endphp
  <div class="rep" data-rep data-token="{{ $token }}">
    <div class="rep-label">{{ $field['label'] }} <span class="rep-count">({{ count($rows) }})</span></div>
    <div class="rep-rows" data-rep-rows>
      @foreach($rows as $i => $row)
        @include('admin.about._rep_row', [
          'fields' => $field['fields'],
          'name' => $name.'['.$i.']',
          'values' => is_array($row) ? $row : [],
          'itemLabel' => $itemLabel,
        ])
      @endforeach
    </div>
    <template data-rep-tpl>
      @include('admin.about._rep_row', [
        'fields' => $field['fields'],
        'name' => $name.'['.$token.']',
        'values' => [],
        'itemLabel' => $itemLabel,
      ])
    </template>
    <button type="button" class="btn btn-ghost btn-sm rep-add" data-rep-add>
      <i data-lucide="plus" style="width:14px;height:14px;"></i> Add {{ $itemLabel }}
    </button>
  </div>

@elseif($type === 'textarea')
  <div class="field">
    <label>{{ $field['label'] }}</label>
    <textarea name="{{ $name }}" rows="3">{{ $value }}</textarea>
  </div>

@elseif($type === 'select')
  <div class="field">
    <label>{{ $field['label'] }}</label>
    <select name="{{ $name }}">
      @foreach($field['options'] as $optVal => $optLabel)
        <option value="{{ $optVal }}" @selected((string) $value === (string) $optVal)>{{ $optLabel }}</option>
      @endforeach
    </select>
  </div>

@elseif($type === 'checkbox')
  <label class="check-row">
    <input type="hidden" name="{{ $name }}" value="0">
    <input type="checkbox" name="{{ $name }}" value="1" @checked($value)>
    <span>{{ $field['label'] }}</span>
  </label>

@elseif($type === 'icon')
  <div class="field icon-field">
    <label>{{ $field['label'] }}</label>
    <div class="icon-row">
      <span class="icon-prev" data-icon-preview>@if($value)<i data-lucide="{{ $value }}"></i>@endif</span>
      <input type="text" name="{{ $name }}" value="{{ $value }}" placeholder="lucide icon name"
             list="lucide-suggest" data-icon-input autocomplete="off" spellcheck="false">
    </div>
  </div>

@elseif($type === 'image')
  @php $src = $value ? (\Illuminate\Support\Str::startsWith($value, ['http', '/']) ? $value : asset($value)) : ''; @endphp
  <div class="field img-field" data-img-field>
    <label>{{ $field['label'] }}</label>
    <div class="img-row">
      <span class="img-prevwrap">
        <img class="img-prev" data-img-preview alt="" @if($src) src="{{ $src }}" @endif>
        <span class="img-ph" data-img-ph @if($src) style="display:none" @endif><i data-lucide="image"></i></span>
      </span>
      <span class="img-controls">
        <input type="text" name="{{ $name }}" value="{{ $value }}" placeholder="Image URL, or upload →" data-img-url>
        <span class="img-buttons">
          <label class="filedrop"><i data-lucide="upload" style="width:14px;height:14px;vertical-align:-2px;"></i> Upload
            <input type="file" accept="image/*" data-img-file hidden></label>
          <button type="button" class="icon-btn del" data-img-clear title="Clear">✕</button>
          <span class="uploading" data-img-uploading hidden>Uploading…</span>
        </span>
      </span>
    </div>
  </div>

@else
  <div class="field">
    <label>{{ $field['label'] }}</label>
    <input type="text" name="{{ $name }}" value="{{ $value }}">
  </div>
@endif
