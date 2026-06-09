@php
    use Illuminate\Support\Str;

    $page      = $mbbsContent['page']      ?? [];
    $sections  = $mbbsContent['sections']  ?? [];
    $subpoints = $mbbsContent['subpoints'] ?? [];
    $facts     = $mbbsContent['facts']     ?? [];
    $neet      = $mbbsContent['neet']      ?? [];
    $neetTrend = $mbbsContent['neetTrend'] ?? [];

    $countryName = $page['country']   ?? ($countryMeta['name'] ?? Str::headline($countrySlug));
    $countryFlag = $page['flag_code'] ?? ($countryMeta['flag'] ?? '');
    $flagUrl     = $page['flag_url']  ?? ($countryMeta['flag_url'] ?? '');
    $heroImage   = $page['hero_image'] ?? ($countryMeta['hero_image'] ?? '');
    $heroBadge   = trim((string) ($page['hero_badge'] ?? ''));
    $updated     = trim((string) ($page['source_updated'] ?? ''));

    $pageTitle       = ($page['page_title'] ?? 'MBBS in '.$countryName).' | One Degree Advisory';
    $pageDescription = $page['hero_text'] ?? 'Study MBBS in '.$countryName.' with One Degree Advisory.';
    $activeNav       = 'mbbs';
    $mainId          = 'mbbs-main';
    $bodyClass       = 'page-mbbs-country';

    // ---- helpers ----------------------------------------------------------
    // Split a long body into readable paragraphs (explicit " | " first, else sentence pairs).
    $splitText = function (?string $text, int $max = 5): array {
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

    // Pipe-joined bullet string -> clean array.
    $bullets = function (array $sp): array {
        $raw = (string) ($sp['subpoint_bullets'] ?? '');
        if ($raw === '') {
            return [];
        }
        $parts = preg_split('/\s*\|\s*/', $raw) ?: [];
        return array_values(array_filter(array_map(
            fn ($b) => trim((string) preg_replace('/^[\s•·▪◦\-–—!]+/u', '', (string) $b)),
            $parts
        )));
    };

    // Tidy a card title (drop trailing colon / leading alert glyph).
    $tidyHeading = fn (?string $t) => trim((string) preg_replace('/[:\s]+$/', '', trim((string) preg_replace('/^[\s!•]+/u', '', (string) $t))));

    // "Label: description" -> [title, text]; plain text -> ['', text].
    $titled = function (string $line): array {
        if (preg_match('/^([^:]{3,64}):\s*(.+)$/u', $line, $m)) {
            return [trim($m[1]), trim($m[2])];
        }
        return ['', trim($line)];
    };

    // ---- 3: Why study / Honest assessment --------------------------------
    $whySection = $sections['why_study'] ?? ($sections['fit_assessment'] ?? null);
    $whyKey     = $whySection['section_key'] ?? '';
    $whySubs    = $whyKey !== '' ? ($subpoints[$whyKey] ?? []) : [];
    $whyNeutral = array_values(array_filter($whySubs, fn ($s) => ($s['subpoint_tone'] ?? 'neutral') === 'neutral'));
    $whyPos     = array_values(array_filter($whySubs, fn ($s) => ($s['subpoint_tone'] ?? '') === 'positive'));
    $whyNeg     = array_values(array_filter($whySubs, fn ($s) => ($s['subpoint_tone'] ?? '') === 'negative'));
    $isFit      = $whyKey === 'fit_assessment';

    // ---- 4: NMC Gazette compliance ---------------------------------------
    $nmcSection = $sections['nmc_compliance'] ?? null;
    $nmcSubs    = $nmcSection ? ($subpoints['nmc_compliance'] ?? []) : [];
    $nmcCards   = [];
    $nmcNotes   = [];
    foreach ($nmcSubs as $sp) {
        $bl   = $bullets($sp);
        $body = trim((string) ($sp['subpoint_body'] ?? ''));
        if ($bl) {
            if ($body !== '') {
                $nmcNotes[] = $body;
            }
            foreach ($bl as $line) {
                [$t, $d] = $titled($line);
                $nmcCards[] = ['title' => $t, 'text' => $d];
            }
        } elseif ($body !== '') {
            $nmcCards[] = ['title' => $tidyHeading($sp['subpoint_heading'] ?? ''), 'text' => $body];
        }
    }

    // ---- 5: Daily life ----------------------------------------------------
    $dailySection = $sections['student_life'] ?? null;
    $dailySubs    = $dailySection ? ($subpoints['student_life'] ?? []) : [];
    $dailyIcon = function (string $heading): string {
        $h = Str::lower($heading);
        return match (true) {
            str_contains($h, 'weather') || str_contains($h, 'climate') => 'cloud-sun',
            str_contains($h, 'food')                                   => 'utensils',
            str_contains($h, 'hostel') || str_contains($h, 'accommod') => 'bed-double',
            str_contains($h, 'safe')                                   => 'shield-check',
            str_contains($h, 'around') || str_contains($h, 'transport')=> 'bus',
            str_contains($h, 'homesick')                               => 'heart',
            str_contains($h, 'communit')                               => 'users',
            str_contains($h, 'language')                               => 'languages',
            str_contains($h, 'cost') || str_contains($h, 'money')      => 'wallet',
            default                                                    => 'sparkles',
        };
    };

    $factIcons = ['graduation-cap', 'clock-3', 'languages', 'badge-check', 'wallet', 'banknote', 'coins', 'list-checks', 'calendar-days', 'calendar-clock', 'home', 'shield-check'];

    // Trend direction icon for the NEET history table.
    $trendIcon = function (string $trend): array {
        $t = Str::lower($trend);
        if (str_contains($t, 'down')) return ['trending-down', 'is-down'];
        if (str_contains($t, 'up'))   return ['trending-up', 'is-up'];
        return ['minus', 'is-flat'];
    };

    // ---- sticky jump nav --------------------------------------------------
    $nav = [];
    if ($facts)      $nav[] = ['href' => '#quick-facts', 'label' => 'Quick Facts'];
    if ($whySection) $nav[] = ['href' => '#why', 'label' => $isFit ? 'Honest Assessment' : 'Why '.$countryName];
    if ($nmcSection) $nav[] = ['href' => '#nmc', 'label' => 'NMC Compliance'];
    if ($dailySection) $nav[] = ['href' => '#daily', 'label' => 'Daily Life'];
    if ($neet)       $nav[] = ['href' => '#neet', 'label' => 'NEET Scores'];
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

      @if($heroBadge !== '')
        <p class="mbc-hero__badge"><i data-lucide="shield-check"></i> {{ $heroBadge }}</p>
      @endif

      <div class="mbc-hero__actions">
        <a class="btn btn-primary mbc-cta-btn" href="{{ route('contact') }}">
          <span>Talk to a counsellor</span>
          <i data-lucide="arrow-up-right"></i>
        </a>
        @if($nav)
          <a class="btn mbc-btn-ghost" href="{{ $nav[0]['href'] }}">
            <i data-lucide="book-open"></i>
            <span>Read the guide</span>
          </a>
        @endif
      </div>
    </div>
  </section>

  {{-- ======================= STICKY JUMP NAV ======================= --}}
  @if(count($nav) > 1)
    <nav class="mbc-jump" id="mbc-jump" aria-label="On this page">
      <div class="mbc-shell mbc-jump__inner">
        <span class="mbc-jump__label"><i data-lucide="list"></i> On this page</span>
        <div class="mbc-jump__links">
          @foreach($nav as $item)
            <a href="{{ $item['href'] }}" data-mbc-jump>{{ $item['label'] }}</a>
          @endforeach
        </div>
        <a class="mbc-jump__cta" href="{{ route('contact') }}">Free counselling <i data-lucide="arrow-right"></i></a>
      </div>
    </nav>
  @endif

  {{-- ============================ QUICK FACTS ============================ --}}
  @if($facts)
    <section class="mbc-band mbc-band--paper" id="quick-facts">
      <div class="mbc-shell">
        <header class="mbc-band__head">
          <span class="mbc-eyebrow"><i data-lucide="sparkles"></i> At a glance</span>
          <h2>Quick Facts: MBBS in {{ $countryName }}</h2>
          <p class="mbc-band__lead">Source-verified essentials — course, duration, medium, fees, eligibility, intakes and recognition for {{ $countryName }}.</p>
        </header>

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
    </section>
  @endif

  {{-- ============================ WHY STUDY / HONEST ASSESSMENT ============================ --}}
  @if($whySection)
    <section class="mbc-band mbc-band--light" id="why">
      <div class="mbc-shell">
        <header class="mbc-band__head">
          <span class="mbc-eyebrow">
            <i data-lucide="{{ $isFit ? 'scale' : 'award' }}"></i>
            {{ $isFit ? 'Honest assessment' : 'Why '.$countryName }}
          </span>
          <h2>{{ $whySection['section_heading'] }}</h2>
          @foreach($splitText($whySection['section_intro'] ?? '', 3) as $p)
            <p class="mbc-band__lead">{{ $p }}</p>
          @endforeach
        </header>

        @if($whyNeutral)
          <div class="mbc-benefits">
            @foreach($whyNeutral as $index => $sp)
              <article class="mbc-benefit reveal" style="--i: {{ $index % 3 }};">
                <span class="mbc-benefit__icon" aria-hidden="true"><i data-lucide="check-circle-2"></i></span>
                <h3>{{ $tidyHeading($sp['subpoint_heading'] ?? '') }}</h3>
                @foreach($splitText($sp['subpoint_body'] ?? '', 2) as $p)
                  <p>{{ $p }}</p>
                @endforeach
              </article>
            @endforeach
          </div>
        @endif

        @if($whyPos || $whyNeg)
          <div class="mbc-verdict {{ ($whyPos && $whyNeg) ? '' : 'mbc-verdict--single' }}">
            @foreach($whyPos as $sp)
              <div class="mbc-verdict__col mbc-verdict__col--pos">
                <div class="mbc-verdict__head"><i data-lucide="thumbs-up"></i><h3>{{ $tidyHeading($sp['subpoint_heading'] ?? '') ?: $countryName.' is well-suited if' }}</h3></div>
                <ul class="mbc-verdict__list">
                  @forelse($bullets($sp) as $b)
                    <li><i data-lucide="check"></i><span>{{ $b }}</span></li>
                  @empty
                    @foreach($splitText($sp['subpoint_body'] ?? '', 4) as $p)
                      <li><i data-lucide="check"></i><span>{{ $p }}</span></li>
                    @endforeach
                  @endforelse
                </ul>
              </div>
            @endforeach

            @foreach($whyNeg as $sp)
              <div class="mbc-verdict__col mbc-verdict__col--neg">
                <div class="mbc-verdict__head"><i data-lucide="alert-triangle"></i><h3>{{ $tidyHeading($sp['subpoint_heading'] ?? '') ?: 'Consider alternatives if' }}</h3></div>
                <ul class="mbc-verdict__list">
                  @forelse($bullets($sp) as $b)
                    <li><i data-lucide="x"></i><span>{{ $b }}</span></li>
                  @empty
                    @foreach($splitText($sp['subpoint_body'] ?? '', 4) as $p)
                      <li><i data-lucide="x"></i><span>{{ $p }}</span></li>
                    @endforeach
                  @endforelse
                </ul>
              </div>
            @endforeach
          </div>
        @endif
      </div>
    </section>
  @endif

  {{-- ============================ NMC GAZETTE COMPLIANCE ============================ --}}
  @if($nmcSection && $nmcCards)
    <section class="mbc-band mbc-band--paper" id="nmc">
      <div class="mbc-shell">
        <header class="mbc-band__head">
          <span class="mbc-eyebrow"><i data-lucide="gavel"></i> Legal compliance</span>
          <h2>{{ $nmcSection['section_heading'] }}</h2>
          @foreach($splitText($nmcSection['section_intro'] ?? '', 2) as $p)
            <p class="mbc-band__lead">{{ $p }}</p>
          @endforeach
        </header>

        @foreach($nmcNotes as $note)
          <p class="mbc-note"><i data-lucide="alert-triangle"></i><span>{{ $note }}</span></p>
        @endforeach

        <div class="mbc-checklist">
          @foreach($nmcCards as $index => $card)
            <article class="mbc-check2 reveal" style="--i: {{ $index % 3 }};">
              <span class="mbc-check2__num">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
              <div class="mbc-check2__body">
                @if($card['title'] !== '')
                  <h3>{{ $card['title'] }}</h3>
                @endif
                <p>{{ $card['text'] }}</p>
              </div>
            </article>
          @endforeach
        </div>
      </div>
    </section>
  @endif

  {{-- ============================ DAILY LIFE ============================ --}}
  @if($dailySection && ($dailySubs || trim((string) ($dailySection['section_intro'] ?? '')) !== ''))
    <section class="mbc-band mbc-band--light" id="daily">
      <div class="mbc-shell">
        <header class="mbc-band__head">
          <span class="mbc-eyebrow"><i data-lucide="sun"></i> On the ground</span>
          <h2>{{ $dailySection['section_heading'] }}</h2>
          @if($dailySubs)
            @foreach($splitText($dailySection['section_intro'] ?? '', 2) as $p)
              <p class="mbc-band__lead">{{ $p }}</p>
            @endforeach
          @endif
        </header>

        @if($dailySubs)
          <div class="mbc-daily">
            @foreach($dailySubs as $index => $sp)
              <article class="mbc-daily__card reveal" style="--i: {{ $index % 3 }};">
                <span class="mbc-daily__icon" aria-hidden="true"><i data-lucide="{{ $dailyIcon($sp['subpoint_heading'] ?? '') }}"></i></span>
                <h3>{{ $tidyHeading($sp['subpoint_heading'] ?? '') }}</h3>
                @foreach($splitText($sp['subpoint_body'] ?? '', 3) as $p)
                  <p>{{ $p }}</p>
                @endforeach
                @if($bullets($sp))
                  <ul>
                    @foreach($bullets($sp) as $b)
                      <li>{{ $b }}</li>
                    @endforeach
                  </ul>
                @endif
              </article>
            @endforeach
          </div>
        @else
          <div class="mbc-prose-panel">
            @foreach($splitText($dailySection['section_intro'] ?? '', 8) as $p)
              <p>{{ $p }}</p>
            @endforeach
          </div>
        @endif
      </div>
    </section>
  @endif

  {{-- ============================ NEET SCORE REQUIREMENTS ============================ --}}
  @if($neet)
    <section class="mbc-band mbc-band--paper" id="neet">
      <div class="mbc-shell">
        <header class="mbc-band__head">
          <span class="mbc-eyebrow"><i data-lucide="target"></i> Eligibility</span>
          <h2>NEET Score Requirements (2026&ndash;2027)</h2>
          <p class="mbc-band__lead">The qualifying NEET marks Indian students need for MBBS in {{ $countryName }}, with the recent cut-off trend.</p>
        </header>

        <div class="mbc-neet">
          <div class="mbc-neet__cards">
            @foreach($neet as $index => $n)
              <article class="mbc-neetcard reveal {{ $index === 0 ? 'mbc-neetcard--primary' : '' }}" style="--i: {{ $index }};">
                <span class="mbc-neetcard__cat">{{ $n['category'] }}</span>
                <strong class="mbc-neetcard__marks">{{ $n['marks'] ?: '—' }}</strong>
                @if(! empty($n['note']))
                  <p class="mbc-neetcard__note">{{ $n['note'] }}</p>
                @endif
              </article>
            @endforeach
          </div>

          @if($neetTrend)
            <figure class="mbc-trend">
              <figcaption><i data-lucide="line-chart"></i> Historical NEET cut-off trend</figcaption>
              <div class="mbc-trend__scroll">
                <table>
                  <thead>
                    <tr><th>Year</th><th>General</th><th>Reserved</th><th>Trend</th></tr>
                  </thead>
                  <tbody>
                    @foreach($neetTrend as $row)
                      @php [$ic, $cls] = $trendIcon($row['trend'] ?? ''); @endphp
                      <tr>
                        <td class="mbc-trend__year">{{ $row['year'] }}</td>
                        <td>{{ $row['general_marks'] }}</td>
                        <td>{{ $row['reserved_marks'] }}</td>
                        <td><span class="mbc-trend__dir {{ $cls }}"><i data-lucide="{{ $ic }}"></i> <span class="mbc-trend__word">{{ $row['trend'] ?: '—' }}</span></span></td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
              <p class="mbc-trend__foot">* Based on official NMC qualifying minimums.</p>
            </figure>
          @endif
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
        <h2>Build your {{ $countryName }} MBBS plan with verified details.</h2>
        <p>Bring your NEET score, budget range, and intake target. We'll help you compare requirements, timelines, and the exact next steps — honestly.</p>
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
  // Sticky jump-nav: highlight the section currently in view.
  (function () {
    var nav = document.getElementById('mbc-jump');
    if (!nav) return;
    var links = Array.prototype.slice.call(nav.querySelectorAll('[data-mbc-jump]'));
    if (!links.length) return;

    var targets = links.map(function (a) {
      var id = a.getAttribute('href').slice(1);
      return { link: a, el: document.getElementById(id) };
    }).filter(function (t) { return t.el; });

    if (!('IntersectionObserver' in window)) return;

    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        var match = targets.find(function (t) { return t.el === entry.target; });
        if (!match) return;
        links.forEach(function (l) { l.classList.remove('is-active'); });
        match.link.classList.add('is-active');
      });
    }, { rootMargin: '-45% 0px -50% 0px', threshold: 0 });

    targets.forEach(function (t) { io.observe(t.el); });
  })();
</script>
@endsection
