<?php

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

Route::get('/mbbs/student', [PageController::class, 'mbbsStudent'])->name('mbbs.student');
Route::get('/mbbs/student-v2', [PageController::class, 'mbbsStudentV2'])->name('mbbs.student.v2');

Route::get('/mbbs/country/{country}', [PageController::class, 'mbbsCountry'])
    ->where('country', '[a-z]+')
    ->name('mbbs.country');

Route::get('/countries-v2/{country}.html', [PageController::class, 'countryV2'])
    ->where('country', '[A-Za-z0-9-]+')
    ->name('country.v2.legacy');

Route::get('/countries-v2/{country}', [PageController::class, 'countryV2'])
    ->where('country', '[A-Za-z0-9-]+')
    ->name('country.v2');

Route::get('/countries/study-in-uk-dynamic', [PageController::class, 'studyInUkDynamic'])
    ->name('country.uk.dynamic');

Route::get('/countries/study-in-uk-dynamic.html', [PageController::class, 'studyInUkDynamic'])
    ->name('country.uk.dynamic.legacy');

Route::get('/countries/{country}.html', [PageController::class, 'country'])
    ->where('country', '[A-Za-z0-9-]+')
    ->name('country.legacy');

Route::get('/countries/{country}', [PageController::class, 'country'])
    ->where('country', '[A-Za-z0-9-]+')
    ->name('country.show');
