<?php

namespace Tests\Feature\Inventory\Api;

use App\Models\Inventory\InventoryTransaction;
use App\Services\Inventory\InventoryStockService;
use Illuminate\Support\Facades\Storage;

class InventoryHistoryAttachmentApiTest extends InventoryApiTestCase
{
    public function test_user_only_sees_and_opens_own_history(): void
    {
        $user = $this->actingAsInventoryUser();
        $other = $this->inventoryUser();
        $item = $this->item(['item_type' => 'equipment', 'current_stock' => 5]);
        $requestType = $this->requestType();
        $service = app(InventoryStockService::class);
        $own = $service->stockOut($item, 1, $user, [
            'inventory_request_type_id' => $requestType->id,
            'purpose' => 'Milik sendiri',
        ]);
        $otherTransaction = $service->stockOut($item, 1, $other, [
            'inventory_request_type_id' => $requestType->id,
            'purpose' => 'Milik user lain',
        ]);

        $this->getJson('/api/v1/inventory/my-history?item_type=equipment&date_from='.now()->toDateString())
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $own->id)
            ->assertJsonMissing(['id' => $otherTransaction->id])
            ->assertJsonMissingPath('data.0.inventory_user_id')
            ->assertJsonMissingPath('data.0.legacy_payload');

        $this->getJson('/api/v1/inventory/my-history/'.$own->id)
            ->assertOk()->assertJsonPath('data.id', $own->id);
        $this->getJson('/api/v1/inventory/my-history/'.$otherTransaction->id)
            ->assertNotFound();
    }

    public function test_user_can_stream_own_private_attachment_but_not_other_users_file(): void
    {
        Storage::fake('local');
        $user = $this->actingAsInventoryUser();
        $other = $this->inventoryUser();
        $item = $this->item(['current_stock' => 5]);
        $requestType = $this->requestType();
        $service = app(InventoryStockService::class);
        $ownTransaction = $service->stockOut($item, 1, $user, [
            'inventory_request_type_id' => $requestType->id,
            'purpose' => 'Own',
        ]);
        $otherTransaction = $service->stockOut($item, 1, $other, [
            'inventory_request_type_id' => $requestType->id,
            'purpose' => 'Other',
        ]);
        Storage::disk('local')->put('inventory/own.jpg', 'image-content');
        Storage::disk('local')->put('inventory/other.jpg', 'image-content');
        $own = $ownTransaction->attachments()->create([
            'attachment_type' => 'supporting_photo',
            'disk' => 'local',
            'path' => 'inventory/own.jpg',
            'original_name' => 'own.jpg',
            'mime_type' => 'image/jpeg',
        ]);
        $otherAttachment = $otherTransaction->attachments()->create([
            'attachment_type' => 'supporting_photo',
            'disk' => 'local',
            'path' => 'inventory/other.jpg',
            'original_name' => 'other.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $this->get('/api/v1/inventory/attachments/'.$own->id, ['Accept' => 'application/json'])
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');
        $this->getJson('/api/v1/inventory/attachments/'.$otherAttachment->id)
            ->assertNotFound();
    }

    public function test_missing_attachment_file_returns_not_found(): void
    {
        Storage::fake('local');
        $user = $this->actingAsInventoryUser();
        $transaction = InventoryTransaction::query()->create([
            'transaction_number' => 'INV-OUT-MISSING',
            'inventory_item_id' => $this->item()->id,
            'inventory_user_id' => $user->id,
            'transaction_type' => 'stock_out',
            'quantity' => 1,
            'stock_before' => 1,
            'stock_after' => 0,
            'source' => 'flutter',
            'item_uid_snapshot' => 'ITEM',
            'item_name_snapshot' => 'Item',
            'unit_snapshot' => 'EA',
            'transaction_at' => now(),
        ]);
        $attachment = $transaction->attachments()->create([
            'attachment_type' => 'supporting_photo',
            'disk' => 'local',
            'path' => 'inventory/missing.jpg',
        ]);

        $this->getJson('/api/v1/inventory/attachments/'.$attachment->id)->assertNotFound();
    }
}
