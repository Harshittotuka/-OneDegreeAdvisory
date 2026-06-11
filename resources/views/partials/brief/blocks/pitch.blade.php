@php
    $blkStyle = $blkStyle ?? '';
    $cols = array_filter($data['columns'] ?? [], fn ($c) => trim($c['heading'] ?? '') !== '' || !empty($c['items']));
@endphp
<section class="odp-section" @if($blkStyle) style="{{ $blkStyle }}" @endif>
  @if(!empty($data['label']))<p class="odp-section-label">{{ $data['label'] }}</p>@endif
  <div class="odp-pitch">
    @if(!empty($data['heading']))<h2>{{ $data['heading'] }}</h2>@endif
    @if(!empty($data['intro']))<p>{{ $data['intro'] }}</p>@endif
    @if(count($cols))
      <div class="odp-pitch-cols">
        @foreach($cols as $col)
          <div class="odp-pitch-col">
            @if(!empty($col['heading']))<h3>{{ $col['heading'] }}</h3>@endif
            @foreach(array_filter($col['items'] ?? [], fn ($i) => trim($i['text'] ?? '') !== '') as $i)
              <p>↗ {{ $i['text'] }}</p>
            @endforeach
          </div>
        @endforeach
      </div>
    @endif
  </div>
</section>
