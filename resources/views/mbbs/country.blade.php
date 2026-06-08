@php
    use Illuminate\Support\Str;

    $page           = $mbbsContent['page']           ?? [];
    $sections       = $mbbsContent['sections']       ?? [];
    $bullets        = $mbbsContent['bullets']        ?? [];
    $subpoints      = $mbbsContent['subpoints']      ?? [];
    $facts          = $mbbsContent['facts']          ?? [];
    $admissionSteps = $mbbsContent['admissionSteps'] ?? [];

    $countryName = $page['country'] ?? ($countryMeta['name'] ?? Str::headline($countrySlug));
    $countryFlag = $page['flag_code'] ?? ($countryMeta['flag'] ?? '');
    $flagUrl     = $page['flag_url'] ?? ($countryMeta['flag_url'] ?? '');
    $heroImage   = $page['hero_image'] ?? ($countryMeta['hero_image'] ?? '');
    $updated     = trim((string) ($page['source_updated'] ?? ''));

    $pageTitle       = ($page['page_title'] ?? 'MBBS in '.$countryName).' | One Degree Advisory';
    $pageDescription = $page['hero_text'] ?? 'Study MBBS in '.$countryName.' with One Degree Advisory.';
    $activeNav       = 'mbbs';
    $mainId          = 'mbbs-main';
    $bodyClass       = 'page-mbbs-country';

    $sectionRows = array_values($sections);
    usort($sectionRows, fn ($a, $b) => ((int) ($a['section_order'] ?? 0)) <=> ((int) ($b['section_order'] ?? 0)));

    $introSection = $sections['for_indian_students'] ?? null;
    $contentSections = array_values(array_filter(
        $sectionRows,
        fn ($section) => ! in_array((string) ($section['section_key'] ?? ''), ['for_indian_students', 'admission_process'], true)
    ));

    $subpointsBySection = [];
    foreach ($subpoints as $subpoint) {
        $key = (string) ($subpoint['section_key'] ?? '');
        if ($key !== '') {
            $subpointsBySection[$key][] = $subpoint;
        }
    }

    // Break a long body string into readable paragraphs — first on explicit
    // " | " separators, otherwise grouping sentences in pairs.
    $splitText = function (?string $text, int $max = 4): array {
        $text = trim((string) $text);
        if ($text === '') {
            return [];
        }
        $parts = preg_split('/\s+\|\s+/', $text) ?: [];
        if (count($parts) <= 1) {
            $sentences = preg_split('/(?<=[.!?])\s+(?=[A-Z(])/u', $text) ?: [$text];
            $parts = [];
            $buffer = [];
            foreach ($sentences as $sentence) {
                $buffer[] = trim($sentence);
                if (count($buffer) >= 2) {
                    $parts[] = implode(' ', array_filter($buffer));
                    $buffer = [];
                }
            }
            if ($buffer) {
                $parts[] = implode(' ', array_filter($buffer));
            }
        }

        return array_slice(array_values(array_filter(array_map('trim', $parts))), 0, $max);
    };

    // Strip a leading list glyph from scraped bullets so we don't double the marker.
    $cleanBullet = fn (?string $t) => trim((string) preg_replace('/^[\s•·▪◦\-–—]+/u', '', (string) $t));

    $sectionAnchor = fn (array $section, int $index): string
        => 'mbc-sec-'.Str::slug(($section['section_key'] ?? '') !== '' ? (string) $section['section_key'] : 'section-'.$index);

    $factIcons = ['clock-3', 'languages', 'badge-check', 'wallet', 'graduation-cap', 'landmark', 'calendar-days', 'file-check-2', 'globe-2', 'shield-check', 'book-open-check', 'plane'];

    // Highlights ticker — a short curated set of facts for the marquee band.
    $marqueeFacts = array_slice($facts, 0, 6);
    $totalSections = count($contentSections);

    // Where the hero "Read the guide" button jumps to (first available block).
    $guideStartHref = $facts ? '#quick-facts'
        : ($introSection ? '#overview'
        : ($contentSections ? '#'.$sectionAnchor($contentSections[0], 0) : '#top'));
@endphp

@extends('layouts.app')

@section('content')
<main id="mbbs-main" class="mbc">

  {{-- ============================ HERO ============================ --}}
  <section class="mbc-hero" id="top">
    <div class="mbc-hero__bg" aria-hidden="true">
      @if($heroImage !== '')
        <img class="mbc-hero__img" src="{{ $heroImage }}" alt="" loading="eager" fetchpriority="high">
      @endif
      <span class="mbc-hero__veil"></span>
      <span class="mbc-hero__glow"></span>
    </div>

    <div class="mbc-shell mbc-hero__inner">
      <a class="mbc-back" href="{{ route('mbbs.student') }}">
        <i data-lucide="arrow-left"></i>
        <span>All MBBS destinations</span>
      </a>

      <div class="mbc-hero__meta">
        <span class="mbc-flagchip">
          <span class="mbc-flagchip__flag" aria-hidden="true">
            @if($countryFlag !== '')
              <img src="https://flagcdn.com/w80/{{ strtolower($countryFlag) }}.png" alt="">
            @elseif($flagUrl !== '')
              <img src="{{ $flagUrl }}" alt="">
            @else
              {{ Str::upper(Str::substr($countryName, 0, 1)) }}
            @endif
          </span>
          <span class="mbc-flagchip__txt">
            <small>MBBS destination</small>
            <b>{{ $countryName }}</b>
          </span>
        </span>
        @if($updated !== '')
          <span class="mbc-stamp"><i data-lucide="badge-check"></i> Updated {{ $updated }}</span>
        @endif
      </div>

      <h1 class="mbc-hero__title">{{ $page['hero_heading'] ?? 'MBBS in '.$countryName }}</h1>

      @if(! empty($page['hero_text']))
        <p class="mbc-hero__lede">{{ $page['hero_text'] }}</p>
      @endif

      <div class="mbc-hero__actions">
        <a class="btn btn-primary mbc-cta-btn" href="{{ route('contact') }}">
          <span>Talk to a counsellor</span>
          <i data-lucide="arrow-up-right"></i>
        </a>
        @if($facts || $introSection || $contentSections)
          <a class="btn mbc-btn-ghost" href="{{ $guideStartHref }}">
            <i data-lucide="book-open"></i>
            <span>Read the guide</span>
          </a>
        @endif
      </div>
    </div>
  </section>

  {{-- ======================= HIGHLIGHTS MARQUEE ======================= --}}
  @if($marqueeFacts)
    <section class="mbc-marquee" aria-label="{{ $countryName }} highlights">
      <div class="mbc-marquee__viewport">
        <div class="mbc-marquee__track">
          @for($copy = 0; $copy < 2; $copy++)
            <div class="mbc-marquee__group" @if($copy === 1) aria-hidden="true" @endif>
              @foreach($marqueeFacts as $index => $fact)
                <span class="mbc-chip">
                  <i data-lucide="{{ $factIcons[$index % count($factIcons)] }}"></i>
                  <span class="mbc-chip__label">{{ $fact['fact_label'] }}</span>
                  <span class="mbc-chip__value">{{ $fact['fact_value'] }}</span>
                </span>
              @endforeach
            </div>
          @endfor
        </div>
      </div>
    </section>
  @endif

  {{-- ============================ AT A GLANCE (sticky title) ============================ --}}
  @if($facts)
    <section class="mbc-sec mbc-sec--light" id="quick-facts">
      <div class="mbc-shell mbc-sec__grid">
        <aside class="mbc-sec__aside">
          <span class="mbc-eyebrow"><i data-lucide="sparkles"></i> Quick facts</span>
          <h2>MBBS in {{ $countryName }} at a glance</h2>
          <p class="mbc-sec__note">Verified, source-backed essentials — fees, duration, eligibility and recognition for {{ $countryName }}.</p>
          <a class="mbc-textlink" href="{{ route('contact') }}">
            <span>Ask about {{ $countryName }}</span>
            <i data-lucide="arrow-right"></i>
          </a>
        </aside>
        <div class="mbc-sec__main">
          <div class="mbc-facts">
            @foreach($facts as $index => $fact)
              <article class="mbc-factcard reveal" style="--i: {{ $index % 4 }};">
                <span class="mbc-factcard__icon" aria-hidden="true"><i data-lucide="{{ $factIcons[$index % count($factIcons)] }}"></i></span>
                <p class="mbc-factcard__label">{{ $fact['fact_label'] }}</p>
                <strong class="mbc-factcard__value">{{ $fact['fact_value'] }}</strong>
              </article>
            @endforeach
          </div>
        </div>
      </div>
    </section>
  @endif

  {{-- ============================ OVERVIEW (sticky title + image) ============================ --}}
  @if($introSection)
    <section class="mbc-sec mbc-sec--paper" id="overview">
      <div class="mbc-shell mbc-sec__grid">
        <aside class="mbc-sec__aside">
          <span class="mbc-eyebrow"><i data-lucide="compass"></i> Overview</span>
          <h2>{{ $introSection['section_heading'] }}</h2>
          @if($heroImage !== '')
            <figure class="mbc-figure">
              <img src="{{ $heroImage }}" alt="{{ $countryName }}" loading="lazy">
              <figcaption><i data-lucide="map-pin"></i> {{ $countryName }}</figcaption>
            </figure>
          @endif
          <a class="mbc-textlink" href="{{ route('contact') }}">
            <span>Speak to our {{ $countryName }} team</span>
            <i data-lucide="arrow-right"></i>
          </a>
        </aside>
        <div class="mbc-sec__main">
          <div class="mbc-prose mbc-prose--lead">
            <span class="mbc-quote" aria-hidden="true"><i data-lucide="quote"></i></span>
            @foreach($splitText($introSection['section_body'] ?? '', 6) as $paragraph)
              <p>{{ $paragraph }}</p>
            @endforeach
          </div>
        </div>
      </div>
    </section>
  @endif

  {{-- ============================ GUIDE SECTIONS (each: sticky title + scrolling content) ============================ --}}
  @foreach($contentSections as $i => $section)
    @php
      $key = (string) ($section['section_key'] ?? '');
      $anchor = $sectionAnchor($section, $i);
      $sectionBullets = $bullets[$key] ?? [];
      $sectionSubpoints = $subpointsBySection[$key] ?? [];
      $paragraphs = $splitText($section['section_body'] ?? '', 6);
      $useCarousel = count($sectionSubpoints) >= 3;
    @endphp
    <section class="mbc-sec {{ $i % 2 === 0 ? 'mbc-sec--light' : 'mbc-sec--paper' }}" id="{{ $anchor }}">
      <div class="mbc-shell mbc-sec__grid">
        <aside class="mbc-sec__aside">
          <span class="mbc-sec__index">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }} <i>/</i> {{ str_pad((string) $totalSections, 2, '0', STR_PAD_LEFT) }}</span>
          <h2>{{ $section['section_heading'] }}</h2>
          <a class="mbc-textlink" href="{{ route('contact') }}">
            <span>Questions? Talk to us</span>
            <i data-lucide="arrow-right"></i>
          </a>
        </aside>

        <div class="mbc-sec__main">
          @if($paragraphs)
            <div class="mbc-prose">
              @foreach($paragraphs as $paragraph)
                <p>{{ $paragraph }}</p>
              @endforeach
            </div>
          @endif

          @if($sectionBullets)
            <ul class="mbc-checks">
              @foreach(array_slice($sectionBullets, 0, 16) as $bullet)
                <li>
                  <i data-lucide="check"></i>
                  <span>{{ $cleanBullet($bullet['bullet_text'] ?? '') }}</span>
                </li>
              @endforeach
            </ul>
          @endif

          @if($sectionSubpoints && $useCarousel)
            <div class="mbc-carousel" data-mbc-carousel>
              <div class="mbc-carousel__bar">
                <span class="mbc-carousel__count"><i data-lucide="layers"></i> {{ count($sectionSubpoints) }} listed</span>
                <div class="mbc-carousel__nav">
                  <button type="button" class="carousel-btn mbc-cbtn" data-mbc-prev aria-label="Previous"><i data-lucide="chevron-left"></i></button>
                  <button type="button" class="carousel-btn mbc-cbtn" data-mbc-next aria-label="Next"><i data-lucide="chevron-right"></i></button>
                </div>
              </div>
              <div class="mbc-carousel__track" data-mbc-track>
                @foreach($sectionSubpoints as $subpoint)
                  <article class="mbc-uni" data-mbc-item>
                    @if(! empty($subpoint['subpoint_heading']))
                      <h3>{{ $subpoint['subpoint_heading'] }}</h3>
                    @endif
                    @foreach($splitText($subpoint['subpoint_body'] ?? '', 4) as $paragraph)
                      <p>{{ $paragraph }}</p>
                    @endforeach
                  </article>
                @endforeach
              </div>
            </div>
          @elseif($sectionSubpoints)
            <div class="mbc-cards">
              @foreach($sectionSubpoints as $subpoint)
                <article class="mbc-card reveal">
                  @if(! empty($subpoint['subpoint_heading']))
                    <h3>{{ $subpoint['subpoint_heading'] }}</h3>
                  @endif
                  @foreach($splitText($subpoint['subpoint_body'] ?? '', 4) as $paragraph)
                    <p>{{ $paragraph }}</p>
                  @endforeach
                </article>
              @endforeach
            </div>
          @endif
        </div>
      </div>
    </section>
  @endforeach

  {{-- ============================ ADMISSION PROCESS (carousel) ============================ --}}
  @if($admissionSteps)
    <section class="mbc-process" id="admission-process">
      <div class="mbc-shell">
        <header class="mbc-head mbc-head--center reveal">
          <span class="mbc-eyebrow"><i data-lucide="route"></i> Admission process</span>
          <h2>{{ $sections['admission_process']['section_heading'] ?? 'How we take you from shortlist to seat' }}</h2>
        </header>

        <div class="mbc-carousel mbc-carousel--steps" data-mbc-carousel>
          <div class="mbc-carousel__track" data-mbc-track>
            @foreach($admissionSteps as $i => $step)
              @php
                $parts = array_values(array_filter(array_map('trim', explode('|', (string) ($step['step_body'] ?? '')))));
              @endphp
              <article class="mbc-stepcard" data-mbc-item>
                <span class="mbc-stepcard__num">{{ str_pad((string) ($step['step_order'] ?? $i + 1), 2, '0', STR_PAD_LEFT) }}</span>
                <h3>{{ $step['step_title'] }}</h3>
                @if($parts)
                  <ul>
                    @foreach($parts as $part)
                      <li>{{ $part }}</li>
                    @endforeach
                  </ul>
                @endif
              </article>
            @endforeach
          </div>
          <div class="mbc-carousel__nav mbc-carousel__nav--center">
            <button type="button" class="carousel-btn mbc-cbtn" data-mbc-prev aria-label="Previous step"><i data-lucide="chevron-left"></i></button>
            <button type="button" class="carousel-btn mbc-cbtn" data-mbc-next aria-label="Next step"><i data-lucide="chevron-right"></i></button>
          </div>
        </div>
      </div>
    </section>
  @endif

  {{-- ============================ CTA ============================ --}}
  <section class="mbc-cta">
    <div class="mbc-shell mbc-cta__panel reveal">
      @if($heroImage !== '')
        <img class="mbc-cta__img" src="{{ $heroImage }}" alt="" aria-hidden="true" loading="lazy">
      @endif
      <span class="mbc-cta__orb mbc-cta__orb--a" aria-hidden="true"></span>
      <span class="mbc-cta__orb mbc-cta__orb--b" aria-hidden="true"></span>
      <div class="mbc-cta__copy">
        <span class="mbc-eyebrow mbc-eyebrow--light"><i data-lucide="graduation-cap"></i> One Degree Advisory</span>
        <h2>Build your {{ $countryName }} MBBS shortlist with verified details.</h2>
        <p>Bring your NEET score, budget range, and intake target. We will help you compare universities, requirements, timelines, and the exact next steps.</p>
        <div class="mbc-cta__actions">
          <a class="btn btn-primary mbc-cta-btn" href="{{ route('contact') }}">
            <span>Book free counselling</span>
            <i data-lucide="arrow-up-right"></i>
          </a>
          <a class="mbc-cta__alt" href="{{ route('mbbs.student') }}">
            <i data-lucide="layout-grid"></i>
            <span>Compare all destinations</span>
          </a>
        </div>
      </div>
    </div>
  </section>

</main>

<script>
  // Scoped, self-contained carousels (universities + admission steps). Pure
  // progressive enhancement: the track is a native scroll-snap row, so it works
  // by swipe/scroll even if this never runs. Buttons + mouse-drag are extras.
  (function () {
    var roots = document.querySelectorAll('[data-mbc-carousel]');
    if (!roots.length) return;
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    roots.forEach(function (root) {
      var track = root.querySelector('[data-mbc-track]');
      if (!track) return;
      var prev = root.querySelector('[data-mbc-prev]');
      var next = root.querySelector('[data-mbc-next]');

      var stepBy = function (dir) {
        var item = track.querySelector('[data-mbc-item]');
        var amount = item ? (item.getBoundingClientRect().width + 18) : (track.clientWidth * 0.85);
        track.scrollBy({ left: dir * amount, behavior: reduce ? 'auto' : 'smooth' });
      };

      if (prev) prev.addEventListener('click', function () { stepBy(-1); });
      if (next) next.addEventListener('click', function () { stepBy(1); });

      var sync = function () {
        var max = track.scrollWidth - track.clientWidth - 2;
        if (prev) prev.disabled = track.scrollLeft <= 2;
        if (next) next.disabled = track.scrollLeft >= max;
      };
      track.addEventListener('scroll', sync, { passive: true });
      window.addEventListener('resize', sync);
      sync();

      // Mouse drag-to-scroll (touch uses native momentum scrolling).
      var down = false, startX = 0, startLeft = 0, moved = false;
      track.addEventListener('pointerdown', function (e) {
        if (e.pointerType === 'touch') return;
        down = true; moved = false; startX = e.clientX; startLeft = track.scrollLeft;
        track.classList.add('is-grab');
      });
      window.addEventListener('pointermove', function (e) {
        if (!down) return;
        var dx = e.clientX - startX;
        if (Math.abs(dx) > 3) moved = true;
        track.scrollLeft = startLeft - dx;
      });
      window.addEventListener('pointerup', function () {
        down = false; track.classList.remove('is-grab');
      });
      // Swallow click after a drag so cards/links don't fire accidentally.
      track.addEventListener('click', function (e) {
        if (moved) { e.preventDefault(); e.stopPropagation(); moved = false; }
      }, true);
    });
  })();
</script>
@endsection
