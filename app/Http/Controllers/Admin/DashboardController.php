<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AboutStore;
use App\Support\BlogStore;
use App\Support\CountryDataSync;
use App\Support\HeroContent;
use App\Support\NoticeBarStore;
use App\Support\StudyLocationContent;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;

/**
 * The admin landing page. A read-only overview that pulls live counts from every
 * file-backed CMS store (blog, about, notice bar, hero, country data) and renders
 * them as Materio-style cards. Kept deliberately lightweight — it never runs the
 * country-sync diff, only cheap reads — so the dashboard loads instantly.
 */
class DashboardController extends Controller
{
    public function index(
        BlogStore $blog,
        AboutStore $about,
        NoticeBarStore $notice,
        HeroContent $hero,
        StudyLocationContent $locations,
        CountryDataSync $countrySync,
    ): View {
        $posts = $blog->all();
        $visiblePosts = array_values(array_filter($posts, fn ($p) => ($p['visible'] ?? true) === true));
        $featured = null;
        foreach ($posts as $p) {
            if (! empty($p['featured'])) {
                $featured = $p;
                break;
            }
        }

        $sections = $about->all();
        $visibleSections = array_values(array_filter($sections, fn ($s) => ($s['visible'] ?? true) === true));

        $noticeData = $notice->get();
        $noticeItems = is_array($noticeData['items'] ?? null) ? $noticeData['items'] : [];
        $noticeVisible = array_values(array_filter($noticeItems, fn ($i) => ($i['visible'] ?? true) === true));

        $heroData = $hero->current();
        $heroActions = is_array($heroData['actions'] ?? null) ? $heroData['actions'] : [];

        $destinations = $locations->destinations();

        // Country-sync status — cheap reads only (file mtime + the small status file).
        // We intentionally skip CountryDataSync::state(), which would run a full diff.
        $liveJson = storage_path('app/leverageedu_study_locations_content.json');
        $countryUpdatedAt = is_file($liveJson)
            ? Carbon::createFromTimestamp(filemtime($liveJson))
            : null;

        return view('admin.dashboard', [
            'stats' => [
                'posts_total' => count($posts),
                'posts_visible' => count($visiblePosts),
                'posts_hidden' => count($posts) - count($visiblePosts),
                'featured' => $featured,
                'sections_total' => count($sections),
                'sections_visible' => count($visibleSections),
                'notice_total' => count($noticeItems),
                'notice_visible' => count($noticeVisible),
                'notice_variant' => (string) ($noticeData['variant'] ?? 'original'),
                'hero_actions' => count($heroActions),
                'countries' => count($destinations),
            ],
            'recentPosts' => array_slice($posts, 0, 6),
            'countrySync' => [
                'running' => $countrySync->isRunning(),
                'updated_at' => $countryUpdatedAt,
                'exists' => is_file($liveJson),
            ],
        ]);
    }
}
