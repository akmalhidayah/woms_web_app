<?php

namespace Tests\Feature\Inventory;

use App\Exceptions\Inventory\InactiveInventoryItemException;
use App\Exceptions\Inventory\InsufficientInventoryStockException;
use App\Exceptions\Inventory\InvalidStockQuantityException;
use App\Exceptions\Inventory\InventoryStockOverflowException;
use App\Exceptions\Inventory\OpeningBalanceAlreadyExistsException;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryRequestType;
use App\Models\Inventory\InventoryTransaction;
use App\Models\Inventory\InventoryUser;
use App\Models\User;
use App\Services\Inventory\InventoryStockService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InventoryStockServiceTest extends TestCase
{
    use RefreshDatabase;

    private InventoryStockService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InventoryStockService::class);
    }

    public function test_opening_balance_uses_integer_and_can_only_be_created_once(): void
    {
        Carbon::setTestNow('2026-07-31 10:15:00');
        $item = $this->item();
        $transaction = $this->service->createOpeningBalance($item, '25');

        $this->assertSame(25, $transaction->quantity);
        $this->assertSame(0, $transaction->stock_before);
        $this->assertSame(25, $transaction->stock_after);
        $this->assertSame(25, $item->refresh()->current_stock);
        $this->assertSame('system', $transaction->source);
        $this->assertStringStartsWith('INV-OPEN-20260731-', $transaction->transaction_number);

        $this->expectException(OpeningBalanceAlreadyExistsException::class);
        $this->service->createOpeningBalance($item, 1);
    }

    public function test_stock_in_out_and_adjustments_are_exact_integers(): void
    {
        $item = $this->item(['current_stock' => 10]);
        $admin = $this->admin();

        $in = $this->service->stockIn($item, 5, $admin);
        $out = $this->service->stockOut($item, '4', $admin);
        $adjustmentIn = $this->service->adjustmentIn($item, 2, $admin, 'Opname');
        $adjustmentOut = $this->service->adjustmentOut($item, 3, $admin, 'Rusak');

        $this->assertSame([15, 11, 13, 10], [
            $in->stock_after,
            $out->stock_after,
            $adjustmentIn->stock_after,
            $adjustmentOut->stock_after,
        ]);
        $this->assertSame(10, $item->refresh()->current_stock);
        $this->assertSame($admin->id, $in->woms_user_id);
        $this->assertNull($in->inventory_user_id);
    }

    public function test_flutter_stock_out_records_actor_request_and_snapshot(): void
    {
        $item = $this->item(['uid' => 'SNAPSHOT', 'name' => 'Sarung Tangan', 'unit' => 'PAIR', 'current_stock' => 3]);
        $user = $this->inventoryUser();
        $type = $this->requestType();

        $transaction = $this->service->stockOut($item, 1, $user, [
            'inventory_request_type_id' => $type->id,
            'purpose' => 'Pekerjaan mekanik',
        ]);

        $this->assertSame(2, $transaction->stock_after);
        $this->assertSame($user->id, $transaction->inventory_user_id);
        $this->assertNull($transaction->woms_user_id);
        $this->assertSame('flutter', $transaction->source);
        $this->assertSame('SNAPSHOT', $transaction->item_uid_snapshot);
        $this->assertSame('Sarung Tangan', $transaction->item_name_snapshot);
        $this->assertSame('PAIR', $transaction->unit_snapshot);
    }

    public function test_invalid_integer_quantities_and_overflow_are_rejected(): void
    {
        $item = $this->item();
        $admin = $this->admin();

        foreach ([0, -1, '0', '-1', '1.0', '1.000', '1.5', '1e3', 'abc'] as $quantity) {
            try {
                $this->service->stockIn($item, $quantity, $admin);
                $this->fail("Quantity {$quantity} seharusnya ditolak.");
            } catch (InvalidStockQuantityException) {
                $this->assertSame(0, $item->refresh()->current_stock);
            }
        }

        $this->expectException(InventoryStockOverflowException::class);
        $this->service->stockIn($item, '9223372036854775808', $admin);
    }

    public function test_stock_addition_overflow_is_rejected(): void
    {
        $item = $this->item(['current_stock' => InventoryStockService::MAX_STOCK]);

        $this->expectException(InventoryStockOverflowException::class);
        $this->service->stockIn($item, 1, $this->admin());
    }

    public function test_stock_out_cannot_be_negative_and_failure_creates_no_history(): void
    {
        $item = $this->item(['current_stock' => 2]);

        try {
            $this->service->stockOut($item, 3, $this->admin());
            $this->fail('Stok kurang seharusnya ditolak.');
        } catch (InsufficientInventoryStockException $exception) {
            $this->assertStringContainsString('2 EA', $exception->getMessage());
        }

        $this->assertSame(2, $item->refresh()->current_stock);
        $this->assertDatabaseCount('inventory_transactions', 0);
    }

    public function test_inactive_and_soft_deleted_items_are_rejected(): void
    {
        $inactive = $this->item(['is_active' => false]);
        $this->expectException(InactiveInventoryItemException::class);
        $this->service->stockIn($inactive, 1, $this->admin());
    }

    public function test_soft_deleted_item_is_not_found_under_lock(): void
    {
        $item = $this->item();
        $item->delete();

        $this->expectException(ModelNotFoundException::class);
        $this->service->stockIn($item, 1, $this->admin());
    }

    public function test_database_failure_rolls_back_stock_and_history(): void
    {
        $item = $this->item(['current_stock' => 2]);
        DB::statement("CREATE TRIGGER reject_inventory_transaction BEFORE INSERT ON inventory_transactions BEGIN SELECT RAISE(ABORT, 'forced failure'); END");

        try {
            $this->service->stockIn($item, 1, $this->admin());
            $this->fail('Insert seharusnya gagal.');
        } catch (QueryException) {
            $this->assertSame(2, $item->refresh()->current_stock);
            $this->assertDatabaseCount('inventory_transactions', 0);
        } finally {
            DB::statement('DROP TRIGGER IF EXISTS reject_inventory_transaction');
        }
    }

    public function test_opening_balance_preserves_legacy_payload_and_date(): void
    {
        $transaction = $this->service->createOpeningBalance($this->item(), 10, null, [
            'source' => 'import',
            'transaction_at' => '2025-01-02 03:04:05',
            'legacy_id' => 'OLD-1',
            'legacy_payload' => ['spare_stock_original' => '10.000'],
        ]);

        $this->assertSame('import', $transaction->source);
        $this->assertSame('OLD-1', $transaction->legacy_id);
        $this->assertSame('10.000', $transaction->legacy_payload['spare_stock_original']);
        $this->assertSame('2025-01-02 03:04:05', $transaction->transaction_at->format('Y-m-d H:i:s'));
    }

    private function item(array $attributes = []): InventoryItem
    {
        $stock = (int) ($attributes['current_stock'] ?? 0);
        unset($attributes['current_stock']);
        $item = InventoryItem::query()->create(array_merge([
            'uid' => 'ITEM-'.str()->ulid(),
            'item_type' => 'consumable',
            'name' => 'Barang Inventory',
            'unit' => 'EA',
            'is_active' => true,
        ], $attributes));
        $item->forceFill(['current_stock' => $stock])->save();

        return $item->refresh();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN, 'admin_role' => User::ADMIN_ROLE_ADMIN]);
    }

    private function inventoryUser(): InventoryUser
    {
        return InventoryUser::query()->create([
            'name' => 'Inventory User',
            'email' => str()->ulid().'@inventory.test',
            'password' => 'password',
            'is_active' => true,
        ]);
    }

    private function requestType(): InventoryRequestType
    {
        return InventoryRequestType::query()->create([
            'code' => 'request-'.str()->ulid(),
            'name' => 'Request '.str()->ulid(),
            'is_active' => true,
        ]);
    }
}
