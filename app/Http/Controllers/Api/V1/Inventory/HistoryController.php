<?php

namespace App\Http\Controllers\Api\V1\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Inventory\HistoryIndexRequest;
use App\Http\Resources\Api\V1\Inventory\InventoryTransactionResource;
use App\Models\Inventory\InventoryTransaction;
use App\Models\Inventory\InventoryUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function index(HistoryIndexRequest $request): JsonResponse
    {
        /** @var InventoryUser $user */
        $user = $request->user();
        $filters = $request->validated();
        $query = InventoryTransaction::query()
            ->where('inventory_user_id', $user->getKey())
            ->with(['item', 'requestType', 'attachments'])
            ->latest('transaction_at');

        $query->when($filters['search'] ?? null, function ($query, string $search): void {
            $query->where(function ($query) use ($search): void {
                $query->where('item_uid_snapshot', 'like', "%{$search}%")
                    ->orWhere('item_name_snapshot', 'like', "%{$search}%");
            });
        });
        $query->when($filters['item_type'] ?? null, fn ($query, $type) => $query->whereHas(
            'item',
            fn ($query) => $query->where('item_type', $type)
        ));
        $query->when($filters['request_type_id'] ?? null, fn ($query, $id) => $query->where('inventory_request_type_id', $id));
        $query->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('transaction_at', '>=', $date));
        $query->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('transaction_at', '<=', $date));
        $paginator = $query->paginate($filters['per_page'] ?? 20)->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'History berhasil diambil.',
            'data' => InventoryTransactionResource::collection($paginator->getCollection()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'links' => [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ],
        ]);
    }

    public function show(Request $request, InventoryTransaction $inventoryTransaction): JsonResponse
    {
        abort_unless(
            (int) $inventoryTransaction->inventory_user_id === (int) $request->user()?->getKey(),
            404
        );

        return response()->json([
            'success' => true,
            'message' => 'Detail history berhasil diambil.',
            'data' => new InventoryTransactionResource(
                $inventoryTransaction->load(['item', 'requestType', 'attachments'])
            ),
        ]);
    }
}
