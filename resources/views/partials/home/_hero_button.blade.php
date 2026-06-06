{{-- One hero action button (flow layout). Vars: $a (button data), $edit. --}}
@php
  use App\Support\HeroContent;
  $edit = $edit ?? false;
  $style = in_array($a['style'] ?? '', HeroContent::STYLES, true) ? $a['style'] : 'orange';
  $cls = 'btn '.match ($style) { 'ghost' => 'btn-ghost', 'disabled' => 'btn-disabled', default => 'btn-orange' };
  $href = $a['href'] ?? '';
  $icHtml = function ($n) use ($edit) {
      $n = (string) $n;
      $i = $n !== '' ? '<i data-lucide="'.e($n).'"></i>' : '';
      return $edit
          ? '<span class="le-ic" data-ed-icon="icon" data-ed-iconname="'.e($n).'">'.($i ?: '<i data-lucide="square"></i>').'</span>'
          : $i;
  };
@endphp
@if($edit)
  <a class="{{ $cls }}" href="{{ $href ?: '#' }}" data-ed-item data-he-action data-he-style="{{ $style }}" data-he-href="{{ $href }}"><script type="application/json" class="le-extra">{!! json_encode($a) !!}</script>{!! $icHtml($a['icon'] ?? '') !!}<span data-ed="label">{{ $a['label'] ?? '' }}</span></a>
@elseif($style === 'disabled')
  <span class="{{ $cls }}" aria-disabled="true">{!! $icHtml($a['icon'] ?? '') !!}<span>{{ $a['label'] ?? '' }}</span></span>
@else
  <a class="{{ $cls }}" href="{{ $href ?: '#' }}">{!! $icHtml($a['icon'] ?? '') !!}<span>{{ $a['label'] ?? '' }}</span></a>
@endif
