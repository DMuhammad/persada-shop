<?php

use App\Http\Controllers\Dashboard\AdminManagementController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\ProfileController;
use App\Http\Controllers\Dashboard\SettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
| The dashboard shell itself: its landing page, the admin profile and the
| settings only a superadmin reaches.
*/

Route::middleware(['auth', 'verified'])->prefix('dashboard')->group(function () {
    Route::middleware('role:admin,superadmin')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('profile', [ProfileController::class, 'index'])->name('profile');
        Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    });

    Route::middleware('role:superadmin')->group(function () {
        Route::resource('teams', AdminManagementController::class)->except(['create', 'edit', 'show']);

        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::post('settings', [SettingController::class, 'update'])->name('settings.update');
        Route::get('settings/search-location', [SettingController::class, 'searchSenderLocation'])->name('settings.search-location');
    });
});
