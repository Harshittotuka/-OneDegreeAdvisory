{{--
  Top blue notice bar (shared by the classic and Stripe headers).

  Layout: social icons pinned left · scrolling announcement marquee in the
  middle · WhatsApp number pinned right. Each marquee item shows only its first
  four words followed by "....." as a teaser and links to the related page.
--}}
@php
    $toTeaser = static function (string $text): string {
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $teaser = implode(' ', array_slice($words, 0, 5));
        if (count($words) > 5) {
            $teaser .= ' .....';
        }
        return $teaser;
    };

    $notices = collect(config('site.notices', []))
        ->map(function ($item) use ($toTeaser) {
            $text = is_array($item) ? ($item['text'] ?? '') : (string) $item;
            $href = null;
            if (is_array($item)) {
                $href = ! empty($item['route']) ? route($item['route']) : ($item['href'] ?? null);
            }
            return ['teaser' => $toTeaser($text), 'full' => trim($text), 'href' => $href ?? route('contact')];
        })
        ->filter(fn ($n) => $n['teaser'] !== '')
        ->values();

    // Fall back to the legacy single-string notice if no list is configured.
    if ($notices->isEmpty() && config('site.notice')) {
        $notices = collect([[
            'teaser' => $toTeaser((string) config('site.notice')),
            'full'   => (string) config('site.notice'),
            'href'   => route('contact'),
        ]]);
    }

    $waPhone = config('site.contact.phone');
    $waE164  = config('site.contact.phone_e164');
@endphp

<div class="notice">
  @include('partials.socials', ['variant' => 'notice'])

  @if ($waPhone)
    {{-- "Compact" top-bar variant only: WhatsApp shown as an icon beside the socials. --}}
    <a class="notice-wa-icon"
       href="https://wa.me/{{ $waE164 }}"
       target="_blank" rel="noopener"
       aria-label="Chat with us on WhatsApp at {{ $waPhone }}">
      <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.885-9.885 9.885M20.52 3.449C18.24 1.245 15.24 0 12.045 0 5.463 0 .104 5.359.101 11.946c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.652a11.882 11.882 0 0 0 5.71 1.448h.006c6.585 0 11.946-5.36 11.949-11.946C24 8.39 22.797 5.652 20.52 3.449"/>
      </svg>
    </a>
  @endif

  @if ($notices->isNotEmpty())
    @php
        // Repeat the set enough times to fill wide screens; the keyframe shifts
        // by exactly one copy so the loop is seamless regardless of item count.
        $copies = 4;
        $shift = '-' . rtrim(rtrim(number_format(100 / $copies, 4, '.', ''), '0'), '.') . '%';
    @endphp
    <div class="notice-marquee" data-notice-marquee>
      <div class="notice-marquee-track" style="--marquee-shift: {{ $shift }}">
        @for ($copy = 0; $copy < $copies; $copy++)
          <div class="notice-marquee-group" @if ($copy > 0) aria-hidden="true" @endif>
            @foreach ($notices as $notice)
              <a class="notice-marquee-item"
                 href="{{ $notice['href'] }}"
                 title="{{ $notice['full'] }}"
                 @if ($copy > 0) tabindex="-1" @endif>
                <span class="notice-marquee-dot" aria-hidden="true"></span>
                <span>{{ $notice['teaser'] }}</span>
              </a>
            @endforeach
          </div>
        @endfor
      </div>
    </div>
  @endif

  @if ($waPhone)
    <a class="notice-whatsapp"
       href="https://wa.me/{{ $waE164 }}"
       target="_blank" rel="noopener"
       aria-label="Chat with us on WhatsApp at {{ $waPhone }}">
      <svg class="notice-whatsapp-icon" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.885-9.885 9.885M20.52 3.449C18.24 1.245 15.24 0 12.045 0 5.463 0 .104 5.359.101 11.946c0 2.096.547 4.142 1.588 5.945L0 24l6.335-1.652a11.882 11.882 0 0 0 5.71 1.448h.006c6.585 0 11.946-5.36 11.949-11.946C24 8.39 22.797 5.652 20.52 3.449"/>
      </svg>
      <span>{{ $waPhone }}</span>
    </a>
  @endif
</div>
