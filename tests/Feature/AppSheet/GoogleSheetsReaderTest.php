<?php

namespace Tests\Feature\AppSheet;

use App\Exceptions\AppSheet\GoogleOAuthException;
use App\Exceptions\AppSheet\GoogleSheetsException;
use App\Services\AppSheet\GoogleOAuthService;
use App\Services\AppSheet\GoogleSheetsReader;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class GoogleSheetsReaderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.google.spreadsheet_id' => 'test-spreadsheet',
            'services.google.history_consumable_sheet' => 'HISTORY CONS',
            'services.google.stock_consumable_sheet' => 'STOCK CONS BMS',
            'services.google.stock_consumable_gudang_sheet' => 'STOCK CONS GUDANG',
            'services.google.stock_material_bms_sheet' => 'STOCK MATERIAL BMS',
            'services.google.stock_material_gudang_sheet' => 'STOK MATERIAL GUDANG',
            'services.google.data_sheet' => 'Data',
        ]);
        $cache = Cache::store('array');
        Cache::shouldReceive('store')->with('file')->andReturn($cache);
        Http::preventStrayRequests();
    }

    public function test_header_reordering_short_rows_and_empty_rows_are_supported_without_photo_data(): void
    {
        Http::fake(['sheets.googleapis.com/*' => Http::response([
            'range' => "'HISTORY CONS'!A1:J5",
            'values' => [
                [' UID ', 'DESC.', 'PHOTO', 'INPUT BY', 'CATEGORY', 'INPUT TYPE', 'QTY', 'INPUT DATE', 'TUJUAN PENGGUNAAN', 'JENIS PERMINTAAN'],
                [' uid-1 ', ' Consumable ', 'photo.jpg', 'Admin', 'Tools', 'STOCK OUT', 0, 45000.5, 'Workshop', 'Normal'],
                ['uid-2'],
                [],
                ['', '   '],
            ],
        ])]);

        $rows = $this->reader()->historyConsumable();

        self::assertCount(2, $rows);
        self::assertSame(' uid-1 ', $rows[0]['UID']);
        self::assertSame(' Consumable ', $rows[0]['DESC.']);
        self::assertSame(0, $rows[0]['QTY']);
        self::assertSame('', $rows[1]['INPUT DATE']);
        self::assertArrayNotHasKey('PHOTO', $rows[0]);
        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && str_contains(rawurldecode($request->url()), "/values/'HISTORY CONS'")
            && $request->hasHeader('Authorization', 'Bearer test-access-token')
            && str_contains($request->url(), 'valueRenderOption=UNFORMATTED_VALUE'));
    }

    public function test_cache_is_separate_per_sheet_expires_after_thirty_seconds_and_contains_no_token(): void
    {
        Http::fake(['sheets.googleapis.com/*' => Http::response(['range' => 'sheet', 'values' => []])]);
        $reader = $this->reader();

        $reader->historyConsumable();
        $reader->historyConsumable();
        $reader->stockConsumable();
        Http::assertSentCount(2);
        self::assertNotNull(Cache::store('file')->get('appsheet:history-consumable'));
        self::assertNotNull(Cache::store('file')->get('appsheet:stock-consumable'));
        self::assertStringNotContainsString('test-access-token', json_encode(Cache::store('file')->get('appsheet:history-consumable')));

        $this->travel(31)->seconds();
        $reader->historyConsumable();
        Http::assertSentCount(3);
    }

    public function test_stock_reader_uses_current_reordered_headers_and_ignores_extra_columns(): void
    {
        Http::fake(['sheets.googleapis.com/*' => Http::response([
            'range' => "'STOCK CONS BMS'!A1:P2",
            'values' => [
                [
                    'IMG', 'SPARE STOCK', 'UID', 'LOC ID', 'CATEGORY', 'TYPE CATEGORY', 'QTY', 'DESC.',
                    'STOCK OUT', 'SIZE', 'SUB CATEGORY', 'STN', 'LOC', 'INPUT DATE', 'INPUT. BY', 'STOCK IN',
                ],
                [
                    'photo.jpg', 115, 'BMS-C14', 'LOC-01', 'CONSUMABLE', 'ELECTRODE', 999,
                    'Electrode 7018 dia. 3,2 mm', 3226, '3,2 mm', 'KONSUMABEL UMUM', 'KG',
                    'RUANG TOOLS', '10/04/2025', 'Hadi Purnomo', 3341,
                ],
            ],
        ])]);

        $row = $this->reader()->stockConsumable()[0];

        self::assertSame('BMS-C14', $row['UID']);
        self::assertSame('ELECTRODE', $row['TYPE CATEGORY']);
        self::assertSame('Electrode 7018 dia. 3,2 mm', $row['DESC.']);
        self::assertSame(3341, $row['STOCK IN']);
        self::assertSame(3226, $row['STOCK OUT']);
        self::assertSame(115, $row['SPARE STOCK']);
        self::assertSame('KONSUMABEL UMUM', $row['SUB CATEGORY']);
        self::assertSame('10/04/2025', $row['INPUT DATE']);
        self::assertSame('photo.jpg', $row['IMG']);
        self::assertArrayNotHasKey('LOC ID', $row);
        self::assertSame('3,2 mm', $row['SIZE']);
        self::assertArrayNotHasKey('QTY', $row);
    }

    public function test_new_stock_readers_map_reordered_headers_and_use_independent_caches(): void
    {
        $sheets = [
            'stockConsumableGudang' => ['STOCK CONS GUDANG', GoogleSheetsReader::STOCK_CONSUMABLE_GUDANG_HEADERS, 'appsheet:stock-consumable-gudang'],
            'stockMaterialBms' => ['STOCK MATERIAL BMS', GoogleSheetsReader::STOCK_MATERIAL_BMS_HEADERS, 'appsheet:stock-material-bms'],
            'stockMaterialGudang' => ['STOK MATERIAL GUDANG', GoogleSheetsReader::STOCK_MATERIAL_GUDANG_HEADERS, 'appsheet:stock-material-gudang'],
        ];
        Http::fake(function ($request) use ($sheets) {
            foreach ($sheets as [$sheet, $headers]) {
                if (str_contains(rawurldecode($request->url()), "/values/'".$sheet."'")) {
                    $reversed = array_reverse($headers);

                    return Http::response(['range' => $sheet, 'values' => [
                        [...$reversed, 'Duplikat'],
                        [...array_map(fn (string $header) => in_array($header, ['QTY', 'QTY KONSINYASI'], true) ? 2.345 : $header.' value', $reversed), 'ignored-helper'],
                    ]]);
                }
            }

            return Http::response([], 404);
        });

        $reader = $this->reader();
        foreach ($sheets as $method => [$sheet, $headers, $cacheKey]) {
            $rows = $reader->{$method}();
            self::assertSame($rows, $reader->{$method}());
            self::assertSame($headers, array_keys($rows[0]));
            self::assertSame(2.345, $rows[0][$method === 'stockConsumableGudang' ? 'QTY KONSINYASI' : 'QTY']);
            self::assertArrayNotHasKey('Duplikat', $rows[0]);
            self::assertNotNull(Cache::store('file')->get($cacheKey));
            self::assertStringNotContainsString('test-access-token', json_encode(Cache::store('file')->get($cacheKey)));
        }
        Http::assertSentCount(3);
        Http::assertSent(fn ($request) => str_contains($request->url(), 'valueRenderOption=UNFORMATTED_VALUE'));
    }

    public function test_material_gudang_accepts_full_location_header_and_preserves_the_existing_mapping(): void
    {
        Http::fake(['sheets.googleapis.com/*' => Http::response([
            'range' => "'STOK MATERIAL GUDANG'!A1:K2",
            'values' => [
                ['NO. MATERIAL', 'MATERIAL', 'MRP TYPE', 'DESKRIPSI', 'QTY CAPEX', 'QTY', 'STN', 'MATERIAL LOCATION', 'UPDATE BY', 'UPDATE DATE', 'Duplikat'],
                ['-', 'Plat', 'V1', 'Keterangan', 90, 2.5, 'EA', 'Gudang Utama', 'Petugas', '16/09/2026', 1],
            ],
        ])]);

        $row = $this->reader()->stockMaterialGudang()[0];

        self::assertSame('Gudang Utama', $row['MATERIAL LOC']);
        self::assertSame('Gudang Utama', \App\Support\AppSheet\StockData::materialGudang($row)['location']);
        self::assertSame('-', $row['NO. MATERIAL']);
        self::assertSame(2.5, $row['QTY']);
        self::assertArrayNotHasKey('MATERIAL LOCATION', $row);
        self::assertArrayNotHasKey('Duplikat', $row);
    }

    public function test_material_gudang_keeps_the_canonical_location_header_when_both_headers_exist(): void
    {
        Http::fake(['sheets.googleapis.com/*' => Http::response([
            'range' => "'STOK MATERIAL GUDANG'!A1:K2",
            'values' => [
                ['NO. MATERIAL', 'MATERIAL', 'MRP TYPE', 'DESKRIPSI', 'QTY CAPEX', 'QTY', 'STN', 'MATERIAL LOCATION', 'UPDATE BY', 'UPDATE DATE', 'MATERIAL LOC'],
                ['M1', 'Plat', '', '', 0, 1, 'EA', 'Lokasi Alias', 'Petugas', '16/09/2026', 'Lokasi Utama'],
            ],
        ])]);

        self::assertSame('Lokasi Utama', $this->reader()->stockMaterialGudang()[0]['MATERIAL LOC']);
    }

    public function test_requester_profiles_fetch_only_required_data_columns_without_password_values(): void
    {
        Http::fake([
            'sheets.googleapis.com/*' => Http::sequence()
                ->push([
                    'range' => "'Data'!1:1",
                    'values' => [[
                        'KATA SANDI', 'IMG', 'NAMA', 'SHIFT', 'JABATAN', 'REGU', 'FIELD LAIN',
                    ]],
                ])
                ->push([
                    'spreadsheetId' => 'test-spreadsheet',
                    'valueRanges' => [
                        ['range' => "'Data'!C2:C", 'values' => [[' Hadi Purnomo ']]],
                        ['range' => "'Data'!F2:F", 'values' => [['A']]],
                        ['range' => "'Data'!D2:D", 'values' => [['1']]],
                        ['range' => "'Data'!E2:E", 'values' => [['Teknisi']]],
                        ['range' => "'Data'!B2:B", 'values' => [['Data_Images/Hadi.png']]],
                    ],
                ]),
        ]);

        $rows = $this->reader()->requesterProfiles();

        self::assertSame([[
            'NAMA' => ' Hadi Purnomo ',
            'REGU' => 'A',
            'SHIFT' => '1',
            'JABATAN' => 'Teknisi',
            'IMG' => 'Data_Images/Hadi.png',
        ]], $rows);
        self::assertSame(GoogleSheetsReader::PROFILE_HEADERS, array_keys($rows[0]));
        Http::assertSent(function ($request): bool {
            $url = rawurldecode($request->url());

            return str_contains($url, '/values:batchGet?')
                && str_contains($url, "ranges='Data'!C2:C")
                && str_contains($url, "ranges='Data'!B2:B")
                && ! str_contains($url, "ranges='Data'!A2:A")
                && ! str_contains($url, 'KATA SANDI');
        });
    }

    public function test_api_token_failure_requests_reconnection_without_returning_response_body(): void
    {
        Http::fake(['sheets.googleapis.com/*' => Http::response(['error' => ['message' => 'sensitive-response-body']], 401)]);

        try {
            $this->reader()->historyConsumable();
            self::fail('Expected a safe connection error.');
        } catch (GoogleSheetsException $exception) {
            self::assertTrue($exception->requiresReconnect);
            self::assertSame('Koneksi Google perlu diperbarui.', $exception->getMessage());
            self::assertNull($exception->getPrevious());
        }
    }

    public function test_failed_existing_oauth_token_refresh_does_not_call_sheets(): void
    {
        Http::fake();
        $google = Mockery::mock(GoogleOAuthService::class);
        $google->shouldReceive('accessToken')->once()->andThrow(new GoogleOAuthException('Expired grant', true));

        try {
            (new GoogleSheetsReader($google))->historyConsumable();
            self::fail('Expected a safe connection error.');
        } catch (GoogleSheetsException $exception) {
            self::assertTrue($exception->requiresReconnect);
            self::assertSame('Koneksi Google perlu diperbarui.', $exception->getMessage());
        }
        Http::assertNothingSent();
    }

    public function test_missing_sheet_error_names_the_sheet_without_forwarding_api_details(): void
    {
        Http::fake(['sheets.googleapis.com/*' => Http::response(['error' => ['message' => 'sensitive-response-body']], 400)]);

        try {
            $this->reader()->stockConsumable();
            self::fail('Expected a safe sheet error.');
        } catch (GoogleSheetsException $exception) {
            self::assertFalse($exception->requiresReconnect);
            self::assertStringContainsString('STOCK CONS BMS', $exception->getMessage());
            self::assertStringNotContainsString('sensitive-response-body', $exception->getMessage());
        }
    }

    private function reader(): GoogleSheetsReader
    {
        $google = Mockery::mock(GoogleOAuthService::class);
        $google->shouldReceive('accessToken')->andReturn('test-access-token');

        return new GoogleSheetsReader($google);
    }
}
