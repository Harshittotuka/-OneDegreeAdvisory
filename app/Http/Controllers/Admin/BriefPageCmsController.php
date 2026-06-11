<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\BriefPageStore;
use App\Support\BriefPresets;
use App\Support\BriefSchema;
use App\Support\PersistsInlineImages;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * The Brief Page Builder — a super-admin CMS for creating .odp-* "brief" pages
 * (Europe, intelligence briefs, etc.) from composable blocks. Mirrors the About
 * CMS pattern (schema-driven sanitize + PersistsInlineImages), extended to manage
 * many pages and to drive a block-builder UI with a live preview.
 */
class BriefPageCmsController extends Controller
{
    use PersistsInlineImages;

    public function __construct(private BriefPageStore $store)
    {
    }

    /* ───────────────────────── Pages list ───────────────────────── */

    public function index(): View
    {
        $this->guard();

        return view('admin.brief.index', [
            'pages' => $this->store->all(),
        ]);
    }

    public function storePage(Request $request): RedirectResponse
    {
        $this->guard();

        $request->validate(['title' => ['required', 'string', 'max:160']]);

        $slug = $this->store->uniqueSlug((string) $request->input('title'));
        $page = [
            'slug' => $slug,
            'path' => '/briefs/'.$slug,
            'title' => trim((string) $request->input('title')),
            'page_title' => trim((string) $request->input('title')).' | One Degree Advisory',
            'meta_description' => '',
            'visible' => false,
            'sections' => [
                ['id' => 'hero', 'type' => 'hero', 'visible' => true, 'data' => BriefSchema::blank('hero')],
            ],
        ];

        $this->store->save($page);

        return redirect()->route('admin.pages.edit', $slug)->with('status', 'Page created — start building.');
    }

    /* ───────────────────────── Builder ───────────────────────── */

    /** Old two-pane editor is superseded by the full-screen studio. */
    public function edit(string $slug): RedirectResponse
    {
        return redirect()->route('admin.pages.studio', $slug);
    }

    /** Full-screen drag-and-drop visual builder. */
    public function studio(string $slug): View|RedirectResponse
    {
        $this->guard();

        $page = $this->store->find($slug);
        if ($page === null) {
            return redirect()->route('admin.pages.index')->with('status', 'That page no longer exists.');
        }

        // Render each component preset once so the palette can show a live thumbnail.
        $presets = [];
        foreach (BriefPresets::all() as $key => $p) {
            $presets[$key] = $p + [
                'node' => view('admin.brief._blocknode', ['type' => $p['type'], 'data' => $p['data']])->render(),
            ];
        }

        return view('admin.brief.studio', [
            'page' => $page,
            'types' => BriefSchema::types(),
            'presets' => $presets,
        ]);
    }

    /** A ready-made component (block with data): rendered node + settings form. */
    public function preset(Request $request): JsonResponse
    {
        $this->guard();

        $preset = BriefPresets::find((string) $request->query('key', ''));
        if ($preset === null) {
            abort(404);
        }

        $block = ['id' => 'b'.Str::random(7), 'type' => $preset['type'], 'visible' => true, 'data' => $preset['data']];

        return response()->json([
            'id' => $block['id'],
            'type' => $block['type'],
            'node' => view('admin.brief._blocknode', ['type' => $block['type'], 'data' => $block['data']])->render(),
            'form' => view('admin.brief._settings', ['block' => $block, 'def' => BriefSchema::type($block['type'])])->render(),
        ]);
    }

    /** Save the whole page (meta + grid layout) from the studio. */
    public function save(Request $request, string $slug): JsonResponse
    {
        $this->guard();

        $page = $this->store->find($slug);
        if ($page === null) {
            return response()->json(['ok' => false, 'message' => 'Page not found.'], 404);
        }

        $layout = $request->input('layout');
        if (! is_array($layout)) {
            return response()->json(['ok' => false, 'message' => 'Bad payload.'], 422);
        }

        $page['title'] = mb_substr(trim((string) $request->input('title', $page['title'] ?? 'Untitled')), 0, 160) ?: 'Untitled';
        $page['page_title'] = mb_substr(trim((string) $request->input('page_title', $page['page_title'] ?? '')), 0, 200);
        $page['meta_description'] = mb_substr(trim((string) $request->input('meta_description', $page['meta_description'] ?? '')), 0, 300);
        $page['visible'] = $request->boolean('visible');
        $page['path'] = $this->cleanPath((string) $request->input('path', $page['path'] ?? ''), $page);
        // Inline (freshly cropped/uploaded) images → disk, then sanitize every block.
        $layout = $this->persistInlineImages($layout, 'brief');
        $page['layout'] = $this->sanitizeLayout($layout);
        unset($page['sections']); // fully migrated to the grid layout

        $this->store->save($page, $slug);

        return response()->json(['ok' => true, 'message' => 'Page saved.', 'path' => $page['path']]);
    }

    /**
     * Normalize a user-chosen URL path. Invalid, reserved or already-taken paths
     * fall back to the page's current path (so a bad edit can never orphan it).
     */
    private function cleanPath(string $raw, array $page): string
    {
        $current = $page['path'] ?? ('/briefs/'.($page['slug'] ?? 'page'));

        $p = '/'.trim(strtolower(trim($raw)), '/');
        $p = preg_replace('#/+#', '/', $p);

        if ($p === '/' || ! preg_match('#^/[a-z0-9/-]+$#', $p)) {
            return $current;
        }

        foreach (['/admin', '/storage', '/api', '/login', '/logout'] as $reserved) {
            if ($p === $reserved || str_starts_with($p, $reserved.'/')) {
                return $current;
            }
        }

        $other = $this->store->findByPath($p);
        if ($other !== null && ($other['slug'] ?? null) !== ($page['slug'] ?? null)) {
            return $current;
        }

        return $p;
    }

    /** Blank block for the palette: returns its rendered node + settings form. */
    public function block(Request $request): JsonResponse
    {
        $this->guard();

        $type = (string) $request->query('type', '');
        if (! BriefSchema::isType($type)) {
            abort(404);
        }

        $block = ['id' => 'b'.Str::random(7), 'type' => $type, 'visible' => true, 'data' => BriefSchema::blank($type)];

        return response()->json([
            'id' => $block['id'],
            'type' => $type,
            'node' => view('admin.brief._blocknode', ['type' => $type, 'data' => $block['data']])->render(),
            'form' => view('admin.brief._settings', ['block' => $block, 'def' => BriefSchema::type($type)])->render(),
        ]);
    }

    /** Re-render one block to HTML after an edit in the drawer. */
    public function render(Request $request): JsonResponse
    {
        $this->guard();

        $type = (string) $request->input('type', '');
        if (! BriefSchema::isType($type)) {
            return response()->json(['node' => ''], 422);
        }
        $data = $this->sanitizeData($type, is_array($request->input('data')) ? $request->input('data') : []);

        return response()->json([
            'node' => view('admin.brief._blocknode', ['type' => $type, 'data' => $data])->render(),
        ]);
    }

    /** Clean a full grid layout (rows → cols → blocks). */
    private function sanitizeLayout(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $cols = [];
            foreach (($row['cols'] ?? []) as $col) {
                if (! is_array($col)) {
                    continue;
                }
                $blocks = [];
                foreach (($col['blocks'] ?? []) as $b) {
                    if (! is_array($b)) {
                        continue;
                    }
                    $type = (string) ($b['type'] ?? '');
                    if (! BriefSchema::isType($type)) {
                        continue;
                    }
                    $blocks[] = [
                        'id' => (Str::slug((string) ($b['id'] ?? '')) ?: ('b'.Str::random(6))),
                        'type' => $type,
                        'visible' => (bool) ($b['visible'] ?? true),
                        'data' => $this->sanitizeData($type, is_array($b['data'] ?? null) ? $b['data'] : []),
                    ];
                }
                $span = max(1, min(12, (int) ($col['span'] ?? 12)));
                $cols[] = ['id' => (Str::slug((string) ($col['id'] ?? '')) ?: ('c'.Str::random(6))), 'span' => $span, 'blocks' => $blocks];
            }
            if ($cols) {
                $out[] = [
                    'id' => (Str::slug((string) ($row['id'] ?? '')) ?: ('r'.Str::random(6))),
                    'width' => (($row['width'] ?? '') === 'full') ? 'full' : '',
                    'cols' => $cols,
                ];
            }
        }

        return $out;
    }

    /* ───────────────────────── Page actions ───────────────────────── */

    public function duplicate(string $slug): RedirectResponse
    {
        $this->guard();
        $copy = $this->store->duplicate($slug);

        return $copy
            ? redirect()->route('admin.pages.edit', $copy['slug'])->with('status', 'Page duplicated.')
            : redirect()->route('admin.pages.index')->with('status', 'Could not duplicate that page.');
    }

    public function toggleVisibility(string $slug): RedirectResponse
    {
        $this->guard();
        $page = $this->store->find($slug);
        if ($page !== null) {
            $this->store->setVisibility($slug, ! ($page['visible'] ?? true));
        }

        return redirect()->route('admin.pages.index')->with('status', 'Visibility updated.');
    }

    public function destroy(string $slug): RedirectResponse
    {
        $this->guard();
        $this->store->delete($slug);

        return redirect()->route('admin.pages.index')->with('status', 'Page deleted.');
    }

    /* ───────────────────────── Images ───────────────────────── */

    public function upload(Request $request): JsonResponse
    {
        $this->guard();
        $request->validate(['file' => ['required', 'image', 'max:8192']]);
        $path = $request->file('file')->store('brief', 'public');

        return response()->json(['url' => asset('storage/'.$path)]);
    }

    public function importUrl(Request $request): JsonResponse
    {
        $this->guard();

        $url = trim((string) $request->input('url', ''));
        if (! preg_match('#^https?://#i', $url)) {
            return response()->json(['ok' => false, 'message' => 'Enter a valid http(s) image URL.'], 422);
        }

        try {
            $resp = Http::timeout(12)->withHeaders(['User-Agent' => 'OneDegreeCMS/1.0'])->get($url);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Could not fetch that image.'], 422);
        }

        $type = strtolower((string) $resp->header('Content-Type'));
        if (! $resp->ok() || ! str_starts_with($type, 'image/')) {
            return response()->json(['ok' => false, 'message' => 'That URL is not a reachable image.'], 422);
        }

        $body = $resp->body();
        if (strlen($body) > 8 * 1024 * 1024) {
            return response()->json(['ok' => false, 'message' => 'Image is larger than 8 MB.'], 422);
        }

        return response()->json(['ok' => true, 'url' => 'data:'.$type.';base64,'.base64_encode($body)]);
    }

    /* ───────────────────────── Sanitization ───────────────────────── */


    private function sanitizeData(string $type, array $raw): array
    {
        $out = [];
        foreach (BriefSchema::type($type)['fields'] ?? [] as $field) {
            $key = $field['key'];
            if ($field['type'] === 'repeater') {
                $rows = is_array($raw[$key] ?? null) ? $raw[$key] : [];
                $out[$key] = $this->sanitizeRepeater($field['fields'], $rows);

                continue;
            }
            $out[$key] = $this->cleanScalar($field, $raw[$key] ?? null);
        }

        return $out;
    }

    private function sanitizeRepeater(array $fields, array $rows): array
    {
        $items = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $clean = [];
            $hasContent = false;
            foreach ($fields as $field) {
                if ($field['type'] === 'repeater') {
                    $nested = is_array($row[$field['key']] ?? null) ? $row[$field['key']] : [];
                    $clean[$field['key']] = $this->sanitizeRepeater($field['fields'], $nested);
                    if ($clean[$field['key']] !== []) {
                        $hasContent = true;
                    }

                    continue;
                }
                $value = $this->cleanScalar($field, $row[$field['key']] ?? null);
                $clean[$field['key']] = $value;
                if ($value !== '' && $value !== false) {
                    $hasContent = true;
                }
            }
            if ($hasContent) {
                $items[] = $clean;
            }
        }

        return $items;
    }

    private function cleanScalar(array $field, mixed $value): mixed
    {
        return match ($field['type']) {
            'checkbox' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'icon' => preg_replace('/[^a-z0-9-]/', '', strtolower(trim((string) $value))),
            'color' => preg_match('/^#([0-9a-f]{3}|[0-9a-f]{6})$/i', trim((string) $value)) ? strtolower(trim((string) $value)) : '',
            'select' => array_key_exists((string) $value, $field['options'] ?? [])
                ? (string) $value
                : (string) array_key_first($field['options'] ?? ['' => '']),
            'richtext' => $this->cleanRichText((string) $value),
            // Raw embed code (HTML/CSS/JS) — super-admin only, stored as-is (capped).
            'code' => mb_substr((string) $value, 0, 120000),
            'image' => mb_substr(trim((string) $value), 0, 2000),
            'textarea' => mb_substr(trim((string) $value), 0, 6000),
            default => mb_substr(trim((string) $value), 0, 1000),
        };
    }

    /** Strip <script>/<style> and on* handlers; keep basic formatting HTML. */
    private function cleanRichText(string $html): string
    {
        $html = preg_replace('#<\s*(script|style)[^>]*>.*?<\s*/\s*\1\s*>#is', '', $html);
        $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);

        return mb_substr(trim($html), 0, 12000);
    }

    private function guard(): void
    {
        if (! (bool) session('cms_super_admin')) {
            abort(403, 'The Page Builder is available to super-admins only.');
        }
    }
}
