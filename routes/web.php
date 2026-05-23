<?php

use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/index.html', [PageController::class, 'home'])->name('home.legacy');

Route::get('/insights', [PageController::class, 'insights'])->name('insights');
Route::get('/insights.html', [PageController::class, 'insights'])->name('insights.legacy');

Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/about.html', [PageController::class, 'about'])->name('about.legacy');

Route::get('/contact', [PageController::class, 'contact'])->name('contact');
Route::get('/contact.html', [PageController::class, 'contact'])->name('contact.legacy');

Route::get('/mbbs/student', [PageController::class, 'mbbsStudent'])->name('mbbs.student');

Route::get('/countries/{country}.html', [PageController::class, 'country'])
    ->where('country', '[A-Za-z0-9-]+')
    ->name('country.legacy');

Route::get('/countries/{country}', [PageController::class, 'country'])
    ->where('country', '[A-Za-z0-9-]+')
    ->name('country.show');
