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
                <a class="contact-head-phone" href="tel:+{{ config('site.contact.phone_e164') }}" aria-label="Call {{ config('site.name') }} on {{ config('site.contact.phone') }}">
                  <i data-lucide="phone-call"></i>
                  <span>{{ config('site.contact.phone') }}</span>
                </a>
                {{-- Glyph only on desktop, where the three pills share one
                     458-560px column and a spelled-out label pushed this one
                     onto a line of its own. aria-label carries the name; the
                     word itself returns below 920px, where the pills go
                     full-width. --}}
                <a class="contact-head-phone contact-head-phone--wa" href="https://wa.me/{{ config('site.contact.phone_e164') }}" target="_blank" rel="noopener" title="Chat on WhatsApp" aria-label="Chat with {{ config('site.name') }} on WhatsApp">
                  <i data-lucide="message-circle"></i>
                  <span class="contact-head-phone__label">WhatsApp</span>
                </a>
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