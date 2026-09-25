<?php

namespace Tests\Unit\AppSheet;

use App\Exceptions\AppSheet\GoogleSheetsException;
use App\Services\AppSheet\GoogleOAuthService;
use App\Services\AppSheet\GoogleSheetsWriter;
use App\Support\AppSheet\StockSheetMap;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class GoogleSheetsWriterTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.spreadsheet_id' => 'test-spreadsheet',
            'services.google.stock_consumable_sheet' => 'STOCK CONS BMS',
            'services.google.stock_consumable_gudang_sheet' => 'STOCK CONS GUDANG',
            'services.google.stock_material_bms_sheet' => 'STOCK MATERIAL BMS',
            'services.google.stock_material_gudang_sheet' => 'STOK MATERIAL GUDANG',
        ]);
        $cache = Cache::store('array');
        Cache::shouldReceive('store')->with('file')->andReturn($cache);
        Http::preventStrayRequests();
    }

    public function test_consumable_bms_increase_updates_stock_in_spare_and_audit_cells_only(): void
    {
        $this->fakeSheet([
            'DESC.', 'STOCK OUT', 'UID', 'INPUT DATE', 'SPARE STOCK', 'STN', 'STOCK IN', 'INPUT. BY', 'HELPER', 'HELPER',
        ], [
            ['KAPUR BESI', 848, 'BMS-C03', '2026-09-01 08:00:00', 896, 'BTG', 1744, 'Petugas Lama', 'A', 'B'],
        ]);
        Cache::store('file')->put('appsheet:stock-consumable', ['cached' => true], 300);
        Cache::store('file')->put('appsheet:history-consumable', ['keep' => true], 300);

        $result = $this->writer()->updateStock(
            StockSheetMap::CONSUMABLE_BMS,
            'BMS-C03',
            ['spare_stock' => 900],
            ['spare_stock' => 896],
            'Admin Laravel',
        );

        self::assertTrue($result['changed']);
        self::assertSame(['old' => 896.0, 'new' => 900.0], $result['changes']['spare_stock']);
        $cells = $this->writtenCells();
        self::assertSame(1748.0, $cells["'STOCK CONS BMS'!G2"]);
        self::assertSame(900.0, $cells["'STOCK CONS BMS'!E2"]);
        self::assertSame('Admin Laravel', $cells["'STOCK CONS BMS'!H2"]);
        self::assertArrayHasKey("'STOCK CONS BMS'!D2", $cells);
        self::assertArrayNotHasKey("'STOCK CONS BMS'!B2", $cells);
        self::assertNull(Cache::store('file')->get('appsheet:stock-consumable'));
        self::assertSame(['keep' => true], Cache::store('file')->get('appsheet:history-consumable'));
    }

    public function test_consumable_bms_decrease_updates_stock_out_without_changing_stock_in(): void
    {
        $this->fakeSheet([
            'UID', 'DESC.', 'STOCK IN', 'STOCK OUT', 'SPARE STOCK', 'STN', 'INPUT. BY', 'INPUT DATE',
        ], [
            ['BMS-C03', 'KAPUR BESI', 1744, 848, 896, 'BTG', 'Lama', '2026-09-01'],
        ]);

        $this->writer()->updateStock(
            StockSheetMap::CONSUMABLE_BMS,
            'BMS-C03',
            ['spare_stock' => 890],
            ['spare_stock' => 896],
            'Admin Laravel',
        );

        $cells = $this->writtenCells();
        self::assertSame(854.0, $cells["'STOCK CONS BMS'!D2"]);
        self::assertSame(890.0, $cells["'STOCK CONS BMS'!E2"]);
        self::assertArrayNotHasKey("'STOCK CONS BMS'!C2", $cells);
    }

    public function test_no_change_does_not_write_or_invalidate_cache(): void
    {
        $this->fakeSheet([
            'UID', 'DESC.', 'STOCK IN', 'STOCK OUT', 'SPARE STOCK', 'STN', 'INPUT. BY', 'INPUT DATE',
        ], [
            ['BMS-C03', 'KAPUR BESI', 1744, 848, 896, 'BTG', 'Lama', '2026-09-01'],
        ]);
        Cache::store('file')->put('appsheet:stock-consumable', ['cached' => true], 300);

        $result = $this->writer()->updateStock(
            StockSheetMap::CONSUMABLE_BMS,
            'BMS-C03',
            ['spare_stock' => 896],
            ['spare_stock' => 896],
            'Admin Laravel',
        );

        self::assertFalse($result['changed']);
        Http::assertSentCount(1);
        self::assertSame(['cached' => true], Cache::store('file')->get('appsheet:stock-consumable'));
    }

    public function test_stale_original_value_is_rejected_without_overwriting_latest_stock(): void
    {
        $this->fakeSheet([
            'UID', 'DESC.', 'STOCK IN', 'STOCK OUT', 'SPARE STOCK', 'STN', 'INPUT. BY', 'INPUT DATE',
        ], [
            ['BMS-C03', 'KAPUR BESI', 1748, 848, 900, 'BTG', 'User B', '2026-09-25'],
        ]);

        try {
            $this->writer()->updateStock(
                StockSheetMap::CONSUMABLE_BMS,
                'BMS-C03',
                ['spare_stock' => 890],
                ['spare_stock' => 896],
                'User A',
            );
            self::fail('Expected a stock concurrency conflict.');
        } catch (GoogleSheetsException $exception) {
            self::assertStringContainsString('Nilai terbaru SPARE STOCK adalah 900', $exception->getMessage());
        }

        Http::assertSentCount(1);
    }

    public function test_missing_identifier_is_rejected(): void
    {
        $this->fakeSheet(['UID', 'JENIS MATERIAL', 'QTY', 'STN', 'UPD. BY', 'LAST UPDATE'], [
            ['OTHER', 'Item', 1, 'EA', 'User', '2026-09-01'],
        ]);

        try {
            $this->writer()->updateStock(
                StockSheetMap::MATERIAL_BMS,
                'TARGET',
                ['quantity' => 3],
                ['quantity' => 1],
                'Admin',
            );
            self::fail('Expected missing identifier failure.');
        } catch (GoogleSheetsException $exception) {
            self::assertStringContainsString('tidak ditemukan', $exception->getMessage());
        }

        Http::assertSentCount(1);
    }

    public function test_duplicate_identifier_is_rejected(): void
    {
        $this->fakeSheet(['UID', 'JENIS MATERIAL', 'QTY', 'STN', 'UPD. BY', 'LAST UPDATE'], [
            ['TARGET', 'Item A', 1, 'EA', 'User', '2026-09-01'],
            ['TARGET', 'Item B', 2, 'EA', 'User', '2026-09-02'],
        ]);

        try {
            $this->writer()->updateStock(
                StockSheetMap::MATERIAL_BMS,
                'TARGET',
                ['quantity' => 3],
                ['quantity' => 1],
                'Admin',
            );
            self::fail('Expected duplicate identifier failure.');
        } catch (GoogleSheetsException $exception) {
            self::assertStringContainsString('lebih dari satu kali', $exception->getMessage());
        }

        Http::assertSentCount(1);
    }

    public function test_consumable_gudang_supports_identifier_alias_and_updates_both_quantities(): void
    {
        $this->fakeSheet([
            'NO. MATERIAL', 'CONSUMABLE', 'DESKRIPSI', 'QTY KONSINYASI', 'QTY NON KONSINYASI', 'STN', 'UPD. BY', 'UPD. DATE',
        ], [
            ['M-01', 'Kawat', 'Kawat Las', 10.5, 20, 'KG', 'Lama', '2026-09-01'],
        ]);

        $this->writer()->updateStock(
            StockSheetMap::CONSUMABLE_GUDANG,
            'M-01',
            ['qty_consignment' => 11.25, 'qty_non_consignment' => 21],
            ['qty_consignment' => 10.5, 'qty_non_consignment' => 20],
            'Admin',
        );

        $cells = $this->writtenCells();
        self::assertSame(11.25, $cells["'STOCK CONS GUDANG'!D2"]);
        self::assertSame(21.0, $cells["'STOCK CONS GUDANG'!E2"]);
        self::assertSame('Admin', $cells["'STOCK CONS GUDANG'!G2"]);
    }

    public function test_material_bms_updates_quantity_only_with_audit_cells(): void
    {
        $this->fakeSheet(['UID', 'JENIS MATERIAL', 'QTY', 'STN', 'UPD. BY', 'LAST UPDATE'], [
            ['BMS-M1', 'Plat', 2.5, 'EA', 'Lama', '2026-09-01'],
        ]);

        $this->writer()->updateStock(
            StockSheetMap::MATERIAL_BMS,
            'BMS-M1',
            ['quantity' => 3.75],
            ['quantity' => 2.5],
            'Admin',
        );

        $cells = $this->writtenCells();
        self::assertSame(3.75, $cells["'STOCK MATERIAL BMS'!C2"]);
        self::assertCount(3, $cells);
    }

    public function test_material_gudang_supports_identifier_alias_and_updates_qty_and_capex(): void
    {
        $this->fakeSheet([
            'NO MATERIAL', 'MATERIAL', 'DESKRIPSI', 'QTY', 'QTY CAPEX', 'STN', 'UPDATE BY', 'UPDATE DATE',
        ], [
            ['MAT-01', 'Plat', 'Plat Baja', 4, 2, 'EA', 'Lama', '2026-09-01'],
        ]);

        $this->writer()->updateStock(
            StockSheetMap::MATERIAL_GUDANG,
            'MAT-01',
            ['quantity' => 5, 'qty_capex' => 3.5],
            ['quantity' => 4, 'qty_capex' => 2],
            'Admin',
        );

        $cells = $this->writtenCells();
        self::assertSame(5.0, $cells["'STOK MATERIAL GUDANG'!D2"]);
        self::assertSame(3.5, $cells["'STOK MATERIAL GUDANG'!E2"]);
        self::assertCount(4, $cells);
    }

    public function test_write_api_error_is_safe_and_does_not_invalidate_cache(): void
    {
        Log::spy();
        Cache::store('file')->put('appsheet:stock-material-bms', ['cached' => true], 300);
        Http::fake(function (Request $request) {
            if ($request->method() === 'GET') {
                return Http::response(['range' => 'sheet', 'values' => [
                    ['UID', 'JENIS MATERIAL', 'QTY', 'STN', 'UPD. BY', 'LAST UPDATE'],
                    ['BMS-M1', 'Plat', 2, 'EA', 'Lama', '2026-09-01'],
                ]]);
            }

            return Http::response(['error' => ['message' => 'sensitive-api-body test-access-token']], 500);
        });

        try {
            $this->writer()->updateStock(
                StockSheetMap::MATERIAL_BMS,
                'BMS-M1',
                ['quantity' => 3],
                ['quantity' => 2],
                'Admin',
            );
            self::fail('Expected a safe write failure.');
        } catch (GoogleSheetsException $exception) {
            self::assertSame('Stock belum dapat diperbarui. Silakan coba kembali.', $exception->getMessage());
            self::assertStringNotContainsString('sensitive-api-body', $exception->getMessage());
            self::assertStringNotContainsString('test-access-token', $exception->getMessage());
        }

        self::assertSame(['cached' => true], Cache::store('file')->get('appsheet:stock-material-bms'));
        Log::shouldNotHaveReceived('warning');
    }

    public function test_legacy_oauth_scope_requires_reconnect_before_writing(): void
    {
        $google = Mockery::mock(GoogleOAuthService::class);
        $google->shouldReceive('hasSheetsWriteScope')->once()->andReturnFalse();
        $google->shouldNotReceive('accessToken');

        $this->expectException(GoogleSheetsException::class);
        $this->expectExceptionMessage('Koneksi Google belum memiliki izin edit Google Sheets. Silakan Hubungkan Ulang Google.');

        (new GoogleSheetsWriter($google))->updateStock(
            StockSheetMap::MATERIAL_BMS,
            'BMS-M1',
            ['quantity' => 3],
            ['quantity' => 2],
            'Admin',
        );
    }

    private function writer(): GoogleSheetsWriter
    {
        $google = Mockery::mock(GoogleOAuthService::class);
        $google->shouldReceive('hasSheetsWriteScope')->once()->andReturnTrue();
        $google->shouldReceive('accessToken')->once()->andReturn('test-access-token');

        return new GoogleSheetsWriter($google);
    }

    /** @param array<int, string> $headers */
    private function fakeSheet(array $headers, array $rows): void
    {
        Http::fake(function (Request $request) use ($headers, $rows) {
            if ($request->method() === 'GET') {
                return Http::response(['range' => 'sheet', 'values' => [$headers, ...$rows]]);
            }

            return Http::response(['totalUpdatedCells' => count($request->data()['data'] ?? [])]);
        });
    }

    /** @return array<string, float|string> */
    private function writtenCells(): array
    {
        $recorded = Http::recorded(fn (Request $request): bool => $request->method() === 'POST')->first();
        self::assertNotNull($recorded, 'Google Sheets batch update request was not sent.');

        return collect($recorded[0]->data()['data'] ?? [])->mapWithKeys(fn (array $update): array => [
            $update['range'] => $update['values'][0][0],
        ])->all();
    }
}
