@php
    $blkStyle = $blkStyle ?? '';
    $blkSurface = $blkSurface ?? '';
    $links = array_filter($data['links'] ?? [], fn ($l) => trim($l['text'] ?? '') !== '' || trim($l['href'] ?? '') !== '');
@endphp
<section class="odp-section {{ $blkSurface }}" @if($blkStyle) style="{{ $blkStyle }}" @endif>
  @if(!empty($data['label']))<p class="odp-section-label">{{ $data['label'] }}</p>@endif
  <div class="odp-sources">
    @foreach($links as $l)
      <a href="{{ $l['href'] ?: '#' }}" target="_blank" rel="noopener">{{ $l['text'] ?: $l['href'] }}</a>
    @endforeach
  </div>
</section>
