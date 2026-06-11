@php $blkStyle = $blkStyle ?? ''; $style = $data['style'] ?? 'line'; @endphp
<div class="odp-divider odp-divider--{{ $style }}" @if($blkStyle) style="{{ $blkStyle }}" @endif></div>
