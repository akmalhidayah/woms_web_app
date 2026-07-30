<?php

use App\Http\Controllers\Admin\Inventory\AdjustmentController;
use App\Http\Controllers\Admin\Inventory\DashboardController;
use App\Http\Controllers\Admin\Inventory\InventoryUserController;
use App\Http\Controllers\Admin\Inventory\ItemController;
use App\Http\Controllers\Admin\Inventory\MasterDataController;
use App\Http\Controllers\Admin\Inventory\StockInController;
use App\Http\Controllers\Admin\Inventory\TransactionController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/inventory')
    ->name('admin.inventory.')
    ->middleware(['auth', 'role:admin', 'admin_menu:inventory'])
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/items', [ItemController::class, 'index'])->name('items.index');
        Route::get('/stock-in', [StockInController::class, 'index'])->name('stock-in.index');
        Route::get('/adjustments', [AdjustmentController::class, 'index'])->name('adjustments.index');
        Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::get('/users', [InventoryUserController::class, 'index'])->name('users.index');
        Route::get('/master-data', [MasterDataController::class, 'index'])->name('master-data.index');
    });
