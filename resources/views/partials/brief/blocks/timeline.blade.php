@php
    $blkStyle = $blkStyle ?? '';
    $blkSurface = $blkSurface ?? '';
    $rows = array_filter($data['rows'] ?? [], fn ($r) => trim($r['date'] ?? '') !== '' || trim($r['detail'] ?? '') !== '');
@endphp
<section class="odp-section {{ $blkSurface }}" @if($blkStyle) style="{{ $blkStyle }}" @endif>
  @if(!empty($data['label']))<p class="odp-section-label">{{ $data['label'] }}</p>@endif
  <div class="odp-timeline">
    @foreach($rows as $r)
      <div class="odp-tl-row">
        <span class="odp-tl-date">{{ $r['date'] ?? '' }}</span>
        <p>{{ $r['detail'] ?? '' }}</p>
      </div>
    @endforeach
  </div>
</section>
