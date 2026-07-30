<?php

namespace App\Http\Controllers\Api\V1\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Inventory\ItemIndexRequest;
use App\Http\Resources\Api\V1\Inventory\InventoryItemDetailResource;
use App\Http\Resources\Api\V1\Inventory\InventoryItemResource;
use App\Models\Inventory\InventoryItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ItemController extends Controller
{
    public function index(ItemIndexRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $query = InventoryItem::query()
            ->where('is_active', true)
            ->with(['category', 'subcategory', 'location']);

        $query->when($filters['search'] ?? null, function ($query, string $search): void {
            $query->where(function ($query) use ($search): void {
                $query->where('uid', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('type_category', 'like', "%{$search}%");
            });
        });
        $query->when($filters['item_type'] ?? null, fn ($query, $value) => $query->where('item_type', $value));
        $query->when($filters['category_id'] ?? null, fn ($query, $value) => $query->where('inventory_category_id', $value));
        $query->when($filters['subcategory_id'] ?? null, fn ($query, $value) => $query->where('inventory_subcategory_id', $value));
        $query->when($filters['location_id'] ?? null, fn ($query, $value) => $query->where('inventory_location_id', $value));
        $this->stockStatus($query, $filters['stock_status'] ?? null);
        $this->sort($query, $filters['sort'] ?? 'name');

        $paginator = $query->paginate($filters['per_page'] ?? 20)->withQueryString();

        return response()->json([
            'success' => true,
            'message' => 'Data barang berhasil diambil.',
            'data' => InventoryItemResource::collection($paginator->getCollection()),
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

    public function show(InventoryItem $inventoryItem): JsonResponse
    {
        abort_unless($inventoryItem->is_active, 404);

        return response()->json([
            'success' => true,
            'message' => 'Detail barang berhasil diambil.',
            'data' => new InventoryItemDetailResource(
                $inventoryItem->load(['category', 'subcategory', 'location'])
            ),
        ]);
    }

    public function image(InventoryItem $inventoryItem): Response
    {
        abort_unless($inventoryItem->is_active && filled($inventoryItem->image_path), 404);
        abort_if($this->unsafePath($inventoryItem->image_path), 404);
        $disk = Storage::disk($inventoryItem->image_disk);
        abort_unless($disk->exists($inventoryItem->image_path), 404);

        return $disk->response($inventoryItem->image_path, null, [
            'Content-Type' => $disk->mimeType($inventoryItem->image_path) ?: 'application/octet-stream',
            'Cache-Control' => 'private, max-age=3600',
        ], 'inline');
    }

    private function stockStatus($query, ?string $status): void
    {
        match ($status) {
            'out' => $query->where('current_stock', '<=', 0),
            'low' => $query->where('current_stock', '>', 0)
                ->where('minimum_stock', '>', 0)
                ->whereColumn('current_stock', '<=', 'minimum_stock'),
            'available' => $query->where('current_stock', '>', 0)
                ->where(function ($query): void {
                    $query->where('minimum_stock', '<=', 0)
                        ->orWhereColumn('current_stock', '>', 'minimum_stock');
                }),
            default => null,
        };
    }

    private function sort($query, string $sort): void
    {
        match ($sort) {
            'uid' => $query->orderBy('uid'),
            'current_stock' => $query->orderBy('current_stock'),
            'newest' => $query->latest(),
            default => $query->orderBy('name'),
        };
    }

    private function unsafePath(string $path): bool
    {
        return str_starts_with($path, '/') || str_contains($path, '..') || str_contains($path, "\0");
    }
}
