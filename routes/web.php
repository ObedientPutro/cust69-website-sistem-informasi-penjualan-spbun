<?php

use App\Http\Controllers\Core\CustomerController;
use App\Http\Controllers\Core\DashboardController;
use App\Http\Controllers\Core\ProductController;
use App\Http\Controllers\Core\UserController;
use App\Http\Controllers\History\RestockHistoryController;
use App\Http\Controllers\History\SoundingHistoryController;
use App\Http\Controllers\History\TransactionHistoryController;
use App\Http\Controllers\Inventory\InventoryController;
use App\Http\Controllers\Inventory\InventoryLogController;
use App\Http\Controllers\Inventory\RestockController;
use App\Http\Controllers\Inventory\TankSoundingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Report\ReportController;
use App\Http\Controllers\Transaction\DebtController;
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
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // --- TRANSAKSI (POS) ---
    // Edit, Update, Destroy (Itu jatah Owner)
    Route::get('/pos', [TransactionController::class, 'create'])->name('transactions.create');
    Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.save');

    // --- MODUL HUTANG ---
    Route::get('/debts', [DebtController::class, 'index'])->name('debts.index');
    Route::post('/debts/{transaction}/repay', [DebtController::class, 'repay'])->name('debts.repay');

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
    Route::patch('/customers/{customer}/toggle', [CustomerController::class, 'toggleStatus'])
        ->name('customers.toggle-status');

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
        Route::patch('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])
            ->name('users.toggle-status');

        // --- MASTER DATA: PRODUCTS (BBM) ---
        Route::resource('/products', ProductController::class)
            ->except(['show', 'create', 'edit'])
            ->names([
                'index'   => 'products.index',
                'store'   => 'products.save',
                'update'  => 'products.update',
                'destroy' => 'products.delete',
            ]);
        Route::patch('/products/{product}/toggle', [ProductController::class, 'toggleStatus'])
            ->name('products.toggle-status');

        // --- RIWAYAT RESTOCK ---
        Route::get('/history/restocks', [RestockHistoryController::class, 'index'])->name('restock-history.index');
        Route::get('/history/restocks/export', [RestockHistoryController::class, 'export'])->name('restock-history.export');

        // --- RIWAYAT SOUNDING (AUDIT) ---
        Route::get('/history/soundings', [SoundingHistoryController::class, 'index'])->name('sounding-history.index');
        Route::get('/history/soundings/export', [SoundingHistoryController::class, 'export'])->name('sounding-history.export');

        // --- RIWAYAT TRANSACTION ---
        Route::get('/history/transactions', [TransactionHistoryController::class, 'index'])->name('history.transactions.index');
        Route::get('/history/transactions/export', [TransactionHistoryController::class, 'export'])->name('history.transactions.export');

        // --- LAPORAN ---


        // CRUD Transaksi (Edit & Hapus & Update Backdate)
        Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
        Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.delete');
    });

});

require __DIR__.'/auth.php';
