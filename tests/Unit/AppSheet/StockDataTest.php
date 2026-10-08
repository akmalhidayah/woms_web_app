<?php

namespace Tests\Unit\AppSheet;

use App\Support\AppSheet\StockData;
use PHPUnit\Framework\TestCase;

class StockDataTest extends TestCase
{
    public function test_quantities_preserve_decimals_and_do_not_guess_formatted_text(): void
    {
        self::assertSame('12.000', StockData::displayQuantity(12000));
        self::assertSame('2,345', StockData::displayQuantity(2.345));
        self::assertSame('0,125', StockData::displayQuantity(0.125));
        self::assertSame('0,0000001', StockData::displayQuantity(1e-7));
        self::assertSame('0', StockData::displayQuantity(0));
        self::assertSame('12.000', StockData::displayQuantity('12.000'));
        self::assertSame('2,345', StockData::displayQuantity('2,345'));
        self::assertSame('-', StockData::displayQuantity(null));
    }

    public function test_consignment_and_minimum_are_separate_values_without_a_derived_status(): void
    {
        $item = StockData::consumableGudang([
            'CONSUMABLE' => ' Kawat Las ', 'QTY KONSINYASI' => 2.345,
            'QTY NON KONSINYASI' => 12000, 'MIN' => 99999,
            'UPD. DATE' => '11/09/2026 16.05.14',
        ]);

        self::assertSame('Kawat Las', $item['name']);
        self::assertSame(2.345, $item['consignment']);
        self::assertSame(12000, $item['non_consignment']);
        self::assertSame(99999, $item['minimum']);
        self::assertSame('11/09/2026', $item['date_display']);
        self::assertSame('16:05:14', $item['time_display']);
        self::assertArrayNotHasKey('status', $item);
        self::assertArrayNotHasKey('quantity', $item);
    }

    public function test_material_status_uses_qty_only_and_keeps_unknown_values_unknown(): void
    {
        foreach ([0, -2, 0.25, null, '#VALUE!'] as $quantity) {
            $expected = match ($quantity) {
                0, -2 => 'habis',
                0.25 => 'tersedia',
                default => null,
            };
            self::assertSame($expected, StockData::materialBms(['QTY' => $quantity])['status']);
            self::assertSame($expected, StockData::materialGudang(['QTY' => $quantity, 'QTY CAPEX' => 900])['status']);
        }
    }

    public function test_material_names_survive_missing_codes_and_descriptions_stay_separate(): void
    {
        foreach (['', '-', null] as $code) {
            $item = StockData::materialGudang([
                'NO. MATERIAL' => $code, 'MATERIAL' => 'Plat',
                'DESKRIPSI' => 'SUDAH DI ISSUED', 'Duplikat' => 1,
            ]);
            self::assertTrue(StockData::hasItem($item));
            self::assertSame('Plat', $item['name']);
            self::assertSame('SUDAH DI ISSUED', $item['description']);
            self::assertArrayNotHasKey('Duplikat', $item);
        }

        self::assertFalse(StockData::hasItem(StockData::materialGudang(['NO. MATERIAL' => '-', 'QTY' => 0])));
    }
}
