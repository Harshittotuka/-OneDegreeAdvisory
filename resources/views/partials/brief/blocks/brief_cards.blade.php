@php
    $blkStyle = $blkStyle ?? '';
    $blkSurface = $blkSurface ?? '';
    $cards = array_filter($data['cards'] ?? [], fn ($c) => trim($c['title'] ?? '') !== '' || trim($c['body'] ?? '') !== '');
@endphp
<section class="odp-section {{ $blkSurface }}" @if($blkStyle) style="{{ $blkStyle }}" @endif>
  @if(!empty($data['label']))<p class="odp-section-label">{{ $data['label'] }}</p>@endif
  <div class="odp-brief-cards">
    @foreach($cards as $c)
      @php $level = $c['level'] ?? ''; @endphp
      <article class="odp-brief-card {{ $level === 'medium' ? 'odp-brief-card--medium' : '' }}">
        <div class="odp-brief-card-top">
          <h3>{{ $c['title'] ?? '' }}</h3>
          @if($level)
            <span class="odp-brief-badge {{ $level === 'medium' ? 'odp-brief-badge--medium' : '' }}">{{ strtoupper($level) }}</span>
          @endif
        </div>
        @if(!empty($c['body']))<p>{{ $c['body'] }}</p>@endif
        @php $tags = array_filter($c['tags'] ?? [], fn ($t) => trim($t['text'] ?? '') !== ''); @endphp
        @if(count($tags))
          <div class="odp-tags">
            @foreach($tags as $t)<span class="odp-tag">{{ $t['text'] }}</span>@endforeach
          </div>
        @endif
      </article>
    @endforeach
  </div>
</section>
