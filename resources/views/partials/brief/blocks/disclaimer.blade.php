@php
    $blkStyle = $blkStyle ?? '';
    $items = array_filter($data['items'] ?? [], fn ($i) => trim($i['text'] ?? '') !== '');
@endphp
<section class="odp-file-disclaimer" @if($blkStyle) style="{{ $blkStyle }}" @endif>
  @if(!empty($data['heading']))<h2>{{ $data['heading'] }}</h2>@endif
  <ul class="odp-disclaimer-list">
    @foreach($items as $i)
      <li><span class="odp-check" aria-hidden="true">•</span><span>{!! $i['text'] !!}</span></li>
    @endforeach
  </ul>
</section>
