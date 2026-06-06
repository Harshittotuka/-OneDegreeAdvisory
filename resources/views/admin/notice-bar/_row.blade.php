<div class="nb-row" data-nb-row>
  <div class="nb-row-fields">
    <input type="text" name="items[{{ $i }}][text]" value="{{ $item['text'] ?? '' }}"
           placeholder="Announcement text (e.g. Spring 2027 intake planning is open)">
    <input type="text" name="items[{{ $i }}][href]" value="{{ $item['href'] ?? '' }}"
           placeholder="Link — /contact, https://…, #section (leave empty = not clickable)">
  </div>

  <label class="nb-vis" title="Show this item on the site">
    <input type="hidden" name="items[{{ $i }}][visible]" value="0">
    <input type="checkbox" name="items[{{ $i }}][visible]" value="1" @checked($item['visible'] ?? true)>
    <span>Show</span>
  </label>

  <div class="nb-row-actions">
    <button type="button" class="nb-icon-btn" data-nb-up title="Move up"><i data-lucide="chevron-up"></i></button>
    <button type="button" class="nb-icon-btn" data-nb-down title="Move down"><i data-lucide="chevron-down"></i></button>
    <button type="button" class="nb-icon-btn nb-del" data-nb-del title="Remove item"><i data-lucide="trash-2"></i></button>
  </div>
</div>
