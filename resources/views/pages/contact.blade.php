@php
    $pageTitle = 'Contact | One Degree Advisory';
    $pageDescription = 'Book a complimentary 30-minute consultation with a senior counsellor. Reach One Degree Advisory by phone, email, or visit our Jaipur office.';
    $activeNav = 'contact';
    $mainId = 'main';
@endphp

@extends('layouts.app')

@section('content')
<main id="main" class="contact-page-main">
      <section class="contact-page" id="top" aria-labelledby="contact-page-title">
        <div class="container">
          <header class="contact-page-head reveal">
            <div class="contact-page-head-copy">
              <span class="eyebrow">Let's begin</span>
              <h1 class="contact-page-title" id="contact-page-title">Contact One Degree</h1>
              <p class="contact-page-kicker">Your trusted <em>study-abroad</em> consultants.</p>
            </div>
            <div class="contact-page-head-aside">
              <p>
                Let's start working towards your dream. Tell us where you are in your journey and we'll match you to a senior counsellor within 24 hours.
              </p>
              <div class="contact-page-head-actions">
                <a class="btn btn-primary" href="#contact-form">
                  <span>Request a free review</span>
                  <i data-lucide="arrow-up-right"></i>
                </a>
                {{-- One pill, two actions: the number dials, the segment on
                     its end opens WhatsApp. Two separate labelled pills plus the
                     primary CTA cannot share this column (458-560px) on one
                     line, and a lone glyph pill beside them just read as an
                     unrelated chat button. --}}
                <div class="contact-head-reach">
                  <a class="contact-head-reach__call" href="tel:+{{ config('site.contact.phone_e164') }}" aria-label="Call {{ config('site.name') }} on {{ config('site.contact.phone') }}">
                    <i data-lucide="phone-call"></i>
                    <span>{{ config('site.contact.phone') }}</span>
                  </a>
                  <a class="contact-head-reach__wa" href="https://wa.me/{{ config('site.contact.phone_e164') }}" target="_blank" rel="noopener" title="Chat on WhatsApp" aria-label="Chat with {{ config('site.name') }} on WhatsApp">
                    {{-- The real WhatsApp mark, in WhatsApp green: the glyph is
                         what identifies this half, so it has to be the actual
                         brand shape rather than a generic speech bubble. --}}
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                      <path d="M17.47 14.38c-.29-.15-1.7-.84-1.96-.93-.26-.1-.45-.15-.64.14-.19.29-.74.93-.9 1.12-.17.19-.33.21-.62.07-.29-.15-1.22-.45-2.32-1.43-.86-.77-1.44-1.71-1.6-2-.17-.29-.02-.45.13-.59.13-.13.29-.34.43-.51.14-.17.19-.29.29-.48.1-.19.05-.36-.02-.51-.07-.14-.64-1.55-.88-2.13-.23-.55-.47-.48-.64-.49l-.55-.01c-.19 0-.5.07-.77.36-.26.29-1 .98-1 2.38 0 1.4 1.03 2.76 1.17 2.95.14.19 2.01 3.07 4.88 4.3.68.29 1.21.47 1.63.6.68.22 1.31.19 1.8.12.55-.08 1.7-.69 1.94-1.36.24-.67.24-1.24.17-1.36-.07-.12-.26-.19-.55-.34zM12.04 2.5c-5.25 0-9.5 4.25-9.5 9.5 0 1.67.44 3.31 1.27 4.74L2.5 21.5l4.91-1.29a9.43 9.43 0 0 0 4.63 1.18h.01c5.25 0 9.5-4.25 9.5-9.5 0-2.54-.99-4.92-2.78-6.72A9.43 9.43 0 0 0 12.04 2.5zm0 17.4h-.01a7.86 7.86 0 0 1-4-1.1l-.29-.17-2.96.78.79-2.89-.19-.3a7.84 7.84 0 0 1-1.2-4.18c0-4.35 3.54-7.89 7.9-7.89 2.11 0 4.09.82 5.58 2.31a7.84 7.84 0 0 1 2.31 5.58c0 4.35-3.55 7.9-7.9 7.9z"/>
                    </svg>
                    <span class="contact-head-reach__label">WhatsApp</span>
                  </a>
                </div>
              </div>
            </div>
          </header>

          <div class="contact-page-grid">
            <aside class="contact-info-panel reveal" aria-label="Contact details">
              <h2>Contact us</h2>
              <p>Free consultation - 30 minutes with a senior counsellor.</p>

              <div class="contact-info-list">
                <div class="contact-info-item">
                  <i data-lucide="phone"></i>
                  <div>
                    <strong>Phone</strong>
                    <a href="tel:+{{ config('site.contact.phone_e164') }}" aria-label="Call {{ config('site.name') }} on {{ config('site.contact.phone') }}">{{ config('site.contact.phone') }}</a>
                    <span aria-hidden="true">&middot;</span>
                    <a href="https://wa.me/{{ config('site.contact.phone_e164') }}" target="_blank" rel="noopener">WhatsApp</a>
                  </div>
                </div>
                <div class="contact-info-item">
                  <i data-lucide="mail"></i>
                  <div>
                    <strong>Email</strong>
                    <a href="mailto:admissions@onedegreeadvisory.com">admissions@onedegreeadvisory.com</a>
                  </div>
                </div>
                <div class="contact-info-item">
                  <i data-lucide="map-pin"></i>
                  <div>
                    <strong>Office</strong>
                    <a href="https://www.google.com/maps/search/?api=1&amp;query=26.8692893,75.7895342" target="_blank" rel="noopener" aria-label="Open One Degree Advisory office in Google Maps">A-16A, Van Vihar colony, Tonk Road, Jaipur, Rajasthan, 302018</a>
                  </div>
                </div>
                <div class="contact-info-item">
                  <i data-lucide="clock-3"></i>
                  <div>
                    <strong>Hours</strong>
                    <span>Mon&ndash;Sat &middot; 10:00 to 19:00 IST<br>Sundays by appointment</span>
                  </div>
                </div>
              </div>

              <p class="contact-privacy-note">
                <i data-lucide="shield-check" aria-hidden="true"></i>
                <span>Your details are handled per our <a href="{{ route('privacy') }}">Privacy Policy</a> and <a href="{{ route('terms') }}">Terms &amp; Conditions</a>.</span>
              </p>

              <div class="contact-response-card">
                <strong>Average response time</strong>
                <span>Within 4 hours</span>
                <p>Business days &middot; IST timezone</p>
              </div>

              <div class="contact-aside-socials">
                <span class="contact-aside-socials-label">Follow us</span>
                @include('partials.socials', ['variant' => 'aside'])
              </div>
            </aside>

            <div class="contact-form-panel reveal" id="contact-form">
              <h2>Get in touch with us for a FREE counselling.</h2>
              @include('partials.contact-form', ['formId' => 'contact'])
            </div>
          </div>

          <div class="contact-map reveal" aria-label="One Degree Advisory office location">
            <iframe src="https://maps.google.com/maps?q=26.8692893,75.7895342&amp;z=17&amp;output=embed" title="One Degree Advisory office location" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
          </div>
        </div>
      </section>
    </main>
@endsection