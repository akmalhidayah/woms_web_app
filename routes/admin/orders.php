<?php

use App\Http\Controllers\Admin\Orders\OrderController;
use App\Http\Controllers\Admin\ApprovalSignatureReassignmentController;
use App\Http\Controllers\Admin\Orders\OrderDocumentController;
use App\Http\Controllers\Admin\Orders\InitialWorkController;
use App\Http\Controllers\Admin\Orders\OrderScopeOfWorkController;
use App\Http\Controllers\Admin\Orders\OrderWorkshopController;
use App\Http\Controllers\Admin\Orders\OrderWorkshopQualityControlController;
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
        Route::get('/workshop/{order}/quality-control/create', [OrderWorkshopQualityControlController::class, 'create'])->name('workshop.quality-control.create');
        Route::post('/workshop/{order}/quality-control', [OrderWorkshopQualityControlController::class, 'store'])->name('workshop.quality-control.store');
        Route::get('/workshop/{order}/quality-control/{qualityControlReport}/edit', [OrderWorkshopQualityControlController::class, 'edit'])->name('workshop.quality-control.edit');
        Route::put('/workshop/{order}/quality-control/{qualityControlReport}', [OrderWorkshopQualityControlController::class, 'update'])->name('workshop.quality-control.update');
        Route::get('/workshop/{order}/quality-control/{qualityControlReport}/pdf', [OrderWorkshopQualityControlController::class, 'pdf'])->name('workshop.quality-control.pdf');
        Route::post('/workshop/{order}/quality-control/{qualityControlReport}/resend-approval', [OrderWorkshopQualityControlController::class, 'resendApproval'])->name('workshop.quality-control.approval.resend');
        Route::post('/workshop/{order}/quality-control/{qualityControlReport}/regenerate-approval-token', [OrderWorkshopQualityControlController::class, 'regenerateApprovalToken'])->name('workshop.quality-control.approval.regenerate');
        Route::patch('/approval-signatures/quality-control/{signature}/reassign', [ApprovalSignatureReassignmentController::class, 'qualityControl'])
            ->whereNumber('signature')
            ->name('approval-signatures.quality-control.reassign');
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
        Route::post('/{order}/initial-work/{initialWork}/resend-approval', [InitialWorkController::class, 'resendApproval'])->name('initial-work.approval.resend');
        Route::post('/{order}/initial-work/{initialWork}/regenerate-approval-token', [InitialWorkController::class, 'regenerateApprovalToken'])->name('initial-work.approval.regenerate');
        Route::patch('/approval-signatures/initial-work/{signature}/reassign', [ApprovalSignatureReassignmentController::class, 'initialWork'])
            ->whereNumber('signature')
            ->name('approval-signatures.initial-work.reassign');
        Route::post('/{order}/scope-of-work', [OrderScopeOfWorkController::class, 'store'])->name('scope-of-work.store');
        Route::put('/{order}/scope-of-work/{scopeOfWork}', [OrderScopeOfWorkController::class, 'update'])->name('scope-of-work.update');
        Route::get('/{order}/scope-of-work/{scopeOfWork}/pdf', [OrderScopeOfWorkController::class, 'pdf'])->name('scope-of-work.pdf');
    });

Route::delete('admin/quality-control/{qualityControlReport}/files/{file}', [OrderWorkshopQualityControlController::class, 'destroyFile'])
    ->middleware(['auth', 'role:admin', 'admin_menu:orders'])
    ->name('admin.quality-control.files.destroy');

Route::get('admin/quality-control/{qualityControlReport}/files/{file}/preview', [OrderWorkshopQualityControlController::class, 'showFile'])
    ->middleware(['auth', 'role:admin', 'admin_menu:orders'])
    ->name('admin.quality-control.files.preview');
