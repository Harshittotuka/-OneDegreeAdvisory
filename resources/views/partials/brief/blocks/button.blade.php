@php
    $blkStyle = $blkStyle ?? '';
    $align = $data['align'] ?? 'left';
    $style = $data['style'] ?? 'gradient';
    $size = $data['size'] ?? '';
    $shape = $data['shape'] ?? 'pill';
    $cls = 'odp-btn odp-btn--'.$style.' odp-btn--shape-'.$shape
         .($size ? ' odp-btn--'.$size : '')
         .(! empty($data['block']) ? ' odp-btn--block' : '');
@endphp
<div class="odp-btnblk odp-btnblk--{{ $align }}" @if($blkStyle) style="{{ $blkStyle }}" @endif>
  @if(!empty($data['label']))
    <a class="{{ $cls }}" href="{{ $data['href'] ?: '#' }}">
      @if(!empty($data['icon']))<i data-lucide="{{ $data['icon'] }}" aria-hidden="true"></i>@endif
      <span>{{ $data['label'] }}</span>
    </a>
  @endif
</div>
