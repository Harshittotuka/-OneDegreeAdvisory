<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AboutSchema;
use App\Support\AboutStore;
use App\Support\PersistsInlineImages;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class AboutCmsController extends Controller
{
    use PersistsInlineImages;

    public function __construct(private AboutStore $store)
    {
    }

    /* ───────────────────────── Entry ───────────────────────── */

    /**
     * The structured list/form editor was removed — the live editor is now the
     * single way to edit the About page. Keep this route for direct internal use.
     */
    public function index(): View|RedirectResponse
    {
        if (! $this->enabled()) {
            return $this->inDevelopment();
        }

        return redirect()->route('admin.about.live');
    }

    /* ───────────────────────── Live editor ───────────────────────── */

    public function live(): View
    {
        if (! $this->enabled()) {
            return $this->inDevelopment();
        }

        return view('admin.about.live', [
            'sections' => $this->store->all(),
            'types' => AboutSchema::types(),
        ]);
    }

    /** Render one blank, fully-instrumented section wrapper for the "Add section" action. */
    public function liveSection(Request $request): View|RedirectResponse
    {
        if (! $this->enabled()) {
            abort(404);
        }

        $type = (string) $request->query('type', '');
        if (! AboutSchema::isType($type)) {
            abort(404);
        }

        return view('admin.about._live_section', [
            'section' => [
                'id' => $this->store->uniqueId($type),
                'type' => $type,
                'visible' => true,
                'data' => AboutSchema::blank($type),
            ],
            'types' => AboutSchema::types(),
            'isNew' => true,
        ]);
    }

    /** Persist the whole page from the live editor: order, visibility and every field. */
    public function liveSave(Request $request): JsonResponse
    {
        if (! $this->enabled()) {
            return response()->json(['ok' => false, 'message' => 'About page editor is in development.'], 423);
        }

        $incoming = $request->input('sections');
        if (! is_array($incoming)) {
            return response()->json(['ok' => false, 'message' => 'Bad payload.'], 422);
        }

        // Freshly-cropped images arrive as inline data URLs — write them to disk
        // now (only on a real save), then store the resulting file URLs. This runs
        // before sanitizeData() so the field cleaner never sees the long data URL.
        $incoming = $this->persistInlineImages($incoming, 'about');

        $sections = [];
        $seen = [];
        foreach ($incoming as $raw) {
            $type = (string) ($raw['type'] ?? '');
            if (! AboutSchema::isType($type)) {
                continue;
            }

            $desired = (string) ($raw['id'] ?? '') ?: $type;
            $id = Str::slug($desired) ?: $type;
            $base = $id;
            $n = 2;
            while (in_array($id, $seen, true)) {
                $id = $base.'-'.$n;
                $n++;
            }
            $seen[] = $id;

            $sections[] = [
                'id' => $id,
                'type' => $type,
                'visible' => (bool) ($raw['visible'] ?? true),
                'data' => $this->sanitizeData($type, is_array($raw['data'] ?? null) ? $raw['data'] : []),
            ];
        }

        $this->store->replaceAll($sections);

        return response()->json(['ok' => true, 'count' => count($sections), 'message' => 'Page saved.']);
    }

    /* ───────────────────────── Image upload ───────────────────────── */

    public function upload(Request $request): JsonResponse
    {
        if (! $this->enabled()) {
            return response()->json(['ok' => false, 'message' => 'About page editor is in development.'], 423);
        }

        $request->validate([
            'file' => ['required', 'image', 'max:5120'], // 5 MB
        ]);

        $path = $request->file('file')->store('about', 'public');

        return response()->json([
            'url' => asset('storage/'.$path),
        ]);
    }

    /**
     * Fetch a remote image and return it as a same-origin base64 data URL, so the
     * browser can crop it without tainting the canvas. Nothing is written to disk
     * here — the cropped result is only persisted when the page is saved.
     */
    public function importUrl(Request $request): JsonResponse
    {
        if (! $this->enabled()) {
            return response()->json(['ok' => false, 'message' => 'About page editor is in development.'], 423);
        }

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
        if (strlen($body) > 5 * 1024 * 1024) {
            return response()->json(['ok' => false, 'message' => 'Image is larger than 5 MB.'], 422);
        }

        return response()->json([
            'ok' => true,
            'url' => 'data:'.$type.';base64,'.base64_encode($body),
        ]);
    }

    /* ───────────────────────── Helpers ───────────────────────── */

    /** Walk the type's schema and pull only declared fields out of the raw input. */
    private function sanitizeData(string $type, array $raw): array
    {
        $out = [];

        foreach (AboutSchema::type($type)['fields'] ?? [] as $field) {
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
            'checkbox' => (bool) $value,
            'icon' => preg_replace('/[^a-z0-9-]/', '', strtolower(trim((string) $value))),
            'select' => array_key_exists((string) $value, $field['options'] ?? [])
                ? (string) $value
                : (string) array_key_first($field['options'] ?? ['' => '']),
            'textarea' => mb_substr(trim((string) $value), 0, 5000),
            default => mb_substr(trim((string) $value), 0, 1000),
        };
    }

    private function enabled(): bool
    {
        // The About editor is hidden from the standard CMS, but the super-admin
        // ("infolith") login unlocks it — using the exact same store/views, so
        // edits stay in sync with the live site.
        return (bool) config('site.about_cms_enabled') || (bool) session('cms_super_admin');
    }

    private function inDevelopment(): View
    {
        return view('admin.in-development', [
            'title' => 'About Page',
            'message' => 'This section is in development.',
        ]);
    }
}
