@php
    // The One Degree Method — an interactive compass. The four "D" stages sit at
    // the cardinal points of a graduated compass bezel; the two threads are
    // curved, clickable ribbons riding the two ring circumferences. Selecting any
    // item highlights it and swaps the detail panel beside the compass.
    $stages = [
        'discovery' => [
            'pos'   => 'n',
            'num'   => '01',
            'label' => 'Discovery',
            'kind'  => 'Stage 01',
            'lead'  => 'Defining the future worth pursuing.',
            'body'  => 'Before discussing universities, we seek to understand the student — their ambitions, strengths, motivations, and vision for the future. Together, we define what success truly looks like, creating a foundation for every decision that follows.',
        ],
        'design' => [
            'pos'   => 'e',
            'num'   => '02',
            'label' => 'Design',
            'kind'  => 'Stage 02',
            'lead'  => 'Building the path to get there.',
            'body'  => 'Once the destination is clear, we design the journey. Rather than chasing rankings or following conventional paths, we identify the universities, programmes, experiences, and opportunities that create the greatest long-term value for the individual student.',
        ],
        'distinction' => [
            'pos'   => 's',
            'num'   => '03',
            'label' => 'Distinction',
            'kind'  => 'Stage 03',
            'lead'  => 'Creating a profile that stands apart.',
            'body'  => 'Exceptional applications are not built on achievements alone. They are built on clarity and coherence. We help students develop meaningful experiences, articulate their story, and present a profile that reflects both their potential and their purpose.',
        ],
        'decision' => [
            'pos'   => 'w',
            'num'   => '04',
            'label' => 'Decision',
            'kind'  => 'Stage 04',
            'lead'  => 'Choosing with clarity and conviction.',
            'body'  => 'An offer is not the end of the journey. It is the beginning of the next chapter. We help students and families evaluate opportunities thoughtfully — academic fit, career outcomes, finances, and personal aspirations — so every decision is made with confidence.',
        ],
        'horizon' => [
            'thread' => true,
            'label'  => 'The Horizon',
            'kind'   => 'The thread · Career Mentorship',
            'lead'   => 'Every journey begins with direction.',
            'body'   => 'Through personalised mentorship, we help students explore possibilities, clarify ambitions, and develop a deeper understanding of the future they want to create.',
        ],
        'runway' => [
            'thread' => true,
            'label'  => 'The Runway',
            'kind'   => 'The thread · Student Development',
            'lead'   => 'The strongest opportunities belong to students who invest in themselves early.',
            'body'   => 'We help students build the skills, experiences, confidence, and perspective that expand what becomes possible — long before applications begin.',
        ],
    ];

    // Graduated bezel ticks (every 6°, 0° at the top). Cardinal/major ticks are
    // longer; the cardinals carry the four D stages outside the bezel.
    $cx = 220; $cy = 220; $rOut = 208;
    $ticks = [];
    for ($deg = 0; $deg < 360; $deg += 6) {
        $major = ($deg % 90 === 0);
        $mid   = ($deg % 30 === 0);
        $rIn   = $major ? 184 : ($mid ? 188 : 194);
        $a     = deg2rad($deg - 90);
        $ticks[] = [
            'x1' => round($cx + $rOut * cos($a), 1),
            'y1' => round($cy + $rOut * sin($a), 1),
            'x2' => round($cx + $rIn  * cos($a), 1),
            'y2' => round($cy + $rIn  * sin($a), 1),
            'cls' => $major ? 'is-major' : ($mid ? 'is-mid' : ''),
        ];
    }
@endphp

<section class="method-section odm-section" id="method" aria-labelledby="odm-title" data-odm>
  <div class="container">
    <div class="section-lead centered reveal">
      <span class="eyebrow">The One Degree Method</span>
      <h2 id="odm-title">A one degree shift changes the destination.</h2>
      <p>
        A small change in direction at the start of a journey leads somewhere entirely different.
        The decisions that matter most are made early — when a student first defines what they are
        working towards. <strong>We help you find your inner compass.</strong>
      </p>
    </div>

    <div class="odm-layout reveal">
      <div class="odm-compass" role="group" aria-label="The One Degree Method — interactive 4D compass">
        <svg class="odm-dial" viewBox="0 0 440 440" aria-hidden="true" focusable="false">
          <defs>
            {{-- Top-arch arcs (sweep 1 = ∩) — one per ring circumference --}}
            <path id="odm-arc-horizon" d="M 64,163.2 A 166,166 0 0,1 376,163.2" fill="none"></path>
            <path id="odm-arc-runway" d="M 104,170.8 A 126,126 0 0,1 336,170.8" fill="none"></path>
            {{-- Cream is the only theme, so these brand stops are hard-coded --}}
            <radialGradient id="odm-g-face" cx="50%" cy="38%" r="72%">
              <stop offset="0%" stop-color="#ffffff"></stop>
              <stop offset="62%" stop-color="#fdf9f3"></stop>
              <stop offset="100%" stop-color="#f1ebe0"></stop>
            </radialGradient>
            <linearGradient id="odm-g-orange" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="#ff9466"></stop>
              <stop offset="55%" stop-color="#ff5e32"></stop>
              <stop offset="100%" stop-color="#cf3d18"></stop>
            </linearGradient>
            <linearGradient id="odm-g-ink" x1="0" y1="0" x2="0" y2="1">
              <stop offset="0%" stop-color="#2f1cae"></stop>
              <stop offset="100%" stop-color="#100258"></stop>
            </linearGradient>
            <filter id="odm-rose-shadow" x="-30%" y="-30%" width="160%" height="160%">
              <feDropShadow dx="0" dy="3" stdDeviation="4" flood-color="#100258" flood-opacity="0.22"></feDropShadow>
            </filter>
          </defs>

          {{-- Compass face --}}
          <circle class="odm-face" cx="220" cy="220" r="208"></circle>
          <circle class="odm-face-sheen" cx="220" cy="150" r="150"></circle>

          {{-- Graduated bezel ticks --}}
          @foreach($ticks as $t)
            <line class="odm-grad {{ $t['cls'] }}" x1="{{ $t['x1'] }}" y1="{{ $t['y1'] }}" x2="{{ $t['x2'] }}" y2="{{ $t['y2'] }}"></line>
          @endforeach

          {{-- Rings: bezel + inner circumference --}}
          <circle class="odm-ring odm-ring-outer" cx="220" cy="220" r="208"></circle>
          <circle class="odm-ring odm-ring-bezel" cx="220" cy="220" r="182"></circle>
          <circle class="odm-ring odm-ring-inner" cx="220" cy="220" r="150"></circle>

          {{-- Faint crosshair --}}
          <line class="odm-cross" x1="220" y1="40" x2="220" y2="400"></line>
          <line class="odm-cross" x1="40" y1="220" x2="400" y2="220"></line>

          {{-- Compass rose --}}
          <g class="odm-rose" filter="url(#odm-rose-shadow)">
            {{-- intercardinal (45°) star behind --}}
            <polygon class="odm-rose-inter" points="261,179 234,220 261,261 220,234 179,261 206,220 179,179 220,206"></polygon>
            {{-- cardinal star --}}
            <polygon class="odm-rose-star" points="220,136 234.1,205.9 304,220 234.1,234.1 220,304 205.9,234.1 136,220 205.9,205.9"></polygon>
            {{-- needle halves --}}
            <polygon class="odm-rose-n" points="220,136 234.1,205.9 205.9,205.9"></polygon>
            <polygon class="odm-rose-s" points="220,304 205.9,234.1 234.1,234.1"></polygon>
          </g>
          <circle class="odm-pivot-ring" cx="220" cy="220" r="13"></circle>
          <circle class="odm-pivot" cx="220" cy="220" r="5"></circle>

          {{-- Two threads — curved, clickable ribbons on the two circumferences --}}
          <a class="odm-thread" data-odm-key="horizon" role="button" tabindex="0"
             aria-label="The Horizon — Career Mentorship">
            <path class="odm-thread-track" d="M 64,163.2 A 166,166 0 0,1 376,163.2" fill="none"></path>
            <text class="odm-thread-text"><textPath href="#odm-arc-horizon" startOffset="50%">The Horizon · Career Mentorship</textPath></text>
          </a>
          <a class="odm-thread" data-odm-key="runway" role="button" tabindex="0"
             aria-label="The Runway — Student Development">
            <path class="odm-thread-track" d="M 104,170.8 A 126,126 0 0,1 336,170.8" fill="none"></path>
            <text class="odm-thread-text"><textPath href="#odm-arc-runway" startOffset="50%">The Runway · Student Development</textPath></text>
          </a>
        </svg>

        {{-- The four D stages at the cardinal points --}}
        @foreach($stages as $key => $stage)
          @continue(! empty($stage['thread']))
          <button type="button" class="odm-point odm-point--{{ $stage['pos'] }}"
                  data-odm-key="{{ $key }}" aria-pressed="false">
            <span class="odm-point-num" aria-hidden="true">{{ $stage['num'] }}</span>
            <span class="odm-point-d" aria-hidden="true">D</span>
            <span class="odm-point-name">{{ $stage['label'] }}</span>
          </button>
        @endforeach
      </div>

      <div class="odm-detail" aria-live="polite">
        @foreach($stages as $key => $stage)
          <article class="odm-panel" data-odm-panel="{{ $key }}" @if($loop->first) data-odm-default @endif>
            <span class="odm-panel-kind">{{ $stage['kind'] }}</span>
            <h3 class="odm-panel-title">
              @if(empty($stage['thread']))<span class="odm-panel-d" aria-hidden="true">D</span>@endif{{ $stage['label'] }}
            </h3>
            <p class="odm-panel-lead">{{ $stage['lead'] }}</p>
            <p class="odm-panel-body">{{ $stage['body'] }}</p>
          </article>
        @endforeach

        <div class="odm-hint">
          <i data-lucide="compass"></i>
          <span>Tap a point on the compass — or a thread on its ring — to explore the method.</span>
        </div>
      </div>
    </div>
  </div>
</section>
