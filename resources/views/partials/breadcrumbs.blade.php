{{-- Breadcrumb trail plus the BreadcrumbList built from the same list
     (App\Support\Breadcrumbs). Vars: $trail — Home first, as ['name', 'url']
     pairs; renders nothing when empty. The BreadcrumbList is always emitted;
     the visible strip under the header only when config('site.show_breadcrumbs')
     is on. Its styles live with the header in stripe-nav.css. --}}
@if(! empty($trail))
  @if(config('site.show_breadcrumbs'))
    <nav class="site-crumbs" aria-label="Breadcrumb">
      <ol class="site-crumbs__list">
        @foreach($trail as $crumb)
          @if($loop->last)
            <li class="site-crumbs__item is-current" aria-current="page"><span>{{ $crumb['name'] }}</span></li>
          @else
            <li class="site-crumbs__item"><a href="{{ $crumb['url'] }}">{{ $crumb['name'] }}</a></li>
          @endif
        @endforeach
      </ol>
    </nav>
  @endif
  <script type="application/ld+json">
  {!! \App\Support\Breadcrumbs::jsonLd($trail) !!}
  </script>
@endif
