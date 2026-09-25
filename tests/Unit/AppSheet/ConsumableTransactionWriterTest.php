<?php

namespace Tests\Unit\AppSheet;

use App\Exceptions\AppSheet\GoogleSheetsException;
use App\Services\AppSheet\GoogleOAuthService;
use App\Services\AppSheet\GoogleSheetsWriter;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class ConsumableTransactionWriterTest extends TestCase
{
    private const STOCK_HEADERS = [
        'UID', 'DESC.', 'CATEGORY', 'STOCK IN', 'STOCK OUT', 'SPARE STOCK', 'STN', 'INPUT. BY', 'INPUT DATE',
    ];

    private const HISTORY_HEADERS = [
        'UID', 'INPUT DATE', 'DESC.', 'CATEGORY', 'INPUT TYPE', 'QTY',
        'TUJUAN PENGGUNAAN', 'JENIS PERMINTAAN', 'INPUT BY',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.spreadsheet_id' => 'test-spreadsheet',
            'services.google.stock_consumable_sheet' => 'STOCK CONS BMS',
            'services.google.history_consumable_sheet' => 'HISTORY CONS',
        ]);
        $cache = Cache::store('array');
        Cache::shouldReceive('store')->with('file')->andReturn($cache);
        Http::preventStrayRequests();
        Carbon::setTestNow('2026-09-25 10:15:30');
    }

    public function test_stock_in_updates_latest_stock_and_appends_history_in_one_atomic_batch(): void
    {
        $this->fakeTransactionSheets($this->stockRows(100, 30, 70));
        Cache::store('file')->put('appsheet:history-consumable', ['cached' => true], 300);
        Cache::store('file')->put('appsheet:stock-consumable', ['cached' => true], 300);
        Cache::store('file')->put('appsheet:stock-material-bms', ['keep' => true], 300);

        $result = $this->writer()->createConsumableTransaction(
            'BMS-C32',
            'STOCK IN',
            5,
            'fake browser purpose',
            'fake browser type',
            'Admin Laravel',
            '11111111-1111-4111-8111-111111111111',
        );

        self::assertSame(75.0, $result['stock_after']);
        $payload = $this->atomicBatchPayload();
        self::assertSame([
            3 => 105.0,
            5 => 75.0,
            7 => 'Admin Laravel',
            8 => '2026-09-25 10:15:30',
        ], $this->stockUpdates($payload));
        self::assertSame([
            'UID' => 'BMS-C32',
            'INPUT DATE' => '2026-09-25 10:15:30',
            'DESC.' => 'BATU GERINDA POTONG 4 INCH',
            'CATEGORY' => 'CONSUMABLE',
            'INPUT TYPE' => 'STOCK IN',
            'QTY' => 5.0,
            'TUJUAN PENGGUNAAN' => '-',
            'JENIS PERMINTAAN' => '-',
            'INPUT BY' => 'Admin Laravel',
        ], $this->appendedHistory($payload));
        self::assertCount(1, Http::recorded(fn (Request $request): bool => str_contains($request->url(), ':batchUpdate')));
        self::assertNull(Cache::store('file')->get('appsheet:history-consumable'));
        self::assertNull(Cache::store('file')->get('appsheet:stock-consumable'));
        self::assertSame(['keep' => true], Cache::store('file')->get('appsheet:stock-material-bms'));
    }

    public function test_stock_out_uses_latest_google_stock_and_never_uses_browser_stock(): void
    {
        $this->fakeTransactionSheets($this->stockRows(100, 30, 15));

        $result = $this->writer()->createConsumableTransaction(
            'BMS-C32',
            'STOCK OUT',
            2,
            'Bengkel Medin',
            'PERMINTAAN BARU',
            'Admin Laravel',
            '22222222-2222-4222-8222-222222222222',
        );

        self::assertSame(13.0, $result['stock_after']);
        $payload = $this->atomicBatchPayload();
        self::assertSame([
            4 => 32.0,
            5 => 13.0,
            7 => 'Admin Laravel',
            8 => '2026-09-25 10:15:30',
        ], $this->stockUpdates($payload));
        $history = $this->appendedHistory($payload);
        self::assertSame('STOCK OUT', $history['INPUT TYPE']);
        self::assertSame(2.0, $history['QTY']);
        self::assertSame('Bengkel Medin', $history['TUJUAN PENGGUNAAN']);
        self::assertSame('PERMINTAAN BARU', $history['JENIS PERMINTAAN']);
    }

    public function test_insufficient_stock_rejects_without_history_or_stock_write_or_cache_invalidation(): void
    {
        $this->fakeTransactionSheets($this->stockRows(100, 97, 3));
        Cache::store('file')->put('appsheet:history-consumable', ['cached' => true], 300);
        Cache::store('file')->put('appsheet:stock-consumable', ['cached' => true], 300);

        try {
            $this->writer()->createConsumableTransaction(
                'BMS-C32', 'STOCK OUT', 5, 'Workshop', 'PERMINTAAN BARU', 'Admin',
                '33333333-3333-4333-8333-333333333333',
            );
            self::fail('Expected insufficient stock failure.');
        } catch (GoogleSheetsException $exception) {
            self::assertSame(
                'Stok tidak mencukupi. Stok tersedia 3 EA, sedangkan jumlah yang diminta 5 EA.',
                $exception->getMessage(),
            );
        }

        $this->assertNoBatchWrite();
        self::assertSame(['cached' => true], Cache::store('file')->get('appsheet:history-consumable'));
        self::assertSame(['cached' => true], Cache::store('file')->get('appsheet:stock-consumable'));
    }

    public function test_unknown_uid_is_rejected_without_write(): void
    {
        $this->fakeTransactionSheets($this->stockRows(100, 30, 70, 'OTHER'));

        try {
            $this->writer()->createConsumableTransaction(
                'BMS-C32', 'STOCK IN', 1, null, null, 'Admin',
                '44444444-4444-4444-8444-444444444444',
            );
            self::fail('Expected missing identifier failure.');
        } catch (GoogleSheetsException $exception) {
            self::assertStringContainsString('tidak ditemukan', $exception->getMessage());
        }

        $this->assertNoBatchWrite();
    }

    public function test_duplicate_uid_is_rejected_without_write(): void
    {
        $this->fakeTransactionSheets([
            ...$this->stockRows(100, 30, 70),
            ...$this->stockRows(10, 2, 8),
        ]);

        try {
            $this->writer()->createConsumableTransaction(
                'BMS-C32', 'STOCK IN', 1, null, null, 'Admin',
                '55555555-5555-4555-8555-555555555555',
            );
            self::fail('Expected duplicate identifier failure.');
        } catch (GoogleSheetsException $exception) {
            self::assertStringContainsString('tidak unik', $exception->getMessage());
        }

        $this->assertNoBatchWrite();
    }

    public function test_non_consumable_uid_is_rejected_without_write(): void
    {
        $rows = $this->stockRows(100, 30, 70);
        $rows[0][2] = 'TOOLS';
        $this->fakeTransactionSheets($rows);

        try {
            $this->writer()->createConsumableTransaction(
                'BMS-C32', 'STOCK IN', 1, null, null, 'Admin',
                'cccccccc-cccc-4ccc-8ccc-cccccccccccc',
            );
            self::fail('Expected non-consumable rejection.');
        } catch (GoogleSheetsException $exception) {
            self::assertStringContainsString('Stock Consumable BMS', $exception->getMessage());
        }

        $this->assertNoBatchWrite();
    }

    public function test_stock_out_request_type_must_exist_in_latest_history(): void
    {
        $this->fakeTransactionSheets($this->stockRows(100, 30, 70));

        try {
            $this->writer()->createConsumableTransaction(
                'BMS-C32', 'STOCK OUT', 1, 'Workshop', 'TIPE PALSU', 'Admin',
                'dddddddd-dddd-4ddd-8ddd-dddddddddddd',
            );
            self::fail('Expected request type rejection.');
        } catch (GoogleSheetsException $exception) {
            self::assertStringContainsString('Jenis permintaan tidak tersedia', $exception->getMessage());
        }

        $this->assertNoBatchWrite();
    }

    public function test_formula_stock_cells_are_never_overwritten(): void
    {
        $rows = $this->stockRows(100, 30, 70);
        $formulaRows = $rows;
        $formulaRows[0][5] = '=D2-E2';
        $this->fakeTransactionSheets($rows, $formulaRows);

        $this->expectException(GoogleSheetsException::class);
        $this->expectExceptionMessage('Kolom SPARE STOCK menggunakan formula');

        try {
            $this->writer()->createConsumableTransaction(
                'BMS-C32', 'STOCK IN', 5, null, null, 'Admin',
                '66666666-6666-4666-8666-666666666666',
            );
        } finally {
            $this->assertNoBatchWrite();
        }
    }

    public function test_duplicate_submit_with_same_transaction_token_is_idempotent(): void
    {
        $this->fakeTransactionSheets($this->stockRows(100, 30, 70));
        $writer = $this->writer(2);
        $arguments = [
            'BMS-C32', 'STOCK IN', 5, null, null, 'Admin',
            '77777777-7777-4777-8777-777777777777',
        ];

        $first = $writer->createConsumableTransaction(...$arguments);
        $second = $writer->createConsumableTransaction(...$arguments);

        self::assertSame($first, $second);
        self::assertCount(1, Http::recorded(fn (Request $request): bool => str_contains($request->url(), ':batchUpdate')));
    }

    public function test_write_500_failure_is_safe_and_does_not_invalidate_cache(): void
    {
        $this->assertWriteFailureIsSafe(500, false, 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa');
    }

    public function test_write_429_failure_is_safe_and_does_not_invalidate_cache(): void
    {
        $this->assertWriteFailureIsSafe(429, false, '99999999-9999-4999-8999-999999999999');
    }

    public function test_write_403_failure_requests_reconnect_and_does_not_invalidate_cache(): void
    {
        $this->assertWriteFailureIsSafe(403, true, '88888888-8888-4888-8888-888888888888');
    }

    private function assertWriteFailureIsSafe(int $status, bool $requiresReconnect, string $transactionToken): void
    {
        $this->fakeTransactionSheets($this->stockRows(100, 30, 70), writeStatus: $status);
        Cache::store('file')->put('appsheet:history-consumable', ['cached' => true], 300);
        Cache::store('file')->put('appsheet:stock-consumable', ['cached' => true], 300);

        try {
            $this->writer()->createConsumableTransaction(
                'BMS-C32', 'STOCK IN', 1, null, null, 'Admin', $transactionToken,
            );
            self::fail('Expected safe API failure.');
        } catch (GoogleSheetsException $exception) {
            self::assertStringNotContainsString('sensitive-api-body', $exception->getMessage());
            self::assertSame($requiresReconnect, $exception->requiresReconnect);
        }

        self::assertSame(['cached' => true], Cache::store('file')->get('appsheet:history-consumable'));
        self::assertSame(['cached' => true], Cache::store('file')->get('appsheet:stock-consumable'));
    }

    public function test_legacy_readonly_oauth_scope_requires_reconnect_before_transaction(): void
    {
        $google = Mockery::mock(GoogleOAuthService::class);
        $google->shouldReceive('hasSheetsWriteScope')->once()->andReturnFalse();
        $google->shouldNotReceive('accessToken');

        $this->expectException(GoogleSheetsException::class);
        $this->expectExceptionMessage('Koneksi Google belum memiliki izin edit Google Sheets. Silakan Hubungkan Ulang Google.');

        (new GoogleSheetsWriter($google))->createConsumableTransaction(
            'BMS-C32', 'STOCK IN', 1, null, null, 'Admin',
            'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb',
        );
    }

    /** @return array<int, array<int, float|int|string>> */
    private function stockRows(float $stockIn, float $stockOut, float $spareStock, string $uid = 'BMS-C32'): array
    {
        return [[
            $uid, 'BATU GERINDA POTONG 4 INCH', 'CONSUMABLE', $stockIn, $stockOut,
            $spareStock, 'EA', 'Petugas Lama', '2026-09-01 08:00:00',
        ]];
    }

    private function writer(int $times = 1): GoogleSheetsWriter
    {
        $google = Mockery::mock(GoogleOAuthService::class);
        $google->shouldReceive('hasSheetsWriteScope')->times($times)->andReturnTrue();
        $google->shouldReceive('accessToken')->times($times)->andReturn('test-access-token');

        return new GoogleSheetsWriter($google);
    }

    private function fakeTransactionSheets(array $stockRows, ?array $formulaRows = null, int $writeStatus = 200): void
    {
        $formulaRows ??= $stockRows;
        Http::fake(function (Request $request) use ($stockRows, $formulaRows, $writeStatus) {
            $url = rawurldecode($request->url());
            if ($request->method() === 'POST' && str_contains($url, ':batchUpdate')) {
                return Http::response(
                    $writeStatus === 200 ? ['replies' => []] : ['error' => ['message' => 'sensitive-api-body test-access-token']],
                    $writeStatus,
                );
            }
            if (str_contains($url, "/values/'STOCK CONS BMS'")) {
                $rows = str_contains($request->url(), 'valueRenderOption=FORMULA') ? $formulaRows : $stockRows;

                return Http::response(['range' => 'stock', 'values' => [self::STOCK_HEADERS, ...$rows]]);
            }
            if (str_contains($url, "/values/'HISTORY CONS'")) {
                return Http::response(['range' => 'history', 'values' => [
                    self::HISTORY_HEADERS,
                    ['OLD', '2026-09-01', 'Lama', 'CONSUMABLE', 'STOCK OUT', 1, 'Workshop', 'PERMINTAAN BARU', 'Petugas'],
                ]]);
            }
            if ($request->method() === 'GET' && str_contains($url, '/spreadsheets/test-spreadsheet')) {
                return Http::response(['sheets' => [
                    ['properties' => ['sheetId' => 101, 'title' => 'STOCK CONS BMS']],
                    ['properties' => ['sheetId' => 202, 'title' => 'HISTORY CONS']],
                ]]);
            }

            return Http::response([], 404);
        });
    }

    /** @return array<string, mixed> */
    private function atomicBatchPayload(): array
    {
        $recorded = Http::recorded(fn (Request $request): bool => str_contains($request->url(), ':batchUpdate'))->first();
        self::assertNotNull($recorded, 'Atomic Google Sheets batch request was not sent.');

        return $recorded[0]->data();
    }

    /** @return array<int, float|string> */
    private function stockUpdates(array $payload): array
    {
        return collect($payload['requests'])->pluck('updateCells')->filter()->mapWithKeys(function (array $update): array {
            $value = $update['rows'][0]['values'][0]['userEnteredValue'];

            return [$update['range']['startColumnIndex'] => $value['numberValue'] ?? $value['stringValue']];
        })->sortKeys()->all();
    }

    /** @return array<string, float|string> */
    private function appendedHistory(array $payload): array
    {
        $append = collect($payload['requests'])->pluck('appendCells')->filter()->first();
        self::assertNotNull($append);
        $cells = $append['rows'][0]['values'];

        return collect(self::HISTORY_HEADERS)->mapWithKeys(function (string $header, int $index) use ($cells): array {
            $value = $cells[$index]['userEnteredValue'];

            return [$header => $value['numberValue'] ?? $value['stringValue']];
        })->all();
    }

    private function assertNoBatchWrite(): void
    {
        Http::assertNotSent(fn (Request $request): bool => str_contains($request->url(), ':batchUpdate'));
    }
}
