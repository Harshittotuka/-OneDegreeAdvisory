@php
    use Illuminate\Support\Str;

    $pageTitle = 'MBBS Abroad Route Desk | One Degree Advisory';
    $pageDescription = 'A focused MBBS abroad comparison page with highlighted destination desks, admission support services, and profile-led counselling for Indian students.';
    $activeNav = 'mbbs';
    $mainId = 'mbbs-v2-main';

    $mbbsCountries = $mbbsCountries ?? [];
    $featuredMbbsCountries = array_slice($mbbsCountries, 0, 5);
    $additionalMbbsCountries = array_slice($mbbsCountries, 5);
    $additionalMbbsCountryCount = count($additionalMbbsCountries);
    $additionalMbbsCountryNames = array_slice(array_column($additionalMbbsCountries, 'name'), 0, 3);

    $fact = function (array $facts, array $labels): string {
        foreach ($labels as $label) {
            if (! empty($facts[$label])) {
                return (string) $facts[$label];
            }
        }
        foreach ($facts as $label => $value) {
            foreach ($labels as $wanted) {
                if (stripos((string) $label, $wanted) !== false && trim((string) $value) !== '') {
                    return (string) $value;
                }
            }
        }
        return '';
    };

    $services = [
        ['icon' => 'handshake', 'title' => 'Introduce', 'copy' => 'We introduce you to the universities and countries that fit your preferred course.'],
        ['icon' => 'users', 'title' => 'Advice', 'copy' => 'We advise you on the strongest university fit according to your profile.'],
        ['icon' => 'clipboard-pen', 'title' => 'Registration', 'copy' => 'Our team helps with college and admission registration documents.'],
        ['icon' => 'badge-check', 'title' => 'Apply', 'copy' => 'Once you decide, we apply to the selected university on your behalf.'],
        ['icon' => 'contact', 'title' => 'Admission', 'copy' => 'We check documentation carefully so the seat process stays clean.'],
        ['icon' => 'globe', 'title' => 'Visa', 'copy' => 'We prepare the embassy file and guide you through visa stamping.'],
        ['icon' => 'plane', 'title' => 'Fly', 'copy' => 'We help you compare practical and economical flight options.'],
        ['icon' => 'car', 'title' => 'Airport Pick Up', 'copy' => 'We arrange someone to welcome you at the airport after arrival.'],
        ['icon' => 'home', 'title' => 'Accommodation', 'copy' => 'We support hostel, university housing, and private stay choices.'],
        ['icon' => 'book-open-check', 'title' => 'Study', 'copy' => 'We wish you the best and keep your academic start grounded.'],
    ];
@endphp

@extends('layouts.app')

@section('content')
<main id="mbbs-v2-main" class="mbbs-page mbbs-v2-page">
  <section class="mbbs-v2-hero" id="top" aria-labelledby="mbbs-v2-title">
    <div class="container mbbs-v2-hero-layout">
      <div class="mbbs-v2-hero-copy">
        <div class="mbbs-hero-chips">
          <span class="mbbs-chip">
            <i data-lucide="stethoscope"></i>
            <span>MBBS Route Desk</span>
          </span>
          <span class="mbbs-chip mbbs-chip-warm">
            <i data-lucide="layers-3"></i>
            <span>Comparison View</span>
          </span>
        </div>

        <h1 id="mbbs-v2-title">Choose your path with <span class="mbbs-gold">One Degree</span> Advisory.</h1>
        <p class="mbbs-v2-lede">
          We help students compare destinations, budgets, and timelines &mdash; then build one clear, evidence-led plan that takes you from shortlist to seat with confidence.
        </p>

        <div class="mbbs-hero-actions">
          <a class="btn btn-primary mbbs-hero-cta" href="{{ route('contact') }}">
            <span>Book free counselling</span>
            <i data-lucide="arrow-up-right"></i>
          </a>
        </div>
      </div>

      <aside class="mbbs-v2-country-panel" id="corridor" aria-label="MBBS country comparison board">
        <div class="mbbs-v2-country-carousel" aria-label="MBBS country comparison cards">
          @foreach ($featuredMbbsCountries as $i => $c)
            <article class="mbbs-country-card mbbs-v2-country-card">
              <header class="mbbs-country-head">
                <span class="mbbs-country-flag">
                  @if(! empty($c['flag']))
                    <img src="https://flagcdn.com/w80/{{ $c['flag'] }}.png" alt="">
                  @elseif(! empty($c['flag_url']))
                    <img src="{{ $c['flag_url'] }}" alt="">
                  @else
                    <i data-lucide="map-pin"></i>
                  @endif
                </span>
                <div>
                  <span class="mbbs-country-index">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span>
                  <h3>{{ $c['name'] }}</h3>
                </div>
              </header>
              <p class="mbbs-country-note">{{ Str::limit($c['hero_text'] ?? $c['hero_heading'] ?? '', 155) }}</p>
              @php
                $facts = $c['facts'] ?? [];
                $duration = $fact($facts, ['Duration']);
                $medium = $fact($facts, ['Medium']);
                $fee = $fact($facts, ['Total Course Fees', 'Annual Fee (INR)', 'Annual Fee']);
                $eligibility = $fact($facts, ['Eligibility']);
                $recognition = $fact($facts, ['Recognitions', 'Recognition']);
              @endphp
              <dl class="mbbs-country-grid-meta">
                @if($duration !== '')<div><dt>Duration</dt><dd>{{ $duration }}</dd></div>@endif
                @if($medium !== '')<div><dt>Medium</dt><dd>{{ $medium }}</dd></div>@endif
                @if($fee !== '')<div><dt>Fees</dt><dd>{{ $fee }}</dd></div>@endif
                @if($eligibility !== '')<div><dt>Eligibility</dt><dd>{{ Str::limit($eligibility, 44) }}</dd></div>@endif
                @if($recognition !== '')<div><dt>Recognition</dt><dd>{{ Str::limit($recognition, 44) }}</dd></div>@endif
              </dl>
              <a href="{{ route('mbbs.country', $c['slug']) }}" class="mbbs-country-cta">
                <span>Open {{ $c['name'] }} guide</span>
                <i data-lucide="arrow-up-right"></i>
              </a>
            </article>
          @endforeach

          @if($additionalMbbsCountryCount > 0)
            <a class="mbbs-country-card mbbs-v2-country-card mbbs-v2-country-more-card" href="{{ route('contact') }}" aria-label="Ask about more MBBS destinations">
              <span class="mbbs-v2-more-card__index">{{ str_pad(count($featuredMbbsCountries) + 1, 2, '0', STR_PAD_LEFT) }}</span>
              <span class="mbbs-v2-more-card__mark" aria-hidden="true">
                <i data-lucide="compass"></i>
              </span>
              <span class="mbbs-v2-more-card__head">
                <span class="mbbs-v2-more-card__eyebrow">Additional destinations</span>
                <h3>And many&nbsp;more</h3>
              </span>
              <p>More MBBS routes matched to your budget, NEET score, climate, and intake timeline.</p>
              <span class="mbbs-v2-more-card__chips" aria-label="Examples of more MBBS destinations">
                @foreach($additionalMbbsCountryNames as $countryName)
                  <span>{{ $countryName }}</span>
                @endforeach
                @if($additionalMbbsCountryCount > count($additionalMbbsCountryNames))
                  <span class="mbbs-v2-more-card__chip-extra">+{{ $additionalMbbsCountryCount - count($additionalMbbsCountryNames) }} more</span>
                @endif
              </span>
              <span class="mbbs-v2-more-card__action">
                <span>Build my shortlist</span>
                <i data-lucide="arrow-up-right"></i>
              </span>
            </a>
          @endif
        </div>
      </aside>
    </div>
  </section>

  @php
      $careersBeyondMbbs = [
          ['title' => 'BDS – Dentistry',                       'body' => 'Build a respected clinical career in oral healthcare, surgery, aesthetics, and private practice.',                  'icon' => 'smile',       'tint' => 'teal'],
          ['title' => 'PharmD / Pharmacy',                     'body' => 'Work in clinical research, pharmaceuticals, hospitals, healthcare innovation, and global pharma companies.',         'icon' => 'pill',        'tint' => 'green'],
          ['title' => 'Psychology / Clinical Psychology',      'body' => 'Growing field focused on mental health, therapy, human behavior, and wellness.',                                     'icon' => 'brain',       'tint' => 'lavender'],
          ['title' => 'Physiotherapy',                         'body' => 'High-demand profession in sports rehab, hospitals, pain management, and mobility care.',                            'icon' => 'activity',    'tint' => 'peach'],
          ['title' => 'Biotechnology / Biomedical Sciences',   'body' => 'Ideal for students interested in research, genetics, diagnostics, and healthcare innovation.',                       'icon' => 'dna',         'tint' => 'aqua'],
          ['title' => 'BSc Allied Health Sciences',            'body' => 'Premium specializations like Radiology, OT Technology, Cardiac Care, Medical Lab Sciences.',                         'icon' => 'microscope',  'tint' => 'rose'],
          ['title' => 'Doctor of Occupational Therapy',        'body' => 'Emerging global field helping patients regain independence and functionality.',                                      'icon' => 'hand-heart',  'tint' => 'sky'],
          ['title' => 'Public Health / Healthcare Management', 'body' => 'Perfect for students interested in healthcare leadership, hospitals, policy, and impact at scale.',                  'icon' => 'building-2',  'tint' => 'mint'],
      ];
  @endphp

  <section id="careers-beyond-mbbs" class="mbbsx-section mbbsx-section--careers" aria-labelledby="careers-beyond-mbbs-title">
    <div class="container">
      <div class="mbbsx-careers__head">
        <div class="mbbsx-careers__copy reveal">
          <span class="eyebrow mbbsx-careers__eyebrow">
            <i data-lucide="target"></i>
            <span>Explore your options</span>
          </span>
          <h2 id="careers-beyond-mbbs-title">Exploring Careers<br><span class="gold-text mbbsx-careers__accent">Beyond MBBS?</span></h2>
          <p>Medicine is one path &mdash; healthcare has many powerful careers with strong growth, impact, and global opportunities.</p>
        </div>

        <aside class="mbbsx-careers__callout reveal">
          <span class="mbbsx-careers__callout-icon" aria-hidden="true">
            <i data-lucide="trophy"></i>
          </span>
          <div class="mbbsx-careers__callout-copy">
            <p>Success in healthcare is not limited to MBBS.</p>
            <strong>It depends on choosing the right fit.</strong>
          </div>
        </aside>
      </div>

      <div class="mbbsx-careers__grid">
        @foreach($careersBeyondMbbs as $i => $career)
          <article class="mbbsx-career-card reveal" data-tint="{{ $career['tint'] }}" style="--i: {{ $i }};">
            <div class="mbbsx-career-card__top">
              <span class="mbbsx-career-card__icon" aria-hidden="true">
                <i data-lucide="{{ $career['icon'] }}"></i>
              </span>
              <span class="mbbsx-career-card__no">{{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}</span>
            </div>
            <h3>{{ $career['title'] }}</h3>
            <span class="mbbsx-career-card__rule" aria-hidden="true"></span>
            <p>{{ $career['body'] }}</p>
          </article>
        @endforeach
      </div>
    </div>
  </section>

  <section class="mbbs-v2-services" id="services" aria-labelledby="mbbs-v2-services-title">
    <div class="container">
      <div class="mbbs-v2-services-head">
        <span class="insights-eyebrow">Support desk</span>
        <h2 id="mbbs-v2-services-title">Our Services</h2>
        <p>From country fit to campus arrival, every step is handled as one coordinated MBBS admission file.</p>
      </div>

      <div class="mbbs-v2-services-grid">
        @foreach ($services as $service)
          <article class="mbbs-v2-service-card">
            <span class="mbbs-v2-service-icon" aria-hidden="true">
              <i data-lucide="{{ $service['icon'] }}"></i>
            </span>
            <h3>{{ $service['title'] }}</h3>
            <p>{{ $service['copy'] }}</p>
          </article>
        @endforeach
      </div>
    </div>
  </section>
</main>
@endsection
