<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\CountryVisibilityStore;
use App\Support\MbbsCountryContent;
use App\Support\StudyLocationContent;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CountryVisibilityController extends Controller
{
    public function edit(
        StudyLocationContent $locations,
        MbbsCountryContent $mbbsContent,
        CountryVisibilityStore $visibility
    ): View {
        if (! $this->superOnly()) {
            return $this->inDevelopment();
        }

        return view('admin.country-visibility.edit', [
            'nonMbbsCountries' => $this->withVisibility(
                $locations->allDestinations(),
                CountryVisibilityStore::GROUP_NON_MBBS,
                $visibility
            ),
            'mbbsCountries' => $this->withVisibility(
                $mbbsContent->allCountries(),
                CountryVisibilityStore::GROUP_MBBS,
                $visibility
            ),
        ]);
    }

    public function update(
        Request $request,
        StudyLocationContent $locations,
        MbbsCountryContent $mbbsContent,
        CountryVisibilityStore $visibility
    ): RedirectResponse {
        if (! $this->superOnly()) {
            abort(404);
        }

        $request->validate([
            'visible' => 'array',
            'visible.'.CountryVisibilityStore::GROUP_NON_MBBS => 'array',
            'visible.'.CountryVisibilityStore::GROUP_NON_MBBS.'.*' => 'string|max:120',
            'visible.'.CountryVisibilityStore::GROUP_MBBS => 'array',
            'visible.'.CountryVisibilityStore::GROUP_MBBS.'.*' => 'string|max:120',
        ]);

        $allSlugs = [
            CountryVisibilityStore::GROUP_NON_MBBS => array_column($locations->allDestinations(), 'slug'),
            CountryVisibilityStore::GROUP_MBBS => array_column($mbbsContent->allCountries(), 'slug'),
        ];

        $visibility->saveFromVisible([
            CountryVisibilityStore::GROUP_NON_MBBS => $request->input('visible.'.CountryVisibilityStore::GROUP_NON_MBBS, []),
            CountryVisibilityStore::GROUP_MBBS => $request->input('visible.'.CountryVisibilityStore::GROUP_MBBS, []),
        ], $allSlugs);

        return redirect()
            ->route('admin.country-visibility.index')
            ->with('status', 'Country visibility updated.');
    }

    private function withVisibility(array $countries, string $group, CountryVisibilityStore $visibility): array
    {
        return array_map(function (array $country) use ($group, $visibility): array {
            $country['visible'] = $visibility->isVisible($group, (string) ($country['slug'] ?? ''));

            return $country;
        }, $countries);
    }

    /**
     * Country visibility is a super-admin-only tool. A standard CMS login does
     * not see the nav entry; a direct visit gets the same "in development"
     * placeholder used elsewhere for super-only sections.
     */
    private function superOnly(): bool
    {
        return (bool) session('cms_super_admin');
    }

    private function inDevelopment(): View
    {
        return view('admin.in-development', [
            'title' => 'Country visibility',
            'message' => 'This section is in development.',
        ]);
    }
}
