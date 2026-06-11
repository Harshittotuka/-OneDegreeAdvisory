@php
    $blkStyle = $blkStyle ?? '';
    $plans = array_filter($data['plans'] ?? [], fn ($p) => trim($p['name'] ?? '') !== '');
    $enrol = trim($data['enrol_href'] ?? '') ?: route('contact');
@endphp
<section aria-labelledby="odp-pricing-title" @if($blkStyle) style="{{ $blkStyle }}" @endif>
  @if(!empty($data['heading']) || !empty($data['sub']))
    <div class="odp-packages-heading">
      @if(!empty($data['heading']))<h2 id="odp-pricing-title">{{ $data['heading'] }}</h2>@endif
      @if(!empty($data['sub']))<p>{{ $data['sub'] }}</p>@endif
    </div>
  @endif
  <div class="odp-plans">
    @foreach($plans as $p)
      <article class="odp-file-plan {{ $p['variant'] ?? 'starter' }}">
        <div class="odp-highlight" aria-hidden="true"></div>
        @if(!empty($p['badge']))<div class="odp-badge">{{ $p['badge'] }}</div>@endif
        <h3 class="odp-plan-name">{{ $p['name'] ?? '' }}</h3>
        @if(!empty($p['price']))<div class="odp-plan-price">{{ $p['price'] }}</div>@endif
        @if(!empty($p['desc']))<p class="odp-plan-desc">{{ $p['desc'] }}</p>@endif
        <ul class="odp-plan-list">
          @foreach(array_filter($p['features'] ?? [], fn ($f) => trim($f['text'] ?? '') !== '') as $f)
            <li><span class="odp-check" aria-hidden="true">✓</span><span>{!! $f['text'] !!}</span></li>
          @endforeach
        </ul>
        {{-- Per-plan button; blank fields fall back to the shared enrol link. --}}
        <a class="odp-enrol" href="{{ trim($p['btn_href'] ?? '') ?: $enrol }}" target="_blank" rel="noopener">{{ trim($p['btn_label'] ?? '') ?: 'Enrol Now' }} &nbsp;→</a>
      </article>
    @endforeach
  </div>
</section>
