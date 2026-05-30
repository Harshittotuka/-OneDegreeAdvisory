{{-- About · Call to action. Vars: $data, optional $edit --}}
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
<section class="va-cta" aria-labelledby="va-cta-title">
  <div class="container va-cta-grid">
    <div class="va-cta-copy">
      @if($edit || !empty($d['eyebrow']))<span class="va-eyebrow va-eyebrow--light"{!! $ed('eyebrow') !!}>{{ $d['eyebrow'] ?? '' }}</span>@endif
      @if($edit || !empty($d['heading']))<h2 id="va-cta-title"{!! $ed('heading') !!}>{{ $d['heading'] ?? '' }}</h2>@endif
      @if($edit || !empty($d['body']))<p{!! $ed('body') !!}>{{ $d['body'] ?? '' }}</p>@endif

      @if($edit || !empty($d['actions']))
        <div class="va-cta-actions"{!! $rep('actions') !!}>
          @foreach($d['actions'] ?? [] as $a)
            @php($primary = ($a['style'] ?? 'primary') === 'primary')
            @php($aIcon = ($edit || !empty($a['icon'])) ? $ic('icon', $a['icon'] ?? '') : '')
            <a class="btn {{ $primary ? 'btn-primary' : 'btn-ghost va-cta-ghost' }}" href="{{ $a['href'] ?? '#' }}"{!! $it !!}>{!! $itx($a) !!}@if($primary)<span{!! $ed('label') !!}>{{ $a['label'] ?? '' }}</span>{!! $aIcon !!}@else{!! $aIcon !!}<span{!! $ed('label') !!}>{{ $a['label'] ?? '' }}</span>@endif</a>
          @endforeach
        </div>
      @endif

      @if($edit || !empty($d['tags']))
        <ul class="va-cta-tags"{!! $rep('tags') !!}>
          @foreach($d['tags'] ?? [] as $t)
            <li{!! $it !!}>{!! $itx($t) !!}@if($edit || !empty($t['icon'])){!! $ic('icon', $t['icon'] ?? '') !!}@endif {!! $tw('label', $t['label'] ?? '') !!}</li>
          @endforeach
        </ul>
      @endif
    </div>

    <figure class="va-cta-media" aria-hidden="true">
      @if($edit || !empty($d['image']))<img{!! $im('image', $d['image'] ?? '') !!} src="{{ $d['image'] ?? '' }}" alt="{{ $d['image_alt'] ?? '' }}">@endif
      @if($edit || !empty($d['stat_num']) || !empty($d['stat_label']))
        <div class="va-cta-card">
          @if($edit || !empty($d['stat_num']))<span class="va-cta-card-num"{!! $ed('stat_num') !!}>{{ $d['stat_num'] ?? '' }}</span>@endif
          @if($edit || !empty($d['stat_label']))<span class="va-cta-card-label"{!! $ed('stat_label') !!}>{{ $d['stat_label'] ?? '' }}</span>@endif
        </div>
      @endif
    </figure>
  </div>
</section>
