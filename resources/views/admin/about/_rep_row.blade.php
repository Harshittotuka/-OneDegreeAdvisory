{{--
  One repeater row. Vars: $fields (sub-field defs), $name (prefix incl. index),
  $values (assoc of current values), $itemLabel.
--}}
<div class="rep-row" data-rep-row>
  <div class="rep-row-head">
    <span class="rep-row-title"><i data-lucide="grip-vertical" style="width:13px;height:13px;"></i> {{ $itemLabel }}</span>
    <span class="rep-row-ctl">
      <button type="button" class="icon-btn" data-rep-up title="Move up">↑</button>
      <button type="button" class="icon-btn" data-rep-down title="Move down">↓</button>
      <button type="button" class="icon-btn del" data-rep-del title="Remove">✕</button>
    </span>
  </div>
  <div class="rep-row-body">
    @foreach($fields as $sub)
      @include('admin.about._field', [
        'field' => $sub,
        'name' => $name.'['.$sub['key'].']',
        'value' => $values[$sub['key']] ?? null,
      ])
    @endforeach
  </div>
</div>
