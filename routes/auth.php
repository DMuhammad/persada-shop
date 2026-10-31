<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'loginIndex'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
    Route::get('register', [AuthController::class, 'registerIndex'])->name('register');
    Route::post('register', [AuthController::class, 'register']);

    Route::get('forgot-password', [AuthController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('forgot-password', [AuthController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('reset-password/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

    Route::get('auth/google', [GoogleController::class, 'redirectToGoogle'])->name('google.login');
});

Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);

Route::post('logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');
