<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AppController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\CustomerProfileController;
use App\Http\Controllers\MidtransController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\Dashboard\AdminManagementController;
use App\Http\Controllers\Dashboard\CategoryController;
use App\Http\Controllers\Dashboard\CustomerController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Dashboard\ProductController;
use App\Http\Controllers\Dashboard\ProductImageController;
use App\Http\Controllers\Dashboard\ProductVariantController;
use App\Http\Controllers\Dashboard\ProfileController;
use App\Http\Controllers\Dashboard\SettingController;
use App\Http\Controllers\Dashboard\Stock\StockMovementController;
use App\Http\Controllers\Dashboard\OrderController as DashboardOrderController;
use App\Http\Controllers\Dashboard\ReportController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\NotificationController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [AppController::class, 'index'])->name('home');
Route::get('about', [AppController::class, 'about'])->name('about');

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

Route::post('/midtrans/notification', [MidtransController::class, 'webhook']);

Route::get('/email/verify', function () {
    return view('auth.verify-email');
})->middleware('auth')->name('verification.notice');

Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect()->route('home')->with('auth_success', 'Email berhasil diverifikasi!');
})->middleware(['auth', 'signed'])->name('verification.verify');

Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return back()->with('auth_success', 'Link verifikasi baru telah dikirim!');
})->middleware(['auth', 'throttle:6,1'])->name('verification.send');

Route::middleware(['auth'])->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    Route::middleware(['verified'])->group(function () {
        Route::get('notifications/read/{notification}', [NotificationController::class, 'read'])->name('notifications.read');
        Route::get('notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');

        Route::middleware('role:customer')->group(function () {
            Route::get('products', [AppController::class, 'products'])->name('products.index');
            Route::get('products/{product}', [AppController::class, 'productDetail'])->name('products.detail');

            Route::get('cart', [CartController::class, 'index'])->name('cart.index');
            Route::post('cart', [CartController::class, 'store'])->name('cart.store');
            Route::post('cart/{cartItem}/update', [CartController::class, 'update'])->name('cart.update');
            Route::delete('cart/{cartItem}', [CartController::class, 'destroy'])->name('cart.destroy');
            Route::post('cart/update-selection', [CartController::class, 'updateSelection'])->name('cart.update-selection');

            Route::get('checkout', [CheckoutController::class, 'index'])->name('checkout.index');
            Route::post('checkout', [CheckoutController::class, 'store'])->name('checkout.store');
            Route::post('checkout/calculate-shipping', [CheckoutController::class, 'calculateShipping'])->name('checkout.calculate-shipping');

            Route::get('orders', [OrderController::class, 'index'])->name('order.index');
            Route::get('orders/{order:order_number}', [OrderController::class, 'orderDetail'])->name('order.detail');
            Route::get('orders/{order:order_number}/invoice', [OrderController::class, 'invoice'])->name('order.invoice');

            Route::prefix('profile')->name('customer.profile.')->group(function () {
                Route::get('/', [CustomerProfileController::class, 'index'])->name('index');
                Route::patch('update', [CustomerProfileController::class, 'updateProfile'])->name('update');
                Route::put('password', [CustomerProfileController::class, 'updatePassword'])->name('password.update');

                Route::get('addresses', [CustomerProfileController::class, 'address'])->name('address.index');
                Route::post('addresses', [CustomerProfileController::class, 'storeAddress'])->name('address.store');
                Route::delete('addresses/{address}', [CustomerProfileController::class, 'destroyAddress'])->name('address.destroy');
                Route::patch('addresses/{address}/set-primary', [CustomerProfileController::class, 'setPrimaryAddress'])->name('address.setPrimary');
                Route::get('search-location', [CustomerProfileController::class, 'searchLocation'])->name('search-location');
            });
        });

        Route::prefix('dashboard')->group(function () {
            Route::middleware('role:admin,superadmin')->group(function () {
                Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
                Route::get('profile', [ProfileController::class, 'index'])->name('profile');
                Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');
                Route::put('profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

                Route::get('orders', [DashboardOrderController::class, 'index'])->name('dashboard.orders.index');
                Route::get('orders/{order:order_number}', [DashboardOrderController::class, 'show'])->name('dashboard.orders.show');
                Route::post('orders/{order:order_number}/update-status', [DashboardOrderController::class, 'updateStatus'])->name('dashboard.orders.updateStatus');
                Route::get('orders/{order:order_number}/invoice', [DashboardOrderController::class, 'invoice'])->name('dashboard.orders.invoice');

                Route::prefix('master')->name('master.')->group(function () {
                    Route::prefix('stock')->name('stock.')->group(function () {
                        Route::get('/', [StockMovementController::class, 'index'])->name('index');
                        Route::post('movements', [StockMovementController::class, 'store'])->name('movements.store');
                    });

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

                    Route::get('customers', [CustomerController::class, 'index'])->name('customers.index');
                    Route::get('customers/details/{customer}', [CustomerController::class, 'getDetails'])->name('customers.details');
                });
            });

            Route::middleware('role:superadmin')->group(function () {
                Route::prefix('reports')->name('reports.')->group(function () {
                    Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
                    Route::get('/sales/export', [ReportController::class, 'exportSales'])->name('sales.export');
                    Route::get('/customers', [ReportController::class, 'customers'])->name('customers');
                });

                Route::resource('teams', AdminManagementController::class)->except(['create', 'edit', 'show']);
                Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
                Route::post('settings', [SettingController::class, 'update'])->name('settings.update');
                Route::get('settings/search-location', [SettingController::class, 'searchSenderLocation'])->name('settings.search-location');
            });
        });
    });
});
