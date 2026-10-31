<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\Dashboard\CategoryController;
use App\Http\Controllers\Dashboard\ProductController;
use App\Http\Controllers\Dashboard\ProductImageController;
use App\Http\Controllers\Dashboard\ProductVariantController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Product Routes
|--------------------------------------------------------------------------
| The catalogue a customer browses and the master data an admin maintains.
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::middleware('role:customer')->group(function () {
        Route::get('products', [AppController::class, 'products'])->name('products.index');
        Route::get('products/{product}', [AppController::class, 'productDetail'])->name('products.detail');
    });

    Route::middleware('role:admin,superadmin')->prefix('dashboard/master')->name('master.')->group(function () {
        Route::resource('categories', CategoryController::class)->except(['create', 'edit', 'show']);
        Route::resource('products', ProductController::class)->except(['create', 'edit', 'show']);

        Route::prefix('products/{product}')->name('products.')->group(function () {
            Route::get('detail', [ProductController::class, 'detail'])->name('detail');
            Route::post('variants', [ProductVariantController::class, 'store'])->name('variants.store');
            Route::put('variants/{variant}', [ProductVariantController::class, 'update'])->name('variants.update');
            Route::delete('variants/{variant}', [ProductVariantController::class, 'destroy'])->name('variants.destroy');

            Route::post('images', [ProductImageController::class, 'store'])->name('images.store');
            Route::delete('images/{image}', [ProductImageController::class, 'destroy'])->name('images.destroy');
        });
    });
});
