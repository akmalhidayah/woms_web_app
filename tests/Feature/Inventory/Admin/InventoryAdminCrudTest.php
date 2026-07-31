<?php

namespace Tests\Feature\Inventory\Admin;

use App\Models\Inventory\InventoryCategory;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryLocation;
use App\Models\Inventory\InventoryRequestType;
use App\Models\Inventory\InventoryTransaction;
use App\Models\Inventory\InventoryTransactionAttachment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InventoryAdminCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_item_with_audited_opening_stock_and_cannot_update_stock_directly(): void
    {
        $admin = $this->admin();
        $category = InventoryCategory::query()->create(['name' => 'Consumable']);
        $location = InventoryLocation::query()->create(['code' => 'G1', 'name' => 'Gudang']);

        $this->actingAs($admin)->get(route('admin.inventory.items.create'))->assertOk();
        $this->actingAs($admin)->post(route('admin.inventory.items.store'), [
            'uid' => 'ITEM-NEW',
            'item_type' => 'consumable',
            'inventory_category_id' => $category->id,
            'inventory_location_id' => $location->id,
            'name' => 'Barang Baru',
            'unit' => 'EA',
            'minimum_stock' => 2,
            'opening_stock' => 10,
            'is_active' => 1,
        ])->assertRedirect();

        $item = InventoryItem::query()->where('uid', 'ITEM-NEW')->sole();
        $this->assertSame(10, $item->current_stock);
        $this->assertDatabaseHas('inventory_transactions', [
            'inventory_item_id' => $item->id,
            'transaction_type' => 'opening_balance',
            'quantity' => 10,
            'stock_before' => 0,
            'stock_after' => 10,
        ]);

        $this->actingAs($admin)->put(route('admin.inventory.items.update', $item), [
            'uid' => $item->uid,
            'item_type' => $item->item_type,
            'name' => $item->name,
            'unit' => $item->unit,
            'minimum_stock' => 1,
            'is_active' => 1,
            'remove_image' => 0,
            'current_stock' => 999,
        ])->assertSessionHasErrors('current_stock');
        $this->assertSame(10, $item->refresh()->current_stock);
    }

    public function test_item_image_replace_archive_and_restore_rules_are_safe(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.inventory.items.store'), [
            'uid' => 'IMAGE-ITEM',
            'item_type' => 'equipment',
            'name' => 'Item Gambar',
            'unit' => 'EA',
            'minimum_stock' => 0,
            'opening_stock' => 0,
            'is_active' => 1,
            'image' => UploadedFile::fake()->image('first.jpg'),
        ]);
        $item = InventoryItem::query()->where('uid', 'IMAGE-ITEM')->sole();
        $oldPath = $item->image_path;
        Storage::disk('local')->assertExists($oldPath);

        $this->actingAs($admin)->put(route('admin.inventory.items.update', $item), [
            'uid' => $item->uid,
            'item_type' => $item->item_type,
            'name' => $item->name,
            'unit' => $item->unit,
            'minimum_stock' => 0,
            'is_active' => 1,
            'remove_image' => 0,
            'image' => UploadedFile::fake()->image('second.png'),
        ])->assertRedirect();
        Storage::disk('local')->assertMissing($oldPath);
        Storage::disk('local')->assertExists($item->refresh()->image_path);

        $this->actingAs($admin)->delete(route('admin.inventory.items.destroy', $item))->assertRedirect();
        $this->assertSoftDeleted('inventory_items', ['id' => $item->id]);
        $this->actingAs($admin)->post(route('admin.inventory.items.restore', $item->id))->assertRedirect();
        $this->assertFalse($item->fresh()->is_active);

        $item->forceFill(['current_stock' => 1, 'is_active' => true])->save();
        $this->actingAs($admin)->delete(route('admin.inventory.items.destroy', $item))->assertSessionHasErrors('archive');
        $this->assertNull($item->fresh()->deleted_at);
    }

    public function test_adjustments_create_audit_and_prevent_negative_stock(): void
    {
        $admin = $this->admin();
        $item = $this->item(5);

        $this->actingAs($admin)->post(route('admin.inventory.adjustments.store'), [
            'inventory_item_id' => $item->id,
            'adjustment_type' => 'adjustment_in',
            'quantity' => 3,
            'reason' => 'Hasil opname',
        ])->assertRedirect();
        $this->assertSame(8, $item->refresh()->current_stock);

        $this->actingAs($admin)->post(route('admin.inventory.adjustments.store'), [
            'inventory_item_id' => $item->id,
            'adjustment_type' => 'adjustment_out',
            'quantity' => 2,
            'reason' => 'Barang rusak',
        ])->assertRedirect();
        $this->assertSame(6, $item->refresh()->current_stock);
        $this->assertSame(['adjustment_in', 'adjustment_out'], InventoryTransaction::query()->pluck('transaction_type')->all());

        $this->actingAs($admin)->post(route('admin.inventory.adjustments.store'), [
            'inventory_item_id' => $item->id,
            'adjustment_type' => 'adjustment_out',
            'quantity' => 7,
            'reason' => 'Berlebih',
        ])->assertSessionHasErrors('quantity');
        $this->assertSame(6, $item->refresh()->current_stock);
    }

    public function test_admin_can_view_flutter_transaction_and_private_attachment(): void
    {
        Storage::fake('local');
        $admin = $this->admin();
        $item = $this->item(1);
        $type = InventoryRequestType::query()->create(['code' => 'new', 'name' => 'Baru']);
        $transaction = InventoryTransaction::query()->create([
            'transaction_number' => 'INV-OUT-DETAIL',
            'inventory_item_id' => $item->id,
            'inventory_request_type_id' => $type->id,
            'transaction_type' => 'stock_out',
            'quantity' => 1,
            'stock_before' => 1,
            'stock_after' => 0,
            'purpose' => 'Operasional',
            'source' => 'flutter',
            'item_uid_snapshot' => $item->uid,
            'item_name_snapshot' => $item->name,
            'unit_snapshot' => $item->unit,
            'transaction_at' => now(),
        ]);
        Storage::disk('local')->put('inventory/transactions/proof.jpg', 'photo');
        $attachment = $transaction->attachments()->create([
            'attachment_type' => 'supporting_photo',
            'disk' => 'local',
            'path' => 'inventory/transactions/proof.jpg',
            'original_name' => "proof\n.jpg",
            'mime_type' => 'image/jpeg',
        ]);

        $this->actingAs($admin)->get(route('admin.inventory.transactions.show', $transaction))
            ->assertOk()->assertSeeText('Operasional')->assertSeeText('Attachment');
        $attachmentResponse = $this->actingAs($admin)->get(route('admin.inventory.attachments.show', $attachment))->assertOk();
        $this->assertStringContainsString('no-store', (string) $attachmentResponse->headers->get('Cache-Control'));

        $unsafe = InventoryTransactionAttachment::query()->create([
            'inventory_transaction_id' => $transaction->id,
            'attachment_type' => 'other',
            'disk' => 'local',
            'path' => '../secret',
        ]);
        $this->actingAs($admin)->get(route('admin.inventory.attachments.show', $unsafe))->assertNotFound();
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $this->actingAs($user)->get(route('admin.inventory.transactions.show', $transaction))->assertForbidden();
    }

    public function test_master_data_crud_and_reference_guards_work(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('admin.inventory.master-data.store', 'categories'), [
            'code' => 'TOOLS', 'name' => 'Tools', 'is_active' => 1,
        ])->assertRedirect();
        $category = InventoryCategory::query()->sole();
        $this->actingAs($admin)->put(route('admin.inventory.master-data.update', ['categories', $category->id]), [
            'code' => 'TOOLS', 'name' => 'Tools Updated', 'is_active' => 1,
        ])->assertRedirect();
        $this->actingAs($admin)->patch(route('admin.inventory.master-data.status', ['categories', $category->id]))
            ->assertRedirect();
        $this->assertFalse($category->refresh()->is_active);

        $item = $this->item();
        $item->update(['inventory_category_id' => $category->id]);
        $this->actingAs($admin)->delete(route('admin.inventory.master-data.destroy', ['categories', $category->id]))
            ->assertSessionHasErrors('master_data');
        $this->assertDatabaseHas('inventory_categories', ['id' => $category->id]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN, 'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN]);
    }

    private function item(int $stock = 0): InventoryItem
    {
        $item = InventoryItem::query()->create([
            'uid' => 'ITEM-'.str()->ulid(),
            'item_type' => 'consumable',
            'name' => 'Barang',
            'unit' => 'EA',
            'minimum_stock' => 0,
            'is_active' => true,
        ]);
        $item->forceFill(['current_stock' => $stock])->save();

        return $item->refresh();
    }
}
