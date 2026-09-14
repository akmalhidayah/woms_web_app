<?php

namespace Tests\Feature\AppSheet;

use App\Models\User;
use App\Services\AppSheet\GoogleOAuthService;
use App\Services\AppSheet\GoogleSheetsReader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AppSheetControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_only_contains_consumables_and_uses_current_schema_and_statuses(): void
    {
        $rows = [
            $this->stockRow('TOOLS-01', ['CATEGORY' => 'TOOLS', 'INPUT DATE' => '31/12/2026']),
            $this->stockRow('BMS-C14', [
                'CATEGORY' => ' consumable ',
                'TYPE CATEGORY' => 'ELECTRODE',
                'DESC.' => 'Electrode 7018 dia. 3,2 mm',
                'STOCK IN' => 3341,
                'STOCK OUT' => 3226,
                'SPARE STOCK' => 115,
                'QTY' => 9999,
                'STN' => 'KG',
                'SUB CATEGORY' => ' KONSUMABEL UMUM ',
                'LOC' => 'RUANG TOOLS',
                'INPUT. BY' => 'Hadi Purnomo',
                'INPUT DATE' => '10/04/2025',
            ]),
            $this->stockRow('EMPTY-01', ['SPARE STOCK' => 0, 'INPUT DATE' => '09/04/2025']),
            $this->stockRow('NEGATIVE-01', ['SPARE STOCK' => -2, 'INPUT DATE' => '08/04/2025']),
            $this->stockRow('INVALID-01', ['SPARE STOCK' => '#VALUE!', 'INPUT DATE' => '07/04/2025']),
        ];
        $this->mockStockRows($rows);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.appsheet.stock-consumable.index'))
            ->assertOk()
            ->assertSee('Stok Saat Ini')
            ->assertSee('Sub Category')
            ->assertSee('Tersedia')
            ->assertSee('Habis')
            ->assertDontSee('Qty Konsinyasi')
            ->assertDontSee('Minimum')
            ->assertDontSee('9999');

        $stockRows = collect($response->viewData('rows')->items())->keyBy('UID');

        self::assertSame(['BMS-C14', 'EMPTY-01', 'NEGATIVE-01', 'INVALID-01'], $stockRows->keys()->all());
        self::assertSame(4, $response->viewData('totalRows'));
        self::assertSame(115, $stockRows['BMS-C14']['SPARE STOCK']);
        self::assertSame('tersedia', $stockRows['BMS-C14']['_stock_status']);
        self::assertSame('habis', $stockRows['EMPTY-01']['_stock_status']);
        self::assertSame('habis', $stockRows['NEGATIVE-01']['_stock_status']);
        self::assertNull($stockRows['INVALID-01']['_stock_status']);
        self::assertSame(['KONSUMABEL UMUM'], $response->viewData('subCategories')->all());
    }

    public function test_stock_sub_category_and_status_filters_are_applied(): void
    {
        $this->mockStockRows([
            $this->stockRow('GENERAL-AVAILABLE', [
                'SUB CATEGORY' => ' KONSUMABEL UMUM ',
                'SPARE STOCK' => 10,
            ]),
            $this->stockRow('GENERAL-EMPTY', [
                'SUB CATEGORY' => 'KONSUMABEL UMUM',
                'SPARE STOCK' => 0,
            ]),
            $this->stockRow('SPECIAL-AVAILABLE', [
                'SUB CATEGORY' => 'KONSUMABEL KHUSUS',
                'SPARE STOCK' => 5,
            ]),
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.appsheet.stock-consumable.index', [
            'jenis' => 'KONSUMABEL UMUM',
            'status' => 'tersedia',
        ]));

        self::assertSame(
            ['GENERAL-AVAILABLE'],
            collect($response->viewData('rows')->items())->pluck('UID')->all(),
        );
    }

    public function test_stock_search_covers_current_schema_case_insensitively(): void
    {
        $rows = [
            $this->stockRow('BMS-C14', [
                'TYPE CATEGORY' => 'ELECTRODE',
                'DESC.' => 'Electrode 7018 dia. 3,2 mm',
                'SUB CATEGORY' => 'KONSUMABEL KHUSUS',
                'LOC' => 'RUANG TOOLS',
                'STN' => 'KG',
            ]),
            $this->stockRow('OTHER-01'),
        ];
        $searches = ['bms-c14', 'electrode', '7018', 'khusus', 'ruang tools', 'kg'];
        $this->mockStockRows($rows, count($searches));

        foreach ($searches as $search) {
            $response = $this->actingAs($this->admin())->get(route('admin.appsheet.stock-consumable.index', [
                'search' => $search,
            ]));

            self::assertSame(
                ['BMS-C14'],
                collect($response->viewData('rows')->items())->pluck('UID')->all(),
                'Search gagal untuk '.$search,
            );
        }
    }

    public function test_history_is_stably_sorted_by_parsed_date_and_time_with_invalid_dates_last(): void
    {
        $this->mockHistoryRows([
            $this->historyRow('INVALID', 'invalid-date'),
            $this->historyRow('JANUARY', '31/01/2026'),
            $this->historyRow('DECEMBER', '01/12/2026'),
            $this->historyRow('LATEST-A', '31/12/2026 15:00'),
            $this->historyRow('LATEST-B', '31/12/2026 15:00'),
            $this->historyRow('SAME-DAY-EARLIER', '31/12/2026 10:00'),
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.appsheet.history-consumable.index'))
            ->assertOk()
            ->assertDontSee('_sort_timestamp');

        self::assertSame(
            ['LATEST-A', 'LATEST-B', 'SAME-DAY-EARLIER', 'DECEMBER', 'JANUARY', 'INVALID'],
            collect($response->viewData('rows')->items())->pluck('UID')->all(),
        );
    }

    public function test_stock_is_sorted_before_pagination_with_invalid_dates_last(): void
    {
        $rows = [
            $this->stockRow('INVALID', ['INPUT DATE' => 'not-a-date']),
            $this->stockRow('JANUARY', ['INPUT DATE' => '31/01/2026']),
            $this->stockRow('DECEMBER', ['INPUT DATE' => '01/12/2026']),
            $this->stockRow('LATEST', ['INPUT DATE' => '31/12/2026 15:00']),
            $this->stockRow('SAME-DAY-EARLIER', ['INPUT DATE' => '31/12/2026 10:00']),
        ];

        foreach (range(1, 48) as $index) {
            $rows[] = $this->stockRow('OLD-'.$index, ['INPUT DATE' => '01/01/2025']);
        }

        $this->mockStockRows($rows);
        $response = $this->actingAs($this->admin())
            ->get(route('admin.appsheet.stock-consumable.index'))
            ->assertOk()
            ->assertDontSee('_sort_timestamp');
        $paginator = $response->viewData('rows');

        self::assertSame(53, $paginator->total());
        self::assertSame('LATEST', $paginator->items()[0]['UID']);
        self::assertSame('SAME-DAY-EARLIER', $paginator->items()[1]['UID']);

        $this->mockStockRows($rows);
        $lastPage = $this->actingAs($this->admin())
            ->get(route('admin.appsheet.stock-consumable.index', ['page' => 2]))
            ->viewData('rows');

        self::assertSame('INVALID', collect($lastPage->items())->last()['UID']);
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
    }

    private function mockHistoryRows(array $rows): void
    {
        $google = Mockery::mock(GoogleOAuthService::class);
        $google->shouldReceive('isConnected')->once()->andReturnTrue();
        $reader = Mockery::mock(GoogleSheetsReader::class);
        $reader->shouldReceive('historyConsumable')->once()->andReturn($rows);

        $this->app->instance(GoogleOAuthService::class, $google);
        $this->app->instance(GoogleSheetsReader::class, $reader);
    }

    private function mockStockRows(array $rows, int $times = 1): void
    {
        $google = Mockery::mock(GoogleOAuthService::class);
        $google->shouldReceive('isConnected')->times($times)->andReturnTrue();
        $reader = Mockery::mock(GoogleSheetsReader::class);
        $reader->shouldReceive('stockConsumable')->times($times)->andReturn($rows);

        $this->app->instance(GoogleOAuthService::class, $google);
        $this->app->instance(GoogleSheetsReader::class, $reader);
    }

    private function historyRow(string $uid, mixed $date): array
    {
        return [
            'INPUT DATE' => $date,
            'UID' => $uid,
            'DESC.' => 'Consumable '.$uid,
            'CATEGORY' => 'CONSUMABLE',
            'INPUT TYPE' => 'STOCK IN',
            'QTY' => 1,
            'TUJUAN PENGGUNAAN' => 'Workshop',
            'JENIS PERMINTAAN' => 'Normal',
            'INPUT BY' => 'Tester',
        ];
    }

    private function stockRow(string $uid, array $overrides = []): array
    {
        return [
            'UID' => $uid,
            'TYPE CATEGORY' => 'DEFAULT TYPE',
            'DESC.' => 'Default description',
            'STOCK IN' => 10,
            'STOCK OUT' => 5,
            'SPARE STOCK' => 5,
            'STN' => 'PCS',
            'CATEGORY' => 'CONSUMABLE',
            'SUB CATEGORY' => 'KONSUMABEL UMUM',
            'LOC' => 'DEFAULT LOCATION',
            'INPUT. BY' => 'Tester',
            'INPUT DATE' => '01/01/2025',
            ...$overrides,
        ];
    }
}
