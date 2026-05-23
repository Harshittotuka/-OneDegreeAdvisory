@php
    $pageTitle = 'Contact | OneDegreeAdvisory';
    $pageDescription = 'Book a complimentary 30-minute consultation with a senior counsellor. Reach OneDegreeAdvisory by phone, email, or visit our Jaipur office.';
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
              <h1 class="contact-page-title" id="contact-page-title">Contact OneDegree</h1>
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
                <a class="contact-head-phone" href="tel:8233365888">
                  <i data-lucide="phone"></i>
                  <span>8233365888</span>
                </a>
              </div>
            </div>
          </header>

          <div class="contact-page-grid">
            <aside class="contact-info-panel reveal" aria-label="Contact details">
              <h2>Contact us on</h2>
              <p>Free consultation &middot; No obligation &middot; 30 minutes with a senior counsellor.</p>

              <div class="contact-info-list">
                <div class="contact-info-item">
                  <i data-lucide="phone"></i>
                  <div>
                    <strong>Phone</strong>
                    <a href="tel:8233365888">8233365888</a>
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
                    <span>A-16A, Van Vihar colony, Tonk Road, Jaipur, Rajasthan, 302018</span>
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

              <strong class="contact-mini-title">Connect with us</strong>
              <div class="contact-socials" aria-label="Social links">
                <a href="#" aria-label="LinkedIn"><i data-lucide="briefcase-business"></i></a>
                <a href="#" aria-label="Instagram"><i data-lucide="camera"></i></a>
                <a href="#" aria-label="YouTube"><i data-lucide="play"></i></a>
                <a href="#" aria-label="WhatsApp"><i data-lucide="message-circle"></i></a>
              </div>

              <div class="contact-response-card">
                <strong>Average response time</strong>
                <span>Under 4 hours</span>
                <p>Business days &middot; IST timezone</p>
              </div>
            </aside>

            <div class="contact-form-panel reveal" id="contact-form">
              <h2>Get in touch with us for a FREE counselling.</h2>
              <form data-consult-form>
                <div class="contact-form-row">
                  <label class="contact-field" for="contact-name">
                    <span>Full name *</span>
                    <input id="contact-name" name="name" type="text" required placeholder="e.g. Aanya Mehta">
                  </label>
                  <label class="contact-field" for="contact-email">
                    <span>Email address *</span>
                    <input id="contact-email" name="email" type="email" required placeholder="you@example.com">
                  </label>
                </div>

                <div class="contact-form-row">
                  <label class="contact-field" for="contact-phone">
                    <span>Mobile number *</span>
                    <input id="contact-phone" name="phone" type="tel" required placeholder="+91 98xxxxxxxx">
                  </label>
                  <label class="contact-field" for="contact-city">
                    <span>City</span>
                    <input id="contact-city" name="city" type="text" placeholder="e.g. Bengaluru">
                  </label>
                </div>

                <div class="contact-form-row">
                  <label class="contact-field" for="contact-destination">
                    <span>Preferred destination</span>
                    <select id="contact-destination" name="destination">
                      <option>Not sure yet</option>
                      <option>United States</option>
                      <option>United Kingdom</option>
                      <option>Canada</option>
                      <option>Australia</option>
                      <option>Germany</option>
                      <option>Singapore</option>
                      <option>Multiple countries</option>
                    </select>
                  </label>
                  <label class="contact-field" for="contact-level">
                    <span>Current academic level *</span>
                    <select id="contact-level" name="level" required>
                      <option value="">Choose one...</option>
                      <option>Grade 9&ndash;10</option>
                      <option>Grade 11&ndash;12</option>
                      <option>Undergraduate (1&ndash;4 yr)</option>
                      <option>Graduate</option>
                      <option>Working professional</option>
                    </select>
                  </label>
                </div>

                <label class="contact-field contact-field-full" for="contact-message">
                  <span>Tell us about your dream</span>
                  <textarea id="contact-message" name="message" placeholder="What programs or universities are you considering? What's your timeline?"></textarea>
                </label>

                <label class="contact-consent">
                  <input type="checkbox" name="consent" checked>
                  <span>I agree to receive communications from OneDegreeAdvisory including (but not limited to) WhatsApp, SMS, RCS and email about my admissions journey.</span>
                </label>

                <button class="btn btn-primary" type="submit">
                  <span>Request my free profile review</span>
                  <i data-lucide="arrow-up-right"></i>
                </button>
                <p class="contact-privacy">By submitting, you agree to our <a href="#">Privacy Policy</a>. We never share your data.</p>
                <p class="form-status" role="status" aria-live="polite" data-form-status></p>
              </form>
            </div>
          </div>

          <div class="contact-map reveal" aria-label="OneDegreeAdvisory office location">
            <iframe src="https://maps.google.com/maps?q=26.8692893,75.7895342&amp;z=17&amp;output=embed" title="OneDegreeAdvisory office location" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
          </div>
        </div>
      </section>
    </main>
@endsection