<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * File-backed store for blog posts. Posts live in a single editable JSON file
 * so the CMS can create, update, and delete them with no database. The file is
 * seeded once from BlogContent::defaults().
 */
class BlogStore
{
    private string $path;

    public function __construct()
    {
        $this->path = storage_path('app/blog-posts.json');
    }

    /** All posts, in display order (newest created first). */
    public function all(): array
    {
        if (! is_file($this->path)) {
            $seed = (new BlogContent)->defaults();
            $this->writeAll($seed);

            return $seed;
        }

        $data = json_decode((string) file_get_contents($this->path), true);

        return is_array($data) ? $data : [];
    }

    public function find(string $slug): ?array
    {
        foreach ($this->all() as $post) {
            if (($post['slug'] ?? null) === $slug) {
                return $post;
            }
        }

        return null;
    }

    /**
     * Create or update a post. When $originalSlug matches an existing post the
     * record is updated in place (preserving its position); otherwise the post
     * is prepended so new articles appear first.
     */
    public function save(array $post, ?string $originalSlug = null): array
    {
        $posts = $this->all();
        $needle = $originalSlug ?? ($post['slug'] ?? null);

        $index = null;
        foreach ($posts as $i => $existing) {
            if (($existing['slug'] ?? null) === $needle) {
                $index = $i;
                break;
            }
        }

        if ($index === null) {
            array_unshift($posts, $post);
        } else {
            $posts[$index] = $post;
        }

        $this->writeAll($posts);

        return $post;
    }

    /** Mark one post as the sole featured post, clearing the flag on all others. */
    public function makeSoleFeatured(string $slug): void
    {
        $posts = $this->all();
        foreach ($posts as &$p) {
            $p['featured'] = (($p['slug'] ?? null) === $slug);
        }
        unset($p);

        $this->writeAll($posts);
    }

    public function setVisibility(string $slug, bool $visible): void
    {
        $posts = $this->all();
        foreach ($posts as &$p) {
            if (($p['slug'] ?? null) === $slug) {
                $p['visible'] = $visible;
            }
        }
        unset($p);

        $this->writeAll($posts);
    }

    /** Reorder posts to match the given slug order; any omitted posts keep their order at the end. */
    public function reorder(array $slugs): void
    {
        $bySlug = [];
        foreach ($this->all() as $post) {
            $bySlug[$post['slug'] ?? ''] = $post;
        }

        $ordered = [];
        foreach ($slugs as $slug) {
            if (isset($bySlug[$slug])) {
                $ordered[] = $bySlug[$slug];
                unset($bySlug[$slug]);
            }
        }

        // Append any posts not present in the submitted order.
        foreach ($bySlug as $post) {
            $ordered[] = $post;
        }

        $this->writeAll($ordered);
    }

    public function delete(string $slug): void
    {
        $posts = array_values(array_filter(
            $this->all(),
            fn (array $p) => ($p['slug'] ?? null) !== $slug
        ));

        $this->writeAll($posts);
    }

    /** A URL-safe slug unique across all posts except $ignore (the post being edited). */
    public function uniqueSlug(string $desired, ?string $ignore = null): string
    {
        $base = Str::slug($desired) ?: 'post';
        $taken = array_filter(
            array_column($this->all(), 'slug'),
            fn ($s) => $s !== $ignore
        );

        $slug = $base;
        $n = 2;
        while (in_array($slug, $taken, true)) {
            $slug = $base.'-'.$n;
            $n++;
        }

        return $slug;
    }

    private function writeAll(array $posts): void
    {
        if (! is_dir(dirname($this->path))) {
            mkdir(dirname($this->path), 0775, true);
        }

        $written = file_put_contents(
            $this->path,
            json_encode(array_values($posts), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        );

        if ($written === false) {
            throw new \RuntimeException('Could not save the Blog CMS data.');
        }

        app(CmsCrmBackupManager::class)->markDirty('cms-json');
    }
}
