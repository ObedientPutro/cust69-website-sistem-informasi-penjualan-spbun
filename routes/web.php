<?php

use App\Http\Controllers\Core\CustomerController;
use App\Http\Controllers\Core\DashboardController;
use App\Http\Controllers\Core\ProductController;
use App\Http\Controllers\Core\UserController;
use App\Http\Controllers\Inventory\RestockController;
use App\Http\Controllers\Inventory\TankSoundingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Report\ReportController;
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
    Route::resource('/transactions', TransactionController::class)
        ->except(['edit', 'update', 'destroy'])
        ->names([
            'index'   => 'transactions.index',
            'create'  => 'transactions.new',  // HALAMAN KASIR
            'store'   => 'transactions.save', // PROSES BAYAR
            'show'    => 'transactions.view', // LIHAT DETAIL/STRUK
        ]);

    // --- MASTER DATA: CUSTOMERS (NELAYAN) ---
    Route::resource('/customers', CustomerController::class)
        ->except(['show'])
        ->names([
            'index'   => 'customers.index',
            'create'  => 'customers.new',
            'store'   => 'customers.save',
            'edit'    => 'customers.modify',
            'update'  => 'customers.update',
            'destroy' => 'customers.delete',
        ]);

    // --- INVENTORY: TANK SOUNDING (STOK OPNAME) ---
    // Tank Sounding (Operator wajib lapor stok fisik harian)
    // Hanya Create & Store. Index boleh lihat history sendiri.
    Route::resource('/tank-soundings', TankSoundingController::class)
        ->except(['show', 'edit', 'update', 'destroy'])
        ->names([
            'index'   => 'soundings.index',
            'create'  => 'soundings.new',
            'store'   => 'soundings.save',
        ]);

    // =====================================================================
    // GROUP 2: OWNER ONLY (SUPER ADMIN)
    // =====================================================================
    Route::middleware(['can:access-owner'])->group(function () {
        // --- USERS (OPERATOR MANAGEMENT) ---
        Route::resource('/users', UserController::class)
            ->except(['show'])
            ->names([
                'index'   => 'users.index',
                'create'  => 'users.new',
                'store'   => 'users.save',
                'edit'    => 'users.modify',
                'update'  => 'users.update',
                'destroy' => 'users.delete',
            ]);

        // --- MASTER DATA: PRODUCTS (BBM) ---
        Route::resource('/products', ProductController::class)
            ->except(['show'])
            ->names([
                'index'   => 'products.index',
                'create'  => 'products.new',
                'store'   => 'products.save',
                'edit'    => 'products.modify',
                'update'  => 'products.update',
                'destroy' => 'products.delete',
            ]);

        // --- INVENTORY: RESTOCK (DO MASUK) ---
        Route::resource('/restocks', RestockController::class)
            ->except(['show'])
            ->names([
                'index'   => 'restocks.index',
                'create'  => 'restocks.new',
                'store'   => 'restocks.save',
                'edit'    => 'restocks.modify',
                'update'  => 'restocks.update',
                'destroy' => 'restocks.delete',
            ]);

        // --- LAPORAN ---
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('/export-pdf', [ReportController::class, 'exportPdf'])->name('export.pdf');
            Route::get('/export-excel', [ReportController::class, 'exportExcel'])->name('export.excel');
        });

        // Hapus Stok Opname (Jika operator salah input parah)
        Route::delete('/tank-soundings/{tank_sounding}', [TankSoundingController::class, 'destroy'])
            ->name('soundings.delete');

        // CRUD Transaksi (Edit & Hapus & Update Backdate)
        Route::get('/transactions/{transaction}/edit', [TransactionController::class, 'edit'])->name('transactions.modify');
        Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
        Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.delete');
    });

});

require __DIR__.'/auth.php';
