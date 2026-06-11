@php $blkStyle = $blkStyle ?? ''; @endphp
<div class="odp-country-banner" @if($blkStyle) style="{{ $blkStyle }}" @endif>
  @if(!empty($data['flag']))
    <img src="{{ $data['flag'] }}" alt="" width="120" height="72" loading="lazy">
  @endif
  <div class="odp-cb-body">
    @if(!empty($data['kicker']))<p class="odp-cb-kicker">{{ $data['kicker'] }}</p>@endif
    <h2>{{ $data['heading'] ?? '' }}</h2>
  </div>
</div>
