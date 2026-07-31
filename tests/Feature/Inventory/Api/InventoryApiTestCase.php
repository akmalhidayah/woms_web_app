<?php

namespace Tests\Feature\Inventory\Api;

use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryRequestType;
use App\Models\Inventory\InventoryUser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

abstract class InventoryApiTestCase extends TestCase
{
    use RefreshDatabase;

    protected function inventoryUser(array $attributes = []): InventoryUser
    {
        return InventoryUser::query()->create(array_merge([
            'name' => 'Mobile Inventory User',
            'email' => str()->ulid().'@inventory.test',
            'password' => 'password-lama',
            'is_active' => true,
            'must_change_password' => false,
        ], $attributes));
    }

    protected function actingAsInventoryUser(?InventoryUser $user = null): InventoryUser
    {
        $user ??= $this->inventoryUser();
        Sanctum::actingAs($user, ['inventory-mobile']);

        return $user;
    }

    protected function item(array $attributes = []): InventoryItem
    {
        $stock = (int) ($attributes['current_stock'] ?? 5);
        unset($attributes['current_stock']);
        $item = InventoryItem::query()->create(array_merge([
            'uid' => 'ITEM-'.str()->ulid(),
            'item_type' => 'consumable',
            'name' => 'Barang Inventory',
            'unit' => 'EA',
            'minimum_stock' => 1,
            'is_active' => true,
        ], $attributes));
        $item->forceFill(['current_stock' => $stock])->save();

        return $item->refresh();
    }

    protected function requestType(array $attributes = []): InventoryRequestType
    {
        return InventoryRequestType::query()->create(array_merge([
            'code' => 'request-'.str()->ulid(),
            'name' => 'Permintaan '.str()->ulid(),
            'is_active' => true,
        ], $attributes));
    }
}
