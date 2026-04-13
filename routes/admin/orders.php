<?php

use App\Http\Controllers\Admin\Orders\OrderController;
use App\Http\Controllers\Admin\Orders\OrderDocumentController;
use App\Http\Controllers\Admin\Orders\InitialWorkController;
use App\Http\Controllers\Admin\Orders\OrderScopeOfWorkController;
use App\Http\Controllers\Admin\Orders\OrderWorkshopController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/orders')
    ->name('admin.orders.')
    ->middleware(['auth', 'role:admin', 'admin_menu:orders'])
    ->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/workshop/list', [OrderWorkshopController::class, 'index'])->name('workshop.index');
        Route::get('/create', [OrderController::class, 'create'])->name('create');
        Route::post('/', [OrderController::class, 'store'])->name('store');
        Route::patch('/{order}/priority', [OrderController::class, 'updatePriority'])->name('priority.update');
        Route::patch('/{order}/user-note', [OrderController::class, 'updateUserNote'])->name('user-note.update');
        Route::patch('/workshop/{order}', [OrderWorkshopController::class, 'update'])->name('workshop.update');
        Route::get('/{order}', [OrderController::class, 'show'])->name('show');
        Route::get('/{order}/edit', [OrderController::class, 'edit'])->name('edit');
        Route::put('/{order}', [OrderController::class, 'update'])->name('update');
        Route::delete('/{order}', [OrderController::class, 'destroy'])->name('destroy');

        Route::get('/{order}/documents', [OrderDocumentController::class, 'index'])->name('documents.index');
        Route::post('/{order}/documents/upload', [OrderDocumentController::class, 'store'])->name('documents.store');
        Route::get('/{order}/documents/{document}/preview', [OrderDocumentController::class, 'preview'])->name('documents.preview');
        Route::get('/{order}/documents/{document}/download', [OrderDocumentController::class, 'download'])->name('documents.download');
        Route::delete('/{order}/documents/{document}', [OrderDocumentController::class, 'destroy'])->name('documents.destroy');
        Route::post('/{order}/initial-work', [InitialWorkController::class, 'store'])->name('initial-work.store');
        Route::put('/{order}/initial-work/{initialWork}', [InitialWorkController::class, 'update'])->name('initial-work.update');
        Route::get('/{order}/initial-work/{initialWork}/pdf', [InitialWorkController::class, 'pdf'])->name('initial-work.pdf');
        Route::post('/{order}/scope-of-work', [OrderScopeOfWorkController::class, 'store'])->name('scope-of-work.store');
        Route::put('/{order}/scope-of-work/{scopeOfWork}', [OrderScopeOfWorkController::class, 'update'])->name('scope-of-work.update');
        Route::get('/{order}/scope-of-work/{scopeOfWork}/pdf', [OrderScopeOfWorkController::class, 'pdf'])->name('scope-of-work.pdf');
    });
