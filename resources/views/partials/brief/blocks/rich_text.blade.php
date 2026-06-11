@php $blkStyle = $blkStyle ?? ''; $blkSurface = $blkSurface ?? ''; @endphp
<section class="odp-section odp-richtext {{ $blkSurface }}" @if($blkStyle) style="{{ $blkStyle }}" @endif>
  {!! $data['html'] ?? '' !!}
</section>
