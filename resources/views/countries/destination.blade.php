@php
    $page = $studyContent['page'] ?? [];
    $sectionCopy = $studyContent['sectionCopy'] ?? [];
    $whyCards = array_slice($studyContent['whyCards'] ?? [], 0, 6);
    $topCourses = array_slice($studyContent['topCourses'] ?? [], 0, 6);
    $courseCount = count($topCourses);
    $costTables = array_slice($studyContent['costTables'] ?? [], 0, 2);
    $intakes = array_slice($studyContent['intakeCards'] ?? [], 0, 4);
    $cities = array_slice($studyContent['cityCards'] ?? [], 0, 6);
    $featureImages = $studyContent['featureImages'] ?? [];
    $indianStudents = $studyContent['indianStudents'] ?? [];
    $indianCards = array_slice($indianStudents['cards'] ?? [], 0, 4);
    $destination = $studyContent['destination'] ?? ($destination ?? []);
    $uiText = $studyContent['uiText'] ?? [];
    $text = fn (string $key): string => (string) ($uiText[$key] ?? '');

    $countrySlug = $destination['slug'] ?? $page['page_slug'] ?? '';
    $countryName = $destination['name'] ?? $page['country'] ?? '';
    $countryLabel = $page['country'] ?? $countryName;

    $pageTitle = $page['seo_title'] ?? $page['page_title'] ?? '';
    $pageDescription = $page['seo_description'] ?? '';
    $activeNav = 'destinations';
    $mainId = 'country-main';
    $bodyClass = 'page-study-location-dynamic page-country-v2';

    $heroImageKey = $destination['hero_key'] ?? \Illuminate\Support\Str::after($countrySlug, 'study-in-');
    $heroClass = $heroImageKey !== '' ? 'country-hero--'.$heroImageKey : 'country-hero--default';
    $heroHeading = $page['hero_heading'] ?? '';
    $heroLead = $text('hero_lead');
    $heroPrefix = trim(preg_replace('/\b('.preg_quote($countryLabel, '/').'|'.preg_quote($countryName, '/').')\b/i', '', $heroHeading));

    $href = function (string $key) use ($uiText): string {
        $value = trim((string) ($uiText[$key] ?? ''));

        if ($value === '') {
            return route('contact');
        }

        return str_starts_with($value, '/') ? url($value) : $value;
    };

    $plainBody = function (?string $bodyText, int $limit = 250) use ($text): string {
        $noise = array_map('strtolower', array_filter(array_map('trim', explode('|', $text('card_cta_noise')))));
        $parts = collect(explode('|', (string) $bodyText))
            ->map(fn ($part) => trim($part))
            ->filter(fn ($part) => $part !== '' && ! in_array(strtolower($part), $noise, true))
            ->unique()
            ->values();

        $candidate = $parts->first(fn ($part) => strlen($part) > 38) ?? $parts->first() ?? '';

        return \Illuminate\Support\Str::limit($candidate, $limit);
    };

    $cardBody = fn (array $card, int $limit = 190): string => \Illuminate\Support\Str::limit((string) ($card['card_body_clean'] ?? $card['card_body'] ?? ''), $limit);
    $tableRows = fn (int $index): \Illuminate\Support\Collection => collect($costTables[$index]['rows'] ?? []);
    $tuitionSnapshotKeywords = array_filter(array_map('trim', explode('|', $text('tuition_snapshot_keywords'))));
    $tuitionSnapshot = $tableRows(0)
        ->filter(fn ($row) => collect($tuitionSnapshotKeywords)->contains(fn ($keyword) => str_contains($row['label'], $keyword)))
        ->map(fn ($row) => $row['value'])
        ->take(2)
        ->join(' / ');
    $livingSnapshot = $tableRows(1)
        ->take(2)
        ->map(fn ($row) => $row['value'])
        ->join(' / ');
    $bannerImage = (string) ($featureImages[0]['image_url'] ?? '');
    $bannerImageAlt = (string) ($featureImages[0]['image_alt'] ?? $countryLabel);
    $intakeImage = (string) ($featureImages[1]['image_url'] ?? ($featureImages[0]['image_url'] ?? ''));
    $intakeImageAlt = (string) ($featureImages[1]['image_alt'] ?? ($featureImages[0]['image_alt'] ?? $countryLabel));
    $cityFallbackImage = $intakeImage !== '' ? $intakeImage : $bannerImage;
    $icons = ['award', 'clock', 'briefcase', 'badge-check', 'flask-conical', 'globe-2'];
    $carouselCities = array_values(array_merge($cities, $cities));
    $cityTilts = [-2.5, 1.5, -1, 2.25, -1.75, 1];

    // Per-country SEO: social share image + canonical (URL is already unique per country).
    $ogImage = (string) ($destination['hero_image'] ?? '') ?: $bannerImage;
    $ogType = 'article';
@endphp

@extends('layouts.app')

@push('head')
  <script type="application/ld+json">
  {!! json_encode([
      '@context' => 'https://schema.org',
      '@type' => 'BreadcrumbList',
      'itemListElement' => [
          ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => route('home')],
          ['@type' => 'ListItem', 'position' => 2, 'name' => 'Destinations', 'item' => route('home').'#destinations'],
          ['@type' => 'ListItem', 'position' => 3, 'name' => $countryName, 'item' => url()->current()],
      ],
  ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
  </script>
  <script type="application/ld+json">
  {!! json_encode(array_filter([
      '@context' => 'https://schema.org',
      '@type' => 'WebPage',
      'name' => $pageTitle,
      'description' => $pageDescription,
      'url' => url()->current(),
      'about' => $countryName !== '' ? ['@type' => 'Place', 'name' => $countryName] : null,
      'inLanguage' => 'en',
      'isPartOf' => ['@type' => 'WebSite', 'name' => config('site.name'), 'url' => route('home')],
  ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
  </script>
@endpush

@section('content')
<main id="country-main" class="country-main country-main--dynamic">
  <section class="country-hero {{ $heroClass }}" id="top">
    <div class="container country-hero-grid">
      <div class="country-hero-copy">
        <a class="country-back" href="{{ route('home') }}#destinations"><i data-lucide="arrow-left"></i><span>{{ $text('back_label') }}</span></a>
        <span class="country-flag-lg">
          @if($destination['eu'] ?? false)
            @include('partials.eu-flag')
          @elseif(! empty($destination['flag']))
            <img src="https://flagcdn.com/w160/{{ $destination['flag'] }}.png" alt="{{ $destination['flag_alt'] ?? '' }}">
          @endif
        </span>
        <span class="eyebrow">{{ $text('hero_eyebrow') }}</span>
        <h1>{!! $heroPrefix !== '' ? e($heroPrefix).' ' : '' !!}<span class="gold-text">{{ $countryLabel }}</span></h1>
        <p class="country-lede">{{ $heroLead }}</p>
        <div class="country-actions">
          @if($text('primary_cta_text') !== '')
            <a class="btn btn-primary" href="{{ $href('primary_cta_url') }}"><span>{{ $text('primary_cta_text') }}</span><i data-lucide="arrow-up-right"></i></a>
          @endif
          @if($text('secondary_cta_text') !== '')
            <a class="btn btn-ghost" href="{{ $href('secondary_cta_url') }}"><i data-lucide="phone-call"></i><span>{{ $text('secondary_cta_text') }}</span></a>
          @endif
        </div>
      </div>
    </div>
    <aside class="country-snapshot">
      <h2>{{ $text('snapshot_heading') }}</h2>
      <dl>
        @php
          $snapshotUni = $whyCards[0]['card_title'] ?? '';
          $snapshotIntakes = collect($intakes)->pluck('card_title')->unique()->take(2)->join(', ');
        @endphp
        @if($snapshotUni !== '')
          <div><dt>{{ $text('snapshot_universities_label') }}</dt><dd>{{ $snapshotUni }}</dd></div>
        @endif
        @if($snapshotIntakes !== '')
          <div><dt>{{ $text('snapshot_intakes_label') }}</dt><dd>{{ $snapshotIntakes }}</dd></div>
        @endif
        @if($tuitionSnapshot !== '')
          <div><dt>{{ $text('snapshot_tuition_label') }}</dt><dd>{{ $tuitionSnapshot }}</dd></div>
        @endif
        @if($livingSnapshot !== '')
          <div><dt>{{ $text('snapshot_living_cost_label') }}</dt><dd>{{ $livingSnapshot }}</dd></div>
        @endif
      </dl>
    </aside>
  </section>

  <section id="why" class="country-section dynamic-section dynamic-section--why">
    <div class="container">
      <div class="section-head">
        <span class="eyebrow">{{ $text('why_eyebrow') }}</span>
        <h2>{{ $sectionCopy['why']['section_heading'] ?? '' }}</h2>
        <p>{{ $plainBody($sectionCopy['why']['section_body'] ?? '', 180) }}</p>
      </div>
      <div class="dynamic-why-grid">
        @foreach($whyCards as $index => $card)
          <article class="dynamic-why-card">
            <div class="dynamic-card-topline">
              <span class="dynamic-card-index">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
              <span class="reason-icon"><i data-lucide="{{ $icons[$index % count($icons)] }}"></i></span>
            </div>
            <h3>{{ $card['card_title'] }}</h3>
            <p>{{ $cardBody($card) }}</p>
          </article>
        @endforeach
      </div>
    </div>
  </section>

  @if($indianStudents && $indianCards)
  <section class="country-section full-bleed" aria-label="{{ $text('indian_banner_aria') }}">
    <div class="container">
      <div class="country-band country-band--wide">
        @if($bannerImage !== '')
          <img src="{{ $bannerImage }}" alt="{{ $bannerImageAlt }}">
        @endif
        <div class="band-inner">
          <span class="eyebrow">{{ $indianStudents['subtitle'] ?: $text('indian_subtitle_fallback') }}</span>
          <h2>{{ $indianStudents['heading_before'] }}<span class="gold-text">{{ $indianStudents['heading_highlight'] }}</span>{{ $indianStudents['heading_after'] }}</h2>
          <div class="band-stats">
            @foreach($indianCards as $index => $card)
              <div @class(['band-stat', 'band-stat--highlight' => $card['highlighted'] ?? false]) style="--card-index: {{ $index }}">
                <span class="band-stat__index">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                <strong>{{ $card['value'] }}</strong>
                <span class="band-stat__rule" aria-hidden="true"></span>
                <span class="band-stat__label">{{ $card['description'] }}</span>
              </div>
            @endforeach
          </div>
        </div>
      </div>
    </div>
  </section>
  @endif

  @if($topCourses)
  <section id="top-courses" class="country-section alt dynamic-section dynamic-section--courses">
    <div class="dynamic-courses-bleed">
      <div class="section-head dynamic-courses-head">
        <span class="eyebrow">{{ $text('courses_eyebrow') }}</span>
        <h2>{{ $sectionCopy['courses']['section_heading'] ?? '' }}</h2>
        <p>{{ $plainBody($sectionCopy['courses']['section_body'] ?? '', 190) }}</p>
        <span class="dynamic-course-count">{{ str_pad((string) $courseCount, 2, '0', STR_PAD_LEFT) }} {{ $text('courses_eyebrow') }}</span>
      </div>

      <div class="dynamic-course-carousel" data-course-carousel aria-label="{{ $sectionCopy['courses']['section_heading'] ?? $text('courses_eyebrow') }}">
        <div class="dynamic-course-track" data-course-track>
          @for($copy = 0; $copy < 2; $copy++)
            <div class="dynamic-course-set dynamic-course-set--count-{{ $courseCount }}" @if($copy === 1) aria-hidden="true" @endif>
              @foreach($topCourses as $index => $course)
                <article class="course-card dynamic-course-card" style="--card-index: {{ $index }}">
                  <div class="dynamic-course-card__top">
                    <span class="dynamic-course-rank">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                    @if($course['credential'] !== '')
                      <span class="course-tag">{{ $course['credential'] }}</span>
                    @endif
                  </div>

                  <h3>{{ $course['course_name'] }}</h3>

                  @if($course['university_name'] !== '')
                    <p class="dynamic-course-university">
                      @if($course['country_flag'] !== '')
                        <span aria-hidden="true">{{ $course['country_flag'] }}</span>
                      @endif
                      {{ $course['university_name'] }}
                    </p>
                  @endif

                  <div class="course-foot">
                    @if($course['duration'] !== '')
                      <span><i data-lucide="clock"></i>{{ $course['duration'] }}</span>
                    @endif
                    @if($course['credential'] !== '')
                      <span><i data-lucide="graduation-cap"></i>{{ $course['credential'] }}</span>
                    @endif
                  </div>

                </article>
              @endforeach
            </div>
          @endfor
        </div>
      </div>
    </div>
  </section>
  @endif

  <section id="intakes" class="country-section alt dynamic-section dynamic-section--intakes">
    <div class="container">
      <div class="section-head">
        <span class="eyebrow">{{ $text('intakes_eyebrow') }}</span>
        <h2>{{ $sectionCopy['intakes']['section_heading'] ?? '' }}</h2>
        <p>{{ $plainBody($sectionCopy['intakes']['section_body'] ?? '', 190) }}</p>
      </div>
      <div class="dynamic-intake-board">
        <article class="dynamic-intake-feature">
          @if($intakeImage !== '')
            <img class="dynamic-intake-feature__image" src="{{ $intakeImage }}" alt="{{ $intakeImageAlt }}" loading="lazy">
          @endif
          <span class="dynamic-intake-feature__veil" aria-hidden="true"></span>
          <div class="dynamic-intake-feature__body">
            <span class="eyebrow">{{ $sectionCopy['intakes']['section_heading'] ?? '' }}</span>
            @if($text('intakes_feature_heading') !== '')
              <h3>{{ $text('intakes_feature_heading') }}</h3>
            @endif
            @php $intakesFeatureBody = $plainBody($sectionCopy['intakes']['section_body'] ?? '', 160); @endphp
            @if($intakesFeatureBody !== '')
              <p>{{ $intakesFeatureBody }}</p>
            @endif
          </div>
        </article>
        <div class="dynamic-intake-cards">
          @foreach($intakes as $index => $intake)
            @php
              /* Source bodies sometimes carry trailing CTA/status labels.
                 The label lists are generated into UiText with the workbook. */
              $body = (string) ($intake['card_body_clean'] ?? $intake['card_body'] ?? '');
              $ctaNoise = array_filter(array_map('trim', explode('|', $text('card_cta_noise'))));
              $cleaned = trim(str_replace($ctaNoise, '', $body));
              $flags = [];
              foreach (array_filter(array_map('trim', explode('|', $text('intake_status_flags')))) as $flag) {
                  if (stripos($cleaned, $flag) !== false) {
                      $flags[] = $flag;
                      $cleaned = trim(str_ireplace($flag, '', $cleaned));
                  }
              }
              $status = $flags[0] ?? '';
              $window = $cleaned;
            @endphp
            <article class="dynamic-intake-card" style="--card-index: {{ $index }}">
              <span class="dynamic-intake-number">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
              <div>
                @if($status !== '')
                  <span class="dynamic-intake-status">{{ $status }}</span>
                @endif
                <h3>{{ $intake['card_title'] }}</h3>
                @if($window !== '')
                  <p>{{ $window }}</p>
                @endif
              </div>
            </article>
          @endforeach
        </div>
      </div>
    </div>
  </section>

  <section id="cities" class="country-section dynamic-section dynamic-section--cities dynamic-section--city-carousel">
    <div class="container">
      <div class="section-head">
        <span class="eyebrow">{{ $text('cities_eyebrow') }}</span>
        <h2>{{ $sectionCopy['cities']['section_heading'] ?? '' }}</h2>
        <p>{{ $plainBody($sectionCopy['cities']['section_body'] ?? '', 190) }}</p>
      </div>
    </div>

    <div class="dynamic-carousel-frame dynamic-city-carousel" aria-label="{{ $text('city_carousel_aria') }}">
      <div class="dynamic-carousel-track dynamic-carousel-track--slow">
        @foreach($carouselCities as $index => $city)
          <div class="dynamic-city-slide" style="--tilt: {{ $cityTilts[$index % count($cityTilts)] }}deg;">
            @php($cityImage = $city['image_url'] ?: $cityFallbackImage)
            @if($cityImage !== '')
              <img src="{{ $cityImage }}" alt="{{ $city['card_title'] }}" loading="lazy">
            @endif
            <div class="dynamic-city-slide-info">
              <span>{{ str_pad((string) (($index % max(count($cities), 1)) + 1), 2, '0', STR_PAD_LEFT) }}</span>
              <h3>{{ $city['card_title'] }}</h3>
              <p>{{ $cardBody($city, 95) }}</p>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  <section id="costs" class="country-section paper dynamic-section dynamic-section--costs">
    <div class="container">
      <div class="section-head">
        <span class="eyebrow">{{ $text('costs_eyebrow') }}</span>
        <h2>{{ $sectionCopy['costs']['section_heading'] ?? '' }}</h2>
        <p>{{ $plainBody($sectionCopy['costs']['section_body'] ?? '', 190) }}</p>
      </div>
      <div class="dynamic-cost-grid">
        @foreach($costTables as $costTable)
          <article class="dynamic-cost-panel">
            <div class="dynamic-cost-panel-head">
              <span class="cost-label">{{ $costTable['title'] }}</span>
              @if(! empty($costTable['intro']))
                <p>{{ $costTable['intro'] }}</p>
              @endif
            </div>
            <div class="dynamic-cost-rows">
              @foreach($costTable['rows'] as $row)
                <div class="dynamic-cost-row">
                  <span>{{ $row['label'] }}</span>
                  <strong>{{ $row['value'] }}</strong>
                </div>
              @endforeach
            </div>
          </article>
        @endforeach
      </div>
    </div>
  </section>
</main>
@endsection
