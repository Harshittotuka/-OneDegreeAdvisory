@php
    $blkStyle = $blkStyle ?? '';
    $blkSurface = $blkSurface ?? '';
    $r = $data['rounded'] ?? '16';
    $radius = $r === '999' ? '50%' : ($r === '0' ? '0' : '16px');
@endphp
<section class="odp-section odp-imageblk {{ $blkSurface }}" @if($blkStyle) style="{{ $blkStyle }}" @endif>
  @if(!empty($data['src']))
    <figure style="margin:0;">
      <img src="{{ $data['src'] }}" alt="{{ $data['alt'] ?? '' }}" loading="lazy" style="width:100%;height:auto;border-radius:{{ $radius }};display:block;{{ $r === '999' ? 'aspect-ratio:1;object-fit:cover;' : '' }}">
      @if(!empty($data['caption']))<figcaption style="margin-top:8px;color:#888;font-size:13px;text-align:center;">{{ $data['caption'] }}</figcaption>@endif
    </figure>
  @endif
</section>
