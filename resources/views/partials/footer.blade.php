<footer class="site-footer">
  <div class="footer-skyline" aria-hidden="true"></div>
  <div class="container footer-grid">
    <div class="footer-brand">
      <a class="brand brand-footer" href="{{ route('home') }}#top" aria-label="{{ config('site.name') }} home">
        <span class="brand-mark" aria-hidden="true">1&deg;</span>
        <span>
          <strong>{{ config('site.name') }}</strong>
          <small>{{ config('site.tagline') }}</small>
        </span>
      </a>
      <p>
        A polished, practical advisory for students ready to study abroad with purpose,
        evidence, and confidence.
      </p>
    </div>

    <div>
      <h2>Quick Links</h2>
      <a href="{{ route('home') }}#top">Home</a>
      <a href="{{ route('home') }}#destinations">Destinations</a>
      <a href="{{ route('about') }}">About</a>
      <a href="{{ route('contact') }}">Contact</a>
    </div>

    <div>
      <h2>Advisory Tracks</h2>
      <a href="{{ route('contact') }}">Undergraduate</a>
      <a href="{{ route('contact') }}">Masters and MBA</a>
      <a href="{{ route('contact') }}">Scholarships</a>
      <a href="{{ route('contact') }}">Visa readiness</a>
    </div>

    <div>
      <h2>Connect</h2>
      <a href="mailto:{{ config('site.contact.email') }}">{{ config('site.contact.email') }}</a>
      <a href="tel:{{ config('site.contact.phone') }}">{{ config('site.contact.phone') }}</a>
      <p class="footer-address">{{ config('site.contact.address') }}</p>
      <a class="footer-contact-button" href="{{ route('contact') }}">
        <i data-lucide="send"></i>
        <span>Contact</span>
      </a>
      <div class="socials" aria-label="Social links">
        <a href="#" aria-label="LinkedIn"><i data-lucide="briefcase-business"></i></a>
        <a href="#" aria-label="Instagram"><i data-lucide="camera"></i></a>
        <a href="#" aria-label="YouTube"><i data-lucide="play"></i></a>
      </div>
    </div>
  </div>
  <div class="container footer-bottom">
    <span>&copy; {{ date('Y') }} {{ config('site.name') }}. All rights reserved.</span>
    <a href="#top">Back to top</a>
  </div>
</footer>
