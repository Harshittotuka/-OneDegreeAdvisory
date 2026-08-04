{{-- Home · Hero. Vars: $data, optional $edit (live editor instrumentation).
     One source of truth for both the public page and the live CMS preview. --}}
@php
  use App\Support\HeroContent;

  $d = $data ?? [];
  $edit = $edit ?? false;

  // Instrumentation helpers — emit data-* hooks only in the live editor.
  $ed  = fn ($k) => $edit ? ' data-ed="'.$k.'"' : '';
  $im  = fn ($k, $v) => $edit ? ' data-ed-img="'.$k.'" data-ed-imgval="'.e((string) $v).'" data-ed-bg="1"' : '';
  $ic  = function ($k, $n) use ($edit) {
      $n = (string) $n;
      $i = $n !== '' ? '<i data-lucide="'.e($n).'"></i>' : '';
      return $edit
          ? '<span class="le-ic" data-ed-icon="'.e($k).'" data-ed-iconname="'.e($n).'">'.($i ?: '<i data-lucide="square"></i>').'</span>'
          : $i;
  };

  $bg = $d['background'] ?? '';
  $styleClass = fn ($s) => 'btn '.match ($s) {
      'ghost' => 'btn-ghost',
      'disabled' => 'btn-disabled',
      default => 'btn-orange',
  };

  $hex = fn ($v, $fallback = '') => preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', trim((string) $v)) ? trim((string) $v) : $fallback;
  $styleDefaults = HeroContent::TEXT_STYLE_DEFAULTS;
  $colors = is_array($d['colors'] ?? null) ? $d['colors'] : [];
  $rawStyles = is_array($d['styles'] ?? null) ? $d['styles'] : [];
  $styles = [];
  foreach (HeroContent::TEXT_STYLE_KEYS as $key) {
      $raw = is_array($rawStyles[$key] ?? null) ? $rawStyles[$key] : [];
      $legacy = $hex($colors[$key] ?? '');
      $mode = in_array($raw['mode'] ?? '', HeroContent::TEXT_STYLE_MODES, true)
          ? $raw['mode']
          : ($legacy !== '' ? 'solid' : $styleDefaults[$key]['mode']);
      $solidColor = $hex($raw['color'] ?? '') ?: $legacy;
      if ($mode === 'solid' && $solidColor === '') {
          $mode = 'default';
      }
      $styles[$key] = [
          'mode' => $mode,
          'color' => $solidColor,
          'gradient_start' => $hex($raw['gradient_start'] ?? '', $styleDefaults[$key]['gradient_start']),
          'gradient_end' => $hex($raw['gradient_end'] ?? '', $styleDefaults[$key]['gradient_end']),
          'animation' => in_array($raw['animation'] ?? '', HeroContent::TEXT_ANIMATIONS, true) ? $raw['animation'] : 'theme',
      ];
  }
  $textClasses = function ($key, $base = '') use ($styles) {
      $s = $styles[$key] ?? [];
      $classes = array_filter([$base]);
      if (($s['mode'] ?? 'default') === 'solid') {
          $classes[] = 'he-text-solid';
      } elseif (($s['mode'] ?? 'default') === 'gradient') {
          $classes[] = 'he-text-gradient';
      }
      if (($s['animation'] ?? 'theme') !== 'theme') {
          $classes[] = 'he-anim-'.$s['animation'];
      }
      return implode(' ', $classes);
  };
  $textStyle = function ($key) use ($styles) {
      $s = $styles[$key] ?? [];
      $props = [];
      if (($s['mode'] ?? 'default') === 'solid') {
          $props[] = '--he-text-color:'.e($s['color'] ?? '');
      } elseif (($s['mode'] ?? 'default') === 'gradient') {
          $props[] = '--he-grad-a:'.e($s['gradient_start'] ?? '');
          $props[] = '--he-grad-b:'.e($s['gradient_end'] ?? '');
      }
      return $props ? ' style="'.implode(';', $props).'"' : '';
  };
  $styleButton = fn ($key, $label) => $edit
      ? '<button type="button" class="he-style-trigger he-style-trigger--'.e($key).'" data-he-style-open="'.e($key).'" title="Style '.$label.'" aria-label="Style '.$label.'" contenteditable="false"><i data-lucide="palette"></i><span class="he-style-trigger-swatch"></span></button>'
      : '';
@endphp
@php
  // Background slideshow: one image renders statically (with the original slow
  // drift); two or more cross-cycle via script.js using the chosen animation.
  $slides = [];
  foreach (($d['slides'] ?? []) as $s) { $s = trim((string) $s); if ($s !== '') { $slides[] = $s; } }
  if (empty($slides) && $bg !== '') { $slides = [$bg]; }
  $ss = is_array($d['slideshow'] ?? null) ? $d['slideshow'] : [];
  $ssAnim = in_array($ss['animation'] ?? 'fade', HeroContent::SLIDE_ANIMATIONS, true) ? $ss['animation'] : 'fade';
  $ssInterval = is_numeric($ss['interval'] ?? null) ? max(2, min(30, (float) $ss['interval'])) : HeroContent::SLIDESHOW_DEFAULTS['interval'];
  $ssDuration = is_numeric($ss['duration'] ?? null) ? max(0.2, min(5, (float) $ss['duration'])) : HeroContent::SLIDESHOW_DEFAULTS['duration'];
  $isShow = count($slides) > 1;
@endphp
<section class="hero" id="top" aria-label="One Degree Advisory">
  <div class="hero-slides{{ $isShow ? '' : ' hero-slides--single' }}" aria-hidden="true"
       data-hero-anim="{{ $ssAnim }}"
       style="--hero-dur: {{ $ssDuration }}s; --hero-kb: {{ $ssInterval + $ssDuration }}s;"
       @if($isShow && ! $edit) data-hero-slideshow data-hero-interval="{{ $ssInterval }}" @endif
       @if($edit) data-he-slides @endif>
    @forelse($slides as $i => $url)
      <div class="hero-slide{{ $i === 0 ? ' is-active' : '' }}" style="background-image: url('{{ $url }}');"></div>
    @empty
      <div class="hero-slide is-active"></div>
    @endforelse
  </div>
  <div class="hero-overlay" aria-hidden="true"></div>
  @if($edit)
    <button type="button" class="he-bg-edit" data-he-bg-edit contenteditable="false"><i data-lucide="images"></i> Background &amp; slideshow</button>
  @endif

  <div class="container hero-grid">
    <div class="hero-copy reveal">
      @if($edit || !empty($d['eyebrow']))
        @if($edit)
          <span class="he-text-wrap he-text-wrap--eyebrow">
            <span class="{{ $textClasses('eyebrow', 'eyebrow') }}" data-he-style-part="eyebrow"{!! $ed('eyebrow') !!}{!! $textStyle('eyebrow') !!}>{{ $d['eyebrow'] ?? '' }}</span>
            {!! $styleButton('eyebrow', 'eyebrow') !!}
          </span>
        @else
          <span class="{{ $textClasses('eyebrow', 'eyebrow') }}" data-he-style-part="eyebrow"{!! $textStyle('eyebrow') !!}>{{ $d['eyebrow'] ?? '' }}</span>
        @endif
      @endif

      <h1 class="hero-headline">
        @if($edit)
          <span class="he-text-wrap he-text-wrap--heading">
            <span class="{{ $textClasses('heading', 'he-heading-text') }}" data-he-style-part="heading"{!! $ed('heading_pre') !!}{!! $textStyle('heading') !!}>{{ $d['heading_pre'] ?? '' }}</span>
            {!! $styleButton('heading', 'headline') !!}
          </span>
        @else
          <span class="{{ $textClasses('heading', 'he-heading-text') }}" data-he-style-part="heading"{!! $textStyle('heading') !!}>{{ $d['heading_pre'] ?? '' }}</span>
        @endif
        @if($edit)
          <span class="he-text-wrap he-text-wrap--highlight">
            <span class="{{ $textClasses('highlight', 'gold-text') }}" data-he-style-part="highlight"{!! $ed('heading_highlight') !!}{!! $textStyle('highlight') !!}>{{ $d['heading_highlight'] ?? '' }}</span>
            {!! $styleButton('highlight', 'highlighted words') !!}
          </span>
        @else
          <span class="{{ $textClasses('highlight', 'gold-text') }}" data-he-style-part="highlight"{!! $textStyle('highlight') !!}>{{ $d['heading_highlight'] ?? '' }}</span>
        @endif
        <br />
        <span class="{{ $textClasses('heading', 'he-heading-text') }}" data-he-style-part="heading"{!! $ed('heading_post') !!}{!! $textStyle('heading') !!}>{{ $d['heading_post'] ?? '' }}</span>
      </h1>

      @php
        // Group buttons into stacked rows (responsive: each row flex-wraps).
        $byRow = [];
        foreach ($d['actions'] ?? [] as $a) { $byRow[(int) ($a['row'] ?? 0)][] = $a; }
        ksort($byRow);
        $rows = array_values($byRow);
        if ($edit && empty($rows)) { $rows = [[]]; } // always one editable row
      @endphp
      <div class="hero-actions-stack">
        @foreach($rows as $rowButtons)
          <div class="hero-actions"{{ $edit ? ' data-he-row' : '' }}>
            @foreach($rowButtons as $a)
              @include('partials.home._hero_button', ['a' => $a, 'edit' => $edit])
            @endforeach
          </div>
        @endforeach

        @if($edit)
          <template id="he-action-tpl"><a class="btn btn-orange" href="#" data-ed-item data-he-action data-he-style="orange" data-he-href=""><script type="application/json" class="le-extra">{"label":"New button","icon":"arrow-right","href":"","style":"orange"}</script><span class="le-ic" data-ed-icon="icon" data-ed-iconname="arrow-right"><i data-lucide="arrow-right"></i></span><span data-ed="label">New button</span></a></template>
          <div class="he-add-controls" contenteditable="false">
            <button type="button" class="he-add-btn" data-he-add><i data-lucide="plus"></i> Add button</button>
            <button type="button" class="he-add-btn" data-he-add-row><i data-lucide="rows-3"></i> Add row</button>
          </div>
        @endif
      </div>
    </div>
  </div>

  @include('partials.home.hero-hub')
</section>
