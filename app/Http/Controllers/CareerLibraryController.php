<?php

namespace App\Http\Controllers;

use App\Support\CareerLibraryStore;
use App\Support\ProfileSubmissionStore;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public Global Career Library — a fully self-contained careers explorer.
 * Every career shown is curated in the CMS (storage/app/career-library.json);
 * there is no external service involved in serving a page.
 */
class CareerLibraryController extends Controller
{
    public function __construct(
        private CareerLibraryStore $store,
        private ProfileSubmissionStore $submissions,
    ) {
    }

    public function index(): View
    {
        return view('career-library.index', array_merge([
            'settings' => $this->store->settings(),
            'careers' => $this->store->visibleCareers(),
            'error' => trim((string) request()->query('error')) ?: null,
        ], $this->store->availableFilters()));
    }

    /**
     * Detail page: /global-career-library/{country}/{career}/{lang}
     * e.g. /global-career-library/in/Data-Science/en-IN — the exact URL shape
     * of the original page (career title hyphenated, original casing kept).
     */
    public function show(string $country, string $career, string $lang): View
    {
        $countryName = CareerLibraryStore::COUNTRIES[strtoupper($country)] ?? 'India';
        $language = array_search(strtolower($lang), array_map('strtolower', CareerLibraryStore::LANGUAGE_CODES), true) ?: 'English';
        $careerName = trim(str_replace('-', ' ', $career));

        $entry = $this->store->findByName($careerName);
        $data = $entry ? $this->store->variantFor($entry, $countryName, $language) : null;

        if (! $data) {
            return view('career-library.index', [
                'settings' => $this->store->settings(),
                'careers' => $this->store->visibleCareers(),
                'error' => 'That career is not in the library yet.',
                'prefill' => ['careerName' => $careerName, 'country' => $countryName, 'language' => $language],
            ]);
        }

        return view('career-library.show', [
            'settings' => $this->store->settings(),
            'data' => $data,
            'careerName' => $data['title'] !== '' ? $data['title'] : $careerName,
            'searchName' => $careerName,
            'countryName' => $countryName,
            'language' => $language,
        ]);
    }

    /**
     * Pre-navigation check used by the landing search form: make sure a
     * curated report exists for the requested career, and hand back the
     * detail URL to redirect to (falling back to the career's default
     * variant when the exact country/language isn't curated).
     */
    public function ensure(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'careerName' => 'required|string|max:120',
            'country' => 'required|string|max:60',
            'language' => 'required|string|max:40',
        ]);

        $careerName = trim($validated['careerName']);
        $countryCode = $this->resolveCountryCode($validated['country']);
        if ($countryCode === null) {
            return response()->json(['ok' => false, 'error' => 'Invalid country.'], 422);
        }

        $language = $validated['language'];
        $langCode = CareerLibraryStore::LANGUAGE_CODES[$language] ?? null;
        if ($langCode === null) {
            return response()->json(['ok' => false, 'error' => 'Invalid language.'], 422);
        }

        $entry = $this->store->findByName($careerName);
        if (! $entry) {
            return response()->json([
                'ok' => false,
                'error' => 'That career is not in the library yet.',
            ], 422);
        }

        $urlName = str_replace(' ', '-', $entry['title']);

        return response()->json([
            'ok' => true,
            'redirect' => url('/global-career-library/'.strtolower($countryCode).'/'.$urlName.'/'.$langCode),
        ]);
    }

    /**
     * Capture a visitor's contact details before they view a career report.
     * The report page shows a blocking form; on submit this records the lead
     * (into the shared profile-submissions store, source "career-library") and
     * the front-end unlocks the page for the rest of the session.
     */
    public function lead(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:190',
            'phone' => 'required|string|max:40',
            // Context — which career/place they were about to open. Optional so a
            // lead is still captured even if the front-end couldn't supply it.
            'career' => 'nullable|string|max:200',
            'country' => 'nullable|string|max:60',
            'language' => 'nullable|string|max:40',
        ]);

        $name = trim($validated['name']);
        $email = trim($validated['email']);
        $phone = trim($validated['phone']);
        $career = trim((string) ($validated['career'] ?? ''));
        $country = trim((string) ($validated['country'] ?? ''));
        $language = trim((string) ($validated['language'] ?? ''));

        // A human-readable snapshot in the same shape ProfileSubmissionStore uses
        // for the profiler/evaluator, so the admin viewer + exports render it the
        // same way (section → question → answer).
        $answers = [
            ['label' => 'Name', 'value' => [$name]],
            ['label' => 'Email', 'value' => [$email]],
            ['label' => 'Phone', 'value' => [$phone]],
        ];
        if ($career !== '') {
            $answers[] = ['label' => 'Career', 'value' => [$career]];
        }
        if ($country !== '') {
            $answers[] = ['label' => 'Country', 'value' => [$country]];
        }
        if ($language !== '') {
            $answers[] = ['label' => 'Language', 'value' => [$language]];
        }

        $sections = [[
            'eyebrow' => 'Trending Career',
            'title'   => 'Contact request',
            'answers' => $answers,
        ]];

        $this->submissions->add(
            'career-library',
            'Trending Career',
            null,
            $sections,
            ['name' => $name, 'email' => $email, 'phone' => $phone],
        );

        return response()->json(['ok' => true]);
    }

    /** Match the original page's forgiving country matching (name or code substring). */
    private function resolveCountryCode(string $input): ?string
    {
        $needle = mb_strtolower(trim($input));
        if ($needle === '') {
            return null;
        }

        foreach (CareerLibraryStore::COUNTRIES as $code => $name) {
            if (str_contains(mb_strtolower($name), $needle) || str_contains(strtolower($code), $needle)) {
                return $code;
            }
        }

        return null;
    }
}
