<?php

use App\Http\Controllers\Api\V1\OrganizationController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/organization')
    ->name('api.v1.organization.')
    ->middleware([
        'auth:sanctum',
        'organization.api',
        'throttle:organization-api',
    ])
    ->controller(OrganizationController::class)
    ->group(function (): void {
        Route::get('snapshot', 'snapshot')->name('snapshot');
        Route::get('departments', 'departments')->name('departments');
        Route::get('units', 'units')->name('units');
        Route::get('sections', 'sections')->name('sections');
    });
