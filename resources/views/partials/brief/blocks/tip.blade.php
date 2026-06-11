@php $blkStyle = $blkStyle ?? ''; $blkSurface = $blkSurface ?: 'odp-file-surface odp-surface-pad'; @endphp
<section class="odp-section {{ $blkSurface }}" @if($blkStyle) style="{{ $blkStyle }}" @endif>
  <div class="odp-tip">
    @if(!empty($data['kicker']))<p class="odp-tip-kicker">{{ $data['kicker'] }}</p>@endif
    <p class="is-quote">{{ $data['body'] ?? '' }}</p>
  </div>
</section>
