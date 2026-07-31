<?php

namespace Tests\Feature\Inventory\Admin;

use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryAdminStockInTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_in_buttons_and_prefilled_form_are_available(): void
    {
        $admin = $this->admin();
        $item = $this->item();

        $this->actingAs($admin)->get(route('admin.inventory.dashboard'))
            ->assertOk()->assertSeeText('Tambah Stok');
        $this->actingAs($admin)->get(route('admin.inventory.stock-in.index'))
            ->assertOk()->assertSeeText('Tambah Stok');
        $this->actingAs($admin)->get(route('admin.inventory.items.index'))
            ->assertOk()->assertSee(route('admin.inventory.stock-in.create', ['item_id' => $item]), false);
        $this->actingAs($admin)->get(route('admin.inventory.stock-in.create', ['item_id' => $item]))
            ->assertOk()->assertSee('value="'.$item->id.'"', false);
    }

    public function test_admin_stock_in_uses_stock_service_and_records_auditable_transaction(): void
    {
        $admin = $this->admin();
        $item = $this->item(['current_stock' => 1]);

        $this->actingAs($admin)->post(route('admin.inventory.stock-in.store'), [
            'inventory_item_id' => $item->id,
            'quantity' => 2,
            'reference_number' => 'GR-001',
            'notes' => 'Penerimaan gudang',
        ])->assertRedirect(route('admin.inventory.stock-in.index'))
            ->assertSessionHas('success');

        $this->assertSame(3, $item->fresh()->current_stock);
        $transaction = InventoryTransaction::query()->sole();
        $this->assertSame('stock_in', $transaction->transaction_type);
        $this->assertSame(1, $transaction->stock_before);
        $this->assertSame(3, $transaction->stock_after);
        $this->assertSame($admin->id, $transaction->woms_user_id);
        $this->assertNull($transaction->inventory_user_id);
        $this->assertSame('woms_admin', $transaction->source);
        $this->assertSame('GR-001', $transaction->reference_number);
    }

    public function test_invalid_quantity_and_inactive_item_do_not_change_stock(): void
    {
        $admin = $this->admin();
        $item = $this->item();

        foreach (['0', '-1', '1.5', '1.000', 'abc'] as $quantity) {
            $this->actingAs($admin)->post(route('admin.inventory.stock-in.store'), [
                'inventory_item_id' => $item->id,
                'quantity' => $quantity,
            ])->assertSessionHasErrors('quantity');
        }

        $item->update(['is_active' => false]);
        $this->actingAs($admin)->post(route('admin.inventory.stock-in.store'), [
            'inventory_item_id' => $item->id,
            'quantity' => 1,
        ])->assertNotFound();

        $this->assertSame(0, $item->fresh()->current_stock);
        $this->assertDatabaseCount('inventory_transactions', 0);
    }

    public function test_non_admin_cannot_open_or_submit_stock_in(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $item = $this->item();

        $this->actingAs($user)->get(route('admin.inventory.stock-in.create'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.inventory.stock-in.store'), [
            'inventory_item_id' => $item->id,
            'quantity' => 1,
        ])->assertForbidden();
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
    }

    private function item(array $attributes = []): InventoryItem
    {
        $stock = (int) ($attributes['current_stock'] ?? 0);
        unset($attributes['current_stock']);
        $item = InventoryItem::query()->create(array_merge([
            'uid' => 'STOCK-'.str()->ulid(),
            'item_type' => 'consumable',
            'name' => 'Barang Stok Masuk',
            'unit' => 'EA',
            'minimum_stock' => 0,
            'is_active' => true,
        ], $attributes));
        $item->forceFill(['current_stock' => $stock])->save();

        return $item->refresh();
    }
}
