{{-- About · Team / Founders. Vars: $data, optional $edit --}}
@php
  $d = $data; $edit = $edit ?? false;
  $ed  = fn ($k) => $edit ? ' data-ed="'.$k.'"' : '';
  $im  = fn ($k, $v, $bg = false) => $edit ? ' data-ed-img="'.$k.'" data-ed-imgval="'.e((string) $v).'"'.($bg ? ' data-ed-bg="1"' : '') : '';
  $rep = fn ($k) => $edit ? ' data-ed-rep="'.$k.'"' : '';
  $it  = $edit ? ' data-ed-item' : '';
  $itx = fn ($arr) => $edit ? '<script type="application/json" class="le-extra">'.json_encode($arr).'</script>' : '';
@endphp
<section class="va-team"@if(!empty($d['anchor'])) id="{{ $d['anchor'] }}"@endif aria-labelledby="va-founders-title">
  <div class="container">
    <header class="va-team-head">
      @if($edit || !empty($d['eyebrow']))<span class="va-eyebrow"{!! $ed('eyebrow') !!}>{{ $d['eyebrow'] ?? '' }}</span>@endif
      @if($edit || !empty($d['heading']))<h2 id="va-founders-title"{!! $ed('heading') !!}>{{ $d['heading'] ?? '' }}</h2>@endif
      @if($edit || !empty($d['intro']))<p{!! $ed('intro') !!}>{{ $d['intro'] ?? '' }}</p>@endif
    </header>

    <div class="va-team-grid va-team-grid--3"{!! $rep('members') !!}>
      @foreach($d['members'] ?? [] as $m)
        <article class="va-team-card"{!! $it !!}>{!! $itx($m) !!}
          @if($edit || !empty($m['photo']))<div class="va-team-photo" style="background-image: url('{{ $m['photo'] ?? '' }}');"{!! $im('photo', $m['photo'] ?? '', true) !!}></div>@endif
          <div class="va-team-body">
            @if($edit || !empty($m['name']))<h3{!! $ed('name') !!}>{{ $m['name'] ?? '' }}</h3>@endif
            @if($edit || !empty($m['role']))<span class="va-team-role"{!! $ed('role') !!}>{{ $m['role'] ?? '' }}</span>@endif
            @if($edit || !empty($m['bio']))<p{!! $ed('bio') !!}>{{ $m['bio'] ?? '' }}</p>@endif
            <div class="va-team-meta">
              @if($edit || !empty($m['desk']))<span class="va-team-desk"{!! $ed('desk') !!}>{{ $m['desk'] ?? '' }}</span>@endif
              @php $handUrl = trim((string) ($m['linkedin'] ?? '')); $hasHandUrl = $handUrl !== '' && $handUrl !== '#'; @endphp
              @if($edit || $hasHandUrl || !empty($m['hand_text']))
                <a class="va-team-social"@if($hasHandUrl) href="{{ $handUrl }}"@endif
                   @if(!empty($m['hand_text'])) data-tip="{{ $m['hand_text'] }}"@endif
                   aria-label="{{ !empty($m['hand_text']) ? $m['hand_text'] : 'Say hi to '.($m['name'] ?? 'this partner') }}"{!! $edit ? ' data-ed-hand' : '' !!}>
                  <i data-lucide="hand" class="va-team-wave"></i>
                </a>
              @endif
            </div>
          </div>
        </article>
      @endforeach
    </div>
  </div>
</section>
