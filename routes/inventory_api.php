<?php

use App\Http\Controllers\Api\V1\Inventory\AttachmentController;
use App\Http\Controllers\Api\V1\Inventory\AuthController;
use App\Http\Controllers\Api\V1\Inventory\CatalogController;
use App\Http\Controllers\Api\V1\Inventory\DashboardController;
use App\Http\Controllers\Api\V1\Inventory\HistoryController;
use App\Http\Controllers\Api\V1\Inventory\ItemController;
use App\Http\Controllers\Api\V1\Inventory\RequestController;
use App\Http\Middleware\Inventory\EnsureInventoryMobileToken;
use App\Http\Middleware\Inventory\EnsureInventoryPasswordChanged;
use App\Http\Middleware\Inventory\HandleInventoryApiExceptions;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/inventory')
    ->name('api.v1.inventory.')
    ->middleware([HandleInventoryApiExceptions::class])
    ->group(function () {
        Route::post('auth/login', [AuthController::class, 'login'])
            ->middleware('throttle:30,1')
            ->name('auth.login');

        Route::middleware(['auth:sanctum', EnsureInventoryMobileToken::class])->group(function () {
            Route::get('auth/me', [AuthController::class, 'me'])->name('auth.me');
            Route::post('auth/change-password', [AuthController::class, 'changePassword'])->name('auth.change-password');
            Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
            Route::post('auth/logout-all', [AuthController::class, 'logoutAll'])->name('auth.logout-all');

            Route::middleware([EnsureInventoryPasswordChanged::class, 'throttle:120,1'])->group(function () {
                Route::get('dashboard', DashboardController::class)->name('dashboard');

                Route::prefix('catalogs')->name('catalogs.')->group(function () {
                    Route::get('categories', [CatalogController::class, 'categories'])->name('categories');
                    Route::get('subcategories', [CatalogController::class, 'subcategories'])->name('subcategories');
                    Route::get('locations', [CatalogController::class, 'locations'])->name('locations');
                    Route::get('request-types', [CatalogController::class, 'requestTypes'])->name('request-types');
                });

                Route::get('items', [ItemController::class, 'index'])->name('items.index');
                Route::get('items/{inventoryItem}', [ItemController::class, 'show'])
                    ->whereNumber('inventoryItem')->name('items.show');
                Route::get('items/{inventoryItem}/image', [ItemController::class, 'image'])
                    ->whereNumber('inventoryItem')->name('items.image');

                Route::post('requests', RequestController::class)
                    ->withoutMiddleware('throttle:120,1')
                    ->middleware('throttle:20,1')
                    ->name('requests.store');

                Route::get('my-history', [HistoryController::class, 'index'])->name('history.index');
                Route::get('my-history/{inventoryTransaction}', [HistoryController::class, 'show'])
                    ->whereNumber('inventoryTransaction')->name('history.show');
                Route::get('attachments/{inventoryAttachment}', AttachmentController::class)
                    ->whereNumber('inventoryAttachment')->name('attachments.show');
            });
        });
    });
