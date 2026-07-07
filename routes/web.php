<?php

use App\Http\Controllers\Admin\AboutCmsController;
use App\Http\Controllers\Admin\BlogCmsController;
use App\Http\Controllers\Admin\CountryVisibilityController;
use App\Http\Controllers\Admin\CountryDataSyncController;
use App\Http\Controllers\Admin\DestinationsLayoutController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EnrollmentsController;
use App\Http\Controllers\Admin\HomeHeroCmsController;
use App\Http\Controllers\Admin\MbbsCountryDataSyncController;
use App\Http\Controllers\Admin\NewsletterController;
use App\Http\Controllers\Admin\NoticeBarCmsController;
use App\Http\Controllers\Admin\ProfileSubmissionsController;
use App\Http\Controllers\Admin\TestPrepCompareCmsController;
use App\Http\Controllers\Admin\UnlinkedPagesController;
use App\Http\Controllers\Admin\BriefPageCmsController;
use App\Http\Controllers\Admin\CareerLibraryCmsController;
use App\Http\Controllers\BriefPageController;
use App\Http\Controllers\CareerLibraryController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::permanentRedirect('/index.html', '/');

Route::get('/about', [PageController::class, 'about'])->name('about');
Route::permanentRedirect('/about.html', '/about');

Route::get('/careers', [PageController::class, 'careers'])->name('careers');
Route::post('/careers', [PageController::class, 'submitCareer'])->name('careers.submit');

Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::permanentRedirect('/contact.html', '/contact');
Route::post('/contact', [PageController::class, 'submitContact'])->name('contact.submit');

Route::post('/newsletter', [PageController::class, 'subscribeNewsletter'])->name('newsletter.subscribe');

Route::prefix('payments')->name('payments.')->group(function (): void {
    Route::post('/order', [PaymentController::class, 'createOrder'])
        ->middleware('throttle:10,10')
        ->name('order');
    Route::post('/confirm', [PaymentController::class, 'confirm'])
        ->middleware('throttle:10,10')
        ->name('confirm');
    Route::post('/razorpay/webhook', [PaymentController::class, 'webhook'])
        ->name('webhook');
});

Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('robots');

Route::get('/blog', [PageController::class, 'blogIndex'])->name('blog.index');
Route::get('/blog/{slug}', [PageController::class, 'blogPost'])
    ->where('slug', '[a-z0-9-]+')
    ->name('blog.post');

Route::get('/services/test-preparation', [PageController::class, 'testPreparation'])->name('services.test-prep');
Route::get('/services/admissions-counselling', [PageController::class, 'admissionsCounselling'])->name('services.admissions-counselling');
Route::get('/services/student-services', [PageController::class, 'studentServices'])->name('services.student-services');

Route::get('/study-abroad', [PageController::class, 'studyAbroad'])->name('study-abroad');

// Global Career Library — a self-contained careers explorer, CMS-managed data
// in storage/app/career-library.json. Detail URLs are shaped:
// /global-career-library/{cc}/{Career-Name}/{lang-code}.
Route::get('/global-career-library', [CareerLibraryController::class, 'index'])->name('career-library.index');
Route::post('/global-career-library/ensure', [CareerLibraryController::class, 'ensure'])
    ->middleware('throttle:15,1')
    ->name('career-library.ensure');
Route::get('/global-career-library/{country}/{career}/{lang}', [CareerLibraryController::class, 'show'])
    ->where(['country' => '[A-Za-z]{2}', 'career' => '[^/]+', 'lang' => '[A-Za-z]{2,4}-[A-Za-z]{2}'])
    ->name('career-library.show');

// Student Profiler module (self-contained in app/Modules/StudentProfiler). One
// invokable controller serves GET (wizard) and POST (session save / submit).
Route::match(['get', 'post'], '/profiler', \App\Modules\StudentProfiler\StudentProfilerController::class)->name('profiler');

// Brief pages — CMS-built (.odp-* design). The four seeded pages keep their
// original top-level URLs; new pages are served under /briefs/{slug}.
Route::get('/europe', [BriefPageController::class, 'show'])->defaults('slug', 'europe')->name('europe');
Route::permanentRedirect('/packages', '/europe');
Route::get('/wednesday-briefings', [BriefPageController::class, 'show'])->defaults('slug', 'wednesday-briefings')->name('wednesday-briefings');
Route::get('/medicine-and-beyond', [BriefPageController::class, 'show'])->defaults('slug', 'medicine-and-beyond')->name('medicine-and-beyond');
Route::get('/destination-new-zealand', [BriefPageController::class, 'show'])->defaults('slug', 'destination-new-zealand')->name('destination-new-zealand');
Route::get('/briefs/{slug}', [BriefPageController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('briefs.show');

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
        Route::get('/', fn () => redirect()->route('admin.portal'));
        // Portal picker shown after login: CMS (content) or Admin (enrollments).
        Route::get('portal', fn () => view('admin.portal'))->name('admin.portal');
        Route::get('cms', [DashboardController::class, 'index'])->name('admin.dashboard');

        // Admin portal — dashboard + enrollments / payments.
        Route::get('panel', [EnrollmentsController::class, 'overview'])->name('admin.overview');
        Route::get('enrollments', [EnrollmentsController::class, 'index'])->name('admin.enrollments.index');
        Route::get('enrollments/test-prep', [EnrollmentsController::class, 'testPrep'])->name('admin.enrollments.test-prep');
        Route::patch('enrollments/{attempt}/status', [EnrollmentsController::class, 'updateStatus'])->name('admin.enrollments.status');
        Route::delete('enrollments/{attempt}', [EnrollmentsController::class, 'destroy'])->name('admin.enrollments.destroy');

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

        /* ── Brief Page Builder (all CMS admins) ── */
        Route::get('pages', [BriefPageCmsController::class, 'index'])->name('admin.pages.index');
        Route::post('pages', [BriefPageCmsController::class, 'storePage'])->name('admin.pages.store');
        // Authorization OTP gate for saving a page that contains a payment section.
        Route::post('pages/payment-otp/request', [BriefPageCmsController::class, 'requestPaymentOtp'])
            ->middleware('throttle:5,10')->name('admin.pages.payment-otp.request');
        Route::post('pages/payment-otp/verify', [BriefPageCmsController::class, 'verifyPaymentOtp'])
            ->middleware('throttle:10,10')->name('admin.pages.payment-otp.verify');
        Route::get('pages/block', [BriefPageCmsController::class, 'block'])->name('admin.pages.block');
        Route::get('pages/preset', [BriefPageCmsController::class, 'preset'])->name('admin.pages.preset');
        Route::post('pages/render', [BriefPageCmsController::class, 'render'])->name('admin.pages.render');
        Route::post('pages/upload', [BriefPageCmsController::class, 'upload'])->name('admin.pages.upload');
        Route::post('pages/import', [BriefPageCmsController::class, 'importUrl'])->name('admin.pages.import');
        Route::get('pages/{slug}/edit', [BriefPageCmsController::class, 'edit'])->where('slug', '[a-z0-9-]+')->name('admin.pages.edit');
        Route::get('pages/{slug}/studio', [BriefPageCmsController::class, 'studio'])->where('slug', '[a-z0-9-]+')->name('admin.pages.studio');
        Route::post('pages/{slug}', [BriefPageCmsController::class, 'save'])->where('slug', '[a-z0-9-]+')->name('admin.pages.save');
        Route::post('pages/{slug}/duplicate', [BriefPageCmsController::class, 'duplicate'])->where('slug', '[a-z0-9-]+')->name('admin.pages.duplicate');
        Route::post('pages/{slug}/visibility', [BriefPageCmsController::class, 'toggleVisibility'])->where('slug', '[a-z0-9-]+')->name('admin.pages.visibility');
        Route::delete('pages/{slug}', [BriefPageCmsController::class, 'destroy'])->where('slug', '[a-z0-9-]+')->name('admin.pages.destroy');

        /* ── Notice-bar CMS (top blue nav) ── */
        Route::get('notice-bar', [NoticeBarCmsController::class, 'edit'])->name('admin.notice-bar.index');
        Route::post('notice-bar', [NoticeBarCmsController::class, 'update'])->name('admin.notice-bar.update');

        /* ── Test-Prep "Compare & enrol" section (programs list, prices, durations, style) ── */
        Route::get('test-prep-compare', [TestPrepCompareCmsController::class, 'edit'])->name('admin.test-prep-compare.index');
        Route::post('test-prep-compare', [TestPrepCompareCmsController::class, 'update'])->name('admin.test-prep-compare.update');

        /* ── Global Career Library (landing settings + per-career LIVE editor) ── */
        Route::get('career-library', [CareerLibraryCmsController::class, 'index'])->name('admin.career-library.index');
        Route::post('career-library/settings', [CareerLibraryCmsController::class, 'updateSettings'])->name('admin.career-library.settings');
        Route::post('career-library', [CareerLibraryCmsController::class, 'store'])->name('admin.career-library.store');
        Route::post('career-library/reorder', [CareerLibraryCmsController::class, 'reorder'])->name('admin.career-library.reorder');
        Route::post('career-library/upload', [CareerLibraryCmsController::class, 'upload'])->name('admin.career-library.upload');
        Route::post('career-library/import', [CareerLibraryCmsController::class, 'importUrl'])->name('admin.career-library.import');
        Route::get('career-library/{slug}/live', [CareerLibraryCmsController::class, 'live'])
            ->where('slug', '[a-z0-9-]+')->name('admin.career-library.live');
        Route::post('career-library/{slug}/live', [CareerLibraryCmsController::class, 'liveSave'])
            ->where('slug', '[a-z0-9-]+')->name('admin.career-library.live.save');
        Route::post('career-library/{slug}/flags', [CareerLibraryCmsController::class, 'updateFlags'])
            ->where('slug', '[a-z0-9-]+')->name('admin.career-library.flags');
        Route::post('career-library/{slug}/variant', [CareerLibraryCmsController::class, 'addVariant'])
            ->where('slug', '[a-z0-9-]+')->name('admin.career-library.variant');
        Route::delete('career-library/{slug}/variant', [CareerLibraryCmsController::class, 'deleteVariant'])
            ->where('slug', '[a-z0-9-]+')->name('admin.career-library.variant.delete');
        Route::delete('career-library/{slug}', [CareerLibraryCmsController::class, 'destroy'])
            ->where('slug', '[a-z0-9-]+')->name('admin.career-library.destroy');

        /* ── Destinations mega-menu layout (nav dropdown grid) ── */
        Route::get('destinations-layout', [DestinationsLayoutController::class, 'edit'])->name('admin.destinations-layout.index');
        Route::post('destinations-layout', [DestinationsLayoutController::class, 'update'])->name('admin.destinations-layout.update');
        Route::post('destinations-layout/reset', [DestinationsLayoutController::class, 'reset'])->name('admin.destinations-layout.reset');

        /* ── Student Profiler submissions (from /profiler) ── */
        Route::get('submissions', [ProfileSubmissionsController::class, 'index'])->name('admin.submissions.index'); // → Student Profiler tab
        Route::get('submissions/student-profiler', [ProfileSubmissionsController::class, 'profiler'])->name('admin.submissions.profiler');
        Route::get('submissions/export', [ProfileSubmissionsController::class, 'export'])->name('admin.submissions.export');
        Route::get('submissions/export-excel', [ProfileSubmissionsController::class, 'exportExcel'])->name('admin.submissions.export-excel');
        Route::post('submissions/delete', [ProfileSubmissionsController::class, 'destroy'])->name('admin.submissions.destroy');
        Route::get('submissions/{id}/download', [ProfileSubmissionsController::class, 'download'])
            ->where('id', '[A-Za-z0-9-]+')->name('admin.submissions.download');
        Route::get('submissions/{id}', [ProfileSubmissionsController::class, 'show'])
            ->where('id', '[A-Za-z0-9-]+')->name('admin.submissions.show');

        /* ── Newsletter subscribers (collected from the blog signup forms) ── */
        Route::get('newsletter', [NewsletterController::class, 'index'])->name('admin.newsletter.index');
        Route::get('newsletter/export', [NewsletterController::class, 'export'])->name('admin.newsletter.export');
        Route::post('newsletter/delete', [NewsletterController::class, 'destroy'])->name('admin.newsletter.destroy');

        Route::get('unlinked-pages', [UnlinkedPagesController::class, 'index'])->name('admin.unlinked-pages.index');

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

// CMS-built brief pages on custom URL paths — resolved last, after every real
// route, so a page path can never shadow application routes.
Route::fallback([BriefPageController::class, 'showByPath']);
