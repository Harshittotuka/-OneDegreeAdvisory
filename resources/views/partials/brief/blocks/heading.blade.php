@php $blkStyle = $blkStyle ?? ''; $blkSurface = $blkSurface ?? ''; @endphp
<section class="odp-section {{ $blkSurface }}" @if($blkStyle) style="{{ $blkStyle }}" @endif>
  @if(!empty($data['label']))<p class="odp-section-label">{{ $data['label'] }}</p>@endif
  @if(!empty($data['heading']))<h2 class="odp-block-title">{{ $data['heading'] }}</h2>@endif
  @if(!empty($data['sub']))<p class="odp-block-sub">{{ $data['sub'] }}</p>@endif
</section>
