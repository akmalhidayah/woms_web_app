<?php

namespace Tests\Feature\Inventory;

use App\Exceptions\Inventory\InactiveInventoryItemException;
use App\Exceptions\Inventory\InsufficientInventoryStockException;
use App\Exceptions\Inventory\InvalidInventoryActorException;
use App\Exceptions\Inventory\InvalidInventoryRequestTypeException;
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
use InvalidArgumentException;
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

    public function test_opening_balance_updates_stock_and_records_system_transaction(): void
    {
        Carbon::setTestNow('2026-07-30 10:15:00');
        $item = $this->item();

        $transaction = $this->service->createOpeningBalance($item, '2.500');

        $this->assertSame(InventoryStockService::TYPE_OPENING_BALANCE, $transaction->transaction_type);
        $this->assertSame('2.500', $transaction->quantity);
        $this->assertSame('0.000', $transaction->stock_before);
        $this->assertSame('2.500', $transaction->stock_after);
        $this->assertSame('2.500', $item->refresh()->current_stock);
        $this->assertSame('system', $transaction->source);
        $this->assertNull($transaction->inventory_user_id);
        $this->assertNull($transaction->woms_user_id);
        $this->assertTrue($transaction->transaction_at->equalTo(now()));
        $this->assertStringStartsWith('INV-OPEN-20260730-', $transaction->transaction_number);
    }

    public function test_opening_balance_accepts_admin_import_context_and_valid_date(): void
    {
        $admin = $this->admin();
        $item = $this->item();

        $transaction = $this->service->createOpeningBalance($item, 1, $admin, [
            'source' => 'import',
            'transaction_at' => '2025-01-02 03:04:05',
            'legacy_id' => 'OLD-001',
            'legacy_payload' => ['input_by' => 'Legacy Admin'],
        ]);

        $this->assertSame($admin->id, $transaction->woms_user_id);
        $this->assertNull($transaction->inventory_user_id);
        $this->assertSame('woms_admin', $transaction->source);
        $this->assertSame('2025-01-02 03:04:05', $transaction->transaction_at->format('Y-m-d H:i:s'));
        $this->assertSame('OLD-001', $transaction->legacy_id);
        $this->assertSame('Legacy Admin', $transaction->legacy_payload['input_by']);
    }

    public function test_opening_balance_can_only_be_created_once(): void
    {
        $item = $this->item();
        $this->service->createOpeningBalance($item, '1.000');

        $this->expectException(OpeningBalanceAlreadyExistsException::class);

        $this->service->createOpeningBalance($item, '2.000');
    }

    public function test_opening_balance_is_rejected_when_stock_is_already_non_zero(): void
    {
        $item = $this->item(['current_stock' => '1.000']);

        $this->expectException(OpeningBalanceAlreadyExistsException::class);

        $this->service->createOpeningBalance($item, '2.000');
    }

    public function test_stock_in_by_woms_admin_uses_exact_decimal_and_actor_columns(): void
    {
        $admin = $this->admin();
        $item = $this->item(['current_stock' => '1.250']);

        $transaction = $this->service->stockIn($item, '2.500', $admin);

        $this->assertSame(InventoryStockService::TYPE_STOCK_IN, $transaction->transaction_type);
        $this->assertSame('1.250', $transaction->stock_before);
        $this->assertSame('3.750', $transaction->stock_after);
        $this->assertSame('3.750', $item->refresh()->current_stock);
        $this->assertSame($admin->id, $transaction->woms_user_id);
        $this->assertNull($transaction->inventory_user_id);
        $this->assertSame('woms_admin', $transaction->source);
        $this->assertTrue($transaction->womsUser->is($admin));
    }

    public function test_stock_out_by_inventory_user_records_request_context_and_snapshot(): void
    {
        $item = $this->item([
            'uid' => 'ITEM-SNAPSHOT',
            'name' => 'Sarung Tangan',
            'unit' => 'PAIR',
            'current_stock' => '3.000',
        ]);
        $inventoryUser = $this->inventoryUser();
        $requestType = $this->requestType();

        $transaction = $this->service->stockOut($item, '1.250', $inventoryUser, [
            'inventory_request_type_id' => $requestType->id,
            'purpose' => 'Pekerjaan mekanik',
            'reference_number' => 'REQ-001',
        ]);

        $this->assertSame(InventoryStockService::TYPE_STOCK_OUT, $transaction->transaction_type);
        $this->assertSame('3.000', $transaction->stock_before);
        $this->assertSame('1.750', $transaction->stock_after);
        $this->assertSame('1.750', $item->refresh()->current_stock);
        $this->assertSame($inventoryUser->id, $transaction->inventory_user_id);
        $this->assertNull($transaction->woms_user_id);
        $this->assertSame('flutter', $transaction->source);
        $this->assertSame($requestType->id, $transaction->inventory_request_type_id);
        $this->assertSame('Pekerjaan mekanik', $transaction->purpose);
        $this->assertSame('ITEM-SNAPSHOT', $transaction->item_uid_snapshot);
        $this->assertSame('Sarung Tangan', $transaction->item_name_snapshot);
        $this->assertSame('PAIR', $transaction->unit_snapshot);
    }

    public function test_stock_out_equal_to_available_stock_results_in_zero(): void
    {
        $item = $this->item(['current_stock' => '1.125']);

        $transaction = $this->service->stockOut($item, '1.125', $this->admin());

        $this->assertSame('0.000', $transaction->stock_after);
        $this->assertSame('0.000', $item->refresh()->current_stock);
    }

    public function test_flutter_stock_out_requires_active_request_type_and_purpose(): void
    {
        $item = $this->item(['current_stock' => '5.000']);
        $user = $this->inventoryUser();

        foreach ([
            [],
            ['inventory_request_type_id' => $this->requestType()->id],
            [
                'inventory_request_type_id' => $this->requestType(['code' => 'inactive', 'name' => 'Inactive', 'is_active' => false])->id,
                'purpose' => 'Test',
            ],
        ] as $context) {
            try {
                $this->service->stockOut($item, '1.000', $user, $context);
                $this->fail('Invalid Flutter stock out context should be rejected.');
            } catch (InvalidInventoryRequestTypeException) {
                $this->assertSame('5.000', $item->refresh()->current_stock);
            }
        }

        $this->assertSame(0, InventoryTransaction::query()->count());
    }

    public function test_insufficient_stock_rolls_back_without_history(): void
    {
        $item = $this->item(['current_stock' => '1.000']);

        try {
            $this->service->stockOut($item, '1.001', $this->admin());
            $this->fail('Insufficient stock should be rejected.');
        } catch (InsufficientInventoryStockException $exception) {
            $this->assertStringContainsString('1.000 EA', $exception->getMessage());
        }

        $this->assertSame('1.000', $item->refresh()->current_stock);
        $this->assertSame(0, InventoryTransaction::query()->count());
    }

    public function test_adjustments_require_admin_and_non_empty_reason(): void
    {
        $admin = $this->admin();
        $item = $this->item(['current_stock' => '5.000']);

        $increase = $this->service->adjustmentIn($item, '1.500', $admin, 'Koreksi opname');
        $decrease = $this->service->adjustmentOut($item, '2.000', $admin, 'Barang rusak');

        $this->assertSame(InventoryStockService::TYPE_ADJUSTMENT_IN, $increase->transaction_type);
        $this->assertSame('Koreksi opname', $increase->notes);
        $this->assertSame(InventoryStockService::TYPE_ADJUSTMENT_OUT, $decrease->transaction_type);
        $this->assertSame('Barang rusak', $decrease->notes);
        $this->assertSame('4.500', $item->refresh()->current_stock);

        $this->expectException(InvalidArgumentException::class);
        $this->service->adjustmentIn($item, 1, $admin, '   ');
    }

    public function test_adjustment_out_cannot_exceed_available_stock(): void
    {
        $item = $this->item(['current_stock' => '2.000']);

        $this->expectException(InsufficientInventoryStockException::class);

        $this->service->adjustmentOut($item, '2.001', $this->admin(), 'Koreksi');
    }

    public function test_invalid_quantities_and_overflow_are_rejected(): void
    {
        $item = $this->item();
        $admin = $this->admin();

        foreach ([0, -1, 'abc', '1.0001', NAN, INF] as $quantity) {
            try {
                $this->service->stockIn($item, $quantity, $admin);
                $this->fail('Invalid quantity should be rejected.');
            } catch (InvalidStockQuantityException) {
                $this->assertSame('0.000', $item->refresh()->current_stock);
            }
        }

        try {
            $this->service->stockIn($item, '1000000000000.000', $admin);
            $this->fail('Overflow quantity should be rejected.');
        } catch (InventoryStockOverflowException) {
            $this->assertSame('0.000', $item->refresh()->current_stock);
        }

        $this->assertSame(0, InventoryTransaction::query()->count());
    }

    public function test_stock_addition_overflow_is_rejected(): void
    {
        $item = $this->item(['current_stock' => '999999999999.999']);

        $this->expectException(InventoryStockOverflowException::class);

        $this->service->stockIn($item, '0.001', $this->admin());
    }

    public function test_inactive_and_soft_deleted_items_cannot_be_transacted(): void
    {
        $admin = $this->admin();
        $inactive = $this->item(['uid' => 'INACTIVE', 'is_active' => false]);

        try {
            $this->service->stockIn($inactive, 1, $admin);
            $this->fail('Inactive item should be rejected.');
        } catch (InactiveInventoryItemException) {
            $this->assertSame('0.000', $inactive->refresh()->current_stock);
        }

        $deleted = $this->item(['uid' => 'DELETED']);
        $deleted->delete();

        $this->expectException(ModelNotFoundException::class);
        $this->service->stockIn($deleted, 1, $admin);
    }

    public function test_invalid_actors_are_rejected_and_actor_columns_never_overlap(): void
    {
        $item = $this->item(['current_stock' => '3.000']);
        $nonAdmin = User::factory()->create(['role' => User::ROLE_USER]);

        try {
            $this->service->stockIn($item, 1, $nonAdmin);
            $this->fail('Non-admin WOMS user should be rejected.');
        } catch (InvalidInventoryActorException) {
            $this->assertSame(0, InventoryTransaction::query()->count());
        }

        $flutter = $this->inventoryUser();
        $flutterTransaction = $this->service->stockOut($item, 1, $flutter, [
            'inventory_request_type_id' => $this->requestType()->id,
            'purpose' => 'Operasional',
        ]);
        $adminTransaction = $this->service->stockOut($item, 1, $this->admin());

        foreach ([$flutterTransaction, $adminTransaction] as $transaction) {
            $this->assertFalse(
                $transaction->inventory_user_id !== null && $transaction->woms_user_id !== null
            );
        }
    }

    public function test_transaction_numbers_are_unique_and_match_operation_prefixes(): void
    {
        $item = $this->item(['current_stock' => '5.000']);
        $admin = $this->admin();

        $stockIn = $this->service->stockIn($item, 1, $admin);
        $stockOut = $this->service->stockOut($item, 1, $admin);
        $adjustmentIn = $this->service->adjustmentIn($item, 1, $admin, 'Test');
        $adjustmentOut = $this->service->adjustmentOut($item, 1, $admin, 'Test');

        $numbers = collect([$stockIn, $stockOut, $adjustmentIn, $adjustmentOut])
            ->pluck('transaction_number');

        $this->assertSame(4, $numbers->unique()->count());
        $this->assertStringContainsString('INV-IN-', $stockIn->transaction_number);
        $this->assertStringContainsString('INV-OUT-', $stockOut->transaction_number);
        $this->assertStringContainsString('INV-ADJIN-', $adjustmentIn->transaction_number);
        $this->assertStringContainsString('INV-ADJOUT-', $adjustmentOut->transaction_number);
    }

    public function test_database_failure_rolls_back_stock_update_and_transaction(): void
    {
        $item = $this->item(['current_stock' => '2.000']);

        DB::statement("
            CREATE TRIGGER reject_inventory_transaction
            BEFORE INSERT ON inventory_transactions
            BEGIN
                SELECT RAISE(ABORT, 'forced inventory transaction failure');
            END
        ");

        try {
            $this->service->stockIn($item, '1.000', $this->admin());
            $this->fail('Database insert failure should bubble up.');
        } catch (QueryException) {
            $this->assertSame('2.000', $item->refresh()->current_stock);
            $this->assertSame(0, InventoryTransaction::query()->count());
        } finally {
            DB::statement('DROP TRIGGER IF EXISTS reject_inventory_transaction');
        }
    }

    public function test_unknown_context_invalid_legacy_payload_and_invalid_date_are_rejected(): void
    {
        $item = $this->item();

        foreach ([
            ['unexpected' => true],
            ['legacy_payload' => 'not-an-array'],
            ['transaction_at' => 'not-a-date'],
        ] as $context) {
            try {
                $this->service->createOpeningBalance($item, 1, null, $context);
                $this->fail('Invalid context should be rejected.');
            } catch (InvalidArgumentException) {
                $this->assertSame('0.000', $item->refresh()->current_stock);
                $this->assertSame(0, InventoryTransaction::query()->count());
            }
        }
    }

    private function item(array $attributes = []): InventoryItem
    {
        return InventoryItem::query()->create(array_merge([
            'uid' => 'ITEM-'.str()->ulid(),
            'item_type' => 'consumable',
            'name' => 'Barang Inventory',
            'unit' => 'EA',
            'current_stock' => '0.000',
        ], $attributes));
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_ADMIN,
        ]);
    }

    private function inventoryUser(array $attributes = []): InventoryUser
    {
        return InventoryUser::query()->create(array_merge([
            'name' => 'Inventory User',
            'email' => str()->ulid().'@inventory.test',
            'password' => 'password',
            'is_active' => true,
        ], $attributes));
    }

    private function requestType(array $attributes = []): InventoryRequestType
    {
        return InventoryRequestType::query()->create(array_merge([
            'code' => 'request-'.str()->ulid(),
            'name' => 'Request '.str()->ulid(),
            'is_active' => true,
        ], $attributes));
    }
}
