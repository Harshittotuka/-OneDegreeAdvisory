<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\BlogContent;
use App\Support\HeroContent;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class HomeHeroCmsController extends Controller
{
    public function __construct(private HeroContent $hero)
    {
    }

    /**
     * The live editor renders the REAL home page (so it looks identical to the
     * live site) with the hero instrumented for inline editing. Every other
     * section is greyed out and locked by the editor chrome (cmsEdit flag).
     */
    public function live(BlogContent $blog): View
    {
        return view('pages.home', [
            'insights' => $blog->homeInsights(),
            'hero' => $this->hero->current(),
            'heroEdit' => true,   // → partials.home.hero renders edit hooks
            'cmsEdit' => true,    // → layouts.app injects the editor chrome
        ]);
    }

    /** Persist the whole hero from the live editor. */
    public function liveSave(Request $request): JsonResponse
    {
        $data = $request->input('data');
        if (! is_array($data)) {
            return response()->json(['ok' => false, 'message' => 'Bad payload.'], 422);
        }

        $this->hero->save($this->hero->sanitize($data));

        return response()->json(['ok' => true, 'message' => 'Hero saved.']);
    }

    /** Image upload (raw or cropped blob) → public storage. */
    public function upload(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'image', 'max:8192'], // 8 MB
        ]);

        $path = $request->file('file')->store('home-hero', 'public');

        return response()->json([
            'url' => asset('storage/'.$path),
        ]);
    }

    /**
     * Download a remote image to local public storage and return a same-origin
     * URL. Lets the browser crop remote images (e.g. Unsplash) without the
     * canvas being tainted by cross-origin pixels.
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

        $ext = match (true) {
            str_contains($type, 'png') => 'png',
            str_contains($type, 'webp') => 'webp',
            str_contains($type, 'gif') => 'gif',
            str_contains($type, 'svg') => 'svg',
            default => 'jpg',
        };
        $name = 'home-hero/import-'.bin2hex(random_bytes(6)).'.'.$ext;
        Storage::disk('public')->put($name, $body);

        return response()->json(['ok' => true, 'url' => asset('storage/'.$name)]);
    }

    /**
     * Stash the current (possibly unsaved) hero in the session so the full home
     * page can be previewed with it via ?__hero_preview=1, without publishing.
     */
    public function preview(Request $request): JsonResponse
    {
        $data = $request->input('data');
        if (! is_array($data)) {
            return response()->json(['ok' => false, 'message' => 'Bad payload.'], 422);
        }

        $request->session()->put('home_hero_preview', $this->hero->sanitize($data));

        return response()->json([
            'ok' => true,
            'url' => route('home', ['__hero_preview' => 1]),
        ]);
    }
}
