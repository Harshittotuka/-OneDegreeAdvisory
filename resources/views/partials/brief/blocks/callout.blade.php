@php $blkStyle = $blkStyle ?? ''; @endphp
<section class="odp-section" @if($blkStyle) style="{{ $blkStyle }}" @endif>
  <div class="odp-callout">
    <i data-lucide="{{ $data['icon'] ?: 'zap' }}" aria-hidden="true"></i>
    <div>
      @if(!empty($data['label']))<strong>{{ $data['label'] }}</strong>@endif
      <p>{{ $data['body'] ?? '' }}</p>
    </div>
  </div>
</section>
