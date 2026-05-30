{{-- About · Pillars (text + image rows). Vars: $data, optional $edit --}}
@php
  $d = $data; $edit = $edit ?? false;
  $ed  = fn ($k) => $edit ? ' data-ed="'.$k.'"' : '';
  $im  = fn ($k, $v, $bg = false) => $edit ? ' data-ed-img="'.$k.'" data-ed-imgval="'.e((string) $v).'"'.($bg ? ' data-ed-bg="1"' : '') : '';
  $ic  = function ($k, $n) use ($edit) { $n = (string) $n; $i = $n !== '' ? '<i data-lucide="'.e($n).'"></i>' : ''; return $edit ? '<span class="le-ic" data-ed-icon="'.e($k).'" data-ed-iconname="'.e($n).'">'.($i ?: '<i data-lucide="square"></i>').'</span>' : $i; };
  $rep = fn ($k) => $edit ? ' data-ed-rep="'.$k.'"' : '';
  $it  = $edit ? ' data-ed-item' : '';
  $itx = fn ($arr) => $edit ? '<script type="application/json" class="le-extra">'.json_encode($arr).'</script>' : '';
  $tw  = fn ($k, $v) => $edit ? '<span data-ed="'.$k.'">'.e((string) $v).'</span>' : e((string) $v);
@endphp
<section class="va-pillars" aria-label="Who we are, why we do it, what we do">
  <div class="container"{!! $rep('items') !!}>
    @foreach($d['items'] ?? [] as $p)
      <article class="va-pillar va-pillar--row @if(!empty($p['reverse'])) va-pillar--reverse @endif"@if(!empty($p['anchor'])) id="{{ $p['anchor'] }}"@endif{!! $it !!}>{!! $itx($p) !!}
        <div class="va-pillar-copy">
          @if($edit || !empty($p['eyebrow']))<span class="va-eyebrow"{!! $ed('eyebrow') !!}>{{ $p['eyebrow'] ?? '' }}</span>@endif
          @if($edit || !empty($p['heading']))<h2{!! $ed('heading') !!}>{{ $p['heading'] ?? '' }}</h2>@endif
          @if($edit || !empty($p['body']))<p{!! $ed('body') !!}>{{ $p['body'] ?? '' }}</p>@endif
          @if($edit || !empty($p['chips']))
            <ul class="va-chips"{!! $rep('chips') !!}>
              @foreach($p['chips'] ?? [] as $chip)
                <li{!! $it !!}>{!! $itx($chip) !!}@if($edit || !empty($chip['icon'])){!! $ic('icon', $chip['icon'] ?? '') !!}@endif {!! $tw('label', $chip['label'] ?? '') !!}</li>
              @endforeach
            </ul>
          @endif
        </div>
        <figure class="va-pillar-media">
          @if($edit || !empty($p['image']))<img{!! $im('image', $p['image'] ?? '') !!} src="{{ $p['image'] ?? '' }}" alt="{{ $p['image_alt'] ?? '' }}">@endif
          @if($edit || !empty($p['tag_label']) || !empty($p['tag_icon']))
            <span class="va-pillar-tag">@if($edit || !empty($p['tag_icon'])){!! $ic('tag_icon', $p['tag_icon'] ?? '') !!}@endif {!! $tw('tag_label', $p['tag_label'] ?? '') !!}</span>
          @endif
        </figure>
      </article>
    @endforeach
  </div>
</section>
