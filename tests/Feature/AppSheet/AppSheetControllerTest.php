<?php

namespace Tests\Feature\AppSheet;

use App\Models\User;
use App\Services\AppSheet\AppSheetProfileDirectoryService;
use App\Services\AppSheet\GoogleDriveMediaService;
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
            ->assertSee('Edit Stock')
            ->assertSee(route('admin.appsheet.stock.update', 'consumable-bms'), false)
            ->assertDontSee('Qty Konsinyasi')
            ->assertDontSee('Minimum')
            ->assertDontSee('9999');

        $stockRows = collect($response->viewData('rows')->items())->keyBy('UID');

        self::assertSame(['BMS-C14', 'EMPTY-01', 'INVALID-01', 'NEGATIVE-01'], $stockRows->keys()->all());
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
        ], transactionStockRows: [
            $this->stockRow('BMS-C32', [
                'DESC.' => 'BATU GERINDA POTONG 4 INCH',
                'SPARE STOCK' => 18,
                'STN' => 'EA',
            ]),
        ]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.appsheet.history-consumable.index'))
            ->assertOk()
            ->assertDontSee('_sort_timestamp')
            ->assertSee('Tambah Transaksi')
            ->assertSee('BATU GERINDA POTONG 4 INCH')
            ->assertSee(route('admin.appsheet.history-consumable.transactions.store'), false);
        $response->assertDontSee('Edit Stock');

        self::assertSame(
            ['LATEST-A', 'LATEST-B', 'SAME-DAY-EARLIER', 'DECEMBER', 'JANUARY', 'INVALID'],
            collect($response->viewData('rows')->items())->pluck('UID')->all(),
        );
    }

    public function test_stock_is_naturally_sorted_by_uid_before_pagination(): void
    {
        $rows = [];
        foreach (array_reverse(range(1, 53)) as $index) {
            $rows[] = $this->stockRow('BMS-C'.$index, ['INPUT DATE' => '01/01/2025']);
        }

        $this->mockStockRows($rows);
        $response = $this->actingAs($this->admin())
            ->get(route('admin.appsheet.stock-consumable.index'))
            ->assertOk()
            ->assertDontSee('_sort_timestamp');
        $paginator = $response->viewData('rows');

        self::assertSame(53, $paginator->total());
        self::assertSame('BMS-C1', $paginator->items()[0]['UID']);
        self::assertSame('BMS-C2', $paginator->items()[1]['UID']);
        self::assertSame(25, $paginator->perPage());
        self::assertSame('BMS-C25', collect($paginator->items())->last()['UID']);

        $this->mockStockRows($rows);
        $secondPage = $this->actingAs($this->admin())
            ->get(route('admin.appsheet.stock-consumable.index', ['page' => 2]))
            ->viewData('rows');

        self::assertSame(array_map(fn (int $index): string => 'BMS-C'.$index, range(26, 50)), collect($secondPage->items())->pluck('UID')->all());
    }

    public function test_history_renders_requester_profile_avatar_and_modern_badges(): void
    {
        $this->mockHistoryRows([
            $this->historyRow('WITH-AVATAR', '11/09/2026 16.05.14'),
        ], [
            'name' => 'Hadi Purnomo',
            'position' => 'Teknisi Senior',
            'regu' => 'A',
            'shift' => '1',
            'image_path' => 'Data_Images/Hadi.png',
            'initials' => 'HP',
        ], '/admin/appsheet/media/'.str_repeat('a', 64));

        $this->actingAs($this->admin())
            ->get(route('admin.appsheet.history-consumable.index'))
            ->assertOk()
            ->assertSee('Hadi Purnomo')
            ->assertSee('Teknisi Senior')
            ->assertSee('Regu A')
            ->assertSee('16:05:14')
            ->assertSee('loading="lazy"', false)
            ->assertSee('/admin/appsheet/media/'.str_repeat('a', 64), false);
    }

    public function test_stock_renders_exact_row_image_and_keeps_rows_without_images(): void
    {
        $rows = [
            $this->stockRow('WITH-IMAGE', [
                'IMG' => 'STOCK CONS BMS_Images/WITH-IMAGE.IMG.1.jpg',
                'SIZE' => '3,2 mm',
            ]),
            $this->stockRow('WITHOUT-IMAGE', ['IMG' => '']),
        ];
        $this->mockStockRows($rows, 1, '/admin/appsheet/media/'.str_repeat('b', 64));

        $response = $this->actingAs($this->admin())
            ->get(route('admin.appsheet.stock-consumable.index'))
            ->assertOk()
            ->assertSee('WITH-IMAGE')
            ->assertSee('WITHOUT-IMAGE')
            ->assertSee('Size 3,2 mm')
            ->assertSee('loading="lazy"', false);

        self::assertCount(2, $response->viewData('rows')->items());
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
    }

    private function mockHistoryRows(
        array $rows,
        ?array $resolvedProfile = null,
        ?string $avatarUrl = null,
        array $transactionStockRows = [],
    ): void {
        $google = Mockery::mock(GoogleOAuthService::class);
        $google->shouldReceive('isConnected')->once()->andReturnTrue();
        $reader = Mockery::mock(GoogleSheetsReader::class);
        $reader->shouldReceive('historyConsumable')->once()->andReturn($rows);
        $reader->shouldReceive('stockConsumable')->once()->andReturn($transactionStockRows);
        $profiles = Mockery::mock(AppSheetProfileDirectoryService::class);
        $profiles->shouldReceive('resolve')->andReturnUsing(function (mixed $name) use ($resolvedProfile): array {
            return $resolvedProfile ?? [
                'name' => trim((string) $name) ?: 'Tidak diketahui',
                'position' => '',
                'regu' => '',
                'shift' => '',
                'image_path' => '',
                'initials' => 'T',
            ];
        });
        $media = Mockery::mock(GoogleDriveMediaService::class);
        $media->shouldReceive('mediaUrl')->andReturn($avatarUrl);

        $this->app->instance(GoogleOAuthService::class, $google);
        $this->app->instance(GoogleSheetsReader::class, $reader);
        $this->app->instance(AppSheetProfileDirectoryService::class, $profiles);
        $this->app->instance(GoogleDriveMediaService::class, $media);
    }

    private function mockStockRows(array $rows, int $times = 1, ?string $imageUrl = null): void
    {
        $google = Mockery::mock(GoogleOAuthService::class);
        $google->shouldReceive('isConnected')->times($times)->andReturnTrue();
        $reader = Mockery::mock(GoogleSheetsReader::class);
        $reader->shouldReceive('stockConsumable')->times($times)->andReturn($rows);
        $media = Mockery::mock(GoogleDriveMediaService::class);
        $media->shouldReceive('mediaUrl')->andReturnUsing(
            fn (string $collection, mixed $path): ?string => trim((string) $path) !== '' ? $imageUrl : null,
        );

        $this->app->instance(GoogleOAuthService::class, $google);
        $this->app->instance(GoogleSheetsReader::class, $reader);
        $this->app->instance(GoogleDriveMediaService::class, $media);
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
            'IMG' => '',
            'TYPE CATEGORY' => 'DEFAULT TYPE',
            'DESC.' => 'Default description',
            'SIZE' => '',
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
