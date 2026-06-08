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
}
