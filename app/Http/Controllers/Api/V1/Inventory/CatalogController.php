<?php

namespace App\Http\Controllers\Api\V1\Inventory;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Inventory\InventoryCategoryResource;
use App\Http\Resources\Api\V1\Inventory\InventoryLocationResource;
use App\Http\Resources\Api\V1\Inventory\InventoryRequestTypeResource;
use App\Http\Resources\Api\V1\Inventory\InventorySubcategoryResource;
use App\Models\Inventory\InventoryCategory;
use App\Models\Inventory\InventoryLocation;
use App\Models\Inventory\InventoryRequestType;
use App\Models\Inventory\InventorySubcategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function categories(): JsonResponse
    {
        return $this->success(InventoryCategoryResource::collection(
            InventoryCategory::query()->where('is_active', true)->orderBy('name')->get()
        ));
    }

    public function subcategories(Request $request): JsonResponse
    {
        $request->validate(['category_id' => ['nullable', 'integer', 'exists:inventory_categories,id']]);
        $query = InventorySubcategory::query()->where('is_active', true);

        if ($request->filled('category_id')) {
            $query->where('inventory_category_id', $request->integer('category_id'));
        }

        return $this->success(InventorySubcategoryResource::collection($query->orderBy('name')->get()));
    }

    public function locations(): JsonResponse
    {
        return $this->success(InventoryLocationResource::collection(
            InventoryLocation::query()->where('is_active', true)->orderBy('name')->get()
        ));
    }

    public function requestTypes(): JsonResponse
    {
        return $this->success(InventoryRequestTypeResource::collection(
            InventoryRequestType::query()->where('is_active', true)->orderBy('name')->get()
        ));
    }

    private function success(mixed $data): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diambil.',
            'data' => $data,
        ]);
    }
}
