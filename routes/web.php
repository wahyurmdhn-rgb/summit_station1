<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CatalogController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\ReturnController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\UserNotificationController;
use App\Http\Controllers\FileController;

// Admin Protected Routes
Route::middleware(['admin'])->group(function () {
    Route::get('/admin', [AdminController::class, 'index'])->name('admin.dashboard');
    Route::get('/admin/dashboard', [AdminController::class, 'index']);
    
    // Alat Routes
    Route::get('/admin/alat', [AdminController::class, 'alat'])->name('admin.alat');
    Route::post('/admin/alat', [AdminController::class, 'storeAlat'])->name('admin.alat.store');
    Route::put('/admin/alat/{id}', [AdminController::class, 'updateAlat'])->name('admin.alat.update');
    Route::patch('/admin/alat/{id}/toggle-status', [AdminController::class, 'toggleStatusAlat'])->name('admin.alat.toggle');
    Route::delete('/admin/alat/{id}', [AdminController::class, 'destroyAlat'])->name('admin.alat.destroy');

    // Paket Sewa (bundle) management within Admin Alat
    Route::post('/admin/alat/bundle', [AdminController::class, 'storeBundle'])->name('admin.alat.bundle.store');
    Route::patch('/admin/alat/bundle/{id}/toggle-status', [AdminController::class, 'toggleStatusBundle'])->name('admin.alat.bundle.toggle');
    Route::put('/admin/alat/bundle/{id}', [AdminController::class, 'updateBundle'])->name('admin.alat.bundle.update');
    Route::delete('/admin/alat/bundle/{id}', [AdminController::class, 'destroyBundle'])->name('admin.alat.bundle.destroy');

    // Kategori Produk management within Admin Alat
    Route::post('/admin/alat/category', [AdminController::class, 'storeCategory'])->name('admin.alat.category.store');
    Route::put('/admin/alat/category/{id}', [AdminController::class, 'updateCategory'])->name('admin.alat.category.update');
    Route::delete('/admin/alat/category/{id}', [AdminController::class, 'destroyCategory'])->name('admin.alat.category.destroy');

    // Penyewaan Routes
    Route::get('/admin/penyewaan', [AdminController::class, 'penyewaan'])->name('admin.penyewaan');
    Route::post('/admin/penyewaan/{id}/confirm', [AdminController::class, 'confirmPenyewaan'])->name('admin.penyewaan.confirm');
    Route::post('/admin/penyewaan/{id}/reject', [AdminController::class, 'rejectPenyewaan'])->name('admin.penyewaan.reject');
    Route::post('/admin/penyewaan/{id}/complete', [AdminController::class, 'completePenyewaan'])->name('admin.penyewaan.complete');
    Route::get('/admin/penyewaan/export', [AdminController::class, 'exportCsvPenyewaan'])->name('admin.penyewaan.export');

    // Pembayaran Routes
    Route::get('/admin/pembayaran', [AdminController::class, 'pembayaran'])->name('admin.pembayaran');
    Route::post('/admin/pembayaran/{id}/approve', [AdminController::class, 'approvePayment'])->name('admin.pembayaran.approve');
    Route::post('/admin/pembayaran/{id}/reject', [AdminController::class, 'rejectPayment'])->name('admin.pembayaran.reject');
    Route::get('/admin/pembayaran/export', [AdminController::class, 'exportCsvPembayaran'])->name('admin.pembayaran.export');

    // Pengembalian Routes
    Route::get('/admin/pengembalian', [AdminController::class, 'pengembalian'])->name('admin.pengembalian');
    Route::post('/admin/pengembalian/{id}/record', [AdminController::class, 'recordReturn'])->name('admin.pengembalian.record');
    Route::post('/admin/pengembalian/{id}/complete', [AdminController::class, 'completeReturn'])->name('admin.pengembalian.complete');
    Route::post('/admin/pengembalian/{id}/penalty', [AdminController::class, 'storeLatePenalty'])->name('admin.pengembalian.penalty.store');
    Route::post('/admin/pengembalian/{id}/penalty/cancel', [AdminController::class, 'cancelLatePenalty'])->name('admin.pengembalian.penalty.cancel');
    Route::post('/admin/pengembalian/{payment}/approve-denda', [AdminController::class, 'approveDendaPayment'])->name('admin.pengembalian.approveDenda');
    Route::post('/admin/pengembalian/{payment}/reject-denda', [AdminController::class, 'rejectDendaPayment'])->name('admin.pengembalian.rejectDenda');

    // Refund Management Routes
    Route::get('/admin/refund', [AdminController::class, 'refund'])->name('admin.refund');
    Route::get('/admin/refund/{id}', [AdminController::class, 'refundDetail'])->name('admin.refund.detail');
    Route::post('/admin/refund/{id}/approve', [AdminController::class, 'approveRefund'])->name('admin.refund.approve');
    Route::post('/admin/refund/{id}/reject', [AdminController::class, 'rejectRefund'])->name('admin.refund.reject');
    Route::post('/admin/refund/{id}/complete', [AdminController::class, 'completeRefund'])->name('admin.refund.complete');

    // Users Routes
    Route::get('/admin/users', [AdminController::class, 'users'])->name('admin.users');
    Route::post('/admin/users', [AdminController::class, 'storeUser'])->name('admin.users.store');
    Route::put('/admin/users/{id}', [AdminController::class, 'updateUser'])->name('admin.users.update');
    Route::patch('/admin/users/{id}/status', [AdminController::class, 'changeUserStatus'])->name('admin.users.status');
    Route::delete('/admin/users/{id}', [AdminController::class, 'destroyUser'])->name('admin.users.destroy');
    Route::post('/admin/users/{id}/parent-consent', [AdminController::class, 'updateParentConsent'])->name('admin.users.parentConsent');
    Route::get('/admin/users/export', [AdminController::class, 'exportCsvUsers'])->name('admin.users.export');

    // Website & CMS Routes
    Route::get('/admin/website', [AdminController::class, 'website'])->name('admin.website');
    Route::post('/admin/website', [AdminController::class, 'updateWebsiteSettings'])->name('admin.website.update');

    // Laporan Routes
    Route::get('/admin/laporan', [AdminController::class, 'laporan'])->name('admin.laporan');
    Route::get('/admin/laporan/export', [AdminController::class, 'exportCsvLaporan'])->name('admin.laporan.export');

    // Reviews Management in Admin
    Route::patch('/admin/reviews/{id}/toggle', [ReviewController::class, 'adminToggle'])->name('admin.reviews.toggle');
    Route::delete('/admin/reviews/{id}', [ReviewController::class, 'adminDestroy'])->name('admin.reviews.destroy');

    // Admin Activity Notifications
    Route::get('/admin/notifikasi', [AdminController::class, 'notifications'])->name('admin.notifications.index');
    Route::get('/admin/notifications/{id}', [AdminController::class, 'openNotification'])->name('admin.notifications.open');
    Route::post('/admin/notifications/read-all', [AdminController::class, 'markAllNotificationsRead'])->name('admin.notifications.readAll');

    // Profil Admin Routes
    Route::get('/admin/profile', [AdminController::class, 'profile'])->name('admin.profile');
    Route::post('/admin/profile', [AdminController::class, 'updateProfile'])->name('admin.profile.update');
    Route::post('/admin/profile/password', [AdminController::class, 'updateAdminPassword'])->name('admin.profile.password');
});

// Reviews Customer Route
Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');

// User Homepage (Beranda)
Route::get('/', [HomeController::class, 'index'])->name('home');

// Catalog Page, Product Detail & Bundle Detail
Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog');
Route::get('/catalog/bundle/{id}', [CatalogController::class, 'showBundle'])->name('catalog.bundle');
Route::get('/catalog/{id}', [CatalogController::class, 'show'])->name('catalog.show');

// Cart Routes (requires customer authentication)
Route::middleware(['customer_auth'])->group(function () {
    Route::get('/cart', [CartController::class, 'index'])->name('cart');
    Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::post('/cart/add-bundle', [CartController::class, 'addBundle'])->name('cart.add-bundle');
    Route::post('/cart/update/{id}', [CartController::class, 'update'])->name('cart.update');
    Route::post('/cart/select', [CartController::class, 'select'])->name('cart.select');
    Route::post('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');
    Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');
});

// Payment Routes (requires customer authentication)
Route::middleware(['customer_auth'])->group(function () {
    Route::get('/payment', [PaymentController::class, 'index'])->name('payment');
    Route::match(['get', 'post'], '/payment/qris', [PaymentController::class, 'qris'])->name('payment.qris');
    Route::post('/payment/process', [PaymentController::class, 'process'])->name('payment.process');
});

// Refund & Notification Routes (requires customer authentication)
Route::middleware(['customer_auth'])->group(function () {
    Route::post('/history/{order}/refund', [RefundController::class, 'store'])->name('refund.store');
    Route::post('/history/{order}/return', [ReturnController::class, 'store'])->name('returns.submit');
    Route::post('/history/{order}/pay-denda', [ReturnController::class, 'payDenda'])->name('returns.payDenda');
    Route::post('/notifications/read-all', [RefundController::class, 'markAllRead'])->name('notifications.readAll');
    Route::get('/notifications/{id}/open', [UserNotificationController::class, 'open'])->name('notifications.open');
});

// Auth Routes
Route::get('/login', [AuthController::class, 'customerLoginForm'])->name('login');
Route::post('/login', [AuthController::class, 'customerLogin'])->name('login.store');
Route::get('/admin/login', [AuthController::class, 'adminLoginForm'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'adminLogin'])->name('admin.login.store');
Route::get('/register', [AuthController::class, 'registerForm'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.store');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Member Portal & User Dashboard Routes
Route::get('/history', [HomeController::class, 'history'])->name('history');
Route::get('/profile', [HomeController::class, 'profile'])->name('profile');
Route::post('/profile', [HomeController::class, 'updateProfile'])->name('profile.update');
Route::post('/profile/password', [HomeController::class, 'updatePassword'])->name('profile.password');

// Public Information Pages
Route::get('/store-location', function () {
    return view('home.store-location');
})->name('store.location');

// File Bukti Terkontrol (admin / pemilik booking) — penyajian dari storage privat.
Route::get('/files/payment-proof/{payment}', [FileController::class, 'paymentProof'])->name('file.payment-proof');
Route::get('/files/return-proof/{return}', [FileController::class, 'returnProof'])->name('file.return-proof');
Route::get('/files/parent-consent/{user}', [FileController::class, 'parentConsent'])->name('file.parent-consent');
Route::get('/files/ktp-guardian/{user}', [FileController::class, 'ktpGuardian'])->name('file.ktp-guardian');
Route::get('/files/student-card/{user}', [FileController::class, 'studentCard'])->name('file.student-card');

Route::get('/contact-admin', function () {
    return view('home.contact-admin');
})->name('contact.admin');
