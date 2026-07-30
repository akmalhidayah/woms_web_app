<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InventoryUser;
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
                            ->orWhere('employee_number', 'like', "%{$search}%");
                    });
                })
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString();
        }

        return view('admin.inventory.users.index', compact('inventoryReady', 'users'));
    }
}
