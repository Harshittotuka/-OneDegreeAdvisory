{{-- About · Vision & Mission cards. Vars: $data, optional $edit --}}
@php
  $d = $data; $edit = $edit ?? false;
  $ed  = fn ($k) => $edit ? ' data-ed="'.$k.'"' : '';
  $ic  = function ($k, $n) use ($edit) { $n = (string) $n; $i = $n !== '' ? '<i data-lucide="'.e($n).'"></i>' : ''; return $edit ? '<span class="le-ic" data-ed-icon="'.e($k).'" data-ed-iconname="'.e($n).'">'.($i ?: '<i data-lucide="square"></i>').'</span>' : $i; };
  $rep = fn ($k) => $edit ? ' data-ed-rep="'.$k.'"' : '';
  $it  = $edit ? ' data-ed-item' : '';
  $itx = fn ($arr) => $edit ? '<script type="application/json" class="le-extra">'.json_encode($arr).'</script>' : '';
@endphp
<section class="va-vm" aria-labelledby="va-vm-title">
  <div class="container">
    <header class="va-vm-head">
      @if($edit || !empty($d['eyebrow']))<span class="va-eyebrow"{!! $ed('eyebrow') !!}>{{ $d['eyebrow'] ?? '' }}</span>@endif
      @if($edit || !empty($d['heading']))<h2 id="va-vm-title"{!! $ed('heading') !!}>{{ $d['heading'] ?? '' }}</h2>@endif
    </header>

    <div class="va-vm-grid"{!! $rep('cards') !!}>
      @foreach($d['cards'] ?? [] as $c)
        @php($accent = $c['accent'] ?? '')
        <article class="va-vm-card @if($accent === 'vision') va-vm-card--vision @elseif($accent === 'mission') va-vm-card--mission @endif"{!! $it !!}>{!! $itx($c) !!}
          @if($edit || !empty($c['icon']))<span class="va-vm-icon" aria-hidden="true">{!! $ic('icon', $c['icon'] ?? '') !!}</span>@endif
          @if($edit || !empty($c['tag']))<span class="va-vm-tag"{!! $ed('tag') !!}>{{ $c['tag'] ?? '' }}</span>@endif
          @if($edit || !empty($c['heading']))<h3{!! $ed('heading') !!}>{{ $c['heading'] ?? '' }}</h3>@endif
          @if($edit || !empty($c['body']))<p{!! $ed('body') !!}>{{ $c['body'] ?? '' }}</p>@endif
        </article>
      @endforeach
    </div>
  </div>
</section>
