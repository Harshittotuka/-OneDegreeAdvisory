@php
    $blkStyle = $blkStyle ?? '';
    $items = array_filter($data['items'] ?? [], fn ($i) => trim($i['name'] ?? '') !== '');
@endphp
<section class="odp-file-surface odp-dest-strip" @if($blkStyle) style="{{ $blkStyle }}" @endif>
  @if(!empty($data['label']))<h2 class="odp-dest-label">{{ $data['label'] }}</h2>@endif
  <div class="odp-dest-flags">
    @foreach($items as $it)
      <div class="odp-dest-item">
        <span class="odp-dest-flag">
          @if(!empty($it['code']))
            <img src="https://flagcdn.com/{{ strtolower(trim($it['code'])) }}.svg" alt="{{ $it['name'] }} flag" width="36" height="27" loading="lazy">
          @else
            🌐
          @endif
        </span>
        <span class="odp-dest-name">{{ $it['name'] }}</span>
      </div>
    @endforeach
  </div>
</section>
