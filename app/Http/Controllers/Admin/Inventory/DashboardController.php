<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryTransaction;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $inventoryReady = Schema::hasTable('inventory_items')
            && Schema::hasTable('inventory_transactions');

        $summary = [
            'items' => 0,
            'consumables' => 0,
            'equipment' => 0,
            'low_stock' => 0,
            'out_of_stock' => 0,
            'transactions_today' => 0,
        ];
        $lowStockItems = collect();
        $recentTransactions = collect();

        if ($inventoryReady) {
            $summary = [
                'items' => InventoryItem::query()->count(),
                'consumables' => InventoryItem::query()->where('item_type', 'consumable')->count(),
                'equipment' => InventoryItem::query()->where('item_type', 'equipment')->count(),
                'low_stock' => InventoryItem::query()
                    ->whereColumn('current_stock', '<=', 'minimum_stock')
                    ->where('current_stock', '>', 0)
                    ->count(),
                'out_of_stock' => InventoryItem::query()->where('current_stock', '<=', 0)->count(),
                'transactions_today' => InventoryTransaction::query()->whereDate('transaction_at', today())->count(),
            ];

            $lowStockItems = InventoryItem::query()
                ->with(['category', 'location'])
                ->whereColumn('current_stock', '<=', 'minimum_stock')
                ->orderBy('current_stock')
                ->limit(10)
                ->get();

            $recentTransactions = InventoryTransaction::query()
                ->with(['item', 'inventoryUser', 'womsUser'])
                ->latest('transaction_at')
                ->limit(10)
                ->get();
        }

        return view('admin.inventory.dashboard', compact(
            'inventoryReady',
            'summary',
            'lowStockItems',
            'recentTransactions',
        ));
    }
}
