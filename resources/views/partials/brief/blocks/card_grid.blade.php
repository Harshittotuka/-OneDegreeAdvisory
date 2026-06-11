@php
    $blkStyle = $blkStyle ?? '';
    $blkSurface = $blkSurface ?? '';
    $cards = array_filter($data['cards'] ?? [], fn ($c) => trim($c['title'] ?? '') !== '' || trim($c['emoji'] ?? '') !== '');
@endphp
<section class="odp-section {{ $blkSurface }}" @if($blkStyle) style="{{ $blkStyle }}" @endif>
  @if(!empty($data['label']))<p class="odp-section-label">{{ $data['label'] }}</p>@endif
  <div class="odp-cc-grid">
    @foreach($cards as $c)
      <article class="odp-cc">
        @if(!empty($c['emoji']))<div class="odp-cc-emoji" aria-hidden="true">{{ $c['emoji'] }}</div>@endif
        @if(!empty($c['title']))<h3>{{ $c['title'] }}</h3>@endif
        @if(!empty($c['meta']))<p class="odp-cc-price">{{ $c['meta'] }}</p>@endif
        @if(!empty($c['body']))<p>{{ $c['body'] }}</p>@endif
      </article>
    @endforeach
  </div>
</section>
