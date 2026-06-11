@php
    $blkStyle = $blkStyle ?? '';
    $blkSurface = $blkSurface ?? '';
    $align = $data['align'] ?? 'left';
    $size = $data['size'] ?? '';
@endphp
<div class="odp-text odp-text--{{ $align }} {{ $size ? 'odp-text--'.$size : '' }} {{ $blkSurface }}" @if($blkStyle) style="{{ $blkStyle }}" @endif>
  {!! $data['body'] ?? '' !!}
</div>
