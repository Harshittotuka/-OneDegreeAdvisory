@php
    /**
     * A country guide's section paragraph.
     *
     * Guides that have been rewritten by hand (resources/data/country-guides)
     * show the short line the page has always shown, and reveal the full
     * written paragraph on hover — asked for so the pages keep their original
     * compact shape.
     *
     * Three things this has to get right, because a plain :hover would get
     * them wrong:
     *   • Touch devices have no hover at all, and are about half the traffic,
     *     so script.js toggles .is-open on tap (see "country prose" there).
     *   • Keyboard users get the same via :focus-within on the tabbable <p>.
     *   • The full text is collapsed by giving its row no height, never
     *     display:none, so it stays in the page for search engines and AI
     *     answers to read. That is what the inner .country-prose-text span is
     *     for: a grid row can animate from 0fr to 1fr, which is the element's
     *     own height, and the child clips itself while the row is short.
     *
     * A guide with no written copy has nothing to reveal and renders exactly
     * as it always did.
     *
     * @var array  $section  one entry of $studyContent['sectionCopy']
     * @var int    $limit    character budget for the scraped short line
     */
    $written = trim((string) ($section['section_body_clean'] ?? ''));
    $short = $plainBody($section['section_body'] ?? '', $limit ?? 190);

    // The courses section has no usable scraped sentence (see the note in
    // destination.blade), so open on the written copy's own first sentence
    // rather than on nothing at all.
    if ($written !== '' && $short === '') {
        $short = rtrim(\Illuminate\Support\Str::before($written, '. '), '.').'.';
    }
@endphp

@if ($written === '')
    @if ($short !== '')
        <p>{{ $short }}</p>
    @endif
@else
    <p class="country-prose" data-country-prose tabindex="0"
       role="button" aria-expanded="false"
       aria-label="{{ $short }} — show more">
        <span class="country-prose-short">{{ $short }}</span>
        <span class="country-prose-full"><span class="country-prose-text">{{ $written }}</span></span>
    </p>
@endif
