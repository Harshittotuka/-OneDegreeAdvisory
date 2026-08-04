{{-- The CRM's own paginator markup.

     Laravel's default view is Tailwind-classed and ships the controls twice: a
     narrow-screen half carrying a bare `hidden` class, and a wide-screen half.
     The CRM defines `.hidden{display:none!important}` for its own popovers, so
     that utility force-hid the wide half, while the CRM's stylesheet hid the
     narrow one — leaving an empty strip and no way off page 1. Owning the markup
     ends that collision for good, and drops the framework's "Showing x to y of
     z results" line, which the count above every list already states. --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Pagination Navigation">
        @if ($paginator->onFirstPage())
            <span aria-disabled="true" aria-label="Previous page"><span aria-hidden="true">&lsaquo;</span></span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">&lsaquo;</a>
        @endif

        @foreach ($elements as $element)
            {{-- The "..." separator between distant page runs. --}}
            @if (is_string($element))
                <span aria-disabled="true"><span>{{ $element }}</span></span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span aria-current="page"><span>{{ $page }}</span></span>
                    @else
                        <a href="{{ $url }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">&rsaquo;</a>
        @else
            <span aria-disabled="true" aria-label="Next page"><span aria-hidden="true">&rsaquo;</span></span>
        @endif
    </nav>
@endif
