<?php

use App\Http\Controllers\Dashboard\ReportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Report Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified', 'role:superadmin'])
    ->prefix('dashboard/reports')
    ->name('reports.')
    ->group(function () {
        Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
        Route::get('/sales/export', [ReportController::class, 'exportSales'])->name('sales.export');
        Route::get('/customers', [ReportController::class, 'customers'])->name('customers');
    });
