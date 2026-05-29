@php
    use Illuminate\Support\Str;

    $page           = $mbbsContent['page']           ?? [];
    $sections       = $mbbsContent['sections']       ?? [];
    $bullets        = $mbbsContent['bullets']        ?? [];
    $subpoints      = $mbbsContent['subpoints']      ?? [];
    $facts          = $mbbsContent['facts']          ?? [];
    $admissionSteps = $mbbsContent['admissionSteps'] ?? [];

    $countryName = $countryMeta['name'] ?? ucfirst($countrySlug);
    $countryFlag = $countryMeta['flag'] ?? 'ge';

    $heroFiles = ['georgia', 'kazakhstan'];
    $heroImage = in_array($countrySlug, $heroFiles, true)
        ? asset('assets/heroes/'.$countrySlug.'.jpg')
        : null;

    $pageTitle       = ($page['page_title'] ?? 'MBBS in '.$countryName).' | One Degree Advisory';
    $pageDescription = $page['hero_text'] ?? 'Study MBBS in '.$countryName.' with One Degree Advisory.';
    $activeNav       = 'mbbs';
    $mainId          = 'mbbs-main';
    $bodyClass       = 'page-mbbs-country';

    $forIndianStudents = $sections['for_indian_students'] ?? [];
    $advantages        = $sections['advantages']          ?? [];
    $eligibility       = $sections['eligibility']         ?? [];
    $whyPopular        = $sections['why_popular']         ?? [];

    $advantageBullets   = $bullets['advantages']  ?? ($bullets['for_indian_students'] ?? []);
    $eligibilityBullets = $bullets['eligibility'] ?? [];

    $advantageIcons = [
        'award', 'globe-2', 'badge-check', 'book-open',
        'languages', 'graduation-cap', 'shield-check', 'sparkles',
        'plane', 'utensils', 'users', 'wallet', 'heart-pulse',
    ];

    $documentKeywords = ['marksheet', 'mark-sheet', 'passport', 'photo', 'aadhar', 'pan', 'card', 'document'];
    $eligibilityRules = [];
    $eligibilityDocs  = [];

    foreach ($eligibilityBullets as $bullet) {
        $text = trim((string) ($bullet['bullet_text'] ?? ''));
        if ($text === '') {
            continue;
        }
        $isDoc = false;
        foreach ($documentKeywords as $kw) {
            if (stripos($text, $kw) !== false) {
                $isDoc = true;
                break;
            }
        }
        $isDoc ? $eligibilityDocs[] = $text : $eligibilityRules[] = $text;
    }

    $heroFacts = array_slice($facts, 0, 4);
    $sideFacts = array_slice($facts, 0, 8);

    /* Split the intro body into 2-3 sentence paragraphs at natural sentence breaks */
    $introBody  = trim((string) ($forIndianStudents['section_body'] ?? ''));
    $introParas = [];
    if ($introBody !== '') {
        $sentences = preg_split('/(?<=[\.!?])\s+(?=[A-Z])/u', $introBody) ?: [$introBody];
        if (count($sentences) <= 3) {
            $introParas = [implode(' ', $sentences)];
        } else {
            $buffer = [];
            foreach ($sentences as $i => $s) {
                $buffer[] = $s;
                if (count($buffer) >= 3 && $i < count($sentences) - 1) {
                    $introParas[] = implode(' ', $buffer);
                    $buffer = [];
                }
            }
            if ($buffer) {
                $introParas[] = implode(' ', $buffer);
            }
        }
    }

    $subpointsRich = array_values(array_filter(
        $subpoints,
        fn ($sp) => trim((string) ($sp['subpoint_body'] ?? '')) !== ''
    ));

    $trustChips = [
        ['icon' => 'badge-check',  'label' => 'NMC & WHO approved'],
        ['icon' => 'languages',    'label' => 'English-medium MBBS'],
        ['icon' => 'shield-check', 'label' => 'NEET-qualified entry'],
        ['icon' => 'wallet',       'label' => 'Low total cost of study'],
    ];

    /*
     * Admission process steps come from the scraped bookmyuniversity content
     * (sheet: AdmissionSteps). $admissionSteps is the raw rows for this slug.
     * The icon is decorative only and chosen by keyword on each row's title.
     */
    $admissionIconFor = function (string $title): string {
        $checks = [
            'research'      => 'search',
            'selection'     => 'search',
            'application'   => 'file-text',
            'apply'         => 'file-text',
            'submission'    => 'send',
            'admission'     => 'mail-check',
            'offer'         => 'mail-check',
            'letter'        => 'send',
            'apostille'     => 'stamp',
            'translation'   => 'languages',
            'document'      => 'folder-open',
            'tuition'       => 'credit-card',
            'fee'           => 'credit-card',
            'invitation'    => 'send',
            'visa'          => 'globe-2',
            'approval'      => 'badge-check',
            'travel'        => 'plane-takeoff',
            'flight'        => 'plane-takeoff',
            'registration'  => 'graduation-cap',
            'residence'     => 'home',
            'permit'        => 'home',
        ];
        $needle = strtolower($title);
        foreach ($checks as $kw => $icon) {
            if (str_contains($needle, $kw)) {
                return $icon;
            }
        }
        return 'circle-dot';
    };

    $admissionSectionHeading = $sections['admission_process']['section_heading'] ?? '';
    $admissionSectionHeading = trim(preg_replace('/^[^\p{L}\p{N}]+/u', '', $admissionSectionHeading));
@endphp

@extends('layouts.app')

@section('content')
<main id="mbbs-main" class="mbbs-country">

  {{-- ========== HERO ========== --}}
  <section class="mbbsx-hero {{ $heroImage ? 'has-image' : 'no-image' }}" id="top">
    @if($heroImage)
      <img class="mbbsx-hero__image" src="{{ $heroImage }}" alt="" loading="eager">
    @endif
    <div class="mbbsx-hero__veil" aria-hidden="true"></div>
    <div class="mbbsx-hero__glow" aria-hidden="true"></div>

    <div class="mbbsx-container mbbsx-hero__grid">
      <div class="mbbsx-hero__copy">
        <a class="mbbsx-back" href="{{ route('mbbs.student') }}">
          <i data-lucide="arrow-left"></i>
          <span>All MBBS destinations</span>
        </a>

        <div class="mbbsx-hero__crest">
          <span class="mbbsx-flag">
            <img src="https://flagcdn.com/w160/{{ $countryFlag }}.png" alt="{{ $countryName }} flag">
          </span>
          <div class="mbbsx-hero__crestmeta">
            <span class="mbbsx-eyebrow">Medical education corridor</span>
            <span class="mbbsx-hero__route">India &nbsp;&rarr;&nbsp; {{ $countryName }}</span>
          </div>
        </div>

        <h1>
          Study <span class="gold-text">MBBS</span><br>
          in <span class="gold-text">{{ $countryName }}</span>
        </h1>

        <p class="mbbsx-lede">
          {{ $page['hero_text'] ?? 'Globally recognised medical degrees, NMC-approved universities and a focused pathway for Indian students.' }}
        </p>

        <div class="mbbsx-actions">
          <a class="btn btn-primary" href="{{ route('contact') }}">
            <span>Talk to a counsellor</span>
            <i data-lucide="arrow-up-right"></i>
          </a>
          <a class="btn btn-ghost" href="{{ route('contact') }}">
            <i data-lucide="phone-call"></i>
            <span>Request a callback</span>
          </a>
        </div>

        <ul class="mbbsx-trust">
          @foreach($trustChips as $chip)
            <li>
              <i data-lucide="{{ $chip['icon'] }}"></i>
              <span>{{ $chip['label'] }}</span>
            </li>
          @endforeach
        </ul>
      </div>

      <aside class="mbbsx-snapshot">
        <div class="mbbsx-snapshot__head">
          <span class="mbbsx-snapshot__eyebrow">At a glance</span>
          <h2>{{ $countryName }}</h2>
          <p>The essentials before you commit &mdash; verified for the 2026&ndash;27 intake.</p>
        </div>

        @if($heroFacts)
          <dl class="mbbsx-snapshot__facts">
            @foreach($heroFacts as $fact)
              <div>
                <dt>{{ $fact['fact_label'] }}</dt>
                <dd>{{ $fact['fact_value'] }}</dd>
              </div>
            @endforeach
          </dl>
        @else
          <dl class="mbbsx-snapshot__facts">
            <div><dt>Duration</dt><dd>5 + 1 year internship</dd></div>
            <div><dt>Medium</dt><dd>English</dd></div>
            <div><dt>Recognition</dt><dd>NMC, WHO, WFME</dd></div>
            <div><dt>Intake</dt><dd>September &ndash; October</dd></div>
          </dl>
        @endif

        <a class="mbbsx-snapshot__link" href="#why-popular">
          <span>See full country profile</span>
          <i data-lucide="arrow-down-right"></i>
        </a>
      </aside>
    </div>
  </section>

  {{-- ========== INTRO / FOR INDIAN STUDENTS ========== --}}
  @if($introParas)
    <section id="intro" class="mbbsx-section mbbsx-section--intro">
      <div class="mbbsx-container mbbsx-intro__grid">
        <div class="mbbsx-intro__copy">
          <span class="eyebrow">For Indian students</span>
          <h2>{{ $forIndianStudents['section_heading'] ?? 'MBBS in '.$countryName.' for Indian students' }}</h2>

          <div class="mbbsx-intro__prose">
            @foreach($introParas as $i => $para)
              <p class="mbbsx-intro__para {{ $i === 0 ? 'has-dropcap' : '' }}">{{ $para }}</p>
            @endforeach
          </div>
        </div>

        <aside class="mbbsx-intro__callout">
          <span class="mbbsx-monogram" aria-hidden="true">{{ Str::upper(Str::substr($countryName, 0, 1)) }}</span>
          <span class="mbbsx-callout__eyebrow">Quick truths</span>
          <ul>
            <li><i data-lucide="check"></i><span>WHO &amp; NMC approved universities</span></li>
            <li><i data-lucide="check"></i><span>English-medium MBBS programmes</span></li>
            <li><i data-lucide="check"></i><span>No donation, NEET-qualified entry</span></li>
            <li><i data-lucide="check"></i><span>Indian food &amp; community support</span></li>
          </ul>
        </aside>
      </div>
    </section>
  @endif

  {{-- ========== ADVANTAGES ========== --}}
  @php
      $advantagesBody = trim((string) ($advantages['section_body'] ?? ''));
  @endphp
  @if($advantageBullets || $advantagesBody !== '')
    <section id="advantages" class="mbbsx-section mbbsx-section--advantages">
      <div class="mbbsx-container">
        <div class="mbbsx-head">
          <span class="eyebrow">Why choose this corridor</span>
          <h2>{{ $advantages['section_heading'] ?? 'Advantages of studying MBBS in '.$countryName }}</h2>
          @if($advantagesBody !== '' && ! $advantageBullets)
            <p>{{ $advantagesBody }}</p>
          @else
            <p>Reasons Indian families consistently pick {{ $countryName }} over higher-cost alternatives.</p>
          @endif
        </div>

        @if($advantageBullets)
          <div class="mbbsx-adv__grid">
            @foreach($advantageBullets as $index => $bullet)
              <article class="mbbsx-adv__card">
                <div class="mbbsx-adv__top">
                  <span class="mbbsx-adv__icon" aria-hidden="true">
                    <i data-lucide="{{ $advantageIcons[$index % count($advantageIcons)] }}"></i>
                  </span>
                  <span class="mbbsx-adv__no">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                </div>
                <p>{{ $bullet['bullet_text'] }}</p>
                <span class="mbbsx-adv__rule" aria-hidden="true"></span>
              </article>
            @endforeach
          </div>
        @endif
      </div>
    </section>
  @endif

  {{-- ========== ELIGIBILITY & DOCUMENTS ========== --}}
  @if($eligibilityRules || $eligibilityDocs)
    <section id="eligibility" class="mbbsx-section mbbsx-section--eligibility">
      <div class="mbbsx-container">
        <div class="mbbsx-head">
          <span class="eyebrow">Eligibility &amp; documents</span>
          <h2>{{ $eligibility['section_heading'] ?? 'Eligibility criteria & required documents for MBBS in '.$countryName }}</h2>
          <p>Plan your application around clear, verified benchmarks &mdash; no surprises later.</p>
        </div>

        <div class="mbbsx-elig__grid">
          @if($eligibilityRules)
            <article class="mbbsx-elig__panel">
              <header>
                <span class="mbbsx-panel__icon"><i data-lucide="badge-check"></i></span>
                <div>
                  <span class="eyebrow">Eligibility criteria</span>
                  <h3>Who can apply</h3>
                </div>
              </header>
              <ul class="mbbsx-check">
                @foreach($eligibilityRules as $rule)
                  <li>
                    <i data-lucide="check"></i>
                    <span>{{ $rule }}</span>
                  </li>
                @endforeach
              </ul>
            </article>
          @endif

          @if($eligibilityDocs)
            <article class="mbbsx-elig__panel mbbsx-elig__panel--dark">
              <header>
                <span class="mbbsx-panel__icon"><i data-lucide="file-text"></i></span>
                <div>
                  <span class="eyebrow">Documents required</span>
                  <h3>What to keep ready</h3>
                </div>
              </header>
              <ul class="mbbsx-check">
                @foreach($eligibilityDocs as $doc)
                  <li>
                    <i data-lucide="check"></i>
                    <span>{{ $doc }}</span>
                  </li>
                @endforeach
              </ul>
            </article>
          @endif
        </div>
      </div>
    </section>
  @endif

  {{-- ========== WHY POPULAR — editorial redesign ========== --}}
  @if($subpointsRich || $sideFacts)
    <section id="why-popular" class="mbbsx-section mbbsx-section--why">
      <div class="mbbsx-container">
        <div class="mbbsx-head">
          <span class="eyebrow">The {{ $countryName }} edge</span>
          <h2>{{ $whyPopular['section_heading'] ?? 'Why '.$countryName.' is most popular for MBBS' }}</h2>
          <p>A closer look at what makes {{ $countryName }} a confident choice &mdash; climate, cost, culture and life on campus.</p>
        </div>

        @if($sideFacts)
          <div class="mbbsx-profile">
            <div class="mbbsx-profile__head">
              <div>
                <span class="mbbsx-callout__eyebrow">Country profile</span>
                <h3>{{ $countryName }} at a glance</h3>
              </div>
              <span class="mbbsx-profile__flag" aria-hidden="true">
                <img src="https://flagcdn.com/w160/{{ $countryFlag }}.png" alt="">
              </span>
            </div>
            <dl class="mbbsx-profile__grid">
              @foreach($sideFacts as $fact)
                <div>
                  <dt>{{ $fact['fact_label'] }}</dt>
                  <dd>{{ $fact['fact_value'] }}</dd>
                </div>
              @endforeach
            </dl>
          </div>
        @endif

        @if($subpointsRich)
          <div class="mbbsx-stories">
            @foreach($subpointsRich as $index => $sp)
              <article class="mbbsx-story">
                <div class="mbbsx-story__index">
                  <span class="mbbsx-story__no">{{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</span>
                  <h3>{{ $sp['subpoint_heading'] }}</h3>
                </div>
                <div class="mbbsx-story__body">
                  <p>{{ $sp['subpoint_body'] }}</p>
                </div>
              </article>
            @endforeach
          </div>
        @endif
      </div>
    </section>
  @endif

  {{-- ========== ADMISSION PROCESS (scraped) ========== --}}
  @if($admissionSteps)
    <section id="process" class="mbbsx-section mbbsx-section--process">
      <div class="mbbsx-container">
        <div class="mbbsx-head">
          <span class="eyebrow">Admission process</span>
          <h2>{{ $admissionSectionHeading !== '' ? $admissionSectionHeading : 'MBBS in '.$countryName.' admission process' }}</h2>
          <p>{{ count($admissionSteps) }} structured steps sourced from the latest {{ $countryName }} university admission flow.</p>
        </div>

        <ol class="mbbsx-process">
          @foreach($admissionSteps as $step)
            @php
              $stepOrder = (int) ($step['step_order'] ?? 0);
              $stepTitle = trim((string) ($step['step_title'] ?? ''));
              $stepBody  = trim((string) ($step['step_body'] ?? ''));
              $stepIcon  = $admissionIconFor($stepTitle);
              $subSteps  = $stepBody === ''
                  ? []
                  : array_values(array_filter(array_map('trim', explode('|', $stepBody))));
            @endphp
            <li class="mbbsx-process__step">
              <header class="mbbsx-process__head">
                <span class="mbbsx-process__badge" aria-hidden="true">
                  <i data-lucide="{{ $stepIcon }}"></i>
                </span>
                <div class="mbbsx-process__heading">
                  <span class="mbbsx-process__no">Step {{ str_pad((string) $stepOrder, 2, '0', STR_PAD_LEFT) }}</span>
                  <h3>{{ $stepTitle }}</h3>
                </div>
              </header>

              @if($subSteps)
                <ul class="mbbsx-process__substeps">
                  @foreach($subSteps as $sub)
                    <li><i data-lucide="check"></i><span>{{ $sub }}</span></li>
                  @endforeach
                </ul>
              @endif
            </li>
          @endforeach
        </ol>
      </div>
    </section>
  @endif

  {{-- ========== CAREERS BEYOND MBBS ========== --}}
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

  <section id="careers-beyond-mbbs" class="mbbsx-section mbbsx-section--careers">
    <div class="mbbsx-container">
      <div class="mbbsx-careers__head">
        <div class="mbbsx-careers__copy reveal">
          <span class="eyebrow mbbsx-careers__eyebrow">
            <i data-lucide="target"></i>
            <span>Explore your options</span>
          </span>
          <h2>Exploring Careers<br><span class="gold-text mbbsx-careers__accent">Beyond MBBS?</span></h2>
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

  {{-- ========== CTA ========== --}}
  <section class="mbbsx-cta">
    <div class="mbbsx-cta__bg" aria-hidden="true"></div>
    <div class="mbbsx-container mbbsx-cta__grid">
      <div class="mbbsx-cta__copy">
        <span class="eyebrow">Ready when you are</span>
        <h2>Map your <span class="gold-text">MBBS in {{ $countryName }}</span> in one call.</h2>
        <p>A One Degree advisor will walk you through universities, NMC alignment, fee structures and admission timelines &mdash; tailored to your NEET score and budget.</p>
        <div class="mbbsx-actions">
          <a class="btn btn-primary" href="{{ route('contact') }}">
            <span>Book a free counselling call</span>
            <i data-lucide="arrow-up-right"></i>
          </a>
          <a class="btn btn-ghost" href="{{ route('mbbs.student') }}#corridor">
            <i data-lucide="compass"></i>
            <span>Compare MBBS corridors</span>
          </a>
        </div>
      </div>

      <div class="mbbsx-cta__card">
        <span class="mbbsx-cta__monogram" aria-hidden="true">{{ Str::upper(Str::substr($countryName, 0, 1)) }}</span>
        <span class="mbbsx-callout__eyebrow">What you'll get</span>
        <h3>A clear plan, in writing.</h3>
        <ul>
          <li><i data-lucide="check"></i><span>NEET-aware university shortlist</span></li>
          <li><i data-lucide="check"></i><span>Total cost of education breakdown</span></li>
          <li><i data-lucide="check"></i><span>Visa &amp; document timeline</span></li>
          <li><i data-lucide="check"></i><span>India return &amp; FMGE planning</span></li>
        </ul>
      </div>
    </div>
  </section>

</main>
@endsection
