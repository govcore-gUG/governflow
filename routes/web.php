<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::post('/auth/login', [LoginController::class, 'store'])
    ->middleware(['guest', 'throttle:login'])
    ->name('login');

Route::post('/auth/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

// The React SPA handles every other page. API, Sanctum and health check
// routes are excluded so that unknown endpoints there still answer 404.
Route::view('/{any?}', 'app')
    ->where('any', '^(?!(api|sanctum|auth)(/|$)|up$).*$');
