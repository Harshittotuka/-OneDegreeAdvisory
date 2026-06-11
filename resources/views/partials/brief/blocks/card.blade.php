@php
    $blkStyle = $blkStyle ?? '';
    $style = $data['style'] ?? 'plain';
    $cls = $style === 'tile' ? 'odp-card--tile' : ($style === 'outline' ? 'odp-card--outline' : '');
@endphp
<div class="odp-card {{ $cls }}" @if($blkStyle) style="{{ $blkStyle }}" @endif>
  @if(!empty($data['image']))
    <div class="odp-card-img"><img src="{{ $data['image'] }}" alt="{{ $data['title'] ?? '' }}" loading="lazy"></div>
  @endif
  <div class="odp-card-body">
    @if(!empty($data['emoji']) || !empty($data['icon']))
      <div class="odp-card-ic" aria-hidden="true">
        @if(!empty($data['emoji'])){{ $data['emoji'] }}@elseif(!empty($data['icon']))<i data-lucide="{{ $data['icon'] }}"></i>@endif
      </div>
    @endif
    @if(!empty($data['eyebrow']))<span class="odp-card-eyebrow">{{ $data['eyebrow'] }}</span>@endif
    @if(!empty($data['title']))<h3 class="odp-card-title">{{ $data['title'] }}</h3>@endif
    @if(!empty($data['body']))<p class="odp-card-text">{!! nl2br(e($data['body'])) !!}</p>@endif
    @if(!empty($data['btn_label']))
      <a class="odp-card-btn" href="{{ $data['btn_href'] ?: '#' }}">
        @if(!empty($data['btn_icon']))<i data-lucide="{{ $data['btn_icon'] }}"></i>@endif
        <span>{{ $data['btn_label'] }}</span>
        @if(empty($data['btn_icon']))<i data-lucide="arrow-right"></i>@endif
      </a>
    @endif
  </div>
</div>
