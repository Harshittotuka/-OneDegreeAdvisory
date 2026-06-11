@php
    $blkStyle = $blkStyle ?? '';
    $cards = array_values(array_filter($data['cards'] ?? [], fn ($c) => trim($c['tier'] ?? '') !== '' || trim($c['amount'] ?? '') !== ''));
@endphp
<section class="odp-file-surface odp-referral" @if($blkStyle) style="{{ $blkStyle }}" @endif>
  @if(!empty($data['title']))<h2 class="odp-referral-title">{{ $data['title'] }}</h2>@endif
  @if(!empty($data['subtitle']))<p class="odp-referral-sub">{{ $data['subtitle'] }}</p>@endif
  <div class="odp-referral-cards">
    @foreach($cards as $index => $v)
      <article class="odp-ref-card {{ $v['variant'] ?? 'explorer' }}" data-ref-slide="{{ $index }}">
        @if(!empty($v['badge']))<div class="odp-ref-badge">{{ $v['badge'] }}</div>@endif
        @if(!empty($v['icon']))<div class="odp-ref-icon" aria-hidden="true">{{ $v['icon'] }}</div>@endif
        <div class="odp-ref-plan">{{ $v['tier'] ?? '' }}</div>
        <div class="odp-ref-voucher">{{ $v['amount'] ?? '' }}</div>
        <div class="odp-ref-label">Credit per referral</div>
      </article>
    @endforeach
  </div>
  <div class="odp-ref-controls" aria-label="Voucher carousel controls">
    @foreach($cards as $index => $v)
      <button class="odp-ref-control @if($index === 0) is-active @endif" type="button" data-ref-target="{{ $index }}">{{ $v['tier'] ?? ('Card '.($index + 1)) }}</button>
    @endforeach
  </div>
</section>

@once
<script>
  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.odp-referral').forEach(function (root) {
      var carousel = root.querySelector('.odp-referral-cards');
      var buttons = Array.prototype.slice.call(root.querySelectorAll('.odp-ref-control'));
      var slides = Array.prototype.slice.call(root.querySelectorAll('[data-ref-slide]'));
      if (!carousel || !buttons.length || !slides.length) return;
      function setActive(i) { buttons.forEach(function (b, bi) { b.classList.toggle('is-active', bi === i); }); }
      buttons.forEach(function (b) {
        b.addEventListener('click', function () {
          var i = Number(b.getAttribute('data-ref-target'));
          var s = slides[i];
          if (!s) return;
          carousel.scrollTo({ left: s.offsetLeft - carousel.offsetLeft - 20, behavior: 'smooth' });
          setActive(i);
        });
      });
      carousel.addEventListener('scroll', function () {
        var center = carousel.scrollLeft + carousel.clientWidth / 2, idx = 0, closest = Infinity;
        slides.forEach(function (s, i) { var c = s.offsetLeft + s.offsetWidth / 2, d = Math.abs(center - c); if (d < closest) { closest = d; idx = i; } });
        setActive(idx);
      }, { passive: true });
    });
  });
</script>
@endonce
