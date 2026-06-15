<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\CmsAuth;
use App\Support\BlogStore;
use App\Support\BriefPageStore;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class BlogCmsController extends Controller
{
    /** Block kinds the editor and the public template both understand. */
    private const KINDS = ['p', 'h2', 'list', 'table', 'quote', 'image'];

    public function __construct(private BlogStore $store)
    {
    }

    /* ───────────────────────── Auth ───────────────────────── */

    public function showLogin(Request $request): View|RedirectResponse
    {
        // Already signed in, or carrying a valid "keep me signed in" cookie.
        if ($request->session()->get('cms_authenticated') || CmsAuth::validRemember($request)) {
            $request->session()->put('cms_authenticated', true);

            // Restore the role from the remember cookie when re-establishing.
            $role = CmsAuth::rememberedRole($request);
            if ($role !== null) {
                $request->session()->put('cms_super_admin', $role === 'super');
            }

            return redirect()->route('admin.dashboard');
        }

        return view('admin.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string']]);

        // The same form accepts either the standard CMS password or the
        // super-admin ("infolith") password — the latter unlocks every page.
        $password = (string) $request->input('password');
        $role = null;
        if (hash_equals((string) config('site.super_admin_password'), $password)) {
            $role = 'super';
        } elseif (hash_equals((string) config('site.cms_password'), $password)) {
            $role = 'admin';
        }

        if ($role === null) {
            return back()->withErrors(['password' => 'Incorrect password.']);
        }

        $request->session()->regenerate();
        $request->session()->put('cms_authenticated', true);
        $request->session()->put('cms_super_admin', $role === 'super');

        $response = redirect()->route('admin.dashboard');

        // "Keep me signed in" → 30-day encrypted, http-only persistent-login cookie.
        if ($request->boolean('remember')) {
            $response->withCookie(cookie(CmsAuth::REMEMBER_COOKIE, CmsAuth::rememberToken($role), 60 * 24 * 30));
        } else {
            $response->withCookie(Cookie::forget(CmsAuth::REMEMBER_COOKIE));
        }

        return $response;
    }

    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('cms_authenticated');
        $request->session()->forget('cms_super_admin');

        return redirect()->route('admin.login')->withCookie(Cookie::forget(CmsAuth::REMEMBER_COOKIE));
    }

    /* ───────────────────────── CRUD ───────────────────────── */

    public function index(): View
    {
        return view('admin.blog.index', [
            'posts' => $this->store->all(),
        ]);
    }

    public function create(): View
    {
        return view('admin.blog.form', [
            'post' => $this->blankPost(),
            'mode' => 'create',
            'categories' => $this->categories(),
            'linkTargets' => $this->linkTargets(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePost($request);
        $post = $this->buildPost($data, null, $request->boolean('featured'), $request->boolean('visible'), $request->boolean('show_cta'));
        $this->store->save($post, null);
        if ($post['featured']) {
            $this->store->makeSoleFeatured($post['slug']);
        }

        return redirect()
            ->route('admin.blog.edit', $post['slug'])
            ->with('status', 'Post created.');
    }

    public function edit(string $slug): View|RedirectResponse
    {
        $post = $this->store->find($slug);
        if (! $post) {
            return redirect()->route('admin.blog.index')->with('status', 'Post not found.');
        }

        return view('admin.blog.form', [
            'post' => $post,
            'mode' => 'edit',
            'categories' => $this->categories(),
            'linkTargets' => $this->linkTargets(),
        ]);
    }

    public function update(Request $request, string $slug): RedirectResponse
    {
        if (! $this->store->find($slug)) {
            return redirect()->route('admin.blog.index')->with('status', 'Post not found.');
        }

        $data = $this->validatePost($request);
        $post = $this->buildPost($data, $slug, $request->boolean('featured'), $request->boolean('visible'), $request->boolean('show_cta'));
        $this->store->save($post, $slug);
        if ($post['featured']) {
            $this->store->makeSoleFeatured($post['slug']);
        }

        return redirect()
            ->route('admin.blog.edit', $post['slug'])
            ->with('status', 'Post saved.');
    }

    public function destroy(string $slug): RedirectResponse
    {
        $this->store->delete($slug);

        return redirect()->route('admin.blog.index')->with('status', 'Post deleted.');
    }

    public function toggleVisibility(Request $request, string $slug): RedirectResponse|JsonResponse
    {
        $post = $this->store->find($slug);
        if (! $post) {
            return $this->toggleResponse($request, $slug, 'Post not found.');
        }

        $new = ! ($post['visible'] ?? true);
        $this->store->setVisibility($slug, $new);

        // A hidden post cannot be the featured one — drop the feature flag.
        if (! $new && ! empty($post['featured'])) {
            $this->store->makeSoleFeatured('__none__');
        }

        return $this->toggleResponse(
            $request,
            $slug,
            $new ? "“{$post['title']}” is now visible." : "“{$post['title']}” is hidden from the blog."
        );
    }

    public function toggleFeatured(Request $request, string $slug): RedirectResponse|JsonResponse
    {
        $post = $this->store->find($slug);
        if (! $post) {
            return $this->toggleResponse($request, $slug, 'Post not found.');
        }

        if (! empty($post['featured'])) {
            $this->store->makeSoleFeatured('__none__'); // clears all
            $message = "“{$post['title']}” is no longer featured.";
        } else {
            $this->store->makeSoleFeatured($slug);
            $this->store->setVisibility($slug, true); // featured posts are always visible
            $message = "“{$post['title']}” is now the featured post.";
        }

        return $this->toggleResponse($request, $slug, $message);
    }

    /** Uniform response for the quick toggles: JSON for AJAX, redirect-back otherwise. */
    private function toggleResponse(Request $request, string $slug, string $message): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            $current = $this->store->find($slug);
            $featuredSlug = null;
            foreach ($this->store->all() as $p) {
                if (! empty($p['featured'])) {
                    $featuredSlug = $p['slug'];
                    break;
                }
            }

            return response()->json([
                'ok' => true,
                'slug' => $slug,
                'visible' => ($current['visible'] ?? true) === true,
                'featuredSlug' => $featuredSlug,
                'message' => $message,
            ]);
        }

        return back()->with('status', $message);
    }

    public function reorder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'slugs' => ['required', 'array'],
            'slugs.*' => ['string'],
        ]);

        $this->store->reorder($data['slugs']);

        return response()->json(['ok' => true]);
    }

    /* ───────────────────────── Image upload ───────────────────────── */

    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'image', 'max:5120'], // 5 MB
        ]);

        $path = $request->file('file')->store('blog', 'public');

        return response()->json([
            'url' => asset('storage/'.$path),
        ]);
    }

    /* ───────────────────────── Helpers ───────────────────────── */

    private function validatePost(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:200'],
            'seo_title' => ['nullable', 'string', 'max:200'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'categories' => ['nullable', 'array'],
            'categories.*' => ['string', 'max:100'],
            'author' => ['nullable', 'string', 'max:100'],
            'date' => ['required', 'date'],
            'read_time' => ['nullable', 'integer', 'min:1', 'max:120'],
            'excerpt' => ['nullable', 'string', 'max:400'],
            'image' => ['nullable', 'string', 'max:500'],
            'alt' => ['nullable', 'string', 'max:300'],
            'body' => ['nullable', 'string'],
            'link_target' => ['nullable', 'string', 'max:500'],
            'link_url_custom' => ['nullable', 'string', 'max:500'],
        ]);
    }

    private function buildPost(array $data, ?string $originalSlug, bool $featured = false, bool $visible = true, bool $showCta = true): array
    {
        $body = $this->sanitizeBody($data['body'] ?? '');
        $desiredSlug = $data['slug'] ?: $data['title'];
        $bodyText = Seo::blogBodyText($body);
        $excerpt = Seo::description(
            $data['excerpt'] ?? '',
            $bodyText ?: 'Study abroad advice from One Degree Advisory.',
            240
        );
        $seoTitle = Seo::title($data['seo_title'] ?? '', '', 90);
        $metaDescription = Seo::description($data['meta_description'] ?? '', '', 170);

        $categories = array_values(array_unique(array_filter(array_map(
            fn ($c) => trim((string) $c),
            $data['categories'] ?? []
        ), fn ($c) => $c !== '')));
        if ($categories === []) {
            $categories = ['One Degree'];
        }

        // A non-empty link makes this post a "redirect" entry — its cards point at
        // another page (existing route, page-builder page, or custom URL) and
        // /blog/{slug} 302s there instead of rendering an article.
        $linkUrl = $this->sanitizeLink(
            ($data['link_target'] ?? '') === '__custom__'
                ? (string) ($data['link_url_custom'] ?? '')
                : (string) ($data['link_target'] ?? '')
        );

        return [
            'slug' => $this->store->uniqueSlug($desiredSlug, $originalSlug),
            'title' => trim($data['title']),
            'seo_title' => $seoTitle,
            'meta_description' => $metaDescription,
            'categories' => $categories,
            'category' => $categories[0], // primary category, kept for the public templates
            'date' => $data['date'],
            'read_time' => isset($data['read_time']) ? (int) $data['read_time'] : null, // blank = hidden everywhere
            'author' => trim($data['author'] ?? '') ?: 'One Degree',
            'excerpt' => $excerpt,
            'image' => trim($data['image'] ?? '') ?: '/assets/heroes/uk.webp',
            'alt' => trim($data['alt'] ?? ''),
            'featured' => $featured,
            'visible' => $visible || $featured, // a featured post is always visible
            'show_cta' => $showCta, // show the "Book a free strategy call" block at the end
            'link_url' => $linkUrl, // redirect target; '' = normal article
            'body' => $body,
        ];
    }

    /**
     * Normalize a redirect target. Absolute URLs, root-relative paths, anchors,
     * and mailto/tel links are kept as-is; anything else becomes a root-relative
     * path so an admin can type "europe" and get "/europe".
     */
    private function sanitizeLink(string $raw): string
    {
        $link = trim($raw);
        if ($link === '') {
            return '';
        }

        if (preg_match('~^(https?://|/|#|mailto:|tel:)~i', $link)) {
            return $link;
        }

        return '/'.ltrim($link, '/');
    }

    /**
     * Pages an admin can point a blog card at, grouped for the editor dropdown:
     * the site's built-in pages plus every page-builder (brief) page. URLs are
     * root-relative so they work across environments.
     */
    private function linkTargets(): array
    {
        $named = [
            'home' => 'Home',
            'about' => 'About',
            'study-abroad' => 'Study Abroad',
            'blog.index' => 'Blog',
            'contact' => 'Contact',
            'careers' => 'Careers',
            'services.test-prep' => 'Services — Test Preparation',
            'services.admissions-counselling' => 'Services — Admissions Counselling',
            'services.student-services' => 'Services — Student Services',
            'courses.ug' => 'Courses — Undergraduate',
            'courses.pg' => 'Courses — Postgraduate',
            'courses.llb' => 'Courses — LLB',
            'courses.mba' => 'Courses — MBA',
            'courses.doctoral' => 'Courses — Doctoral',
            'mbbs.student' => 'MBBS — Student',
        ];

        $existing = [];
        foreach ($named as $name => $label) {
            if (Route::has($name)) {
                $existing[] = ['url' => route($name, [], false), 'label' => $label];
            }
        }

        $builder = [];
        foreach (app(BriefPageStore::class)->all() as $page) {
            $path = trim((string) ($page['path'] ?? ''));
            if ($path === '') {
                $path = '/briefs/'.($page['slug'] ?? '');
            }
            $label = (string) ($page['title'] ?? $page['slug'] ?? $path);
            if (($page['visible'] ?? true) !== true) {
                $label .= ' (hidden)';
            }
            $builder[] = ['url' => $path, 'label' => $label];
        }

        return [
            'Existing pages' => $existing,
            'Page builder pages' => $builder,
        ];
    }

    /** Decode and normalize the JSON body payload from the block editor. */
    private function sanitizeBody(string $json): array
    {
        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            return [];
        }

        $clean = [];
        foreach ($decoded as $block) {
            if (! is_array($block) || ! in_array($block['kind'] ?? '', self::KINDS, true)) {
                continue;
            }

            switch ($block['kind']) {
                case 'p':
                case 'h2':
                    $text = trim((string) ($block['text'] ?? ''));
                    if ($text !== '') {
                        $clean[] = ['kind' => $block['kind'], 'text' => $text];
                    }
                    break;

                case 'list':
                    $items = array_values(array_filter(array_map(
                        fn ($i) => trim((string) $i),
                        is_array($block['items'] ?? null) ? $block['items'] : []
                    ), fn ($i) => $i !== ''));
                    if ($items !== []) {
                        $clean[] = ['kind' => 'list', 'items' => $items];
                    }
                    break;

                case 'table':
                    $rows = [];
                    foreach (is_array($block['rows'] ?? null) ? $block['rows'] : [] as $row) {
                        $cells = array_map(fn ($c) => trim((string) $c), is_array($row) ? $row : []);
                        if (implode('', $cells) !== '') {
                            $rows[] = array_values($cells);
                        }
                    }
                    if ($rows !== []) {
                        $clean[] = ['kind' => 'table', 'rows' => $rows];
                    }
                    break;

                case 'quote':
                    $text = trim((string) ($block['text'] ?? ''));
                    if ($text !== '') {
                        $clean[] = array_filter([
                            'kind' => 'quote',
                            'text' => $text,
                            'attribution' => trim((string) ($block['attribution'] ?? '')),
                        ], fn ($v) => $v !== '');
                    }
                    break;

                case 'image':
                    $url = trim((string) ($block['url'] ?? ''));
                    if ($url !== '') {
                        $clean[] = array_filter([
                            'kind' => 'image',
                            'url' => $url,
                            'alt' => trim((string) ($block['alt'] ?? '')),
                            'caption' => trim((string) ($block['caption'] ?? '')),
                        ], fn ($v) => $v !== '');
                    }
                    break;
            }
        }

        return $clean;
    }

    /** All distinct categories across posts, for autocomplete suggestions. */
    private function categories(): array
    {
        $all = [];
        foreach ($this->store->all() as $post) {
            foreach ($post['categories'] ?? array_filter([$post['category'] ?? null]) as $c) {
                $all[] = $c;
            }
        }

        sort($all);

        return array_values(array_unique(array_filter($all)));
    }

    private function blankPost(): array
    {
        return [
            'slug' => '',
            'title' => '',
            'seo_title' => '',
            'meta_description' => '',
            'categories' => ['College Admissions'],
            'category' => 'College Admissions',
            'date' => now()->toDateString(),
            'read_time' => '',
            'author' => 'One Degree',
            'excerpt' => '',
            'image' => '',
            'alt' => '',
            'featured' => false,
            'visible' => true,
            'show_cta' => true,
            'link_url' => '',
            'body' => [],
        ];
    }
}
