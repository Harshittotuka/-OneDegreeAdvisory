@php $blkStyle = $blkStyle ?? ''; @endphp
<div class="odp-embed" @if($blkStyle) style="{{ $blkStyle }}" @endif>
  {!! $data['html'] ?? '' !!}
</div>
