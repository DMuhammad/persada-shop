<?php

use App\Http\Controllers\Dashboard\OrderController as DashboardOrderController;
use App\Http\Controllers\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Order Routes
|--------------------------------------------------------------------------
| The history a customer reads and the list an admin works through.
*/

Route::middleware(['auth', 'verified'])->group(function () {
    Route::middleware('role:customer')->group(function () {
        Route::get('orders', [OrderController::class, 'index'])->name('order.index');
        Route::get('orders/{order:order_number}', [OrderController::class, 'orderDetail'])->name('order.detail');
        Route::get('orders/{order:order_number}/invoice', [OrderController::class, 'invoice'])->name('order.invoice');
    });

    Route::middleware('role:admin,superadmin')->prefix('dashboard')->name('dashboard.orders.')->group(function () {
        Route::get('orders', [DashboardOrderController::class, 'index'])->name('index');
        Route::get('orders/{order:order_number}', [DashboardOrderController::class, 'show'])->name('show');
        Route::post('orders/{order:order_number}/update-status', [DashboardOrderController::class, 'updateStatus'])->name('updateStatus');
        Route::get('orders/{order:order_number}/invoice', [DashboardOrderController::class, 'invoice'])->name('invoice');
    });
});
