@php
    $layout = $page['layout'] ?? [];
    // Collect every block so we can emit its settings form once into #st-forms.
    $allBlocks = [];
    foreach ($layout as $row) {
        foreach (($row['cols'] ?? []) as $col) {
            foreach (($col['blocks'] ?? []) as $b) {
                if (isset($types[$b['type'] ?? ''])) {
                    $allBlocks[] = $b;
                }
            }
        }
    }
@endphp

<div class="odp-file-page st-canvas-page">
  <div class="odp-file-container">
    <div class="st-lock" title="Locked — every page automatically gets the site header">
      <i data-lucide="lock"></i>
      <span><b>Site header &amp; navigation</b> — added automatically to every page</span>
    </div>

    <div class="st-rows" id="st-rows">
      @foreach($layout as $row)
        @php
          // Same width default as the public renderer: legacy embed-only rows = full.
          $w = $row['width'] ?? null;
          if ($w === null) {
              $any = false; $allEmbed = true;
              foreach (($row['cols'] ?? []) as $c) {
                  foreach (($c['blocks'] ?? []) as $b) {
                      $any = true;
                      if (($b['type'] ?? '') !== 'embed') { $allEmbed = false; }
                  }
              }
              $w = ($any && $allEmbed) ? 'full' : '';
          }
        @endphp
        <div class="st-row" data-id="{{ $row['id'] ?? '' }}" data-width="{{ $w }}">
          <div class="st-row-tools">
            <span class="st-row-drag" title="Drag row"><i data-lucide="grip-vertical"></i> Row</span>
            <span class="st-sp"></span>
            <button type="button" class="st-tbtn" data-strow="width" title="Toggle full-page width"><i data-lucide="{{ $w === 'full' ? 'minimize-2' : 'maximize-2' }}"></i></button>
            <div class="st-presets" role="group" aria-label="Column layout">
              <button type="button" data-cols="12" title="1 column">1</button>
              <button type="button" data-cols="6,6" title="2 columns">2</button>
              <button type="button" data-cols="4,4,4" title="3 columns">3</button>
              <button type="button" data-cols="3,3,3,3" title="4 columns">4</button>
              <button type="button" data-cols="8,4" title="Wide + narrow">⅔·⅓</button>
              <button type="button" data-cols="4,8" title="Narrow + wide">⅓·⅔</button>
            </div>
            <button type="button" class="st-rowai" data-strow="ai" title="Build this row with AI"><i data-lucide="sparkles"></i> AI</button>
            <button type="button" class="st-tbtn st-del" data-strow="del" title="Delete row"><i data-lucide="trash-2"></i></button>
          </div>
          <div class="st-cols">
            @foreach(($row['cols'] ?? []) as $col)
              <div class="st-col" data-span="{{ (int) ($col['span'] ?? 12) }}" style="--span: {{ (int) ($col['span'] ?? 12) }}">
                <div class="st-col-blocks">
                  @foreach(($col['blocks'] ?? []) as $b)
                    @continue(! isset($types[$b['type'] ?? '']))
                    @include('admin.brief._studioblock', ['block' => $b, 'def' => $types[$b['type']]])
                  @endforeach
                </div>
              </div>
            @endforeach
          </div>
        </div>
      @endforeach
    </div>

    <div class="cv-empty" id="cv-empty" @if(count($layout)) hidden @endif>
      <h3>Let's build this page ✨</h3>
      <p>Describe your content and let AI design the whole page, drop in a ready-made component from the left, or start with an empty row.</p>
      <div class="cv-empty-actions">
        <button type="button" class="big-ai" id="cv-empty-ai"><i data-lucide="sparkles"></i> Build with AI</button>
        <button type="button" class="big-row" id="cv-empty-row"><i data-lucide="plus"></i> Add a row</button>
      </div>
    </div>

    <button type="button" class="st-addrow" id="st-addrow"><i data-lucide="plus"></i> Add row</button>

    <div class="st-lock" title="Locked — every page automatically gets the site footer">
      <i data-lucide="lock"></i>
      <span><b>Site footer</b> — added automatically to every page</span>
    </div>
  </div>
</div>

<div id="st-forms" hidden>
  @foreach($allBlocks as $b)
    @include('admin.brief._settings', ['block' => $b, 'def' => $types[$b['type']]])
  @endforeach
</div>
