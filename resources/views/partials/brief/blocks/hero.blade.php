@php
    $blkStyle = $blkStyle ?? '';
    $actions = array_filter($data['actions'] ?? [], fn ($a) => trim($a['label'] ?? '') !== '');
    $panelItems = array_filter($data['panel_items'] ?? [], fn ($i) => trim($i['text'] ?? '') !== '');
@endphp
<header class="odp-web-hero" @if($blkStyle) style="{{ $blkStyle }}" @endif>
  <div class="odp-web-hero-grid">
    <div>
      @if(!empty($data['eyebrow']))<span class="odp-web-eyebrow">{{ $data['eyebrow'] }}</span>@endif
      <h1 class="odp-web-title">{{ $data['title'] ?? '' }}</h1>
      @if(!empty($data['copy']))<p class="odp-web-copy">{{ $data['copy'] }}</p>@endif
      @if(count($actions))
        <div class="odp-web-actions">
          @foreach($actions as $a)
            <a class="odp-web-btn {{ ($a['style'] ?? 'primary') === 'secondary' ? 'odp-web-btn-secondary' : 'odp-web-btn-primary' }}" href="{{ $a['href'] ?: '#' }}">
              @if(!empty($a['icon']))<i data-lucide="{{ $a['icon'] }}" aria-hidden="true"></i>@endif
              <span>{{ $a['label'] }}</span>
            </a>
          @endforeach
        </div>
      @endif
    </div>

    @if(!empty($data['panel_heading']) || count($panelItems))
      <aside class="odp-web-panel">
        @if(!empty($data['panel_heading']))<h2>{{ $data['panel_heading'] }}</h2>@endif
        @if(count($panelItems))
          <ul class="odp-web-list">
            @foreach($panelItems as $i)
              <li>
                @if(!empty($i['icon']))<i data-lucide="{{ $i['icon'] }}" aria-hidden="true"></i>@else<i data-lucide="check" aria-hidden="true"></i>@endif
                <span>{{ $i['text'] }}</span>
              </li>
            @endforeach
          </ul>
        @endif
      </aside>
    @endif
  </div>
</header>
