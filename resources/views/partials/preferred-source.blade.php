@php
    /* Google "Preferred Sources" — a reader who taps this nominates One Degree
       in Google Search's source picker, and Google then shows them more of this
       site in Top Stories. See developers.google.com/search/docs/appearance/preferred-sources.

       Two layers, deliberately:

       1. The link below, rendered server-side. It is Google's documented
          deeplink fallback, it needs no JavaScript, and it is the whole feature
          on its own — everything else here is decoration.

       2. Google's own interactive button, which publisher.js draws into the
          empty div and which opens the add-dialog in an overlay instead of
          sending the visitor to google.com. Google says to put that script in
          <head> on every page; it is not there, because it is a third-party
          request made for a footer control most visits never scroll to.
          script.js fetches it only once a badge is about to come into view and
          only then hides our link (see "preferred source badge" there). If the
          script is blocked, or Google renders nothing because the visitor is
          signed out, the link simply stays — never a blank gap.

       The nominated domain is always the canonical public host, never the
       current one: Google matches preferred sources by domain, so the UAT
       mirror and localhost must still point people at the live site. */
    $preferredSourceHost = trim((string) config('site.canonical_host'));
    $preferredSourceVariant = $variant ?? 'footer';
@endphp
@if ($preferredSourceHost !== '' && $preferredSourceVariant === 'legal')
  {{-- The footer's legal row (Terms / Privacy / Back to top). Deliberately not
       the markup below: that is a pill with a Google logo tile, and dropping it
       into this row put a 28px white circle between the links and pushed the
       label onto a second line. Here the control is simply one more link in the
       row, inheriting its colour, size and hover from .footer-legal-links a.
       No logo, and no publisher.js mount — the deeplink is the whole feature. --}}
  <a href="https://www.google.com/preferences/source?q={{ urlencode($preferredSourceHost) }}"
     target="_blank"
     rel="noopener nofollow"
     aria-label="Add {{ config('site.name') }} as a preferred source on Google">Prefer us on Google</a>
@elseif ($preferredSourceHost !== '')
  <div class="preferred-source preferred-source--{{ $preferredSourceVariant }}" data-preferred-source>
    <a class="preferred-source-link"
       href="https://www.google.com/preferences/source?q={{ urlencode($preferredSourceHost) }}"
       target="_blank"
       rel="noopener nofollow"
       aria-label="Add {{ config('site.name') }} as a preferred source on Google">
      <span class="preferred-source-mark" aria-hidden="true">
        <svg viewBox="0 0 48 48" focusable="false">
          <path fill="#4285F4" d="M45.12 24.5c0-1.56-.14-3.06-.4-4.5H24v8.51h11.84c-.51 2.75-2.06 5.08-4.39 6.64v5.52h7.11c4.16-3.83 6.56-9.47 6.56-16.17z"/>
          <path fill="#34A853" d="M24 46c5.94 0 10.92-1.97 14.56-5.33l-7.11-5.52c-1.97 1.32-4.49 2.1-7.45 2.1-5.73 0-10.58-3.87-12.31-9.07H4.34v5.7C7.96 41.07 15.4 46 24 46z"/>
          <path fill="#FBBC05" d="M11.69 28.18C11.25 26.86 11 25.45 11 24s.25-2.86.69-4.18v-5.7H4.34C2.85 17.09 2 20.45 2 24s.85 6.91 2.34 9.88l7.35-5.7z"/>
          <path fill="#EA4335" d="M24 10.75c3.23 0 6.13 1.11 8.41 3.29l6.31-6.31C34.91 4.18 29.93 2 24 2 15.4 2 7.96 6.93 4.34 14.12l7.35 5.7c1.73-5.2 6.58-9.07 12.31-9.07z"/>
        </svg>
      </span>
      <span class="preferred-source-copy">
        <small>Add us as a</small>
        <strong>Preferred source on Google</strong>
      </span>
    </a>
    {{-- publisher.js draws its button in here. Left visible rather than hidden
         until it fills: an empty div is already zero-height, and a mount the
         script might judge invisible is a mount it might decline to draw in. --}}
    <div class="preferred-source-official" google-add-preferred-source-btn data-theme="dark"></div>
  </div>
@endif
