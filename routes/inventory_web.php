<?php

use App\Http\Controllers\Admin\Inventory\AdjustmentController;
use App\Http\Controllers\Admin\Inventory\AttachmentController;
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
        Route::get('/items/create', [ItemController::class, 'create'])->name('items.create');
        Route::post('/items', [ItemController::class, 'store'])->name('items.store');
        Route::get('/items/{inventoryItem}/edit', [ItemController::class, 'edit'])->whereNumber('inventoryItem')->name('items.edit');
        Route::put('/items/{inventoryItem}', [ItemController::class, 'update'])->whereNumber('inventoryItem')->name('items.update');
        Route::delete('/items/{inventoryItem}', [ItemController::class, 'destroy'])->whereNumber('inventoryItem')->name('items.destroy');
        Route::post('/items/{inventoryItem}/restore', [ItemController::class, 'restore'])->whereNumber('inventoryItem')->name('items.restore');
        Route::get('/items/{inventoryItem}/image', [ItemController::class, 'image'])->whereNumber('inventoryItem')->name('items.image');
        Route::get('/stock-in', [StockInController::class, 'index'])->name('stock-in.index');
        Route::get('/stock-in/create', [StockInController::class, 'create'])->name('stock-in.create');
        Route::post('/stock-in', [StockInController::class, 'store'])->name('stock-in.store');
        Route::get('/adjustments', [AdjustmentController::class, 'index'])->name('adjustments.index');
        Route::post('/adjustments', [AdjustmentController::class, 'store'])->name('adjustments.store');
        Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::get('/transactions/{inventoryTransaction}', [TransactionController::class, 'show'])->whereNumber('inventoryTransaction')->name('transactions.show');
        Route::get('/attachments/{inventoryAttachment}', AttachmentController::class)->whereNumber('inventoryAttachment')->name('attachments.show');
        Route::controller(InventoryUserController::class)->prefix('users')->name('users.')->group(function (): void {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::patch('/{inventoryUser}/status', 'updateStatus')->name('status');
            Route::post('/{inventoryUser}/reset-password', 'resetPassword')->name('reset-password');
        });
        Route::get('/master-data', [MasterDataController::class, 'index'])->name('master-data.index');
        Route::post('/master-data/{type}', [MasterDataController::class, 'store'])->whereIn('type', ['categories', 'subcategories', 'locations', 'request-types'])->name('master-data.store');
        Route::put('/master-data/{type}/{id}', [MasterDataController::class, 'update'])->whereIn('type', ['categories', 'subcategories', 'locations', 'request-types'])->whereNumber('id')->name('master-data.update');
        Route::patch('/master-data/{type}/{id}/status', [MasterDataController::class, 'status'])->whereIn('type', ['categories', 'subcategories', 'locations', 'request-types'])->whereNumber('id')->name('master-data.status');
        Route::delete('/master-data/{type}/{id}', [MasterDataController::class, 'destroy'])->whereIn('type', ['categories', 'subcategories', 'locations', 'request-types'])->whereNumber('id')->name('master-data.destroy');
    });
