<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InventoryTransaction;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AdjustmentController extends Controller
{
    public function index(): View
    {
        $inventoryReady = Schema::hasTable('inventory_transactions');
        $transactions = $inventoryReady
            ? InventoryTransaction::query()->with(['item', 'womsUser'])
                ->whereIn('transaction_type', ['adjustment_in', 'adjustment_out'])
                ->latest('transaction_at')->limit(10)->get()
            : collect();

        return view('admin.inventory.adjustments.index', compact('inventoryReady', 'transactions'));
    }
}
