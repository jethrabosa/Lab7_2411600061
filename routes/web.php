<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryTransactionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SupplierController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('home');

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard');

Route::resource('products', ProductController::class);
Route::get('/transactions/adjustments/create', [InventoryTransactionController::class, 'adjustment'])
    ->name('transactions.adjustment.create');
Route::post('/transactions/adjustments', [InventoryTransactionController::class, 'storeAdjustment'])
    ->name('transactions.adjustment.store');
Route::resource('transactions', InventoryTransactionController::class)
    ->parameters(['transactions' => 'inventoryTransaction'])
    ->except(['edit', 'update', 'destroy']);

Route::get('/api/dashboard/stats', [DashboardController::class, 'stats']);
Route::get('/api/products/low-stock', [DashboardController::class, 'lowStock']);
Route::get('/api/transactions/recent', [DashboardController::class, 'recentTransactions']);

Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
Route::get('/reports/transactions.csv', [ReportController::class, 'export'])->name('reports.export');
Route::get('/notifications', [DashboardController::class, 'lowStockPage'])->name('notifications.index');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('can:manage-procurement')->group(function () {
        Route::resource('suppliers', SupplierController::class)->except(['show']);
        Route::get('/purchase-orders', [PurchaseOrderController::class, 'index'])->name('purchase-orders.index');
        Route::get('/purchase-orders/create', [PurchaseOrderController::class, 'create'])->name('purchase-orders.create');
        Route::post('/purchase-orders', [PurchaseOrderController::class, 'store'])->name('purchase-orders.store');
        Route::post('/purchase-orders/generate', [PurchaseOrderController::class, 'generate'])->name('purchase-orders.generate');
        Route::get('/purchase-orders/{purchaseOrder}', [PurchaseOrderController::class, 'show'])->name('purchase-orders.show');
        Route::post('/purchase-orders/{purchaseOrder}/approve', [PurchaseOrderController::class, 'approve'])->name('purchase-orders.approve');
        Route::post('/purchase-orders/{purchaseOrder}/reject', [PurchaseOrderController::class, 'reject'])->name('purchase-orders.reject');
        Route::post('/purchase-orders/{purchaseOrder}/mark-ordered', [PurchaseOrderController::class, 'markOrdered'])->name('purchase-orders.mark-ordered');
        Route::post('/purchase-orders/{purchaseOrder}/receive', [PurchaseOrderController::class, 'receive'])->name('purchase-orders.receive');
    });
});

require __DIR__.'/auth.php';
