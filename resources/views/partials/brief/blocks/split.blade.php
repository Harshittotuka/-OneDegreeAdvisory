@php
    $blkStyle = $blkStyle ?? '';
    $blkSurface = $blkSurface ?? '';
    $cards = array_filter($data['cards'] ?? [], fn ($c) => trim($c['heading'] ?? '') !== '' || trim($c['body'] ?? '') !== '' || !empty($c['items']));
@endphp
<section class="odp-section {{ $blkSurface }}" @if($blkStyle) style="{{ $blkStyle }}" @endif>
  @if(!empty($data['label']))<p class="odp-section-label">{{ $data['label'] }}</p>@endif
  <div class="odp-split">
    @foreach($cards as $c)
      @php $tone = $c['tone'] ?? ''; @endphp
      <div class="odp-info-card {{ $tone === 'warn' ? 'odp-info-card--warn' : ($tone === 'good' ? 'odp-info-card--good' : '') }}">
        @if(!empty($c['heading']))<h3>{{ $c['heading'] }}</h3>@endif
        @if(!empty($c['body']))<p>{!! nl2br(e($c['body'])) !!}</p>@endif
        @php $items = array_filter($c['items'] ?? [], fn ($i) => trim($i['text'] ?? '') !== ''); @endphp
        @if(count($items))
          <ul>
            @foreach($items as $i)<li>{!! $i['text'] !!}</li>@endforeach
          </ul>
        @endif
      </div>
    @endforeach
  </div>
</section>
