@php $blkStyle = $blkStyle ?? ''; @endphp
<section class="odp-cta-band" @if($blkStyle) style="{{ $blkStyle }}" @endif>
  @if(!empty($data['heading']))<h2>{{ $data['heading'] }}</h2>@endif
  @if(!empty($data['body']))<p>{{ $data['body'] }}</p>@endif
  @if(!empty($data['btn_label']))
    <a class="odp-web-btn odp-web-btn-secondary" href="{{ $data['btn_href'] ?: route('contact') }}">
      @if(!empty($data['btn_icon']))<i data-lucide="{{ $data['btn_icon'] }}" aria-hidden="true"></i>@endif
      <span>{{ $data['btn_label'] }}</span>
    </a>
  @endif
</section>
