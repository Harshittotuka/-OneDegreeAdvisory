<footer class="site-footer">
  <div class="footer-skyline" aria-hidden="true"></div>
  <div class="container footer-grid">
    <div class="footer-brand">
      <a class="brand brand-footer" href="{{ route('home') }}#top" aria-label="{{ config('site.name') }} home">
        <img class="brand-mark" src="{{ asset('assets/Logo/mark-light.svg') }}" alt="" aria-hidden="true" width="104" height="36">
        <span class="brand-wordmark">
          <strong>One Degree</strong>
          <small>Advisory</small>
        </span>
      </a>
      <p>
        A polished, practical advisory for students ready to study abroad with purpose,
        evidence, and confidence.
      </p>
      @include('partials.socials', ['variant' => 'brand'])
    </div>

    <div>
      <h2>Quick Links</h2>
      <a href="{{ route('home') }}#top">Home</a>
      <a href="{{ route('about') }}">About</a>
      <a href="{{ route('careers') }}">Careers</a>
      <a href="{{ route('career-library.index') }}">Trending Career</a>
    </div>

    <div>
      <h2>Advisory Tracks</h2>
      <a href="{{ route('contact') }}">School Students</a>
      <a href="{{ route('contact') }}">College Students</a>
      <a href="{{ route('contact') }}">Working Professionals</a>
    </div>

    <div>
      <h2>Connect</h2>
      <a class="footer-email" href="mailto:{{ config('site.contact.email') }}">{{ config('site.contact.email') }}</a>
      <a href="https://wa.me/{{ config('site.contact.phone_e164') }}" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">{{ config('site.contact.phone') }}</a>
      <a class="footer-address" href="https://www.google.com/maps/search/?api=1&amp;query=26.8692893,75.7895342" target="_blank" rel="noopener" aria-label="Open One Degree Advisory office in Google Maps">
        <i data-lucide="map-pin" aria-hidden="true"></i>
        <span>{{ config('site.contact.address') }}</span>
      </a>
      <a class="footer-contact-button" href="{{ route('contact') }}">
        <i data-lucide="send"></i>
        <span>Contact</span>
      </a>
    </div>
  </div>
  <div class="container footer-bottom">
    <span>&copy; {{ date('Y') }} {{ config('site.name') }}. All rights reserved.</span>
    <span class="footer-legal-links">
      <a href="{{ route('terms') }}">Terms &amp; Conditions</a>
      <a href="{{ route('privacy') }}">Privacy Policy</a>
      <a href="#top">Back to top</a>
    </span>
  </div>
</footer>
