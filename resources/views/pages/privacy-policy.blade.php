@php
    $pageTitle = 'Privacy Policy | One Degree Advisory';
    $pageDescription = 'Read the One Degree Advisory Privacy Policy — how we collect, use, store, and protect your personal information across onedegreeadvisory.com and our services.';
    $activeNav = '';
    $mainId = 'main';

    // The source policy carries a placeholder effective date; we surface a real,
    // human date and keep the machine-readable value for structured data.
    $effectiveDate = 'July 22, 2026';
    $effectiveDateIso = '2026-07-22';

    // Section map — single source of truth for the table of contents, anchors,
    // and the rendered headings.
    $sections = [
        'information-we-collect'   => 'Information We Collect',
        'how-we-use-information'   => 'How We Use Your Information',
        'information-sharing'      => 'Information Sharing',
        'cookies'                  => 'Cookies',
        'data-security'            => 'Data Security',
        'data-retention'           => 'Data Retention',
        'your-rights'              => 'Your Rights',
        'third-party-links'        => 'Third-Party Links',
        'changes'                  => 'Changes to this Privacy Policy',
        'contact-us'               => 'Contact Us',
    ];
@endphp

@extends('layouts.app')

@push('head')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'PrivacyPolicy',
    'name' => 'Privacy Policy',
    'url' => route('privacy'),
    'inLanguage' => 'en',
    'dateModified' => $effectiveDateIso,
    'isPartOf' => [
        '@type' => 'WebSite',
        'name' => config('site.name'),
        'url' => url('/'),
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => config('site.name'),
        'url' => url('/'),
    ],
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>
@endpush

@section('content')
<main id="main" class="legal-page">
  <section class="legal-wrap" id="top">
    <div class="container">

      {{-- ── Header ── --}}
      <header class="legal-head">
        <span class="eyebrow">Legal</span>
        <h1>Privacy Policy</h1>
        <p class="legal-lead">
          At {{ config('site.name') }} (&ldquo;we&rdquo;, &ldquo;our&rdquo;, &ldquo;us&rdquo;), we respect your privacy and are
          committed to protecting your personal information. This policy explains how we collect, use, store, and protect
          the information you provide when using onedegreeadvisory.com and our services.
        </p>
        <p class="legal-date">Effective Date: <time datetime="{{ $effectiveDateIso }}">{{ $effectiveDate }}</time></p>
      </header>

      <div class="legal-grid">

        {{-- ── Table of contents ── --}}
        <aside class="legal-toc" aria-label="On this page">
          <div class="legal-toc-inner">
            <span class="legal-toc-title">Contents</span>
            <nav>
              <ol>
                @foreach ($sections as $anchor => $title)
                  <li><a href="#{{ $anchor }}" data-toc-link="{{ $anchor }}">{{ $title }}</a></li>
                @endforeach
              </ol>
            </nav>
          </div>
        </aside>

        {{-- ── Policy content ── --}}
        <article class="legal-content">

          <section id="information-we-collect" aria-labelledby="information-we-collect-title">
            <h2 id="information-we-collect-title">1. Information We Collect</h2>
            <p>We may collect the following information:</p>
            <ul>
              <li>Personal details such as your name, email address, phone number, and other information you provide through enquiry forms or registrations.</li>
              <li>Payment information (only when applicable) for processing transactions through secure payment partners.</li>
              <li>Technical information such as your IP address, browser type, device information, and website usage data.</li>
              <li>Information collected through cookies and similar technologies to improve your browsing experience.</li>
            </ul>
          </section>

          <section id="how-we-use-information" aria-labelledby="how-we-use-information-title">
            <h2 id="how-we-use-information-title">2. How We Use Your Information</h2>
            <p>We use your information to:</p>
            <ul>
              <li>Provide educational and career advisory services.</li>
              <li>Respond to your enquiries and support requests.</li>
              <li>Process registrations and payments.</li>
              <li>Improve our website, services, and user experience.</li>
              <li>Send important updates, newsletters, promotional offers, or service-related communications (you may opt out at any time).</li>
              <li>Comply with legal obligations and prevent fraud or misuse.</li>
            </ul>
          </section>

          <section id="information-sharing" aria-labelledby="information-sharing-title">
            <h2 id="information-sharing-title">3. Information Sharing</h2>
            <p><strong>We do not sell your personal information.</strong></p>
            <p>We may share your information only when necessary with:</p>
            <ul>
              <li>Trusted service providers who help us operate our website and services.</li>
              <li>Payment processors for secure transaction handling.</li>
              <li>Government authorities or legal agencies when required by law.</li>
              <li>Third parties during business restructuring, merger, or acquisition, if applicable.</li>
            </ul>
          </section>

          <section id="cookies" aria-labelledby="cookies-title">
            <h2 id="cookies-title">4. Cookies</h2>
            <p>Our website uses cookies to improve functionality, remember your preferences, and analyze website traffic. You may disable cookies through your browser settings; however, some features of the website may not function properly.</p>
          </section>

          <section id="data-security" aria-labelledby="data-security-title">
            <h2 id="data-security-title">5. Data Security</h2>
            <p>We implement reasonable technical and organizational measures to safeguard your personal information. While we strive to protect your data, no online platform can guarantee complete security.</p>
          </section>

          <section id="data-retention" aria-labelledby="data-retention-title">
            <h2 id="data-retention-title">6. Data Retention</h2>
            <p>We retain your personal information only for as long as necessary to provide our services, comply with legal obligations, resolve disputes, and enforce our policies.</p>
          </section>

          <section id="your-rights" aria-labelledby="your-rights-title">
            <h2 id="your-rights-title">7. Your Rights</h2>
            <p>You may request to:</p>
            <ul>
              <li>Access your personal information.</li>
              <li>Update or correct your information.</li>
              <li>Delete your personal data, subject to applicable legal requirements.</li>
              <li>Opt out of promotional communications.</li>
            </ul>
          </section>

          <section id="third-party-links" aria-labelledby="third-party-links-title">
            <h2 id="third-party-links-title">8. Third-Party Links</h2>
            <p>Our website may contain links to external websites. We are not responsible for the privacy practices or content of those third-party websites.</p>
          </section>

          <section id="changes" aria-labelledby="changes-title">
            <h2 id="changes-title">9. Changes to this Privacy Policy</h2>
            <p>We may update this Privacy Policy from time to time. Any changes will be posted on this page along with the revised Effective Date.</p>
          </section>

          <section id="contact-us" aria-labelledby="contact-us-title">
            <h2 id="contact-us-title">10. Contact Us</h2>
            <p>If you have any questions, requests, or concerns regarding this Privacy Policy or your personal information, please contact us:</p>
            <p class="legal-contact">
              <strong>{{ config('site.name') }}</strong><br>
              Website: <a href="{{ url('/') }}" rel="noopener">https://onedegreeadvisory.com</a><br>
              Email: <a href="mailto:{{ config('site.contact.email') }}">{{ config('site.contact.email') }}</a><br>
              Phone: <a href="tel:+{{ config('site.contact.phone_e164') }}">{{ config('site.contact.phone') }}</a>
              (<a href="https://wa.me/{{ config('site.contact.phone_e164') }}" target="_blank" rel="noopener">WhatsApp</a>)
            </p>
          </section>

        </article>
      </div>
    </div>
  </section>
</main>

<script>
  // Scrollspy — highlight the current section in the contents list.
  (function () {
    var run = function () {
      var links = Array.prototype.slice.call(document.querySelectorAll('[data-toc-link]'));
      if (!links.length || !('IntersectionObserver' in window)) return;
      var map = {};
      links.forEach(function (l) { map[l.getAttribute('data-toc-link')] = l; });
      var observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            links.forEach(function (l) { l.classList.remove('is-active'); });
            if (map[entry.target.id]) map[entry.target.id].classList.add('is-active');
          }
        });
      }, { rootMargin: '-25% 0px -65% 0px', threshold: 0 });
      document.querySelectorAll('.legal-content > section').forEach(function (s) { observer.observe(s); });
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', run);
    else run();
  })();
</script>

@push('head')
<style>
  /* ── Privacy / legal page — plain, matches the site's light pages ── */
  .legal-page { background: #fbf9f5; }
  .legal-wrap { padding: 104px 0 120px; }

  .legal-head {
    max-width: 940px;
    margin: 0 0 48px;
    padding-bottom: 28px;
    border-bottom: 1px solid var(--line);
  }
  .legal-head h1 {
    margin: 12px 0 22px;
    color: var(--navy);
    font-size: clamp(2.8rem, 6vw, 4.4rem);
  }
  .legal-lead {
    color: var(--muted);
    font-size: 1.08rem;
    line-height: 1.7;
    margin: 0 0 18px;
  }
  .legal-date {
    margin: 0;
    font-size: 0.9rem;
    color: var(--muted);
  }
  .legal-date time { color: var(--ink); font-weight: 600; }

  .legal-grid {
    display: grid;
    grid-template-columns: 252px minmax(0, 1fr);
    gap: 56px;
    align-items: start;
  }

  /* Contents — plain text list that stays pinned while the page scrolls.
     The column must stretch to the full row height (align-self: stretch) or
     the sticky inner has no room to travel. */
  .legal-toc { align-self: stretch; }
  .legal-toc-inner { position: sticky; top: calc(var(--header-offset) + 16px); }
  .legal-toc-title {
    display: block;
    font-size: 0.74rem; text-transform: uppercase; letter-spacing: 0.1em;
    font-weight: 700; color: var(--muted);
    margin: 0 0 14px;
  }
  .legal-toc ol {
    list-style: none; margin: 0; padding: 0;
    border-left: 1px solid var(--line);
  }
  /* Override the global `main li { text-align: justify }` so wrapped TOC
     items don't stretch their words across the line. */
  .legal-toc li { text-align: left; }
  .legal-toc a {
    display: block;
    padding: 7px 0 7px 16px;
    margin-left: -1px;
    border-left: 2px solid transparent;
    color: var(--muted);
    text-decoration: none;
    text-align: left;
    font-size: 0.92rem;
    line-height: 1.4;
    transition: color .15s, border-color .15s;
  }
  .legal-toc a:hover { color: var(--ink); }
  .legal-toc a.is-active {
    color: var(--teal-dark);
    border-left-color: var(--teal-dark);
    font-weight: 600;
  }

  /* Content prose */
  .legal-content { max-width: 100%; }
  .legal-content > section { scroll-margin-top: calc(var(--header-offset) + 20px); }
  .legal-content > section + section { margin-top: 40px; }
  .legal-content h2 {
    color: var(--navy);
    font-size: clamp(1.5rem, 2.4vw, 1.9rem);
    line-height: 1.15;
    margin: 0 0 14px;
  }
  .legal-content p {
    color: var(--muted);
    font-size: 1.02rem;
    line-height: 1.75;
    margin: 0 0 12px;
  }
  .legal-content p strong { color: var(--ink); }
  .legal-content ul {
    margin: 0 0 12px;
    padding-left: 20px;
  }
  .legal-content ul li {
    color: var(--muted);
    font-size: 1.02rem;
    line-height: 1.7;
    margin-bottom: 8px;
    padding-left: 4px;
  }
  .legal-content ul li::marker { color: var(--teal-dark); }
  .legal-contact { line-height: 2; }
  .legal-contact a { color: var(--teal-dark); }

  @media (max-width: 860px) {
    .legal-wrap { padding: 88px 0 80px; }
    .legal-grid { grid-template-columns: 1fr; gap: 36px; }
    .legal-toc-inner { position: static; }
    .legal-toc ol {
      display: flex; flex-wrap: wrap; gap: 2px 20px;
      border-left: none;
      padding-bottom: 16px;
      border-bottom: 1px solid var(--line);
    }
    .legal-toc a { padding: 4px 0; border-left: none; }
    .legal-toc a.is-active { border-left: none; }
  }
</style>
@endpush
@endsection
