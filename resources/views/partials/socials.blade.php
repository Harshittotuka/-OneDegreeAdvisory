@php
    $socials = config('site.socials', []);
    $variant = $variant ?? 'default';

    // The notice bar shows its own dedicated WhatsApp icon, so drop WhatsApp from
    // the social list there to avoid a duplicate in the top blue bar.
    if ($variant === 'notice') {
        $socials = array_values(array_filter(
            $socials,
            fn ($social) => ($social['slug'] ?? '') !== 'whatsapp',
        ));
    }
@endphp
@if (!empty($socials))
  <ul class="site-socials site-socials--{{ $variant }}" aria-label="Social media">
    @foreach ($socials as $social)
      <li>
        <a class="site-social site-social--{{ $social['slug'] }}"
           href="{{ $social['href'] }}"
           target="_blank" rel="noopener"
           aria-label="One Degree Advisory on {{ $social['label'] }}">
          <span class="site-social-glow" aria-hidden="true"></span>
          @if ($social['slug'] === 'instagram')
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <rect x="3" y="3" width="18" height="18" rx="5"/>
              <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37Z"/>
              <circle cx="17.5" cy="6.5" r="0.6" fill="currentColor"/>
            </svg>
          @elseif ($social['slug'] === 'facebook')
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
              <path d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5.02 3.66 9.18 8.44 9.94v-7.03H7.9v-2.91h2.54V9.85c0-2.51 1.5-3.9 3.78-3.9 1.1 0 2.24.2 2.24.2v2.46h-1.26c-1.24 0-1.63.78-1.63 1.57v1.89h2.78l-.44 2.91h-2.34V22c4.78-.76 8.44-4.92 8.44-9.94z"/>
            </svg>
          @elseif ($social['slug'] === 'linkedin')
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
              <path d="M19 3H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2zM8.34 18.34H5.67V9.67h2.67v8.67zM7 8.5a1.55 1.55 0 1 1 0-3.1 1.55 1.55 0 0 1 0 3.1zm11.34 9.84h-2.67v-4.5c0-1.07-.02-2.45-1.5-2.45-1.5 0-1.73 1.17-1.73 2.37v4.58H9.78V9.67h2.56v1.18h.03c.36-.68 1.24-1.4 2.55-1.4 2.73 0 3.24 1.8 3.24 4.13v4.76z"/>
            </svg>
          @elseif ($social['slug'] === 'whatsapp')
            <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
              <path d="M17.47 14.38c-.29-.15-1.7-.84-1.96-.93-.26-.1-.45-.15-.64.14-.19.29-.74.93-.9 1.12-.17.19-.33.21-.62.07-.29-.15-1.22-.45-2.32-1.43-.86-.77-1.44-1.71-1.6-2-.17-.29-.02-.45.13-.59.13-.13.29-.34.43-.51.14-.17.19-.29.29-.48.1-.19.05-.36-.02-.51-.07-.14-.64-1.55-.88-2.13-.23-.55-.47-.48-.64-.49l-.55-.01c-.19 0-.5.07-.77.36-.26.29-1 .98-1 2.38 0 1.4 1.03 2.76 1.17 2.95.14.19 2.01 3.07 4.88 4.3.68.29 1.21.47 1.63.6.68.22 1.31.19 1.8.12.55-.08 1.7-.69 1.94-1.36.24-.67.24-1.24.17-1.36-.07-.12-.26-.19-.55-.34zM12.04 2.5c-5.25 0-9.5 4.25-9.5 9.5 0 1.67.44 3.31 1.27 4.74L2.5 21.5l4.91-1.29a9.43 9.43 0 0 0 4.63 1.18h.01c5.25 0 9.5-4.25 9.5-9.5 0-2.54-.99-4.92-2.78-6.72A9.43 9.43 0 0 0 12.04 2.5zm0 17.4h-.01a7.86 7.86 0 0 1-4-1.1l-.29-.17-2.96.78.79-2.89-.19-.3a7.84 7.84 0 0 1-1.2-4.18c0-4.35 3.54-7.89 7.9-7.89 2.11 0 4.09.82 5.58 2.31a7.84 7.84 0 0 1 2.31 5.58c0 4.35-3.55 7.9-7.9 7.9z"/>
            </svg>
          @endif
          <span class="visually-hidden">{{ $social['label'] }}</span>
        </a>
      </li>
    @endforeach
  </ul>
@endif
