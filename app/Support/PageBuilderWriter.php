<?php

namespace App\Support;

use App\Exceptions\PageBuilderException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * All Page Builder reads and writes that happen without a browser, in one
 * place. The MCP server at /mcp is a thin adapter over this, so the rules below
 * hold however the Page Builder is driven from outside the studio.
 *
 * The safety model lives here, not in the controllers:
 *   - Writes produce hidden drafts; `visible` is never settable.
 *   - A page that is already live is refused; you duplicate it instead.
 *   - A `payment` block is refused outright, leaving the studio's OTP
 *     authorization flow as the only route to a live payment gateway.
 *   - Every block goes through SanitizesBriefLayout, the same sanitizer the
 *     visual studio uses.
 */
class PageBuilderWriter
{
    use PersistsInlineImages, SanitizesBriefLayout;

    public function __construct(private BriefPageStore $store) {}

    /* ───────────────────────── Read ───────────────────────── */

    /** @return array<int, array<string, mixed>> */
    public function list(): array
    {
        return array_map(fn (array $p) => $this->summary($p), $this->store->all());
    }

    public function find(string $slug): array
    {
        $page = $this->store->find($slug);
        if ($page === null) {
            throw PageBuilderException::notFound();
        }

        return $this->detail($page);
    }

    /**
     * The block vocabulary. `payment` is withheld so a caller is never even
     * tempted to compose one.
     *
     * @param  string|null  $only  return just this one type, when given
     */
    public function schema(?string $only = null): array
    {
        $types = [];
        foreach (BriefSchema::types() as $key => $def) {
            if ($key === 'payment') {
                continue;
            }
            if ($only !== null && $only !== '' && $key !== $only) {
                continue;
            }
            $types[$key] = [
                'label' => $def['label'] ?? $key,
                'description' => $def['desc'] ?? null,
                'fields' => $def['fields'] ?? [],
                'blank' => BriefSchema::blank($key),
            ];
        }

        return $types;
    }

    /** Every block type name, for a compact overview. */
    public function typeNames(): array
    {
        return array_values(array_diff(array_keys(BriefSchema::types()), ['payment']));
    }

    /* ───────────────────────── Write ───────────────────────── */

    /**
     * Create a hidden draft.
     *
     * @param  array<string, mixed>  $input
     */
    public function create(array $input, string $actor): array
    {
        $data = $this->validate($input, titleRequired: true);

        $slug = $this->store->uniqueSlug($data['title']);
        $title = $data['title'];

        $page = [
            'slug' => $slug,
            'path' => '/briefs/'.$slug,
            'title' => $title,
            'page_title' => Seo::title($data['page_title'] ?? '', $title.' | '.config('site.name'), 90),
            'meta_description' => Seo::description($data['meta_description'] ?? '', '', 170),
            'visible' => false,
            'layout' => [],
        ];

        if (array_key_exists('path', $data)) {
            $page['path'] = $this->cleanPath($data['path'], $page, $this->store);
        }
        if (array_key_exists('layout', $data)) {
            $page['layout'] = $this->prepareLayout($data['layout']);
        }

        $page = $this->stamp($page, $actor);
        $this->store->save($page);
        $this->audit('created', $page, $actor);

        return $this->detail($page);
    }

    /**
     * Update an existing draft. A live page is refused.
     *
     * @param  array<string, mixed>  $input
     */
    public function update(string $slug, array $input, string $actor): array
    {
        $page = $this->store->find($slug);
        if ($page === null) {
            throw PageBuilderException::notFound();
        }
        if (($page['visible'] ?? false) && $this->draftsOnly()) {
            throw PageBuilderException::published($slug);
        }

        $data = $this->validate($input, titleRequired: false);

        if (array_key_exists('title', $data)) {
            $page['title'] = $data['title'];
        }
        if (array_key_exists('path', $data)) {
            $page['path'] = $this->cleanPath($data['path'], $page, $this->store);
        }
        if (array_key_exists('page_title', $data)) {
            $page['page_title'] = Seo::title($data['page_title'], ($page['title'] ?? '').' | '.config('site.name'), 90);
        }
        if (array_key_exists('meta_description', $data)) {
            $page['meta_description'] = Seo::description($data['meta_description'], '', 170);
        }
        if (array_key_exists('layout', $data)) {
            $page['layout'] = $this->prepareLayout($data['layout']);
        }

        if ($this->draftsOnly()) {
            $page['visible'] = false;
        }
        unset($page['sections']); // fully migrated to the grid layout

        $page = $this->stamp($page, $actor);
        $this->store->save($page, $slug);
        $this->audit('updated', $page, $actor);

        return $this->detail($page);
    }

    /**
     * Append rows to a draft's existing layout, so a long page can be built up
     * over several turns without resending what is already there.
     *
     * @param  array<int, mixed>  $rows
     */
    public function appendRows(string $slug, array $rows, string $actor): array
    {
        $page = $this->store->find($slug);
        if ($page === null) {
            throw PageBuilderException::notFound();
        }

        $existing = $page['layout'] ?? [];

        return $this->update($slug, [
            'layout' => array_merge(is_array($existing) ? $existing : [], $rows),
        ], $actor);
    }

    /** Copy any page into a hidden draft — the safe way to revise a live page. */
    public function duplicate(string $slug, string $actor): array
    {
        $copy = $this->store->duplicate($slug);
        if ($copy === null) {
            throw PageBuilderException::notFound();
        }

        $this->audit('duplicated', $copy, $actor, ['source' => $slug]);

        return $this->detail($copy);
    }

    public function deleteDraft(string $slug, string $actor): void
    {
        $page = $this->store->find($slug);
        if ($page === null) {
            throw PageBuilderException::notFound();
        }
        if ($page['visible'] ?? false) {
            throw PageBuilderException::publishedDelete($slug);
        }

        $this->store->delete($slug);
        $this->audit('deleted', $page, $actor);
    }

    /* ───────────────────────── Internals ───────────────────────── */

    private function draftsOnly(): bool
    {
        return (bool) config('page_api.drafts_only', true);
    }

    /**
     * Validate only the keys actually present, so a caller can change one field
     * without resending the page.
     *
     * @param  array<string, mixed>  $input
     */
    private function validate(array $input, bool $titleRequired): array
    {
        // Unknown keys are dropped rather than rejected: an assistant guessing
        // "seo_title" should get a clear result, not a wall of errors.
        $known = array_intersect_key($input, array_flip([
            'title', 'path', 'page_title', 'meta_description', 'layout',
        ]));

        $valid = Validator::make($known, [
            'title' => [$titleRequired ? 'required' : 'sometimes', 'string', 'max:160'],
            'path' => ['sometimes', 'nullable', 'string', 'max:200'],
            'page_title' => ['sometimes', 'nullable', 'string', 'max:300'],
            'meta_description' => ['sometimes', 'nullable', 'string', 'max:600'],
            'layout' => ['sometimes', 'array'],
        ])->validate();

        if (array_key_exists('title', $valid)) {
            $valid['title'] = mb_substr(trim((string) $valid['title']), 0, 160) ?: 'Untitled';
        }
        foreach (['page_title', 'meta_description', 'path'] as $key) {
            if (array_key_exists($key, $valid)) {
                $valid[$key] = trim((string) $valid[$key]);
            }
        }

        return $valid;
    }

    private function prepareLayout(array $layout): array
    {
        if ($this->layoutHasPayment($layout)) {
            throw ValidationException::withMessages([
                'layout' => 'A payment section cannot be created outside the studio — it needs the authorization code flow in /admin/pages.',
            ]);
        }

        // Any data: image URLs → the public disk, then schema-sanitize every block.
        return $this->sanitizeLayout($this->persistInlineImages($layout, 'brief'));
    }

    private function stamp(array $page, string $actor): array
    {
        $page['updated_at'] = now()->toIso8601String();
        $page['updated_by'] = $actor;

        return $page;
    }

    public function summary(array $page): array
    {
        $visible = (bool) ($page['visible'] ?? false);

        return [
            'slug' => $page['slug'] ?? null,
            'title' => $page['title'] ?? null,
            'path' => $page['path'] ?? null,
            'visible' => $visible,
            'blocks' => $this->countBlocks($page['layout'] ?? []),
            'updated_at' => $page['updated_at'] ?? null,
            'editable' => ! ($visible && $this->draftsOnly()),
        ];
    }

    public function detail(array $page): array
    {
        return $this->summary($page) + [
            'page_title' => $page['page_title'] ?? '',
            'meta_description' => $page['meta_description'] ?? '',
            'preview_url' => rtrim((string) config('app.url'), '/').($page['path'] ?? ''),
            'preview_note' => ($page['visible'] ?? false)
                ? 'Live.'
                : 'Hidden: this URL renders only for a super-admin signed in at /admin. It is 404 for everyone else until published.',
            'layout' => $page['layout'] ?? [],
        ];
    }

    public function countBlocks(array $layout): int
    {
        $n = 0;
        foreach ($layout as $row) {
            foreach ((is_array($row) ? ($row['cols'] ?? []) : []) as $col) {
                $n += count(is_array($col) ? ($col['blocks'] ?? []) : []);
            }
        }

        return $n;
    }

    /** Every write is logged so an assistant-authored change is traceable. */
    private function audit(string $event, array $page, string $actor, array $extra = []): void
    {
        Log::channel('page_api')->info('page-api.'.$event, [
            'slug' => $page['slug'] ?? null,
            'title' => $page['title'] ?? null,
            'path' => $page['path'] ?? null,
            'visible' => (bool) ($page['visible'] ?? false),
            'blocks' => $this->countBlocks($page['layout'] ?? []),
            'actor' => $actor,
            'ip' => request()?->ip(),
        ] + $extra);
    }
}
