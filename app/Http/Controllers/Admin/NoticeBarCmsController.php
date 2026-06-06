<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\NoticeBarStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NoticeBarCmsController extends Controller
{
    public function __construct(private NoticeBarStore $store)
    {
    }

    public function edit(): View
    {
        return view('admin.notice-bar.edit', [
            'bar' => $this->store->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'variant' => 'required|in:original,minimal,compact',
            'word_count' => 'required|integer|min:0|max:50',
            'speed' => 'required|integer|min:5|max:120',
            'items' => 'array',
            'items.*.text' => 'nullable|string|max:300',
            'items.*.href' => 'nullable|string|max:500',
        ]);

        // Items arrive keyed by row token; PHP preserves the submitted (DOM)
        // order, so iterating rebuilds the list in the editor's order. Rows
        // with no text are dropped (that's how "remove" persists).
        $items = [];
        foreach ((array) $request->input('items', []) as $row) {
            if (! is_array($row)) {
                continue;
            }

            $items[] = [
                'text' => (string) ($row['text'] ?? ''),
                'href' => (string) ($row['href'] ?? ''),
                'visible' => ! empty($row['visible']),
            ];
        }

        $this->store->save([
            'variant' => $request->input('variant'),
            'word_count' => (int) $request->input('word_count'),
            'speed' => (int) $request->input('speed'),
            'items' => $items,
        ]);

        return redirect()
            ->route('admin.notice-bar.index')
            ->with('status', 'Notice bar updated.');
    }
}
