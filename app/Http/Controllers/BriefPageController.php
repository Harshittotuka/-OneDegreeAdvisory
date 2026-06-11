<?php

namespace App\Http\Controllers;

use App\Support\BriefPageStore;
use Illuminate\Contracts\View\View;

/**
 * Renders a CMS-built "brief" page (the .odp-* design system) from the
 * BriefPageStore. The four seeded pages keep their original top-level URLs
 * (route defaults supply their slug); every other page is resolved by its
 * custom path via the fallback route (showByPath). Hidden pages stay 404 for
 * the public but remain viewable by a logged-in super-admin, so "Save → View"
 * always works while drafting.
 */
class BriefPageController extends Controller
{
    public function __construct(private BriefPageStore $store)
    {
    }

    public function show(string $slug): View
    {
        $page = $this->store->find($slug);
        if ($page === null) {
            abort(404);
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
