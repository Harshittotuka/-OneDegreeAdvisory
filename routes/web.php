<?php

use App\Http\Controllers\Admin\AboutCmsController;
use App\Http\Controllers\Admin\BlogCmsController;
use App\Http\Controllers\Admin\CountryVisibilityController;
use App\Http\Controllers\Admin\CountryDataSyncController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HomeHeroCmsController;
use App\Http\Controllers\Admin\MbbsCountryDataSyncController;
use App\Http\Controllers\Admin\NoticeBarCmsController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/index.html', [PageController::class, 'home'])->name('home.legacy');

Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/about.html', [PageController::class, 'about'])->name('about.legacy');

Route::get('/careers', [PageController::class, 'careers'])->name('careers');
Route::post('/careers', [PageController::class, 'submitCareer'])->name('careers.submit');

Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::get('/contact.html', [PageController::class, 'contact'])->name('contact.legacy');
Route::post('/contact', [PageController::class, 'submitContact'])->name('contact.submit');

Route::get('/blog', [PageController::class, 'blogIndex'])->name('blog.index');
Route::get('/blog/{slug}', [PageController::class, 'blogPost'])
    ->where('slug', '[a-z0-9-]+')
    ->name('blog.post');

Route::get('/services/test-preparation', [PageController::class, 'testPreparation'])->name('services.test-prep');
Route::get('/services/admissions-counselling', [PageController::class, 'admissionsCounselling'])->name('services.admissions-counselling');
Route::get('/services/student-services', [PageController::class, 'studentServices'])->name('services.student-services');

Route::get('/study-abroad', [PageController::class, 'studyAbroad'])->name('study-abroad');

Route::get('/europe', [PageController::class, 'europe'])->name('europe');
Route::redirect('/packages', '/europe');

// Intelligence briefs — surfaced from the top notice bar, package-page styling.
Route::get('/wednesday-briefings', [PageController::class, 'wednesdayBriefings'])->name('wednesday-briefings');
Route::get('/medicine-and-beyond', [PageController::class, 'medicineAndBeyond'])->name('medicine-and-beyond');
Route::get('/destination-new-zealand', [PageController::class, 'destinationNewZealand'])->name('destination-new-zealand');

Route::get('/courses/undergraduate', [PageController::class, 'undergraduate'])->name('courses.ug');
Route::get('/courses/postgraduate', [PageController::class, 'postgraduate'])->name('courses.pg');
Route::get('/courses/llb', [PageController::class, 'llb'])->name('courses.llb');
Route::get('/courses/mba', [PageController::class, 'mba'])->name('courses.mba');
Route::get('/courses/doctoral', [PageController::class, 'doctoral'])->name('courses.doctoral');

Route::get('/mbbs/student', [PageController::class, 'mbbsStudent'])->name('mbbs.student');

Route::get('/mbbs/country/{country}', [PageController::class, 'mbbsCountry'])
    ->where('country', '[a-z0-9-]+')
    ->name('mbbs.country');

Route::get('/countries/{country}', [PageController::class, 'country'])
    ->where('country', '[A-Za-z0-9-]+')
    ->name('country.show');

/* ───────────────────────── Blog CMS (admin) ───────────────────────── */
Route::prefix('admin')->group(function () {
    Route::get('login', [BlogCmsController::class, 'showLogin'])->name('admin.login');
    Route::post('login', [BlogCmsController::class, 'login'])->name('admin.login.attempt');
    Route::post('logout', [BlogCmsController::class, 'logout'])->name('admin.logout');

    Route::middleware('cms.auth')->group(function () {
        Route::get('/', fn () => redirect()->route('admin.dashboard'));
        Route::get('dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');

        Route::get('blog', [BlogCmsController::class, 'index'])->name('admin.blog.index');
        Route::get('blog/create', [BlogCmsController::class, 'create'])->name('admin.blog.create');
        Route::post('blog', [BlogCmsController::class, 'store'])->name('admin.blog.store');
        Route::post('blog/upload', [BlogCmsController::class, 'upload'])->name('admin.blog.upload');
        Route::post('blog/reorder', [BlogCmsController::class, 'reorder'])->name('admin.blog.reorder');
        Route::post('blog/{slug}/visibility', [BlogCmsController::class, 'toggleVisibility'])
            ->where('slug', '[a-z0-9-]+')->name('admin.blog.visibility');
        Route::post('blog/{slug}/featured', [BlogCmsController::class, 'toggleFeatured'])
            ->where('slug', '[a-z0-9-]+')->name('admin.blog.featured');
        Route::get('blog/{slug}/edit', [BlogCmsController::class, 'edit'])
            ->where('slug', '[a-z0-9-]+')->name('admin.blog.edit');
        Route::put('blog/{slug}', [BlogCmsController::class, 'update'])
            ->where('slug', '[a-z0-9-]+')->name('admin.blog.update');
        Route::delete('blog/{slug}', [BlogCmsController::class, 'destroy'])
            ->where('slug', '[a-z0-9-]+')->name('admin.blog.destroy');

        /* ── About-page CMS · Live editor (the only editor) ── */
        Route::get('about', [AboutCmsController::class, 'index'])->name('admin.about.index'); // → redirects to live
        Route::get('about/live', [AboutCmsController::class, 'live'])->name('admin.about.live');
        Route::post('about/live', [AboutCmsController::class, 'liveSave'])->name('admin.about.live.save');
        Route::get('about/live/section', [AboutCmsController::class, 'liveSection'])->name('admin.about.live.section');
        Route::post('about/upload', [AboutCmsController::class, 'upload'])->name('admin.about.upload');
        Route::post('about/import', [AboutCmsController::class, 'importUrl'])->name('admin.about.import');

        /* ── Home-hero CMS · Live editor ── */
        Route::get('home-hero', [HomeHeroCmsController::class, 'live'])->name('admin.home-hero.index');
        Route::get('home-hero/live', [HomeHeroCmsController::class, 'live'])->name('admin.home-hero.live');
        Route::post('home-hero/live', [HomeHeroCmsController::class, 'liveSave'])->name('admin.home-hero.live.save');
        Route::post('home-hero/upload', [HomeHeroCmsController::class, 'upload'])->name('admin.home-hero.upload');
        Route::post('home-hero/import', [HomeHeroCmsController::class, 'importUrl'])->name('admin.home-hero.import');
        Route::post('home-hero/preview', [HomeHeroCmsController::class, 'preview'])->name('admin.home-hero.preview');

        /* ── Notice-bar CMS (top blue nav) ── */
        Route::get('notice-bar', [NoticeBarCmsController::class, 'edit'])->name('admin.notice-bar.index');
        Route::post('notice-bar', [NoticeBarCmsController::class, 'update'])->name('admin.notice-bar.update');

        Route::get('country-visibility', [CountryVisibilityController::class, 'edit'])->name('admin.country-visibility.index');
        Route::post('country-visibility', [CountryVisibilityController::class, 'update'])->name('admin.country-visibility.update');

        Route::get('country-sync', [CountryDataSyncController::class, 'index'])->name('admin.country-sync.index');
        Route::post('country-sync/check', [CountryDataSyncController::class, 'check'])->name('admin.country-sync.check');
        Route::post('country-sync/start', [CountryDataSyncController::class, 'start'])->name('admin.country-sync.start');
        Route::get('country-sync/progress', [CountryDataSyncController::class, 'progress'])->name('admin.country-sync.progress');
        Route::post('country-sync/apply', [CountryDataSyncController::class, 'applyAll'])->name('admin.country-sync.apply');
        Route::post('country-sync/selected', [CountryDataSyncController::class, 'applySelected'])->name('admin.country-sync.selected');
        Route::get('country-sync/report', [CountryDataSyncController::class, 'downloadReport'])->name('admin.country-sync.report');
        Route::get('country-sync/workbook', [CountryDataSyncController::class, 'downloadWorkbook'])->name('admin.country-sync.workbook');

        Route::get('mbbs-country-sync', [MbbsCountryDataSyncController::class, 'index'])->name('admin.mbbs-country-sync.index');
        Route::post('mbbs-country-sync/check', [MbbsCountryDataSyncController::class, 'check'])->name('admin.mbbs-country-sync.check');
        Route::post('mbbs-country-sync/start', [MbbsCountryDataSyncController::class, 'start'])->name('admin.mbbs-country-sync.start');
        Route::get('mbbs-country-sync/progress', [MbbsCountryDataSyncController::class, 'progress'])->name('admin.mbbs-country-sync.progress');
        Route::post('mbbs-country-sync/apply', [MbbsCountryDataSyncController::class, 'applyAll'])->name('admin.mbbs-country-sync.apply');
        Route::post('mbbs-country-sync/selected', [MbbsCountryDataSyncController::class, 'applySelected'])->name('admin.mbbs-country-sync.selected');
        Route::get('mbbs-country-sync/report', [MbbsCountryDataSyncController::class, 'downloadReport'])->name('admin.mbbs-country-sync.report');
        Route::get('mbbs-country-sync/workbook', [MbbsCountryDataSyncController::class, 'downloadWorkbook'])->name('admin.mbbs-country-sync.workbook');
    });
});
