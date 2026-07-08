<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\CareerLibraryStore;
use App\Support\PersistsInlineImages;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/**
 * CMS for the Global Career Library (/global-career-library): page settings,
 * the career list (order, tile look, trending/visible flags) and every field
 * of each career's report, per country|language variant.
 *
 * Careers are edited on a LIVE editor — the real public career page rendered
 * with inline-editing hooks (career-library.show with $live = true) plus the
 * editor chrome partial. Entirely self-contained; no external service.
 */
class CareerLibraryCmsController extends Controller
{
    use PersistsInlineImages;

    public function __construct(private CareerLibraryStore $store)
    {
    }

    public function index(): View
    {
        return view('admin.career-library.index', [
            'settings' => $this->store->settings(),
            'careers' => $this->store->careers(),
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'hero_title_prefix' => 'nullable|string|max:120',
            'hero_title_highlight' => 'nullable|string|max:120',
            'hero_subtitle' => 'nullable|string|max:300',
            'search_placeholder' => 'nullable|string|max:120',
            'trending_heading' => 'nullable|string|max:120',
            'explore_button' => 'nullable|string|max:120',
            'contact_email' => 'nullable|email|max:120',
            'contact_phone' => 'nullable|string|max:40',
            'next_steps_url' => 'nullable|url|max:300',
            'report_year' => 'nullable|string|max:8',
        ]);

        $settings = array_map(fn ($v) => (string) ($v ?? ''), $validated);
        // Checkbox — absent means off. Kept out of the string cast above so it
        // stays a real boolean for the store.
        $settings['detail_pages_enabled'] = $request->boolean('detail_pages_enabled');

        $this->store->updateSettings($settings);

        return redirect()->route('admin.career-library.index')->with('status', 'Career Library settings saved.');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:120',
        ]);

        $title = trim($validated['title']);
        $slug = CareerLibraryStore::slugify($title);

        if ($this->store->findBySlug($slug)) {
            return redirect()->route('admin.career-library.index')
                ->with('error', "A career with slug “{$slug}” already exists.");
        }

        $this->store->upsertCareer([
            'slug' => $slug,
            'title' => $title,
            'iconType' => 'generic',
            'bg' => 'bg-indigo-100',
            'text' => 'text-indigo-600',
            'trending' => false,
            'visible' => true,
            'data' => [CareerLibraryStore::DEFAULT_VARIANT => $this->store->normalizeCareerData(['title' => $title])],
        ]);

        return redirect()->route('admin.career-library.live', $slug)
            ->with('status', "“{$title}” created — click any text on the page to edit it.");
    }

    /**
     * The live editor: the REAL public career page rendered with $live = true,
     * which adds the inline-editing hooks and pulls in the editor chrome.
     */
    public function live(Request $request, string $slug): View
    {
        $career = $this->store->findBySlug($slug);
        abort_unless($career !== null, 404);

        $variants = array_keys($career['data']);
        $variant = (string) $request->query('variant', CareerLibraryStore::DEFAULT_VARIANT);
        if (! in_array($variant, $variants, true)) {
            $variant = $variants[0] ?? CareerLibraryStore::DEFAULT_VARIANT;
        }

        [$countryName, $language] = array_pad(explode('|', $variant, 2), 2, '');
        $countryName = $countryName !== '' ? $countryName : 'India';
        $language = $language !== '' ? $language : 'English';

        $countryCode = array_search($countryName, CareerLibraryStore::COUNTRIES, true) ?: 'IN';
        $langCode = CareerLibraryStore::LANGUAGE_CODES[$language] ?? 'en-IN';
        $publicUrl = url('/global-career-library/'.strtolower($countryCode).'/'.str_replace(' ', '-', $career['title']).'/'.$langCode);

        return view('career-library.show', [
            'live' => true,
            'settings' => $this->store->settings(),
            'data' => $career['data'][$variant] ?? $this->store->normalizeCareerData(['title' => $career['title']]),
            'careerName' => $career['title'],
            'searchName' => $career['title'],
            'countryName' => $countryName,
            'language' => $language,
            'career' => $career,
            'variant' => $variant,
            'variants' => $variants,
            'iconTypes' => CareerLibraryStore::ICON_TYPES,
            'countries' => array_values(CareerLibraryStore::COUNTRIES),
            'languages' => array_keys(CareerLibraryStore::LANGUAGE_CODES),
            'publicUrl' => $publicUrl,
        ]);
    }

    /**
     * Persist a live-editor save: `{variant, career: {tile fields}, report: {…}}`.
     * The report arrives in the exact nested shape normalizeCareerData() accepts,
     * so all sanitisation is reused as-is. Freshly-cropped thumbnails arrive as
     * inline data URLs and are written to disk here (only on a real save).
     */
    public function liveSave(Request $request, string $slug): JsonResponse
    {
        $career = $this->store->findBySlug($slug);
        if ($career === null) {
            return response()->json(['ok' => false, 'message' => 'Unknown career.'], 404);
        }

        $variant = (string) $request->input('variant');
        if (! isset($career['data'][$variant]) && $variant !== CareerLibraryStore::DEFAULT_VARIANT) {
            return response()->json(['ok' => false, 'message' => 'Unknown variant.'], 422);
        }

        $tile = (array) $request->input('career', []);
        $title = trim((string) ($tile['title'] ?? ''));
        if ($title !== '') {
            $career['title'] = mb_substr($title, 0, 120);
        }
        $iconType = (string) ($tile['iconType'] ?? $career['iconType']);
        if (in_array($iconType, CareerLibraryStore::ICON_TYPES, true)) {
            $career['iconType'] = $iconType;
        }
        $bg = mb_substr(trim((string) ($tile['bg'] ?? '')), 0, 40);
        $text = mb_substr(trim((string) ($tile['text'] ?? '')), 0, 40);
        $career['bg'] = $bg !== '' ? $bg : $career['bg'];
        $career['text'] = $text !== '' ? $text : $career['text'];
        $career['trending'] = (bool) ($tile['trending'] ?? $career['trending']);
        $career['visible'] = (bool) ($tile['visible'] ?? $career['visible']);

        $report = $this->persistInlineImages((array) $request->input('report', []), 'career-library-videos');
        $career['data'][$variant] = $this->store->normalizeCareerData($report);

        $this->store->upsertCareer($career);

        return response()->json(['ok' => true, 'message' => 'Career saved.']);
    }

    /** Quick trending/visible toggles from the list page. */
    public function updateFlags(Request $request, string $slug): RedirectResponse
    {
        $career = $this->store->findBySlug($slug);
        abort_unless($career !== null, 404);

        if ($request->has('trending')) {
            $career['trending'] = $request->boolean('trending');
        }
        if ($request->has('visible')) {
            $career['visible'] = $request->boolean('visible');
        }

        $this->store->upsertCareer($career);

        return back()->with('status', "“{$career['title']}” updated.");
    }

    public function reorder(Request $request): JsonResponse
    {
        $slugs = $request->input('order', []);
        abort_unless(is_array($slugs), 422);

        $this->store->reorder(array_map('strval', $slugs));

        return response()->json(['ok' => true]);
    }

    public function destroy(string $slug): RedirectResponse
    {
        $career = $this->store->findBySlug($slug);
        abort_unless($career !== null, 404);

        $this->store->deleteCareer($slug);

        return redirect()->route('admin.career-library.index')
            ->with('status', "“{$career['title']}” deleted.");
    }

    /** Add a country|language variant to a career by copying its default (or first) variant. */
    public function addVariant(Request $request, string $slug): RedirectResponse
    {
        $career = $this->store->findBySlug($slug);
        abort_unless($career !== null, 404);

        $validated = $request->validate([
            'country' => 'required|string|max:60',
            'language' => 'required|string|max:40',
        ]);

        $country = $validated['country'];
        $language = $validated['language'];
        if (! in_array($country, CareerLibraryStore::COUNTRIES, true)
            || ! array_key_exists($language, CareerLibraryStore::LANGUAGE_CODES)) {
            return back()->with('error', 'Unknown country or language.');
        }

        $key = "{$country}|{$language}";
        if (isset($career['data'][$key])) {
            return redirect()->route('admin.career-library.live', ['slug' => $slug, 'variant' => $key])
                ->with('status', 'That variant already exists — editing it now.');
        }

        $career['data'][$key] = $career['data'][CareerLibraryStore::DEFAULT_VARIANT]
            ?? (reset($career['data']) ?: $this->store->normalizeCareerData(['title' => $career['title']]));
        $this->store->upsertCareer($career);

        return redirect()->route('admin.career-library.live', ['slug' => $slug, 'variant' => $key])
            ->with('status', "Variant {$key} added.");
    }

    public function deleteVariant(Request $request, string $slug): RedirectResponse
    {
        $career = $this->store->findBySlug($slug);
        abort_unless($career !== null, 404);

        $key = (string) $request->input('variant');
        if (count($career['data']) <= 1) {
            return back()->with('error', 'A career needs at least one variant.');
        }

        unset($career['data'][$key]);
        $this->store->upsertCareer($career);

        return redirect()->route('admin.career-library.live', $slug)
            ->with('status', "Variant {$key} removed.");
    }

    /**
     * Media upload from the live editor: video thumbnails (images, later
     * cropped client-side) and attached video files. Stored on the public disk.
     */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,gif,mp4,webm,ogg,mov', 'max:102400'], // 100 MB
        ]);

        $path = $request->file('file')->store('career-library-videos', 'public');

        return response()->json([
            'url' => asset('storage/'.$path),
        ]);
    }

    /**
     * Fetch a remote image and return it as a same-origin base64 data URL, so
     * the browser can crop it without tainting the canvas. Nothing is written
     * to disk here — the cropped result is only persisted on save.
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
        if (strlen($body) > 8 * 1024 * 1024) {
            return response()->json(['ok' => false, 'message' => 'Image is larger than 8 MB.'], 422);
        }

        return response()->json([
            'ok' => true,
            'url' => 'data:'.$type.';base64,'.base64_encode($body),
        ]);
    }
}
