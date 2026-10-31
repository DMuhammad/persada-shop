<?php

use App\Http\Controllers\AppController;
use App\Http\Controllers\MidtransController;
use App\Http\Controllers\NotificationController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| The home pages and the routes no single module owns. Every module keeps its
| own file and is required at the bottom.
*/

Route::get('/', [AppController::class, 'index'])->name('home');
Route::get('about', [AppController::class, 'about'])->name('about');

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

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('notifications/read/{notification}', [NotificationController::class, 'read'])->name('notifications.read');
    Route::get('notifications/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
});

require __DIR__ . '/auth.php';
require __DIR__ . '/product.php';
require __DIR__ . '/cart.php';
require __DIR__ . '/order.php';
require __DIR__ . '/customer.php';
require __DIR__ . '/stock.php';
require __DIR__ . '/report.php';
require __DIR__ . '/dashboard.php';
