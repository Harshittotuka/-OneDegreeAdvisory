@php
    $socials = config('site.socials', []);
    $variant = $variant ?? 'default';
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
          @endif
          <span class="visually-hidden">{{ $social['label'] }}</span>
        </a>
      </li>
    @endforeach
  </ul>
@endif
