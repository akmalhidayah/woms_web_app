<?php

namespace Tests\Feature\Inventory;

use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryTransaction;
use Database\Seeders\InventoryStockAppSheetSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

class InventoryLegacyStockSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_bms_c51_is_preserved_as_942400_grams_with_legacy_payload(): void
    {
        $this->seed(InventoryStockAppSheetSeeder::class);
        $item = InventoryItem::query()->where('uid', 'BMS-C51')->sole();
        $transaction = InventoryTransaction::query()
            ->where('inventory_item_id', $item->id)
            ->where('transaction_type', 'opening_balance')
            ->sole();

        $this->assertSame('GRAM', $item->unit);
        $this->assertSame(942400, $item->current_stock);
        $this->assertSame(942400, $transaction->quantity);
        $this->assertSame('942.400', $transaction->legacy_payload['spare_stock_original']);
    }

    public function test_non_kg_fraction_is_rejected_without_rounding(): void
    {
        $method = new ReflectionMethod(InventoryStockAppSheetSeeder::class, 'normalizeOperationalRow');
        $method->setAccessible(true);

        $this->expectException(RuntimeException::class);
        $method->invoke(new InventoryStockAppSheetSeeder, [
            'uid' => 'BAD-EA',
            'unit' => 'EA',
            'opening_stock' => '1.500',
        ]);
    }
}
