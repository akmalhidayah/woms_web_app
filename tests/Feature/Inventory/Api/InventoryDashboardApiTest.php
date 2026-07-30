<?php

namespace Tests\Feature\Inventory\Api;

use App\Services\Inventory\InventoryStockService;
use Illuminate\Support\Carbon;

class InventoryDashboardApiTest extends InventoryApiTestCase
{
    public function test_dashboard_only_summarizes_authenticated_users_transactions(): void
    {
        Carbon::setTestNow('2026-07-30 12:00:00');
        $user = $this->actingAsInventoryUser();
        $other = $this->inventoryUser();
        $requestType = $this->requestType();
        $consumable = $this->item([
            'item_type' => 'consumable',
            'current_stock' => '3.000',
            'minimum_stock' => '3.000',
        ]);
        $equipment = $this->item([
            'item_type' => 'equipment',
            'current_stock' => '5.000',
        ]);
        $service = app(InventoryStockService::class);
        $service->stockOut($consumable, '1.250', $user, [
            'inventory_request_type_id' => $requestType->id,
            'purpose' => 'Own',
        ]);
        $service->stockOut($equipment, '1.000', $other, [
            'inventory_request_type_id' => $requestType->id,
            'purpose' => 'Other',
        ]);

        $this->getJson('/api/v1/inventory/dashboard')
            ->assertOk()
            ->assertJsonPath('data.available_consumable_types', 1)
            ->assertJsonPath('data.available_equipment_types', 1)
            ->assertJsonPath('data.monthly_request_count', 1)
            ->assertJsonPath('data.monthly_requested_quantity', '1.250')
            ->assertJsonCount(1, 'data.latest_transactions')
            ->assertJsonCount(1, 'data.low_stock_items');
    }
}
