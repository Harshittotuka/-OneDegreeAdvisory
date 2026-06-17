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
    $itemGap      = (int) ($bar['item_gap'] ?? 64);

    // Text appearance — editable in the CMS (/admin/notice-bar).
    $marqueeColor  = (string) ($bar['text_color'] ?? '#ff5e32');
    $marqueeStyle  = (($bar['font_style'] ?? 'normal') === 'italic') ? 'italic' : 'normal';
    $marqueeWeight = ! empty($bar['bold']) ? '700' : '500';

    $waPhone = config('site.contact.phone');
    $waE164  = config('site.contact.phone_e164');

    // Redesigned display styles: left-socials · no-socials · static-notice ·
    // left-socials-cycle (phone fades the icons one-by-one).
    $variant    = (string) ($bar['variant'] ?? 'left-socials');
    $staticText = trim((string) ($bar['static_text'] ?? ''));

    // Socials (incl. WhatsApp) sit on the left for every style except "no-socials",
    // which shows only the scrolling notices — no icons, no WhatsApp.
    $showSocials = $variant !== 'no-socials';
@endphp

<div class="notice">
  @if ($showSocials)
    @include('partials.socials', ['variant' => 'notice', 'withWhatsapp' => true])
  @endif

  @if ($variant === 'static-notice')
    @if ($staticText !== '')
      {{-- Single, centred, non-scrolling announcement. HTML is sanitised in the store. --}}
      <div class="notice-static" style="--notice-text-color: {{ $marqueeColor }}; --notice-font-style: {{ $marqueeStyle }}; --notice-font-weight: {{ $marqueeWeight }};">
        <span class="notice-static-text">{!! $staticText !!}</span>
      </div>
    @endif
  @elseif ($notices->isNotEmpty())
    @php
        // Repeat the set enough times to fill wide screens; the keyframe shifts
        // by exactly one copy so the loop is seamless regardless of item count.
        $copies = 4;
        $shift = '-' . rtrim(rtrim(number_format(100 / $copies, 4, '.', ''), '0'), '.') . '%';
    @endphp
    <div class="notice-marquee" data-notice-marquee>
      <div class="notice-marquee-track" style="--marquee-shift: {{ $shift }}; --marquee-duration: {{ $marqueeSpeed }}s; --notice-gap: {{ $itemGap }}px; --notice-text-color: {{ $marqueeColor }}; --notice-font-style: {{ $marqueeStyle }}; --notice-font-weight: {{ $marqueeWeight }};">
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
</div>
