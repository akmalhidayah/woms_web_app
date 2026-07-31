<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InventoryTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $inventoryReady = Schema::hasTable('inventory_transactions');
        $transactions = collect();

        if ($inventoryReady) {
            $types = ['opening_balance', 'stock_in', 'stock_out', 'adjustment_in', 'adjustment_out'];
            $sources = ['flutter', 'woms_admin', 'import', 'seeder', 'system'];

            $transactions = InventoryTransaction::query()
                ->with(['item', 'inventoryUser', 'womsUser', 'requestType'])
                ->when(in_array($request->string('type')->toString(), $types, true), fn ($query) => $query->where('transaction_type', $request->string('type')->toString()))
                ->when(in_array($request->string('source')->toString(), $sources, true), fn ($query) => $query->where('source', $request->string('source')->toString()))
                ->when($request->filled('date'), fn ($query) => $query->whereDate('transaction_at', $request->string('date')->toString()))
                ->when($request->filled('search'), function ($query) use ($request): void {
                    $search = trim((string) $request->string('search'));
                    $query->where(function ($query) use ($search): void {
                        $query->where('transaction_number', 'like', "%{$search}%")
                            ->orWhere('item_uid_snapshot', 'like', "%{$search}%")
                            ->orWhere('item_name_snapshot', 'like', "%{$search}%")
                            ->orWhere('purpose', 'like', "%{$search}%")
                            ->orWhereHas('inventoryUser', fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('employee_number', 'like', "%{$search}%"));
                    });
                })
                ->latest('transaction_at')
                ->paginate(20)
                ->withQueryString();
        }

        return view('admin.inventory.transactions.index', compact('inventoryReady', 'transactions'));
    }

    public function show(InventoryTransaction $inventoryTransaction): View
    {
        return view('admin.inventory.transactions.show', [
            'inventoryReady' => true,
            'transaction' => $inventoryTransaction->load(['item', 'inventoryUser', 'womsUser', 'requestType', 'attachments']),
        ]);
    }
}
