<?php

use App\Http\Controllers\Admin\AccessControlController;
use App\Http\Controllers\Admin\Hpp\HppController;
use App\Http\Controllers\Admin\InformationUploadController;
use App\Http\Controllers\Admin\OutlineAgreementController;
use App\Http\Controllers\Admin\StructureOrganizationController;
use App\Http\Controllers\Admin\UserPanelController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::get('/', function () {
    if (request()->user()) {
        return redirect()->route(request()->user()->dashboardRouteName());
    }

    return redirect()->route('login');
})->name('home');

Route::get('dashboard', function () {
    return redirect()->route(request()->user()->dashboardRouteName());
})
    ->middleware('auth')
    ->name('dashboard');

Route::get('informasi/{informationUpload}/preview', [InformationUploadController::class, 'preview'])
    ->whereNumber('informationUpload')
    ->name('public.information-upload.preview');

Route::middleware(['auth'])->group(function () {
    Route::view('admin/dashboard', 'dashboards.admin')
        ->middleware('role:admin')
        ->name('admin.dashboard');

    Route::get('admin/access-control', [AccessControlController::class, 'index'])
        ->middleware(['role:admin', 'admin_role:super_admin'])
        ->name('admin.access-control.index');
    Route::put('admin/access-control/{user}', [AccessControlController::class, 'update'])
        ->middleware(['role:admin', 'admin_role:super_admin'])
        ->name('admin.access-control.update');

    Route::get('admin/upload-informasi', [InformationUploadController::class, 'index'])
        ->middleware(['role:admin', 'admin_menu:upload_informasi'])
        ->name('admin.information-upload.index');
    Route::post('admin/upload-informasi', [InformationUploadController::class, 'store'])
        ->middleware(['role:admin', 'admin_menu:upload_informasi'])
        ->name('admin.information-upload.store');
    Route::get('admin/upload-informasi/{informationUpload}/preview', [InformationUploadController::class, 'preview'])
        ->middleware(['role:admin', 'admin_menu:upload_informasi'])
        ->name('admin.information-upload.preview');
    Route::get('admin/upload-informasi/{informationUpload}/download', [InformationUploadController::class, 'download'])
        ->middleware(['role:admin', 'admin_menu:upload_informasi'])
        ->name('admin.information-upload.download');
    Route::delete('admin/upload-informasi/{informationUpload}', [InformationUploadController::class, 'destroy'])
        ->middleware(['role:admin', 'admin_menu:upload_informasi'])
        ->name('admin.information-upload.destroy');

    Route::get('admin/outline-agreements', [OutlineAgreementController::class, 'index'])
        ->middleware(['role:admin', 'admin_menu:kuota_anggaran_oa'])
        ->name('admin.outline-agreements.index');
    Route::post('admin/outline-agreements', [OutlineAgreementController::class, 'store'])
        ->middleware(['role:admin', 'admin_menu:kuota_anggaran_oa'])
        ->name('admin.outline-agreements.store');
    Route::put('admin/outline-agreements/{outlineAgreement}', [OutlineAgreementController::class, 'update'])
        ->middleware(['role:admin', 'admin_menu:kuota_anggaran_oa'])
        ->name('admin.outline-agreements.update');
    Route::post('admin/outline-agreements/{outlineAgreement}/amendments', [OutlineAgreementController::class, 'addAmendment'])
        ->middleware(['role:admin', 'admin_menu:kuota_anggaran_oa'])
        ->name('admin.outline-agreements.amendments.store');

    Route::get('admin/user-panel', [UserPanelController::class, 'index'])
        ->middleware(['role:admin', 'admin_menu:user_panel'])
        ->name('admin.user-panel.index');
    Route::post('admin/user-panel', [UserPanelController::class, 'store'])
        ->middleware(['role:admin', 'admin_menu:user_panel'])
        ->name('admin.user-panel.store');
    Route::put('admin/user-panel/{user}', [UserPanelController::class, 'update'])
        ->middleware(['role:admin', 'admin_menu:user_panel'])
        ->name('admin.user-panel.update');
    Route::delete('admin/user-panel/{user}', [UserPanelController::class, 'destroy'])
        ->middleware(['role:admin', 'admin_menu:user_panel'])
        ->name('admin.user-panel.destroy');

    Route::get('admin/struktur-organisasi', [StructureOrganizationController::class, 'index'])
        ->middleware(['role:admin', 'admin_menu:struktur_organisasi'])
        ->name('admin.structure.index');
    Route::post('admin/struktur-organisasi', [StructureOrganizationController::class, 'store'])
        ->middleware(['role:admin', 'admin_menu:struktur_organisasi'])
        ->name('admin.structure.store');
    Route::put('admin/struktur-organisasi/{unitWork}', [StructureOrganizationController::class, 'update'])
        ->middleware(['role:admin', 'admin_menu:struktur_organisasi'])
        ->name('admin.structure.update');
    Route::put('admin/struktur-organisasi/departments/{department}', [StructureOrganizationController::class, 'updateDepartment'])
        ->middleware(['role:admin', 'admin_menu:struktur_organisasi'])
        ->name('admin.structure.departments.update');
    Route::delete('admin/struktur-organisasi/{unitWork}', [StructureOrganizationController::class, 'destroy'])
        ->middleware(['role:admin', 'admin_menu:struktur_organisasi'])
        ->name('admin.structure.destroy');

    Route::view('user/dashboard', 'dashboards.placeholder', [
        'title' => 'User Dashboard',
        'role' => User::ROLE_USER,
        'description' => 'Placeholder area for the standard user experience and upcoming modules.',
    ])->middleware('role:user')->name('user.dashboard');

    Route::view('pkm/dashboard', 'dashboards.pkm', [
        'pageTitle' => 'Dashboard',
        'pageDescription' => 'Ringkasan utama aktivitas vendor PKM dan status pekerjaan yang sedang berjalan.',
    ])->middleware('role:pkm')->name('pkm.dashboard');

    Route::view('pkm/jobwaiting', 'dashboards.pkm', [
        'pageTitle' => 'List Pekerjaan',
        'pageDescription' => 'Placeholder frontend untuk daftar pekerjaan yang menunggu tindak lanjut vendor PKM.',
    ])->middleware('role:pkm')->name('pkm.jobwaiting');

    Route::view('pkm/items', 'dashboards.pkm', [
        'pageTitle' => 'Item Kebutuhan',
        'pageDescription' => 'Placeholder frontend untuk item kebutuhan, material, dan komponen pekerjaan.',
    ])->middleware('role:pkm')->name('pkm.items.index');

    Route::view('pkm/lhpp', 'dashboards.pkm', [
        'pageTitle' => 'Buat LHPP',
        'pageDescription' => 'Placeholder frontend untuk pembuatan LHPP dan dokumen pendukung pekerjaan.',
    ])->middleware('role:pkm')->name('pkm.lhpp.index');

    Route::view('pkm/laporan', 'dashboards.pkm', [
        'pageTitle' => 'Dokumen',
        'pageDescription' => 'Placeholder frontend untuk arsip dokumen vendor, laporan, dan file pekerjaan.',
    ])->middleware('role:pkm')->name('pkm.laporan');

    Route::view('approver/dashboard', 'dashboards.placeholder', [
        'title' => 'Approver Dashboard',
        'role' => User::ROLE_APPROVER,
        'description' => 'Placeholder area for approvals, verification queues, and decision history.',
    ])->middleware('role:approver')->name('approver.dashboard');

    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
});

require __DIR__.'/admin/orders.php';
require __DIR__.'/auth.php';
    Route::get('admin/hpp', [HppController::class, 'index'])
        ->middleware(['role:admin', 'admin_menu:create_hpp'])
        ->name('admin.hpp.index');
    Route::get('admin/hpp/create', [HppController::class, 'create'])
        ->middleware(['role:admin', 'admin_menu:create_hpp'])
        ->name('admin.hpp.create');
