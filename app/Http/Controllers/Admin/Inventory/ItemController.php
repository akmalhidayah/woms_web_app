<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InventoryCategory;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class ItemController extends Controller
{
    public function index(Request $request): View
    {
        $inventoryReady = Schema::hasTable('inventory_items');
        $items = collect();
        $categories = collect();
        $locations = collect();

        if ($inventoryReady) {
            $items = InventoryItem::query()
                ->with(['category', 'subcategory', 'location'])
                ->when($request->filled('search'), function ($query) use ($request): void {
                    $search = trim((string) $request->string('search'));
                    $query->where(function ($query) use ($search): void {
                        $query->where('uid', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    });
                })
                ->when(in_array($request->string('item_type')->toString(), ['consumable', 'equipment'], true), function ($query) use ($request): void {
                    $query->where('item_type', $request->string('item_type')->toString());
                })
                ->when($request->integer('category'), fn ($query, int $category) => $query->where('inventory_category_id', $category))
                ->when($request->integer('location'), fn ($query, int $location) => $query->where('inventory_location_id', $location))
                ->orderBy('name')
                ->paginate(20)
                ->withQueryString();

            $categories = InventoryCategory::query()->orderBy('name')->get(['id', 'name']);
            $locations = InventoryLocation::query()->orderBy('name')->get(['id', 'code', 'name']);
        }

        return view('admin.inventory.items.index', compact('inventoryReady', 'items', 'categories', 'locations'));
    }
}
