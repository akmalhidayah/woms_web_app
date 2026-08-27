<?php

use App\Http\Controllers\Admin\ApprovalSignatureReassignmentController;
use App\Http\Controllers\Admin\Orders\InitialWorkController;
use App\Http\Controllers\Admin\Orders\OrderController;
use App\Http\Controllers\Admin\Orders\OrderDocumentController;
use App\Http\Controllers\Admin\Orders\OrderScopeOfWorkController;
use App\Http\Controllers\Admin\Orders\OrderWorkshopController;
use App\Http\Controllers\Admin\Orders\OrderWorkshopQualityControlController;
use App\Http\Controllers\Admin\Orders\WorkshopWorkPackageController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin/orders')
    ->name('admin.orders.')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {
        Route::get('/', [OrderController::class, 'index'])->middleware('admin_menu:order_jasa')->name('index');
        Route::get('/workshop/list', [OrderWorkshopController::class, 'index'])->middleware('admin_menu:order_bengkel')->name('workshop.index');
        Route::post('/workshop', [OrderWorkshopController::class, 'store'])->middleware('admin_menu:order_bengkel')->name('workshop.store');
        Route::get('/create', [OrderController::class, 'create'])->middleware('admin_menu:order_jasa')->name('create');
        Route::post('/', [OrderController::class, 'store'])->middleware('admin_menu:order_jasa')->name('store');
        Route::patch('/{order}/priority', [OrderController::class, 'updatePriority'])->middleware('admin_order_menu')->name('priority.update');
        Route::patch('/{order}/user-note', [OrderController::class, 'updateUserNote'])->middleware('admin_order_menu')->name('user-note.update');
        Route::patch('/workshop/{order}', [OrderWorkshopController::class, 'update'])->middleware('admin_menu:order_bengkel')->name('workshop.update');
        Route::get('/workshop/{order}/work-packages', [WorkshopWorkPackageController::class, 'index'])->middleware('admin_menu:order_bengkel')->name('workshop.work-packages.index');
        Route::post('/workshop/{order}/work-packages', [WorkshopWorkPackageController::class, 'store'])->middleware('admin_menu:order_bengkel')->name('workshop.work-packages.store');
        Route::post('/workshop/{order}/work-packages/batch', [WorkshopWorkPackageController::class, 'batch'])->middleware('admin_menu:order_bengkel')->name('workshop.work-packages.batch');
        Route::patch('/workshop/{order}/work-packages/{workPackage}', [WorkshopWorkPackageController::class, 'update'])->middleware('admin_menu:order_bengkel')->name('workshop.work-packages.update');
        Route::delete('/workshop/{order}/work-packages/{workPackage}', [WorkshopWorkPackageController::class, 'destroy'])->middleware('admin_menu:order_bengkel')->name('workshop.work-packages.destroy');
        Route::patch('/workshop/{order}/work-packages/{workPackage}/status', [WorkshopWorkPackageController::class, 'updateStatusForOrder'])->middleware('admin_menu:order_bengkel')->name('workshop.work-packages.status.update');
        Route::patch('/work-packages/{workPackage}/status', [WorkshopWorkPackageController::class, 'updateStatus'])->middleware('admin_menu:display_pekerjaan_bengkel')->name('work-packages.status.update');
        Route::get('/workshop/{order}/quality-control/create', [OrderWorkshopQualityControlController::class, 'create'])->middleware('admin_menu:quality_control_bengkel')->name('workshop.quality-control.create');
        Route::post('/workshop/{order}/quality-control', [OrderWorkshopQualityControlController::class, 'store'])->middleware('admin_menu:quality_control_bengkel')->name('workshop.quality-control.store');
        Route::get('/workshop/{order}/quality-control/{qualityControlReport}/edit', [OrderWorkshopQualityControlController::class, 'edit'])->middleware('admin_menu:quality_control_bengkel')->name('workshop.quality-control.edit');
        Route::put('/workshop/{order}/quality-control/{qualityControlReport}', [OrderWorkshopQualityControlController::class, 'update'])->middleware('admin_menu:quality_control_bengkel')->name('workshop.quality-control.update');
        Route::get('/workshop/{order}/quality-control/{qualityControlReport}/pdf', [OrderWorkshopQualityControlController::class, 'pdf'])->middleware('admin_menu:quality_control_bengkel')->name('workshop.quality-control.pdf');
        Route::post('/workshop/{order}/quality-control/{qualityControlReport}/resend-approval', [OrderWorkshopQualityControlController::class, 'resendApproval'])->middleware('admin_menu:quality_control_bengkel')->name('workshop.quality-control.approval.resend');
        Route::post('/workshop/{order}/quality-control/{qualityControlReport}/regenerate-approval-token', [OrderWorkshopQualityControlController::class, 'regenerateApprovalToken'])->middleware('admin_menu:quality_control_bengkel')->name('workshop.quality-control.approval.regenerate');
        Route::patch('/approval-signatures/quality-control/{signature}/reassign', [ApprovalSignatureReassignmentController::class, 'qualityControl'])
            ->middleware('admin_menu:quality_control_bengkel')
            ->whereNumber('signature')
            ->name('approval-signatures.quality-control.reassign');
        Route::get('/{order}', [OrderController::class, 'show'])->middleware('admin_order_menu')->name('show');
        Route::get('/{order}/edit', [OrderController::class, 'edit'])->middleware('admin_order_menu')->name('edit');
        Route::put('/{order}', [OrderController::class, 'update'])->middleware('admin_order_menu')->name('update');
        Route::delete('/{order}', [OrderController::class, 'destroy'])->middleware('admin_order_menu')->name('destroy');

        Route::get('/{order}/documents', [OrderDocumentController::class, 'index'])->middleware('admin_order_menu')->name('documents.index');
        Route::post('/{order}/documents/upload', [OrderDocumentController::class, 'store'])->middleware('admin_order_menu')->name('documents.store');
        Route::get('/{order}/documents/{document}/preview', [OrderDocumentController::class, 'preview'])->middleware('admin_order_menu')->name('documents.preview');
        Route::get('/{order}/documents/{document}/download', [OrderDocumentController::class, 'download'])->middleware('admin_order_menu')->name('documents.download');
        Route::delete('/{order}/documents/{document}', [OrderDocumentController::class, 'destroy'])->middleware('admin_order_menu')->name('documents.destroy');
        Route::post('/{order}/initial-work', [InitialWorkController::class, 'store'])->middleware('admin_menu:order_jasa')->name('initial-work.store');
        Route::put('/{order}/initial-work/{initialWork}', [InitialWorkController::class, 'update'])->middleware('admin_menu:order_jasa')->name('initial-work.update');
        Route::get('/{order}/initial-work/{initialWork}/pdf', [InitialWorkController::class, 'pdf'])->middleware('admin_menu:order_jasa')->name('initial-work.pdf');
        Route::post('/{order}/initial-work/{initialWork}/resend-approval', [InitialWorkController::class, 'resendApproval'])->middleware('admin_menu:order_jasa')->name('initial-work.approval.resend');
        Route::post('/{order}/initial-work/{initialWork}/regenerate-approval-token', [InitialWorkController::class, 'regenerateApprovalToken'])->middleware('admin_menu:order_jasa')->name('initial-work.approval.regenerate');
        Route::patch('/approval-signatures/initial-work/{signature}/reassign', [ApprovalSignatureReassignmentController::class, 'initialWork'])
            ->middleware('admin_menu:order_jasa')
            ->whereNumber('signature')
            ->name('approval-signatures.initial-work.reassign');
        Route::post('/{order}/scope-of-work', [OrderScopeOfWorkController::class, 'store'])->middleware('admin_order_menu')->name('scope-of-work.store');
        Route::put('/{order}/scope-of-work/{scopeOfWork}', [OrderScopeOfWorkController::class, 'update'])->middleware('admin_order_menu')->name('scope-of-work.update');
        Route::get('/{order}/scope-of-work/{scopeOfWork}/pdf', [OrderScopeOfWorkController::class, 'pdf'])->middleware('admin_order_menu')->name('scope-of-work.pdf');
    });

Route::delete('admin/quality-control/{qualityControlReport}/files/{file}', [OrderWorkshopQualityControlController::class, 'destroyFile'])
    ->middleware(['auth', 'role:admin', 'admin_menu:quality_control_bengkel'])
    ->name('admin.quality-control.files.destroy');

Route::get('admin/quality-control/{qualityControlReport}/files/{file}/preview', [OrderWorkshopQualityControlController::class, 'showFile'])
    ->middleware(['auth', 'role:admin', 'admin_menu:quality_control_bengkel'])
    ->name('admin.quality-control.files.preview');
