<?php

use App\Http\Controllers\ActivityLogController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\CashierController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\ReconciliationController;
use App\Http\Controllers\ShiftController;

// Login
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
});

// Logout
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    // Notifications
    Route::put('/notifications/{id}/read', [NotificationController::class, 'read'])
        ->name('notifications.read');
    Route::put('/notifications/read-all', [NotificationController::class, 'readAll'])
        ->name('notifications.read_all');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::middleware('role:admin,cashier')->group(function () {
        // Shifts
        Route::get('/shift/status', [ShiftController::class, 'status'])->name('shift.status');
        Route::post('/shift/start', [ShiftController::class, 'start'])->name('shift.start');
        Route::post('/shift/end', [ShiftController::class, 'end'])->name('shift.end');
        Route::get('/shift', [ShiftController::class, 'index'])->name('shift');
        Route::get('/shift-data', [ShiftController::class, 'data'])->name('shift.data');

        // Kasir
        Route::get('/kasir', [CashierController::class, 'index'])->name('kasir');
        Route::get('/kasir/products', [CashierController::class, 'products'])->name('kasir.products');
        Route::post('/kasir/checkout', [CashierController::class, 'checkout'])->name('kasir.checkout');
        Route::post('/kasir/checkout/{transaction}/confirm-qris', [CashierController::class, 'confirmQris'])->name('kasir.confirm-qris');
        Route::post('/kasir/checkout/{transaction}/cancel-qris', [CashierController::class, 'cancelQris'])->name('kasir.cancel-qris');
        Route::post('/kasir/hold', [CashierController::class, 'hold'])->name('kasir.hold');
        Route::get('/kasir/holds', [CashierController::class, 'holds'])->name('kasir.holds');
        Route::post('/kasir/holds/{transaction}/resume', [CashierController::class, 'resume'])->name('kasir.holds.resume');
        Route::delete('/kasir/holds/{transaction}', [CashierController::class, 'destroyHold'])->name('kasir.holds.destroy');


        // Transaksi
        Route::get('/transaksi/{transaction}/struk', [TransactionController::class, 'receipt'])->name('transaksi.struk');
        Route::get('/transaksi', [TransactionController::class, 'index'])->name('transaksi');
        Route::get('/transaksi-data', [TransactionController::class, 'data'])->name('transaksi.data');
        Route::get('/transaksi/{transaction}', [TransactionController::class, 'show'])->name('transaksi.show');

        // Pembayaran
        Route::get('/pembayaran/{transaction}', [PaymentController::class, 'show'])->name('pembayaran.show');
        Route::get('/pembayaran/{transaction}/status', [PaymentController::class, 'status'])->name('pembayaran.status');
        Route::get('/pembayaran/{transaction}/complete', [PaymentController::class, 'complete'])->name('pembayaran.complete');

        // Rekonsiliasi
        Route::get('/rekonsiliasi', [ReconciliationController::class, 'index'])->name('rekonsiliasi');
        Route::get('/rekonsiliasi-data', [ReconciliationController::class, 'data'])->name('rekonsiliasi.data');
        Route::get('/rekonsiliasi-shifts', [ReconciliationController::class, 'shifts'])->name('rekonsiliasi.shifts');
    });

    Route::middleware('role:admin')->group(function () {
        // Pembayaran
        Route::get('/pembayaran', [PaymentController::class, 'index'])->name('pembayaran');
        Route::get('/pembayaran-data', [PaymentController::class, 'data'])->name('pembayaran.data');

        // Laporan
        Route::get('/laporan', [ReportController::class, 'index'])->name('laporan');
        Route::get('/laporan-data', [ReportController::class, 'data'])->name('laporan.data');
        Route::get('/laporan/unduh', [ReportController::class, 'download'])->name('laporan.unduh');

        // Kategori
        Route::resource('kategori', CategoryController::class)
            ->parameters(['kategori' => 'category'])
            ->names('kategori')
            ->except(['show']);
        Route::get('/kategori-data', [CategoryController::class, 'data'])->name('kategori.data');

        // Produk
        Route::resource('produk', ProductController::class)
            ->parameters(['produk' => 'product'])
            ->names('produk')
            ->except(['show']);
        Route::get('/produk-data', [ProductController::class, 'data'])->name('produk.data');

        // Pengguna
        Route::resource('pengguna', UserController::class)
            ->parameters(['pengguna' => 'user'])
            ->names('pengguna')
            ->except(['show']);
        Route::get('/pengguna-data', [UserController::class, 'data'])->name('pengguna.data');

        // Pengaturan
        Route::get('/pengaturan', [SettingsController::class, 'index'])->name('pengaturan.index');
        Route::match(['put', 'post'], '/pengaturan', [SettingsController::class, 'update'])->name('pengaturan.update');
        Route::get('/pengaturan/preview-receipt', [SettingsController::class, 'previewReceipt'])->name('pengaturan.preview');

        // Log Aktivitas
        Route::get('/log-aktivitas', [ActivityLogController::class, 'index'])->name('log-aktivitas');
        Route::get('/log-aktivitas-data', [ActivityLogController::class, 'data'])->name('log-aktivitas.data');
    });
});
