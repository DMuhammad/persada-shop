<?php

use App\Http\Controllers\Dashboard\Stock\StockMovementController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Stock Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'role:admin,superadmin'])
    ->prefix('dashboard/master/stock')
    ->name('master.stock.')
    ->group(function () {
        Route::get('/', [StockMovementController::class, 'index'])->name('index');
        Route::post('movements', [StockMovementController::class, 'store'])->name('movements.store');
    });
