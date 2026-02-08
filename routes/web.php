<?php

use App\Http\Controllers\Core\CustomerController;
use App\Http\Controllers\Core\DashboardController;
use App\Http\Controllers\Core\InventoryController;
use App\Http\Controllers\Core\NotificationController;
use App\Http\Controllers\Core\ProductController;
use App\Http\Controllers\Core\SiteSettingController;
use App\Http\Controllers\Core\UserController;
use App\Http\Controllers\History\RestockHistoryController;
use App\Http\Controllers\History\SoundingHistoryController;
use App\Http\Controllers\History\TransactionHistoryController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Report\ReportController;
use App\Http\Controllers\Transaction\DebtController;
use App\Http\Controllers\Transaction\ShiftController;
use App\Http\Controllers\Transaction\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // --- PROFILE ---
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

    // --- TRANSAKSI (POS) ---
    // Edit, Update, Destroy (Itu jatah Owner)
    Route::get('/pos', [TransactionController::class, 'create'])->name('transactions.create');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.save');
    Route::get('/transactions/{transaction}/print', [TransactionController::class, 'print'])->name('transactions.print');

    // --- MODUL HUTANG ---
    Route::get('/debts', [DebtController::class, 'index'])->name('debts.index');
    Route::post('/debts/{transaction}/repay', [DebtController::class, 'repay'])->name('debts.repay');

    // --- MASTER DATA: PRODUCTS (BBM) ---
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');

    // Inventory Routes
    Route::post('/inventory/restock', [InventoryController::class, 'storeRestock'])->name('inventory.restock');
    Route::post('/inventory/sounding', [InventoryController::class, 'storeSounding'])->name('inventory.sounding');

    // --- MASTER DATA: CUSTOMERS (NELAYAN) ---
    Route::resource('/customers', CustomerController::class)
        ->except(['show', 'edit', 'create'])
        ->names([
            'index'   => 'customers.index',
            'store'   => 'customers.save',
            'update'  => 'customers.update',
            'destroy' => 'customers.delete',
        ]);
    Route::patch('/customers/{customer}/toggle', [CustomerController::class, 'toggleStatus'])->name('customers.toggle-status');

    // --- OPEN AND CLOSE SHIFT ---
    Route::get('/shifts', [ShiftController::class, 'index'])->name('shifts.index');
    Route::post('/shifts', [ShiftController::class, 'store'])->name('shifts.store');
    Route::post('/shifts/{shift}/close', [ShiftController::class, 'update'])->name('shifts.close');

    // --- RIWAYAT TRANSACTION ---
    Route::get('/history/transactions', [TransactionHistoryController::class, 'index'])->name('history.transactions.index');

    // --- RIWAYAT RESTOCK ---
    Route::get('/history/restocks', [RestockHistoryController::class, 'index'])->name('restock-history.index');

    // --- RIWAYAT SOUNDING (AUDIT) ---
    Route::get('/history/soundings', [SoundingHistoryController::class, 'index'])->name('sounding-history.index');

    // =====================================================================
    // GROUP 2: OWNER ONLY (SUPER ADMIN)
    // =====================================================================
    Route::middleware(['can:access-owner'])->group(function () {
        // --- USERS (OPERATOR MANAGEMENT) ---
        Route::resource('/users', UserController::class)
            ->except(['show', 'create', 'edit'])
            ->names([
                'index'   => 'users.index',
                'store'   => 'users.save',
                'update'  => 'users.update',
                'destroy' => 'users.delete',
            ]);
        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');

        Route::put('/customers/{customer}/limit', [CustomerController::class, 'updateLimit'])->name('customers.update-limit');
        Route::put('/settings/customers/default-limit', [CustomerController::class, 'saveDefaultLimit'])->name('customers.default-limit');

        // --- MASTER DATA: PRODUCTS (BBM) ---
        Route::post('/products', [ProductController::class, 'store'])->name('products.save');
        Route::put('/products/{product}', [ProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [ProductController::class, 'destroy'])->name('products.delete');
        Route::patch('/products/{product}/toggle', [ProductController::class, 'toggleStatus'])->name('products.toggle-status');

        Route::put('/shifts/{shift}/audit', [ShiftController::class, 'audit'])->name('shifts.audit');

        // --- RIWAYAT RESTOCK ---
        Route::get('/history/restocks/export', [RestockHistoryController::class, 'export'])->name('restock-history.export');
        Route::put('/history/restocks/{id}', [RestockHistoryController::class, 'update'])->name('restock-history.update');

        // --- RIWAYAT SOUNDING (AUDIT) ---
        Route::get('/history/soundings/export', [SoundingHistoryController::class, 'export'])->name('sounding-history.export');
        Route::put('/history/soundings/{id}', [SoundingHistoryController::class, 'update'])->name('sounding-history.update');

        // --- RIWAYAT TRANSACTION ---
        Route::get('/history/transactions/export', [TransactionHistoryController::class, 'export'])->name('history.transactions.export');

        // --- LAPORAN ---
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/sales', [ReportController::class, 'sales'])->name('sales');
            Route::get('/stock', [ReportController::class, 'stock'])->name('stock');
            Route::get('/profit', [ReportController::class, 'profit'])->name('profit');
            Route::get('/export/{type}', [ReportController::class, 'export'])->name('export');
        });

        // BACKDATE TRANSACTION ROUTES
        Route::get('/transactions/backdate', [TransactionController::class, 'createBackdate'])->name('transactions.backdate');
        Route::post('/transactions/backdate', [TransactionController::class, 'storeBackdate'])->name('transactions.store-backdate');

        // CRUD Transaksi (Edit & Hapus & Update Backdate)
        Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
        Route::post('/transactions/{transaction}', [TransactionController::class, 'return'])->name('transactions.return');


        // CRUD WEB SETTINGS
        Route::get('/settings', [SiteSettingController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SiteSettingController::class, 'update'])->name('settings.update');

        // OWNER NOTIFICATION
        Route::get('/notifications/json', [NotificationController::class, 'getJson'])->name('notifications.json');
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
        Route::delete('/notifications/clear-read', [NotificationController::class, 'destroyAllRead'])->name('notifications.destroy-read');
        Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');
    });

});

require __DIR__.'/auth.php';
