{{-- About · Hero. Vars: $data, optional $edit (live editor instrumentation) --}}
@php
  $d = $data; $edit = $edit ?? false;
  $ed  = fn ($k) => $edit ? ' data-ed="'.$k.'"' : '';
  $im  = fn ($k, $v, $bg = false) => $edit ? ' data-ed-img="'.$k.'" data-ed-imgval="'.e((string) $v).'"'.($bg ? ' data-ed-bg="1"' : '') : '';
  $ic  = function ($k, $n) use ($edit) { $n = (string) $n; $i = $n !== '' ? '<i data-lucide="'.e($n).'"></i>' : ''; return $edit ? '<span class="le-ic" data-ed-icon="'.e($k).'" data-ed-iconname="'.e($n).'">'.($i ?: '<i data-lucide="square"></i>').'</span>' : $i; };
  $rep = fn ($k) => $edit ? ' data-ed-rep="'.$k.'"' : '';
  $it  = $edit ? ' data-ed-item' : '';
  $itx = fn ($arr) => $edit ? '<script type="application/json" class="le-extra">'.json_encode($arr).'</script>' : '';
  $tn  = fn ($k) => $edit ? '<span data-ed="'.$k.'">'.e($d[$k] ?? '').'</span>' : e($d[$k] ?? '');
@endphp
<section class="va-hero" id="top" aria-labelledby="va-hero-title">
  <div class="container va-hero-grid">
    <div class="va-hero-copy">
      @if($edit || !empty($d['eyebrow']))<span class="va-eyebrow"{!! $ed('eyebrow') !!}>{{ $d['eyebrow'] ?? '' }}</span>@endif
      <h1 id="va-hero-title">{!! $tn('heading_pre') !!}@if($edit || !empty($d['heading_highlight']))<span class="va-hero-num"{!! $ed('heading_highlight') !!}>{{ $d['heading_highlight'] ?? '' }}</span>@endif{!! $tn('heading_mid') !!}@if($edit || !empty($d['heading_em']))<em{!! $ed('heading_em') !!}>{{ $d['heading_em'] ?? '' }}</em>@endif</h1>
      @if($edit || !empty($d['lede']))<p class="va-hero-lede"{!! $ed('lede') !!}>{{ $d['lede'] ?? '' }}</p>@endif

      @if($edit || !empty($d['actions']))
        <div class="va-hero-actions"{!! $rep('actions') !!}>
          @foreach($d['actions'] ?? [] as $a)
            @php($primary = ($a['style'] ?? 'primary') === 'primary')
            @php($aIcon = ($edit || !empty($a['icon'])) ? $ic('icon', $a['icon'] ?? '') : '')
            <a class="btn {{ $primary ? 'btn-primary' : 'btn-ghost' }}" href="{{ $a['href'] ?? '#' }}"{!! $it !!}>{!! $itx($a) !!}@if($primary)<span{!! $ed('label') !!}>{{ $a['label'] ?? '' }}</span>{!! $aIcon !!}@else{!! $aIcon !!}<span{!! $ed('label') !!}>{{ $a['label'] ?? '' }}</span>@endif</a>
          @endforeach
        </div>
      @endif

      @if($edit || !empty($d['metrics']))
        <div class="va-hero-trust"{!! $rep('metrics') !!}>
          @foreach($d['metrics'] ?? [] as $m)
            <div{!! $it !!}>{!! $itx($m) !!}<strong{!! $ed('value') !!}>{{ $m['value'] ?? '' }}</strong><span{!! $ed('label') !!}>{{ $m['label'] ?? '' }}</span></div>
          @endforeach
        </div>
      @endif
    </div>

    <aside class="va-hero-collage" aria-hidden="true">
      @if($edit || !empty($d['photo_lg']))<div class="va-hero-photo va-hero-photo--lg" style="background-image: url('{{ $d['photo_lg'] ?? '' }}');"{!! $im('photo_lg', $d['photo_lg'] ?? '', true) !!}></div>@endif
      @if($edit || !empty($d['photo_sm']))<div class="va-hero-photo va-hero-photo--sm" style="background-image: url('{{ $d['photo_sm'] ?? '' }}');"{!! $im('photo_sm', $d['photo_sm'] ?? '', true) !!}></div>@endif
      @if($edit || !empty($d['badge_title']) || !empty($d['badge_icon']) || !empty($d['badge_subtitle']))
        <div class="va-hero-badge">
          @if($edit || !empty($d['badge_icon'])){!! $ic('badge_icon', $d['badge_icon'] ?? '') !!}@endif
          <div>
            <strong{!! $ed('badge_title') !!}>{{ $d['badge_title'] ?? '' }}</strong>
            <span{!! $ed('badge_subtitle') !!}>{{ $d['badge_subtitle'] ?? '' }}</span>
          </div>
        </div>
      @endif
    </aside>
  </div>
</section>
