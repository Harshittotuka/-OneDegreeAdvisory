<div class="bp-rep-item">
  <div class="bp-rep-item-bar">
    <span class="bp-rep-grip" title="Drag to reorder"><i data-lucide="grip-vertical"></i></span>
    <button type="button" class="bp-rep-del" title="Remove"><i data-lucide="x"></i></button>
  </div>
  <div class="bp-rep-item-body">
    @foreach($fields as $f)
      @include('admin.brief._field', ['field' => $f, 'value' => $row[$f['key']] ?? ($f['type'] === 'repeater' ? [] : '')])
    @endforeach
  </div>
</div>
