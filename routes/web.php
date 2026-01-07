<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Auth\GoogleController;

use App\Http\Controllers\OrderController;
use App\Http\Controllers\Admin\ProductController as AdminProductController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\MidtransNotificationController;

// ================================
// PUBLIC ROUTES
// ================================
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::view('/tentang', 'tentang');

Route::get('/sapa/{nama}', fn ($nama) => "Halo, $nama! Selamat datang di Toko Online Raihan.");
Route::get('/kategori/{nama?}', fn ($nama = 'Semua') => "Menampilkan kategori: $nama");

// Catalog
Route::get('/products', [CatalogController::class, 'index'])->name('catalog.index');
Route::get('/products/{slug}', [CatalogController::class, 'show'])->name('catalog.show');

// ================================
// AUTH ROUTES (Laravel default)
// ================================
Auth::routes();

// ================================
// GOOGLE AUTH
// ================================
Route::controller(GoogleController::class)->group(function () {
    Route::get('/auth/google', 'redirect')->name('auth.google');
    Route::get('/auth/google/callback', 'callback')->name('auth.google.callback');
});

// ================================
// AUTHENTICATED USER ROUTES
// ================================
Route::middleware('auth')->group(function () {

    // Profile
    Route::prefix('profile')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('profile.update');
        Route::put('/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('profile.destroy');
        Route::delete('/avatar', [ProfileController::class, 'destroyAvatar'])->name('profile.avatar.destroy');
    });

    // Email Verification
    Route::get('/email/verify', fn () => view('auth.verify-email'))->name('verification.notice');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();
        return back()->with('message', 'Verification link sent!');
    })->name('verification.send');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();
        return redirect('/');
    })->middleware('signed')->name('verification.verify');

    // Cart
    Route::prefix('cart')->group(function () {
        Route::get('/', [CartController::class, 'index'])->name('cart.index');
        Route::post('/add', [CartController::class, 'add'])->name('cart.add');
        Route::patch('/{item}', [CartController::class, 'update'])->name('cart.update');
        Route::delete('/{item}', [CartController::class, 'remove'])->name('cart.remove');
    });

    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/toggle/{product}', [WishlistController::class, 'toggle'])->name('wishlist.toggle');

    // Checkout
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

    // Orders
    Route::prefix('orders')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/{order}', [OrderController::class, 'show'])->name('orders.show');

        // Payment
        Route::get('/{order}/pay', [PaymentController::class, 'show'])->name('orders.pay');
        Route::get('/{order}/success', [PaymentController::class, 'success'])->name('orders.success');
        Route::get('/{order}/pending', [PaymentController::class, 'pending'])->name('orders.pending');
    });

    // Authenticated Categories (optional if user bisa kelola kategori sendiri)
    // Route::resource('categories', CategoryController::class)->middleware('auth');
});

// ================================
// ADMIN ROUTES
// ================================
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {

    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');

    // Products & Categories
    Route::resource('products', AdminProductController::class);
    Route::resource('categories', AdminCategoryController::class);

    // Orders
    Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
    Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
    Route::patch('orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.updateStatus');

    // Reports
    Route::get('reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('reports/sales/export', [ReportController::class, 'exportSales'])->name('reports.export-sales');

    // Users
    Route::resource('users', UserController::class)->only(['index', 'show', 'destroy']);
});

// ================================
// MIDTRANS WEBHOOK (PUBLIC)
// ================================
Route::post('midtrans/notification', [MidtransNotificationController::class, 'handle'])
    ->name('midtrans.notification');



Route::middleware(['auth'])->group(function () {
    Route::delete('/profile/google', [ProfileController::class, 'unlinkGoogle'])
        ->name('profile.google.unlink');
});