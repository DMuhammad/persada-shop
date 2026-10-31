<?php

use App\Http\Controllers\CustomerProfileController;
use App\Http\Controllers\Dashboard\CustomerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer Routes
|--------------------------------------------------------------------------
| The profile a customer keeps and the list an admin looks it up in.
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::middleware('role:customer')->prefix('profile')->name('customer.profile.')->group(function () {
        Route::get('/', [CustomerProfileController::class, 'index'])->name('index');
        Route::patch('update', [CustomerProfileController::class, 'updateProfile'])->name('update');
        Route::put('password', [CustomerProfileController::class, 'updatePassword'])->name('password.update');

        Route::get('addresses', [CustomerProfileController::class, 'address'])->name('address.index');
        Route::post('addresses', [CustomerProfileController::class, 'storeAddress'])->name('address.store');
        Route::delete('addresses/{address}', [CustomerProfileController::class, 'destroyAddress'])->name('address.destroy');
        Route::patch('addresses/{address}/set-primary', [CustomerProfileController::class, 'setPrimaryAddress'])->name('address.setPrimary');
        Route::get('search-location', [CustomerProfileController::class, 'searchLocation'])->name('search-location');
    });

    Route::middleware('role:admin,superadmin')->prefix('dashboard/master')->name('master.')->group(function () {
        Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
        Route::get('customers/details/{customer}', [CustomerController::class, 'getDetails'])->name('customers.details');
    });
});
