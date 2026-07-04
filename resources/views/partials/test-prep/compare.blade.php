@php
    /**
     * Test-Prep "Compare & enrol" section.
     *
     * Expects:
     *   $compare        — TestPrepCompareStore::get() config
     *   $paymentEnabled — bool, whether Razorpay is configured
     *
     * One program row is the single source of truth for BOTH the compare
     * visualisation and the payment picker. The price shown is exactly what the
     * customer is charged; the amount is re-derived server-side at order time.
     */
    use App\Support\TestPrepCompareStore;

    $style = in_array(($compare['style'] ?? 'bars'), TestPrepCompareStore::STYLES, true) ? $compare['style'] : 'bars';
    $heading = $compare['heading'] ?? [];
    $pay = $compare['payment'] ?? [];
    $accent = preg_match('/^#[0-9a-f]{6}$/i', (string) ($pay['accent'] ?? '')) ? $pay['accent'] : '#ff5a2e';

    // Visible programs, in stored order — this order is authoritative for the
    // payment option_index the browser will send.
    $programs = array_values(array_filter($compare['programs'] ?? [], fn ($p) => ($p['visible'] ?? true) && trim((string) ($p['name'] ?? '')) !== ''));

    $maxPrice = 0;
    $maxMonths = 0.0;
    foreach ($programs as $p) {
        $maxPrice = max($maxPrice, (int) ($p['price'] ?? 0));
        $maxMonths = max($maxMonths, (float) ($p['months'] ?? 0));
    }
    $maxPrice = max($maxPrice, 1);
    $maxMonths = max($maxMonths, 0.5);

    $fmt = fn ($n) => number_format((int) $n);
    $moLabel = fn ($m) => rtrim(rtrim(number_format((float) $m, 1), '0'), '.').' mo';
@endphp

<section id="tp-compare"
         class="tpc tpc--{{ $style }}"
         data-tpc
         data-page-slug="{{ TestPrepCompareStore::PAGE_SLUG }}"
         data-block-id="{{ TestPrepCompareStore::BLOCK_ID }}"
         style="--tpc-accent: {{ $accent }};">
  <div class="tpc-wrap">

    {{-- ───────── Compare header ───────── --}}
    {{-- Only render the header block when at least one field is set, so a fully
         blank heading leaves no empty box / stray margin. --}}
    @if(!empty($heading['eyebrow']) || !empty($heading['title']) || !empty($heading['subtitle']))
      <div class="tpc-head">
        @if(!empty($heading['eyebrow']))
          <span class="tpc-eyebrow"><span class="tpc-dot"></span> {{ $heading['eyebrow'] }}</span>
        @endif
        @if(!empty($heading['title']))<h2 class="tpc-title">{{ $heading['title'] }}</h2>@endif
        @if(!empty($heading['subtitle']))<p class="tpc-sub">{{ $heading['subtitle'] }}</p>@endif
      </div>
    @endif

    @if(count($programs) === 0)
      <p class="tpc-empty">Programs will appear here once they’re added in the CMS.</p>
    @else

      {{-- Price / Duration toggle — used by the bars & table variants. --}}
      @if(in_array($style, ['bars', 'table'], true))
        <div class="tpc-controls" role="tablist" aria-label="Compare by">
          <button type="button" class="tpc-chip is-active" data-metric="price" role="tab" aria-selected="true">By Price (₹)</button>
          <button type="button" class="tpc-chip" data-metric="months" role="tab" aria-selected="false">By Duration (months)</button>
        </div>
      @endif

      {{-- ═══════════ VARIANT 1 · BARS (default, the original look) ═══════════ --}}
      @if($style === 'bars')
        <div class="tpc-bars" data-tpc-bars
             data-max-price="{{ $maxPrice }}" data-max-months="{{ $maxMonths }}">
          @foreach($programs as $p)
            <div class="tpc-bar-row reveal"
                 data-price="{{ (int) ($p['price'] ?? 0) }}"
                 data-months="{{ (float) ($p['months'] ?? 0) }}">
              <div class="tpc-bar-name">
                {{ $p['name'] }}
                @if(!empty($p['badge']))<span class="tpc-bar-badge">{{ $p['badge'] }}</span>@endif
              </div>
              <div class="tpc-bar-track">
                <div class="tpc-bar-fill" style="--w: {{ $maxPrice ? round(((int) $p['price']) / $maxPrice * 100, 1) : 0 }}%;"></div>
              </div>
              <div class="tpc-bar-val"
                   data-price="₹{{ $fmt($p['price'] ?? 0) }}"
                   data-months="{{ $moLabel($p['months'] ?? 0) }}">₹{{ $fmt($p['price'] ?? 0) }}</div>
            </div>
          @endforeach
        </div>

      {{-- ═══════════ VARIANT 2 · PRICING CARDS ═══════════ --}}
      @elseif($style === 'cards')
        <div class="tpc-cards">
          @foreach($programs as $i => $p)
            @php $payable = (int) ($p['price'] ?? 0) >= 1; @endphp
            <article class="tpc-card reveal">
              @if(!empty($p['badge']))<span class="tpc-card-badge">{{ $p['badge'] }}</span>@endif
              <div class="tpc-card-top">
                <span class="tpc-card-name">{{ $p['name'] }}</span>
              </div>
              <div class="tpc-card-price">
                @if($payable)₹{{ $fmt($p['price']) }}<span>total course fee</span>@else<span class="tpc-card-onreq">Fee on request</span>@endif
              </div>
              <div class="tpc-card-meta">
                <span><i data-lucide="clock"></i> ~{{ $moLabel($p['months'] ?? 0) }}</span>
              </div>
              <button type="button" class="tpc-card-btn" data-tpc-enrol="{{ $i }}" {{ $payable ? '' : 'data-enquire' }}>
                {{ $payable ? 'Enrol' : 'Enquire' }}
                <i data-lucide="arrow-right"></i>
              </button>
            </article>
          @endforeach
        </div>

      {{-- ═══════════ VARIANT 3 · COMPARISON TABLE ═══════════ --}}
      @elseif($style === 'table')
        <div class="tpc-table-scroll">
          <table class="tpc-table" data-tpc-table>
            <thead>
              <tr>
                <th>Program</th>
                <th class="tpc-num">Price</th>
                <th class="tpc-num">Duration</th>
                <th></th>
              </tr>
            </thead>
            <tbody>
              @foreach($programs as $i => $p)
                @php $payable = (int) ($p['price'] ?? 0) >= 1; @endphp
                <tr class="reveal" data-price="{{ (int) ($p['price'] ?? 0) }}" data-months="{{ (float) ($p['months'] ?? 0) }}">
                  <td class="tpc-td-name">
                    {{ $p['name'] }}
                    @if(!empty($p['badge']))<span class="tpc-bar-badge">{{ $p['badge'] }}</span>@endif
                  </td>
                  <td class="tpc-num tpc-td-price">@if($payable)₹{{ $fmt($p['price']) }}@else<span class="tpc-muted">On request</span>@endif</td>
                  <td class="tpc-num">{{ $moLabel($p['months'] ?? 0) }}</td>
                  <td class="tpc-num">
                    <button type="button" class="tpc-table-btn" data-tpc-enrol="{{ $i }}" {{ $payable ? '' : 'data-enquire' }}>{{ $payable ? 'Enrol' : 'Enquire' }}</button>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>

      {{-- ═══════════ VARIANT 4 · TIER LIST (bold full-width rows) ═══════════ --}}
      @elseif($style === 'stack')
        <div class="tpc-stack">
          @foreach($programs as $i => $p)
            @php $payable = (int) ($p['price'] ?? 0) >= 1; @endphp
            <div class="tpc-tier reveal">
              <div class="tpc-tier-lead">
                <span class="tpc-tier-name">{{ $p['name'] }}@if(!empty($p['badge']))<span class="tpc-bar-badge">{{ $p['badge'] }}</span>@endif</span>
                <span class="tpc-tier-meta"><i data-lucide="clock"></i> ~{{ $moLabel($p['months'] ?? 0) }}</span>
              </div>
              <div class="tpc-tier-price">@if($payable)₹{{ $fmt($p['price']) }}@else<span class="tpc-muted" style="font-size:16px;">Fee on request</span>@endif</div>
              <button type="button" class="tpc-tier-btn" data-tpc-enrol="{{ $i }}" {{ $payable ? '' : 'data-enquire' }}>{{ $payable ? 'Enrol' : 'Enquire' }} <i data-lucide="arrow-right"></i></button>
            </div>
          @endforeach
        </div>
      @endif

      {{-- ═══════════════════════ PAYMENT ═══════════════════════ --}}
      @php $firstPayable = (int) ($programs[0]['price'] ?? 0) >= 1; @endphp
      <div class="tpc-pay" id="tp-enrol">
        <div class="tpc-pay-copy">
          @if(!empty($pay['eyebrow']))<span class="tpc-eyebrow tpc-eyebrow--dark"><span class="tpc-dot"></span> {{ $pay['eyebrow'] }}</span>@endif
          @if(!empty($pay['title']))<h3 class="tpc-pay-title">{{ $pay['title'] }}</h3>@endif
          @if(!empty($pay['description']))<p class="tpc-pay-desc">{{ $pay['description'] }}</p>@endif

          {{-- Boarding-pass ticket (mirrors the site hero) — reflects the picked
               program live: Track = name, Fare = price, Status = confirmation. --}}
          <div class="tpc-ticket" aria-hidden="true">
            <div class="tpc-ticket-top">
              <div class="tpc-ticket-route">
                <div class="tpc-ticket-line"></div>
                <div><div class="tpc-ticket-city">HOME</div><small>Where you are</small></div>
                <div class="tpc-ticket-plane">✈</div>
                <div class="tpc-ticket-goal"><div class="tpc-ticket-city">GOAL</div><small>Where you're headed</small></div>
              </div>
            </div>
            <div class="tpc-ticket-body">
              <div class="tpc-ticket-field"><small>Track</small><span data-tpc-tk-track>{{ $programs[0]['name'] ?? 'Test Prep' }}</span></div>
              <div class="tpc-ticket-field"><small>Fare</small><span data-tpc-tk-fare>{{ $firstPayable ? '₹'.$fmt($programs[0]['price']) : 'On request' }}</span></div>
              <div class="tpc-ticket-field"><small>Duration</small><span data-tpc-tk-dur>~{{ $moLabel($programs[0]['months'] ?? 0) }}</span></div>
              <div class="tpc-ticket-field"><small>Status</small><span class="tpc-ticket-ok">Confirmed on booking</span></div>
            </div>
            <div class="tpc-ticket-barcode"></div>
          </div>

          <ul class="tpc-pay-trust">
            <li><i data-lucide="shield-check"></i> Server-verified amount</li>
            <li><i data-lucide="lock"></i> Razorpay secure checkout</li>
            <li><i data-lucide="zap"></i> Instant seat confirmation</li>
          </ul>
        </div>

        <div class="tpc-pay-card">
          <label class="tpc-field-label" for="tpc-prog">Program</label>
          <div class="tpc-select-wrap">
            <select id="tpc-prog" class="tpc-select" data-tpc-prog>
              @foreach($programs as $i => $p)
                @php $payable = (int) ($p['price'] ?? 0) >= 1; @endphp
                <option value="{{ $i }}"
                        data-price="{{ (int) ($p['price'] ?? 0) }}"
                        data-payable="{{ $payable ? '1' : '0' }}"
                        data-name="{{ $p['name'] }}"
                        data-dur="~{{ $moLabel($p['months'] ?? 0) }}">
                  {{ $p['name'] }}@if($payable) — ₹{{ $fmt($p['price']) }}@else — fee on request @endif
                </option>
              @endforeach
            </select>
          </div>

          <div class="tpc-pay-amount" data-tpc-amount>
            <span class="tpc-pay-amount-label">You pay</span>
            <span class="tpc-pay-amount-value" data-tpc-amount-value>₹{{ $fmt($programs[0]['price'] ?? 0) }}</span>
          </div>

          <div class="tpc-pay-fields">
            <label><span>Full name</span><input type="text" data-tpc-name maxlength="160" autocomplete="name" placeholder="Your name" required></label>
            <label><span>Email</span><input type="email" data-tpc-email maxlength="190" autocomplete="email" placeholder="you@example.com" required></label>
            <label><span>Phone / WhatsApp</span><input type="tel" data-tpc-phone maxlength="40" autocomplete="tel" placeholder="+91 98765 43210"></label>
          </div>

          @if($paymentEnabled)
            <button type="button" class="tpc-pay-btn" data-tpc-pay>
              <i data-lucide="lock"></i>
              <span>{{ trim((string) ($pay['button_label'] ?? '')) ?: 'Pay securely' }}</span>
            </button>
          @else
            {{-- No gateway configured → route to the enquiry / contact flow. --}}
            <a class="tpc-pay-btn" href="{{ route('contact') }}" data-tpc-enquire-link>
              <i data-lucide="calendar-check"></i>
              <span>Enquire &amp; reserve a seat</span>
            </a>
          @endif

          <p class="tpc-pay-status" data-tpc-status aria-live="polite"></p>
          @if(!empty($pay['note']))<p class="tpc-pay-note"><i data-lucide="info"></i><span>{{ $pay['note'] }}</span></p>@endif
        </div>
      </div>

    @endif
  </div>
</section>

@include('partials.test-prep._compare-styles')

@if(count($programs) > 0)
  @include('partials.test-prep._compare-script', ['paymentEnabled' => $paymentEnabled])
@endif
