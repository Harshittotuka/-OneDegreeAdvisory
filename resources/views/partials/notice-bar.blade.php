{{--
  Top blue notice bar.

  Layout: social icons pinned left · scrolling announcement marquee in the
  middle · WhatsApp number pinned right. Content + behaviour are managed in the
  CMS (/admin/notice-bar, backed by App\Support\NoticeBarStore):
    • the announcement list (text + optional link),
    • how many words of each item show as a teaser (word_count; 0 = full text),
    • the scroll speed (seconds per loop, fed to --marquee-duration),
    • the display variant (original / minimal / compact) — applied as the
      html.topbar-* class in the layout.
  An item with no link renders as plain, non-clickable text.
--}}
@php
    $bar = app(\App\Support\NoticeBarStore::class)->get();
    $wordCount = (int) ($bar['word_count'] ?? 5);

    $toTeaser = static function (string $text) use ($wordCount): string {
        $text = trim($text);
        if ($wordCount <= 0) {
            return $text;
        }

        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $teaser = implode(' ', array_slice($words, 0, $wordCount));
        if (count($words) > $wordCount) {
            $teaser .= ' .....';
        }

        return $teaser;
    };

    $notices = collect($bar['items'] ?? [])
        ->filter(fn ($item) => ($item['visible'] ?? true) && trim((string) ($item['text'] ?? '')) !== '')
        ->map(fn ($item) => [
            'teaser' => $toTeaser((string) $item['text']),
            'full'   => trim((string) $item['text']),
            'href'   => trim((string) ($item['href'] ?? '')),
        ])
        ->values();

    $marqueeSpeed = (int) ($bar['speed'] ?? 14);

    // Text appearance — editable in the CMS (/admin/notice-bar).
    $marqueeColor  = (string) ($bar['text_color'] ?? '#ff5e32');
    $marqueeStyle  = (($bar['font_style'] ?? 'normal') === 'italic') ? 'italic' : 'normal';
    $marqueeWeight = ! empty($bar['bold']) ? '700' : '500';

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
      <div class="notice-marquee-track" style="--marquee-shift: {{ $shift }}; --marquee-duration: {{ $marqueeSpeed }}s; --notice-text-color: {{ $marqueeColor }}; --notice-font-style: {{ $marqueeStyle }}; --notice-font-weight: {{ $marqueeWeight }};">
        @for ($copy = 0; $copy < $copies; $copy++)
          <div class="notice-marquee-group" @if ($copy > 0) aria-hidden="true" @endif>
            @foreach ($notices as $notice)
              @if ($notice['href'] !== '')
                <a class="notice-marquee-item"
                   href="{{ $notice['href'] }}"
                   target="_blank" rel="noopener"
                   title="{{ $notice['full'] }}"
                   @if ($copy > 0) tabindex="-1" @endif>
                  <span class="notice-marquee-dot" aria-hidden="true"></span>
                  <span>{{ $notice['teaser'] }}</span>
                </a>
              @else
                {{-- No link configured → plain, non-clickable text. --}}
                <span class="notice-marquee-item notice-marquee-item--static" title="{{ $notice['full'] }}">
                  <span class="notice-marquee-dot" aria-hidden="true"></span>
                  <span>{{ $notice['teaser'] }}</span>
                </span>
              @endif
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
