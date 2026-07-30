<?php

namespace Tests\Feature\Inventory\Api;

use App\Models\Inventory\InventoryCategory;
use App\Models\Inventory\InventoryLocation;
use App\Models\Inventory\InventoryRequestType;

class InventoryCatalogItemApiTest extends InventoryApiTestCase
{
    public function test_catalogs_only_return_active_records_and_subcategory_filter_works(): void
    {
        $this->actingAsInventoryUser();
        $category = InventoryCategory::query()->create(['code' => 'ACTIVE', 'name' => 'Active']);
        InventoryCategory::query()->create(['code' => 'INACTIVE', 'name' => 'Inactive', 'is_active' => false]);
        $subcategory = $category->subcategories()->create(['name' => 'Sub Active']);
        InventoryRequestType::query()->create(['code' => 'active', 'name' => 'Active']);
        InventoryRequestType::query()->create(['code' => 'inactive', 'name' => 'Inactive', 'is_active' => false]);
        InventoryLocation::query()->create(['code' => 'L1', 'name' => 'Location']);

        $this->getJson('/api/v1/inventory/catalogs/categories')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $category->id);
        $this->getJson('/api/v1/inventory/catalogs/subcategories?category_id='.$category->id)
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $subcategory->id);
        $this->getJson('/api/v1/inventory/catalogs/request-types')
            ->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/inventory/catalogs/locations')
            ->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_item_listing_filters_search_sort_stock_status_and_pagination(): void
    {
        $this->actingAsInventoryUser();
        $category = InventoryCategory::query()->create(['name' => 'Tools']);
        $location = InventoryLocation::query()->create(['code' => 'G1', 'name' => 'Gudang']);
        $available = $this->item([
            'uid' => 'EQ-SEARCH',
            'name' => 'Equipment Search',
            'item_type' => 'equipment',
            'inventory_category_id' => $category->id,
            'inventory_location_id' => $location->id,
            'current_stock' => '5.000',
            'minimum_stock' => '1.000',
        ]);
        $this->item(['uid' => 'LOW', 'current_stock' => '1.000', 'minimum_stock' => '2.000']);
        $this->item(['uid' => 'OUT', 'current_stock' => '0.000']);
        $inactive = $this->item(['uid' => 'INACTIVE', 'is_active' => false]);
        $deleted = $this->item(['uid' => 'DELETED']);
        $deleted->delete();

        $this->getJson('/api/v1/inventory/items?search=EQ-SEARCH&item_type=equipment&category_id='.$category->id.'&location_id='.$location->id)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $available->id)
            ->assertJsonPath('data.0.stock_status', 'available');
        $this->getJson('/api/v1/inventory/items?stock_status=low')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/inventory/items?stock_status=out')->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/inventory/items?per_page=51')->assertUnprocessable();
        $this->getJson('/api/v1/inventory/items?sort=invalid')->assertUnprocessable();
        $this->getJson('/api/v1/inventory/items')->assertJsonMissing(['id' => $inactive->id]);
    }

    public function test_active_item_detail_is_available_without_exposing_image_path(): void
    {
        $this->actingAsInventoryUser();
        $item = $this->item(['image_disk' => 'local', 'image_path' => 'inventory/items/test.jpg']);

        $this->getJson('/api/v1/inventory/items/'.$item->id)
            ->assertOk()
            ->assertJsonPath('data.id', $item->id)
            ->assertJsonMissingPath('data.image_path')
            ->assertJsonPath('data.can_request', true);

        $inactive = $this->item(['is_active' => false]);
        $this->getJson('/api/v1/inventory/items/'.$inactive->id)->assertNotFound();
    }
}
