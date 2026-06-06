<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AboutSchema;
use App\Support\AboutStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AboutCmsController extends Controller
{
    public function __construct(private AboutStore $store)
    {
    }

    /* ───────────────────────── Entry ───────────────────────── */

    /**
     * The structured list/form editor was removed — the live editor is now the
     * single way to edit the About page. The dashboard nav still points here.
     */
    public function index(): RedirectResponse
    {
        return redirect()->route('admin.about.live');
    }

    /* ───────────────────────── Live editor ───────────────────────── */

    public function live(): View
    {
        return view('admin.about.live', [
            'sections' => $this->store->all(),
            'types' => AboutSchema::types(),
        ]);
    }

    /** Render one blank, fully-instrumented section wrapper for the "Add section" action. */
    public function liveSection(Request $request): View|RedirectResponse
    {
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
        $incoming = $request->input('sections');
        if (! is_array($incoming)) {
            return response()->json(['ok' => false, 'message' => 'Bad payload.'], 422);
        }

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
        $request->validate([
            'file' => ['required', 'image', 'max:5120'], // 5 MB
        ]);

        $path = $request->file('file')->store('about', 'public');

        return response()->json([
            'url' => asset('storage/'.$path),
        ]);
    }

    /**
     * Download a remote image to local public storage and return a same-origin
     * URL. Lets the browser crop remote images (e.g. Unsplash) without the
     * canvas being tainted by cross-origin pixels.
     */
    public function importUrl(Request $request): JsonResponse
    {
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

        $ext = match (true) {
            str_contains($type, 'png') => 'png',
            str_contains($type, 'webp') => 'webp',
            str_contains($type, 'gif') => 'gif',
            str_contains($type, 'svg') => 'svg',
            default => 'jpg',
        };
        $name = 'about/import-'.bin2hex(random_bytes(6)).'.'.$ext;
        Storage::disk('public')->put($name, $body);

        return response()->json(['ok' => true, 'url' => asset('storage/'.$name)]);
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
}
