<?php

namespace App\Http\Controllers\Admin\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Inventory\InventoryCategory;
use App\Models\Inventory\InventoryLocation;
use App\Models\Inventory\InventoryRequestType;
use App\Models\Inventory\InventorySubcategory;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class MasterDataController extends Controller
{
    public function index(): View
    {
        $inventoryReady = Schema::hasTable('inventory_categories')
            && Schema::hasTable('inventory_subcategories')
            && Schema::hasTable('inventory_locations')
            && Schema::hasTable('inventory_request_types');

        $categories = $inventoryReady ? InventoryCategory::query()->withCount('subcategories')->orderBy('name')->get() : collect();
        $locations = $inventoryReady ? InventoryLocation::query()->orderBy('name')->get() : collect();
        $requestTypes = $inventoryReady ? InventoryRequestType::query()->orderBy('name')->get() : collect();
        $subcategoryCount = $inventoryReady ? InventorySubcategory::query()->count() : 0;

        return view('admin.inventory.master-data.index', compact(
            'inventoryReady',
            'categories',
            'locations',
            'requestTypes',
            'subcategoryCount',
        ));
    }
}
