<?php

namespace Tests\Feature\Inventory;

use App\Models\Inventory\InventoryCategory;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryLocation;
use App\Models\Inventory\InventoryRequestType;
use App\Models\Inventory\InventoryTransaction;
use App\Models\Inventory\InventoryUser;
use App\Models\User;
use Database\Seeders\InventoryRequestTypeSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InventoryDatabaseFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_tables_exist_without_changing_woms_users_table(): void
    {
        foreach ([
            'inventory_users',
            'inventory_categories',
            'inventory_subcategories',
            'inventory_locations',
            'inventory_request_types',
            'inventory_items',
            'inventory_transactions',
            'inventory_transaction_attachments',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Table {$table} was not created.");
        }

        $this->assertTrue(Schema::hasColumns('users', [
            'id',
            'name',
            'email',
            'password',
            'role',
            'admin_role',
        ]));
        $this->assertFalse(Schema::hasColumn('users', 'inventory_role'));
        $this->assertFalse(Schema::hasColumn('users', 'inventory_user_id'));
    }

    public function test_inventory_models_and_relations_support_flutter_and_woms_actors(): void
    {
        $inventoryUser = InventoryUser::query()->create([
            'name' => 'Flutter Inventory User',
            'email' => 'flutter.inventory@example.test',
            'password' => 'secret-password',
            'employee_number' => 'EMP-001',
        ]);
        $womsAdmin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
        $category = InventoryCategory::query()->create([
            'code' => 'TOOLS',
            'name' => 'Tools',
        ]);
        $subcategory = $category->subcategories()->create([
            'code' => 'HAND-TOOLS',
            'name' => 'Hand Tools',
        ]);
        $location = InventoryLocation::query()->create([
            'code' => 'LOC-001',
            'name' => 'Gudang Test',
        ]);
        $requestType = InventoryRequestType::query()->create([
            'code' => 'new_request',
            'name' => 'Permintaan Baru',
        ]);
        $item = InventoryItem::query()->create([
            'uid' => 'ITEM-001',
            'item_type' => 'equipment',
            'inventory_location_id' => $location->id,
            'inventory_category_id' => $category->id,
            'inventory_subcategory_id' => $subcategory->id,
            'name' => 'Kunci Pas',
            'unit' => 'EA',
            'minimum_stock' => 2,
        ]);
        $item->forceFill(['current_stock' => 12])->save();

        $flutterTransaction = InventoryTransaction::query()->create([
            'transaction_number' => 'INV-TX-FLUTTER-001',
            'inventory_item_id' => $item->id,
            'inventory_user_id' => $inventoryUser->id,
            'woms_user_id' => null,
            'inventory_request_type_id' => $requestType->id,
            'transaction_type' => 'stock_out',
            'quantity' => 1,
            'stock_before' => 12,
            'stock_after' => 11,
            'purpose' => 'Keperluan pekerjaan',
            'source' => 'flutter',
            'item_uid_snapshot' => $item->uid,
            'item_name_snapshot' => $item->name,
            'unit_snapshot' => $item->unit,
            'transaction_at' => now(),
            'legacy_id' => 'LEGACY-TX-001',
            'legacy_payload' => [
                'nama_peminjam' => 'Data Lama',
                'bukti_penyerahan' => 'legacy-proof.jpg',
            ],
        ]);
        $adminTransaction = InventoryTransaction::query()->create([
            'transaction_number' => 'INV-TX-WOMS-001',
            'inventory_item_id' => $item->id,
            'inventory_user_id' => null,
            'woms_user_id' => $womsAdmin->id,
            'transaction_type' => 'stock_in',
            'quantity' => 3,
            'stock_before' => 11,
            'stock_after' => 14,
            'source' => 'woms_admin',
            'item_uid_snapshot' => $item->uid,
            'item_name_snapshot' => $item->name,
            'unit_snapshot' => $item->unit,
            'transaction_at' => now(),
        ]);
        $attachment = $flutterTransaction->attachments()->create([
            'attachment_type' => 'supporting_photo',
            'disk' => 'private',
            'path' => 'inventory/transactions/supporting-photo.jpg',
            'original_name' => 'supporting-photo.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 2048,
        ]);
        $inventoryUser->refresh();
        $item->refresh();

        $this->assertTrue($inventoryUser->is_active);
        $this->assertTrue($inventoryUser->must_change_password);
        $this->assertNotSame('secret-password', $inventoryUser->password);
        $this->assertTrue($category->subcategories->contains($subcategory));
        $this->assertTrue($subcategory->category->is($category));
        $this->assertTrue($item->category->is($category));
        $this->assertTrue($item->subcategory->is($subcategory));
        $this->assertTrue($item->location->is($location));
        $this->assertTrue($flutterTransaction->inventoryUser->is($inventoryUser));
        $this->assertNull($flutterTransaction->womsUser);
        $this->assertTrue($adminTransaction->womsUser->is($womsAdmin));
        $this->assertNull($adminTransaction->inventoryUser);
        $this->assertTrue($attachment->transaction->is($flutterTransaction));
        $this->assertSame(12, $item->current_stock);
        $this->assertSame(1, $flutterTransaction->quantity);
        $this->assertSame(11, $flutterTransaction->stock_after);
        $this->assertSame('Data Lama', $flutterTransaction->legacy_payload['nama_peminjam']);
    }

    public function test_item_history_survives_soft_delete_and_restricts_hard_delete(): void
    {
        $item = InventoryItem::query()->create([
            'uid' => 'ITEM-HISTORY-001',
            'item_type' => 'consumable',
            'name' => 'Consumable Test',
        ]);
        $transaction = InventoryTransaction::query()->create([
            'transaction_number' => 'INV-TX-HISTORY-001',
            'inventory_item_id' => $item->id,
            'transaction_type' => 'opening_balance',
            'quantity' => 5,
            'stock_before' => 0,
            'stock_after' => 5,
            'source' => 'seeder',
            'item_uid_snapshot' => $item->uid,
            'item_name_snapshot' => $item->name,
            'unit_snapshot' => $item->unit,
            'transaction_at' => now(),
        ]);

        $item->delete();

        $this->assertSoftDeleted('inventory_items', ['id' => $item->id]);
        $this->assertDatabaseHas('inventory_transactions', ['id' => $transaction->id]);

        try {
            $item->forceDelete();
            $this->fail('Hard delete should be restricted while transaction history exists.');
        } catch (QueryException) {
            $this->assertDatabaseHas('inventory_items', ['id' => $item->id]);
            $this->assertDatabaseHas('inventory_transactions', ['id' => $transaction->id]);
        }
    }

    public function test_request_type_seeder_is_idempotent(): void
    {
        $this->seed(InventoryRequestTypeSeeder::class);
        $this->seed(InventoryRequestTypeSeeder::class);

        $this->assertSame(5, InventoryRequestType::query()->count());
        $this->assertSame(5, InventoryRequestType::query()->distinct()->count('code'));
        $this->assertDatabaseHas('inventory_request_types', [
            'code' => 'damaged_replacement',
            'name' => 'Penggantian Alat Rusak',
            'requires_damaged_photo' => true,
            'requires_new_item_photo' => false,
        ]);
    }
}
