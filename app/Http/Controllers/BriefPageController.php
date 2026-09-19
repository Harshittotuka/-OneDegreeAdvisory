<?php

namespace App\Http\Controllers;

use App\Support\BriefPageStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Renders a CMS-built "brief" page (the .odp-* design system) from the
 * BriefPageStore. The four seeded pages keep their original top-level URLs
 * (route defaults supply their slug) until an editor moves them, after which
 * the original URL redirects to the new one; every other page is resolved by
 * its custom path via the fallback route (showByPath). Hidden pages stay 404 for
 * the public but remain viewable by a logged-in super-admin, so "Save → View"
 * always works while drafting.
 */
class BriefPageController extends Controller
{
    public function __construct(private BriefPageStore $store)
    {
    }

    public function show(string $slug): View|RedirectResponse
    {
        $requestPath = '/'.ltrim(request()->path(), '/');

        // A page's public URL is whatever it stores in `path`, so resolve by
        // that before falling back to the slug. /briefs/{slug} used to look the
        // slug up directly, which 404'd any page pointed at a /briefs/ URL that
        // was not its own slug -- an editor moving a page to /briefs/scholarship
        // while its slug was scholarship-4 got a 404 they could not explain.
        $page = $this->store->findByPath($requestPath) ?? $this->store->find($slug);
        if ($page === null) {
            abort(404);
        }

        // The four seeded URLs are hardcoded routes, so they keep answering even
        // after an editor moves the page in the Page Builder. Send visitors on
        // to the path the page now claims, or renaming one would appear to do
        // nothing. Temporary, not permanent: an editor can move a page back and
        // a cached 301 would strand the original URL.
        $path = $page['path'] ?? null;
        if (is_string($path) && $path !== '' && $path !== $requestPath) {
            return redirect()->to($path);
        }

        return $this->render($page);
    }

    /** Fallback-route handler: resolve a page by its custom URL path. */
    public function showByPath(): View
    {
        $page = $this->store->findByPath('/'.ltrim(request()->path(), '/'));
        if ($page === null) {
            abort(404);
        }

        return $this->render($page);
    }

    private function render(array $page): View
    {
        if (! ($page['visible'] ?? true) && ! session('cms_super_admin')) {
            abort(404);
        }

        return view('pages.brief', ['page' => $page]);
    }
}
