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
            'services.google.stock_consumable_sheet' => 'STOCK CONS GUDANG',
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
            self::assertStringContainsString('STOCK CONS GUDANG', $exception->getMessage());
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
