<?php

namespace App\Http\Controllers\Api\V1\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Inventory\InventoryItemResource;
use App\Http\Resources\Api\V1\Inventory\InventoryTransactionResource;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryTransaction;
use App\Models\Inventory\InventoryUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        /** @var InventoryUser $user */
        $user = $request->user();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();
        $userTransactions = InventoryTransaction::query()
            ->where('inventory_user_id', $user->getKey())
            ->where('transaction_type', 'stock_out')
            ->whereBetween('transaction_at', [$monthStart, $monthEnd]);
        $latest = InventoryTransaction::query()
            ->where('inventory_user_id', $user->getKey())
            ->with(['item', 'requestType', 'attachments'])
            ->latest('transaction_at')
            ->limit(5)
            ->get();
        $lowStock = InventoryItem::query()
            ->where('is_active', true)
            ->where('current_stock', '>', 0)
            ->where('minimum_stock', '>', 0)
            ->whereColumn('current_stock', '<=', 'minimum_stock')
            ->with(['category', 'subcategory', 'location'])
            ->orderBy('current_stock')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Dashboard berhasil diambil.',
            'data' => [
                'available_consumable_types' => $this->availableCount('consumable'),
                'available_equipment_types' => $this->availableCount('equipment'),
                'monthly_request_count' => (clone $userTransactions)->count(),
                'monthly_requested_quantity' => number_format((float) (clone $userTransactions)->sum('quantity'), 3, '.', ''),
                'latest_transactions' => InventoryTransactionResource::collection($latest),
                'low_stock_items' => InventoryItemResource::collection($lowStock),
                'server_time' => now()->toISOString(),
            ],
        ]);
    }

    private function availableCount(string $type): int
    {
        return InventoryItem::query()
            ->where('is_active', true)
            ->where('item_type', $type)
            ->where('current_stock', '>', 0)
            ->count();
    }
}
