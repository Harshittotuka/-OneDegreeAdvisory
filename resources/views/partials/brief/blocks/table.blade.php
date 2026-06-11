@php
    $blkStyle = $blkStyle ?? '';
    $blkSurface = $blkSurface ?? '';
    $headings = array_filter($data['headings'] ?? [], fn ($h) => trim($h['text'] ?? '') !== '');
    $rows = $data['rows'] ?? [];
    $toneClass = ['key' => 'odp-td-key', 'good' => 'odp-td-good', 'warn' => 'odp-td-warn'];
@endphp
<section class="odp-section {{ $blkSurface }}" @if($blkStyle) style="{{ $blkStyle }}" @endif>
  @if(!empty($data['label']))<p class="odp-section-label">{{ $data['label'] }}</p>@endif
  <div class="odp-table-wrap">
    <table class="odp-table">
      @if(count($headings))
        <thead>
          <tr>@foreach($headings as $h)<th>{{ $h['text'] }}</th>@endforeach</tr>
        </thead>
      @endif
      <tbody>
        @foreach($rows as $row)
          @php $cells = $row['cells'] ?? []; @endphp
          @continue(! count(array_filter($cells, fn ($c) => trim($c['text'] ?? '') !== '')))
          <tr>
            @foreach($cells as $c)
              <td class="{{ $toneClass[$c['tone'] ?? ''] ?? '' }}">{{ $c['text'] ?? '' }}</td>
            @endforeach
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  @if(!empty($data['note']))<p class="odp-block-sub" style="margin:12px 0 0;font-style:italic;">{{ $data['note'] }}</p>@endif
</section>
