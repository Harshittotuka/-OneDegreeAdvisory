@php
    $d = is_array($data ?? null) ? $data : [];
    $blkStyle = '';
    if (! empty($d['accent']))  { $blkStyle .= '--file-orange:'.e($d['accent']).';'; }
    if (! empty($d['accent2'])) { $blkStyle .= '--file-blue:'.e($d['accent2']).';'; }
    $sf = $d['surface'] ?? '';
    $blkSurface = $sf === 'card' ? 'odp-file-surface odp-surface-pad'
                : ($sf === 'tint' ? 'odp-blk--tint'
                : ($sf === 'gradient' ? 'odp-blk--gradient' : ''));
@endphp
@includeIf('partials.brief.blocks.'.$type, [
  'data' => $d,
  'blkStyle' => $blkStyle,
  'blkSurface' => $blkSurface,
  'blockId' => 'cms-preview',
  'pageSlug' => '',
  'cmsPreview' => true,
])
