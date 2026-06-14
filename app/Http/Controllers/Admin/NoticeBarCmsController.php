<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\NoticeBarStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class NoticeBarCmsController extends Controller
{
    public function __construct(private NoticeBarStore $store)
    {
    }

    public function edit(): View
    {
        return view('admin.notice-bar.edit', [
            'bar' => $this->store->get(),
            'linkSuggestions' => $this->linkSuggestions(),
        ]);
    }

    /**
     * Link targets offered as autocomplete in the item "link" field:
     * every public page (derived from the router so it stays in sync) plus
     * the homepage's in-page section anchors. Returned as value => label.
     */
    private function linkSuggestions(): array
    {
        $pages = [];

        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            $uri = $route->uri();

            if (str_starts_with($uri, 'admin')              // back-office
                || str_contains($uri, '{')                  // needs a parameter
                || str_ends_with($uri, '.html')) {          // legacy alias
                continue;
            }

            $path = $uri === '/' ? '/' : '/'.$uri;
            $pages[$path] = $this->humanisePath($uri);
        }

        ksort($pages);

        // Real section IDs on the homepage — linkable from any page via /#id.
        $sections = [
            '/#destinations' => 'Home → Destinations',
            '/#outcomes'     => 'Home → Outcomes',
            '/#about'        => 'Home → About',
            '/#insights'     => 'Home → Insights',
            '/#contact'      => 'Home → Contact',
        ];

        return ['Page' => $pages, 'Section' => $sections];
    }

    private function humanisePath(string $uri): string
    {
        if ($uri === '/' || $uri === '') {
            return 'Home';
        }

        return collect(explode('/', $uri))
            ->map(fn ($segment) => ucwords(str_replace('-', ' ', $segment)))
            ->implode(' › ');
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'variant' => 'required|in:original,minimal,compact',
            'word_count' => 'required|integer|min:0|max:50',
            'speed' => 'required|integer|min:5|max:120',
            'text_color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'font_style' => 'nullable|in:normal,italic',
            'bold' => 'nullable|boolean',
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
            'text_color' => (string) $request->input('text_color', '#ff5e32'),
            'font_style' => (string) $request->input('font_style', 'normal'),
            'bold' => $request->boolean('bold'),
            'items' => $items,
        ]);

        return redirect()
            ->route('admin.notice-bar.index')
            ->with('status', 'Notice bar updated.');
    }
}
