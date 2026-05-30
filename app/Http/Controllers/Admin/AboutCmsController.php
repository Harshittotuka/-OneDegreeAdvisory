<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AboutSchema;
use App\Support\AboutStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AboutCmsController extends Controller
{
    public function __construct(private AboutStore $store)
    {
    }

    /* ───────────────────────── List / reorder ───────────────────────── */

    public function index(): View
    {
        return view('admin.about.index', [
            'sections' => $this->store->all(),
            'types' => AboutSchema::types(),
        ]);
    }

    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['string'],
        ]);

        $this->store->reorder($data['ids']);

        return response()->json(['ok' => true]);
    }

    public function toggleVisibility(Request $request, string $id): RedirectResponse|JsonResponse
    {
        $section = $this->store->find($id);
        if (! $section) {
            return $this->toggleResponse($request, $id, false, 'Section not found.');
        }

        $new = ! ($section['visible'] ?? true);
        $this->store->setVisibility($id, $new);

        $label = AboutSchema::type($section['type'])['label'] ?? 'Section';

        return $this->toggleResponse(
            $request,
            $id,
            $new,
            $new ? "“{$label}” is now visible." : "“{$label}” is hidden from the page."
        );
    }

    private function toggleResponse(Request $request, string $id, bool $visible, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'id' => $id,
                'visible' => $visible,
                'message' => $message,
            ]);
        }

        return back()->with('status', $message);
    }

    /* ───────────────────────── Create / edit ───────────────────────── */

    public function create(Request $request): View|RedirectResponse
    {
        $type = (string) $request->query('type', '');
        if (! AboutSchema::isType($type)) {
            return redirect()->route('admin.about.index')->with('status', 'Pick a section type to add.');
        }

        return view('admin.about.form', [
            'mode' => 'create',
            'section' => [
                'id' => '',
                'type' => $type,
                'visible' => true,
                'data' => AboutSchema::blank($type),
            ],
            'schema' => AboutSchema::type($type),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $type = (string) $request->input('type');
        if (! AboutSchema::isType($type)) {
            return redirect()->route('admin.about.index')->with('status', 'Unknown section type.');
        }

        $section = $this->buildSection($request, $type, null);
        $this->store->save($section, null);

        return redirect()
            ->route('admin.about.edit', $section['id'])
            ->with('status', 'Section added.');
    }

    public function edit(string $id): View|RedirectResponse
    {
        $section = $this->store->find($id);
        if (! $section || ! AboutSchema::isType($section['type'] ?? '')) {
            return redirect()->route('admin.about.index')->with('status', 'Section not found.');
        }

        return view('admin.about.form', [
            'mode' => 'edit',
            'section' => $section,
            'schema' => AboutSchema::type($section['type']),
        ]);
    }

    public function update(Request $request, string $id): RedirectResponse
    {
        $existing = $this->store->find($id);
        if (! $existing || ! AboutSchema::isType($existing['type'] ?? '')) {
            return redirect()->route('admin.about.index')->with('status', 'Section not found.');
        }

        $section = $this->buildSection($request, $existing['type'], $id);
        $this->store->save($section, $id);

        return redirect()
            ->route('admin.about.edit', $section['id'])
            ->with('status', 'Section saved.');
    }

    public function destroy(string $id): RedirectResponse
    {
        $this->store->delete($id);

        return redirect()->route('admin.about.index')->with('status', 'Section deleted.');
    }

    /* ───────────────────────── Live editor (Mode 2) ───────────────────────── */

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

    /* ───────────────────────── Helpers ───────────────────────── */

    private function buildSection(Request $request, string $type, ?string $originalId): array
    {
        $raw = $request->input('data', []);
        $raw = is_array($raw) ? $raw : [];

        $data = $this->sanitizeData($type, $raw);

        // On edit keep the existing id stable; on create derive it from the
        // anchor field (if any) or fall back to the type name.
        $desiredId = (string) $request->input('id', '')
            ?: $originalId
            ?: ($data['anchor'] ?? '')
            ?: $type;

        return [
            'id' => $this->store->uniqueId($desiredId, $originalId),
            'type' => $type,
            'visible' => $request->boolean('visible'),
            'data' => $data,
        ];
    }

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
