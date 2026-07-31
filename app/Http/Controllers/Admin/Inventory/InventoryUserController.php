<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Inventory\StoreInventoryUserRequest;
use App\Http\Requests\Admin\Inventory\UpdateInventoryUserStatusRequest;
use App\Models\Inventory\InventoryUser;
use App\Models\User;
use App\Services\Inventory\InventoryUserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class InventoryUserController extends Controller
{
    public function index(Request $request): View
    {
        $inventoryReady = Schema::hasTable('inventory_users');
        $users = collect();

        if ($inventoryReady) {
            $users = InventoryUser::query()
                ->when($request->filled('search'), function ($query) use ($request): void {
                    $search = trim((string) $request->string('search'));
                    $query->where(function ($query) use ($search): void {
                        $query->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('employee_number', 'like', "%{$search}%")
                            ->orWhere('department', 'like', "%{$search}%")
                            ->orWhere('position', 'like', "%{$search}%");
                    });
                })
                ->when($request->string('status')->toString() === 'active', fn ($query) => $query->where('is_active', true))
                ->when($request->string('status')->toString() === 'inactive', fn ($query) => $query->where('is_active', false))
                ->when($request->string('status')->toString() === 'must_change_password', fn ($query) => $query->where('must_change_password', true))
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString();
        }

        return view('admin.inventory.users.index', compact('inventoryReady', 'users'));
    }

    public function create(): View
    {
        $inventoryReady = Schema::hasTable('inventory_users');

        return view('admin.inventory.users.create', compact('inventoryReady'));
    }

    public function store(
        StoreInventoryUserRequest $request,
        InventoryUserManagementService $service,
    ): RedirectResponse {
        $admin = $request->user();
        abort_unless($admin instanceof User, 403);

        $inventoryUser = $service->createUser($admin, $request->validated());

        return redirect()->route('admin.inventory.users.index')->with([
            'success' => 'Akun user aplikasi berhasil dibuat.',
            'inventory_user_name' => $inventoryUser->name,
            'inventory_user_email' => $inventoryUser->email,
            'temporary_password' => $inventoryUser->temporary_password,
            'password_notice' => 'User wajib mengganti password saat login pertama.',
        ]);
    }

    public function updateStatus(
        UpdateInventoryUserStatusRequest $request,
        InventoryUser $inventoryUser,
        InventoryUserManagementService $service,
    ): RedirectResponse {
        $admin = $request->user();
        abort_unless($admin instanceof User, 403);

        $isActive = $request->boolean('is_active');
        $service->updateStatus($admin, $inventoryUser, $isActive);

        return redirect()->route('admin.inventory.users.index')->with(
            'success',
            $isActive ? 'Akun user berhasil diaktifkan.' : 'Akun user berhasil dinonaktifkan.',
        );
    }

    public function resetPassword(
        Request $request,
        InventoryUser $inventoryUser,
        InventoryUserManagementService $service,
    ): RedirectResponse {
        $admin = $request->user();
        abort_unless($admin instanceof User && $admin->isAdmin(), 403);

        $inventoryUser = $service->resetPassword($admin, $inventoryUser);

        return redirect()->route('admin.inventory.users.index')->with([
            'success' => 'Password user berhasil direset.',
            'inventory_user_name' => $inventoryUser->name,
            'inventory_user_email' => $inventoryUser->email,
            'temporary_password' => $inventoryUser->temporary_password,
            'password_notice' => 'Seluruh sesi user telah dikeluarkan dan user wajib mengganti password.',
        ]);
    }
}
