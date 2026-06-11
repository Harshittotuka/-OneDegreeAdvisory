@php
    $blkStyle = $blkStyle ?? '';
    $steps = array_filter($data['steps'] ?? [], fn ($s) => trim($s['heading'] ?? '') !== '' || !empty($s['items']));
@endphp
<section class="odp-file-surface odp-journey" @if($blkStyle) style="{{ $blkStyle }}" @endif>
  @if(!empty($data['title']))<h2 class="odp-journey-title">{{ $data['title'] }}</h2>@endif
  <div class="odp-journey-steps">
    @foreach($steps as $index => $step)
      <article class="odp-step">
        <div class="odp-step-num">{{ $index + 1 }}</div>
        @if(!empty($step['label']))<div class="odp-step-label">{{ $step['label'] }}</div>@endif
        @if(!empty($step['heading']))<h3 class="odp-step-heading">{{ $step['heading'] }}</h3>@endif
        <ul class="odp-step-items">
          @foreach(array_filter($step['items'] ?? [], fn ($i) => trim($i['name'] ?? '') !== '') as $item)
            <li>
              <div class="odp-si-name">{{ $item['name'] }}</div>
              @if(!empty($item['desc']))<div class="odp-si-desc">{{ $item['desc'] }}</div>@endif
            </li>
          @endforeach
        </ul>
      </article>
    @endforeach

    @if(!empty($data['final_title']) || !empty($data['final_body']))
      <article class="odp-final-step">
        <div class="plane" aria-hidden="true">✈️</div>
        @if(!empty($data['final_title']))<h3>{{ $data['final_title'] }}</h3>@endif
        @if(!empty($data['final_body']))<p>{{ $data['final_body'] }}</p>@endif
      </article>
    @endif
  </div>
</section>
