@php
    $blkStyle = $blkStyle ?? '';
    $blkSurface = $blkSurface ?? '';
    $items = array_values(array_filter($data['items'] ?? [], fn ($i) => trim($i['text'] ?? '') !== ''));
    $quoted = ! empty($data['quoted']);
@endphp
<section class="odp-section {{ $blkSurface }}" @if($blkStyle) style="{{ $blkStyle }}" @endif>
  @if(!empty($data['label']))<p class="odp-section-label">{{ $data['label'] }}</p>@endif
  <div class="odp-talk">
    @foreach($items as $i => $item)
      <div class="odp-talk-item">
        <span class="odp-talk-num">{{ $i + 1 }}</span>
        <p>{{ $quoted ? '“'.$item['text'].'”' : $item['text'] }}</p>
      </div>
    @endforeach
  </div>
</section>
