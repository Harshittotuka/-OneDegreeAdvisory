{{-- About · Impact stats. Vars: $data, optional $edit --}}
@php
  $d = $data; $edit = $edit ?? false;
  $ed  = fn ($k) => $edit ? ' data-ed="'.$k.'"' : '';
  $ic  = function ($k, $n) use ($edit) { $n = (string) $n; $i = $n !== '' ? '<i data-lucide="'.e($n).'"></i>' : ''; return $edit ? '<span class="le-ic" data-ed-icon="'.e($k).'" data-ed-iconname="'.e($n).'">'.($i ?: '<i data-lucide="square"></i>').'</span>' : $i; };
  $rep = fn ($k) => $edit ? ' data-ed-rep="'.$k.'"' : '';
  $it  = $edit ? ' data-ed-item' : '';
  $itx = fn ($arr) => $edit ? '<script type="application/json" class="le-extra">'.json_encode($arr).'</script>' : '';
  $tw  = fn ($k, $v) => $edit ? '<span data-ed="'.$k.'">'.e((string) $v).'</span>' : e((string) $v);
@endphp
<section class="va-impact" aria-labelledby="va-impact-title">
  <div class="container">
    <header class="va-impact-head">
      @if($edit || !empty($d['eyebrow']))<span class="va-eyebrow va-eyebrow--light"{!! $ed('eyebrow') !!}>{{ $d['eyebrow'] ?? '' }}</span>@endif
      @if($edit || !empty($d['heading']))<h2 id="va-impact-title"{!! $ed('heading') !!}>{{ $d['heading'] ?? '' }}</h2>@endif
      @if($edit || !empty($d['intro']))<p{!! $ed('intro') !!}>{{ $d['intro'] ?? '' }}</p>@endif
    </header>

    <div class="va-impact-grid"{!! $rep('stats') !!}>
      @foreach($d['stats'] ?? [] as $s)
        <article class="va-impact-card"{!! $it !!}>{!! $itx($s) !!}
          @if($edit || !empty($s['icon']))<span class="va-impact-icon" aria-hidden="true">{!! $ic('icon', $s['icon'] ?? '') !!}</span>@endif
          <strong>{!! $tw('value', $s['value'] ?? '') !!}@if($edit || !empty($s['suffix']))<small{!! $ed('suffix') !!}>{{ $s['suffix'] ?? '' }}</small>@endif</strong>
          <span{!! $ed('label') !!}>{{ $s['label'] ?? '' }}</span>
        </article>
      @endforeach
    </div>
  </div>
</section>
