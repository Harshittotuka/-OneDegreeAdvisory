@php
    use App\Support\StudyLocationContent;

    $blkStyle = $blkStyle ?? '';
    $items = array_filter($data['items'] ?? [], fn ($i) => trim($i['name'] ?? '') !== '');

    // Build a case-insensitive name → visible destination slug map so each flag
    // links to its own country page when one is published. Destinations without a
    // dedicated page (e.g. Latvia, Lithuania, "More Destinations") fall back to the
    // main Europe page. An explicit `href` on an item always wins.
    $destLinks = [];
    foreach (app(StudyLocationContent::class)->destinations(true) as $dest) {
        $key = strtolower(trim((string) ($dest['name'] ?? '')));
        if ($key !== '' && ($dest['slug'] ?? '') !== '') {
            $destLinks[$key] = $dest['slug'];
        }
    }

    $fallbackHref = url('/europe');
    $destHref = function (array $item) use ($destLinks, $fallbackHref) {
        $explicit = trim((string) ($item['href'] ?? ''));
        if ($explicit !== '') {
            return str_starts_with($explicit, '/') ? url($explicit) : $explicit;
        }

        $slug = $destLinks[strtolower(trim((string) ($item['name'] ?? '')))] ?? null;

        return $slug ? route('country.show', $slug) : $fallbackHref;
    };
@endphp
<section class="odp-file-surface odp-dest-strip" @if($blkStyle) style="{{ $blkStyle }}" @endif>
  <div class="odp-dest-topbar">
    <a class="odp-dest-back" href="{{ url('/') }}" onclick="if(document.referrer&&history.length>1){history.back();return false;}">
      <span class="odp-dest-back-arrow" aria-hidden="true">&larr;</span> Back
    </a>
  </div>
  @if(!empty($data['label']))<h2 class="odp-dest-label">{{ $data['label'] }}</h2>@endif
  <div class="odp-dest-flags">
    @foreach($items as $it)
      <a class="odp-dest-item" href="{{ $destHref($it) }}" aria-label="Explore study options in {{ $it['name'] }}">
        <span class="odp-dest-flag">
          @if(!empty($it['code']))
            <img src="https://flagcdn.com/{{ strtolower(trim($it['code'])) }}.svg" alt="{{ $it['name'] }} flag" width="36" height="27" loading="lazy">
          @else
            🌐
          @endif
        </span>
        <span class="odp-dest-name">{{ $it['name'] }}</span>
      </a>
    @endforeach
  </div>
</section>
