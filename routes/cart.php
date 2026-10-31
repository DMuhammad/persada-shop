<?php

use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Cart and Checkout Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'role:customer'])->group(function () {
    Route::get('cart', [CartController::class, 'index'])->name('cart.index');
    Route::post('cart', [CartController::class, 'store'])->name('cart.store');
    Route::post('cart/{cartItem}/update', [CartController::class, 'update'])->name('cart.update');
    Route::delete('cart/{cartItem}', [CartController::class, 'destroy'])->name('cart.destroy');
    Route::post('cart/update-selection', [CartController::class, 'updateSelection'])->name('cart.update-selection');

    Route::get('checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('checkout', [CheckoutController::class, 'store'])->name('checkout.store');
    Route::post('checkout/calculate-shipping', [CheckoutController::class, 'calculateShipping'])->name('checkout.calculate-shipping');
});
