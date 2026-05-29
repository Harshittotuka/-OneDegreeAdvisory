<?php

use App\Http\Controllers\Admin\BlogCmsController;
use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/index.html', [PageController::class, 'home'])->name('home.legacy');

Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/about.html', [PageController::class, 'about'])->name('about.legacy');

Route::get('/careers', [PageController::class, 'careers'])->name('careers');

Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::get('/contact.html', [PageController::class, 'contact'])->name('contact.legacy');

Route::get('/blog', [PageController::class, 'blogIndex'])->name('blog.index');
Route::get('/blog/{slug}', [PageController::class, 'blogPost'])
    ->where('slug', '[a-z0-9-]+')
    ->name('blog.post');

Route::get('/services/test-preparation', [PageController::class, 'testPreparation'])->name('services.test-prep');
Route::get('/services/admissions-counselling', [PageController::class, 'admissionsCounselling'])->name('services.admissions-counselling');
Route::get('/services/student-services', [PageController::class, 'studentServices'])->name('services.student-services');

Route::get('/courses/undergraduate', [PageController::class, 'undergraduate'])->name('courses.ug');
Route::get('/courses/postgraduate', [PageController::class, 'postgraduate'])->name('courses.pg');
Route::get('/courses/llb', [PageController::class, 'llb'])->name('courses.llb');
Route::get('/courses/mba', [PageController::class, 'mba'])->name('courses.mba');
Route::get('/courses/doctoral', [PageController::class, 'doctoral'])->name('courses.doctoral');

Route::get('/mbbs/student', [PageController::class, 'mbbsStudent'])->name('mbbs.student');

Route::get('/mbbs/country/{country}', [PageController::class, 'mbbsCountry'])
    ->where('country', '[a-z]+')
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
        Route::get('/', fn () => redirect()->route('admin.blog.index'));
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
    });
});
