<?php

use App\Http\Controllers\Admin\AboutCmsController;
use App\Http\Controllers\Admin\BlogCmsController;
use App\Http\Controllers\Admin\CareerCounsellingCmsController;
use App\Http\Controllers\Admin\CountryVisibilityController;
use App\Http\Controllers\Admin\CountryDataSyncController;
use App\Http\Controllers\Admin\DestinationsLayoutController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HomeHeroCmsController;
use App\Http\Controllers\Admin\MbbsCountryDataSyncController;
use App\Http\Controllers\Admin\NoticeBarCmsController;
use App\Http\Controllers\Admin\TestPrepCompareCmsController;
use App\Http\Controllers\Admin\UnlinkedPagesController;
use App\Http\Controllers\Admin\BriefPageCmsController;
use App\Http\Controllers\Admin\CareerLibraryCmsController;
use App\Http\Controllers\BriefPageController;
use App\Http\Controllers\CareerCounsellingController;
use App\Http\Controllers\CareerLibraryController;
use App\Http\Controllers\LoanAccoController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\SopController;
use App\Http\Controllers\VisaMockAssessmentController;
use App\Http\Controllers\VisaMockBatchAssessmentController;
use App\Http\Controllers\VisaMockInviteController;
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

Route::get('/privacy-policy', [PageController::class, 'privacyPolicy'])->name('privacy');
Route::permanentRedirect('/privacy-policy.html', '/privacy-policy');

Route::get('/terms-and-conditions', [PageController::class, 'termsAndConditions'])->name('terms');
Route::permanentRedirect('/terms-and-conditions.html', '/terms-and-conditions');
Route::permanentRedirect('/terms', '/terms-and-conditions');

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

// Referral Program — Student Hub page where anyone can refer a prospective
// student and earn a reward once that student enrols. The form records the
// STUDENT as a CRM lead (source = referral) with the referrer attached as their
// own section, so the team knows who to credit.
Route::get('/referral-program', [ReferralController::class, 'index'])->name('referral');
Route::post('/referral-program', [ReferralController::class, 'submit'])
    ->middleware('throttle:10,1')
    ->name('referral.submit');

// Career Counselling — counselling / assessment / guidance landing page, linked
// from the home hero's "Career Mentoring" button. Plans and prices are
// CMS-managed in storage/app/career-counselling.json; a plan card checks out
// through the shared /payments flow (priced server-side by PaymentBlockResolver)
// and the consultation form records a CRM lead (source = career-counselling).
Route::get('/career-counselling', [CareerCounsellingController::class, 'index'])->name('career-counselling');
Route::post('/career-counselling/lead', [CareerCounsellingController::class, 'lead'])
    ->middleware('throttle:15,1')
    ->name('career-counselling.lead');

// Global Career Library — a self-contained careers explorer, CMS-managed data
// in storage/app/career-library.json. Detail URLs are shaped:
// /global-career-library/{cc}/{Career-Name}/{lang-code}.
Route::get('/global-career-library', [CareerLibraryController::class, 'index'])->name('career-library.index');
Route::post('/global-career-library/ensure', [CareerLibraryController::class, 'ensure'])
    ->middleware('throttle:15,1')
    ->name('career-library.ensure');
// Contact-details capture that gates viewing a career report. Stored as a lead
// as a classified CRM lead (source = career-library).
Route::post('/global-career-library/lead', [CareerLibraryController::class, 'lead'])
    ->middleware('throttle:15,1')
    ->name('career-library.lead');
Route::get('/global-career-library/{country}/{career}/{lang}', [CareerLibraryController::class, 'show'])
    ->where(['country' => '[A-Za-z]{2}', 'career' => '[^/]+', 'lang' => '[A-Za-z]{2,4}-[A-Za-z]{2}'])
    ->name('career-library.show');

// Student Profiler module (self-contained in app/Modules/StudentProfiler). One
// invokable controller serves GET (wizard) and POST (session save / submit).
Route::match(['get', 'post'], '/profiler', \App\Modules\StudentProfiler\StudentProfilerController::class)->name('profiler');

// Loan & Acco — Education-Loan + Student-Accommodation landing page (Student
// Hub). Both enquiry forms POST to ::lead, which records a lead in the shared
// CRM lead record (source = loan-acco).
Route::get('/loan-accommodation', [LoanAccoController::class, 'index'])->name('loan-acco.index');
Route::post('/loan-accommodation/lead', [LoanAccoController::class, 'lead'])
    ->middleware('throttle:15,1')
    ->name('loan-acco.lead');

// Visa — Student Hub landing page with a free visa-eligibility pre-check. Fully
// static (no lead capture): the advisor CTA routes to /contact and the checker
// result opens a WhatsApp / email "connect with a counsellor" popup.
Route::get('/visa', [PageController::class, 'visa'])->name('visa');

// AI Visa Mock Interview — a self-contained, browser-based mock-interview tool
// (Student Hub). The free round is capped at 10 questions; unlocking the full
// interview opens a popup that captures contact details, recorded as a lead in
// the CRM lead pipeline (source = "visa-mock").
Route::get('/visa-mock-interview', [PageController::class, 'visaMock'])->name('visa-mock');
Route::post('/visa-mock-interview/lead', [PageController::class, 'visaMockLead'])
    ->middleware('throttle:15,1')
    ->name('visa-mock.lead');
Route::post('/visa-mock-interview/assess', VisaMockAssessmentController::class)
    ->middleware('throttle:60,1')
    ->name('visa-mock.assess');
Route::post('/visa-mock-interview/assess-batch', VisaMockBatchAssessmentController::class)
    ->middleware('throttle:10,1')
    ->name('visa-mock.assess-batch');

// Counsellor-issued invite links. A CRM user generates one of these to grant a
// student an extended (15/20/39-question) round for a fixed number of uses. The
// landing page never spends a use — only ::start does, and it also serves the
// question queue so the granted count cannot be raised from the browser.
Route::get('/mock-interview/i/{token}', [VisaMockInviteController::class, 'show'])
    ->where('token', '[A-Za-z0-9]+')
    ->name('visa-mock.invite');
Route::post('/mock-interview/i/{token}/start', [VisaMockInviteController::class, 'start'])
    ->where('token', '[A-Za-z0-9]+')
    ->middleware('throttle:20,1')
    ->name('visa-mock.invite.start');
Route::post('/mock-interview/i/{token}/finish', [VisaMockInviteController::class, 'finish'])
    ->where('token', '[A-Za-z0-9]+')
    ->middleware('throttle:20,1')
    ->name('visa-mock.invite.finish');

// Statement of Purpose — SOP / admissions-writing studio landing page (Student
// Hub). The "book a strategy call" form POSTs to ::lead, which records a lead in
// the CRM lead pipeline (source = sop).
Route::get('/statement-of-purpose', [SopController::class, 'index'])->name('sop.index');
Route::post('/statement-of-purpose/lead', [SopController::class, 'lead'])
    ->middleware('throttle:15,1')
    ->name('sop.lead');

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
Route::get('/courses/mba', [PageController::class, 'mba'])->name('courses.mba');
Route::get('/courses/doctoral', [PageController::class, 'doctoral'])->name('courses.doctoral');

Route::get('/mbbs/student', [PageController::class, 'mbbsStudent'])->name('mbbs.student');

Route::get('/mbbs/country/{country}', [PageController::class, 'mbbsCountry'])
    ->where('country', '[a-z0-9-]+')
    ->name('mbbs.country');

Route::get('/countries/{country}', [PageController::class, 'country'])
    ->where('country', '[A-Za-z0-9-]+')
    ->name('country.show');

/* ───────────────── One Degree Lead CRM ───────────────── */
Route::prefix('crm')->name('crm.')->group(function (): void {
    Route::get('login', [\App\Http\Controllers\Crm\CrmAuthController::class, 'show'])->name('login');
    Route::post('otp/request', [\App\Http\Controllers\Crm\CrmAuthController::class, 'requestOtp'])
        ->middleware('throttle:5,10')->name('otp.request');
    Route::post('otp/verify', [\App\Http\Controllers\Crm\CrmAuthController::class, 'verify'])
        ->middleware('throttle:10,10')->name('otp.verify');

    Route::middleware('crm.auth')->group(function (): void {
        Route::get('/', [\App\Http\Controllers\Crm\CrmDashboardController::class, 'index'])->name('dashboard');
        Route::post('logout', [\App\Http\Controllers\Crm\CrmAuthController::class, 'logout'])->name('logout');
        Route::get('leads/export', [\App\Http\Controllers\Crm\CrmDashboardController::class, 'export'])->name('leads.export');
        Route::get('website-leads/export/csv', [\App\Http\Controllers\Crm\CrmWebsiteLeadController::class, 'exportCsv'])->name('website.export.csv');
        Route::get('website-leads/export/excel', [\App\Http\Controllers\Crm\CrmWebsiteLeadController::class, 'exportExcel'])->name('website.export.excel');
        Route::get('website-leads/{submission}/download', [\App\Http\Controllers\Crm\CrmWebsiteLeadController::class, 'download'])->name('website.download');
        Route::delete('website-leads/{submission}', [\App\Http\Controllers\Crm\CrmWebsiteLeadController::class, 'destroy'])->name('website.destroy');
        Route::get('enrollments/export', [\App\Http\Controllers\Crm\CrmEnrollmentController::class, 'export'])->name('enrollments.export');
        Route::patch('enrollments/{attempt}', [\App\Http\Controllers\Crm\CrmEnrollmentController::class, 'update'])->name('enrollments.update');
        Route::delete('enrollments/{attempt}', [\App\Http\Controllers\Crm\CrmEnrollmentController::class, 'destroy'])->name('enrollments.destroy');
        Route::get('subscribers/export', [\App\Http\Controllers\Crm\CrmSubscriberController::class, 'export'])->name('subscribers.export');
        Route::patch('subscribers/{subscriber}', [\App\Http\Controllers\Crm\CrmSubscriberController::class, 'update'])->name('subscribers.update');
        Route::delete('subscribers/{subscriber}', [\App\Http\Controllers\Crm\CrmSubscriberController::class, 'destroy'])->name('subscribers.destroy');
        Route::post('leads/import', [\App\Http\Controllers\Crm\CrmLeadController::class, 'import'])->name('leads.import');
        Route::post('leads', [\App\Http\Controllers\Crm\CrmLeadController::class, 'store'])->name('leads.store');
        Route::put('leads/{lead}', [\App\Http\Controllers\Crm\CrmLeadController::class, 'update'])->name('leads.update');
        Route::post('leads/{lead}/comments', [\App\Http\Controllers\Crm\CrmLeadController::class, 'comment'])->name('leads.comments.store');
        Route::post('leads/{lead}/follow-up/complete', [\App\Http\Controllers\Crm\CrmLeadController::class, 'completeFollowUp'])->name('leads.follow-up.complete');
        Route::post('leads/{lead}/convert', [\App\Http\Controllers\Crm\CrmLeadController::class, 'convert'])->name('leads.convert');
        Route::patch('leads/{lead}/student-journey', [\App\Http\Controllers\Crm\CrmLeadController::class, 'updateStudentJourney'])->name('leads.student-journey.update');
        // Report-production tool (moved from the admin CMS). The GET page is the
        // dashboard "shortlisting" view; this endpoint does the merge + download.
        Route::post('pdf-shortlisting', [\App\Http\Controllers\Crm\CrmPdfShortlistingController::class, 'generate'])
            ->middleware('throttle:10,1')->name('pdf-shortlisting.generate');
        // Mock-interview invite links (the "Mock interviews" tab).
        Route::post('mock-invites', [\App\Http\Controllers\Crm\CrmMockInviteController::class, 'store'])->name('mock-invites.store');
        Route::patch('mock-invites/{invite}/revoke', [\App\Http\Controllers\Crm\CrmMockInviteController::class, 'revoke'])->name('mock-invites.revoke');
        Route::post('team', [\App\Http\Controllers\Crm\CrmUserController::class, 'store'])->name('team.store');
        Route::patch('team/{member}', [\App\Http\Controllers\Crm\CrmUserController::class, 'update'])->name('team.update');
        Route::patch('team/{member}/toggle', [\App\Http\Controllers\Crm\CrmUserController::class, 'toggle'])->name('team.toggle');
        Route::patch('team/{member}/role', [\App\Http\Controllers\Crm\CrmUserController::class, 'changeRole'])->name('team.role');
        Route::delete('team/{member}', [\App\Http\Controllers\Crm\CrmUserController::class, 'destroy'])->name('team.destroy');
    });
});

/* ───────────────────────── Blog CMS (admin) ───────────────────────── */
// Content Studio dashboard has a clean URL; authentication is unchanged.
Route::get('/cms', [DashboardController::class, 'index'])
    ->middleware('cms.auth')
    ->name('admin.dashboard');

Route::prefix('admin')->group(function () {
    Route::redirect('cms', '/cms', 301);
    Route::get('login', [BlogCmsController::class, 'showLogin'])->name('admin.login');
    Route::post('login', [BlogCmsController::class, 'login'])->name('admin.login.attempt');
    Route::post('logout', [BlogCmsController::class, 'logout'])->name('admin.logout');

    Route::middleware('cms.auth')->group(function () {
        Route::get('/', fn () => redirect()->route('admin.dashboard'));
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

        /* ── Career Counselling "Plans & Pricing" (stages, plan cards, prices) ── */
        Route::get('career-counselling', [CareerCounsellingCmsController::class, 'edit'])->name('admin.career-counselling.index');
        Route::post('career-counselling', [CareerCounsellingCmsController::class, 'update'])->name('admin.career-counselling.update');

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
