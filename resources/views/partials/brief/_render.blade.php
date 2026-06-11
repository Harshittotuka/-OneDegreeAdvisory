{{-- Shared brief-page renderer. Walks the grid layout (rows → columns → blocks)
     and dispatches each block to its partial, computing per-block appearance
     (accent CSS vars + surface class). Falls back to a flat `sections` list by
     wrapping each block in a full-width row. Used by the public page, the studio
     canvas and the preview. --}}
@php
    $rows = $layout ?? null;
    if (! is_array($rows)) {
        // Legacy flat list → one full-width column per block.
        $rows = [];
        foreach (($sections ?? []) as $s) {
            $rows[] = ['cols' => [['span' => 12, 'blocks' => [$s]]]];
        }
    }
@endphp

@foreach($rows as $row)
  @php
    $cols = $row['cols'] ?? [];
    // Width: explicit 'full'/'' wins; legacy rows (no key) holding only AI/embed
    // blocks default to full-bleed, since AI sections are designed edge-to-edge.
    $w = $row['width'] ?? null;
    if ($w === null) {
        $any = false; $allEmbed = true;
        foreach ($cols as $c) {
            foreach (($c['blocks'] ?? []) as $b) {
                $any = true;
                if (($b['type'] ?? '') !== 'embed') { $allEmbed = false; }
            }
        }
        $w = ($any && $allEmbed) ? 'full' : '';
    }
  @endphp
  @continue(empty($cols))
  <div class="odp-row {{ $w === 'full' ? 'odp-row--full' : '' }}">
    @foreach($cols as $col)
      <div class="odp-col" style="--span: {{ (int) ($col['span'] ?? 12) }}">
        @foreach(($col['blocks'] ?? []) as $s)
          @continue(! ($s['visible'] ?? true))
          @php
            $type = $s['type'] ?? '';
            $d = is_array($s['data'] ?? null) ? $s['data'] : [];
            $blkStyle = '';
            if (! empty($d['accent']))  { $blkStyle .= '--file-orange:'.e($d['accent']).';'; }
            if (! empty($d['accent2'])) { $blkStyle .= '--file-blue:'.e($d['accent2']).';'; }
            $sf = $d['surface'] ?? '';
            $blkSurface = $sf === 'card' ? 'odp-file-surface odp-surface-pad'
                        : ($sf === 'tint' ? 'odp-blk--tint'
                        : ($sf === 'gradient' ? 'odp-blk--gradient' : ''));
          @endphp
          @includeIf('partials.brief.blocks.'.$type, ['data' => $d, 'blkStyle' => $blkStyle, 'blkSurface' => $blkSurface])
        @endforeach
      </div>
    @endforeach
  </div>
@endforeach
