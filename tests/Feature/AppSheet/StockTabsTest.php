<?php

namespace Tests\Feature\AppSheet;

use App\Exceptions\AppSheet\GoogleSheetsException;
use App\Models\AdminRoleMenuAccess;
use App\Models\User;
use App\Services\AppSheet\GoogleOAuthService;
use App\Services\AppSheet\GoogleSheetsReader;
use App\Support\AdminMenuRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class StockTabsTest extends TestCase
{
    use RefreshDatabase;

    public function test_consumable_gudang_reads_only_its_sheet_and_filters_without_adding_a_stock_status(): void
    {
        $this->mockRows('stockConsumableGudang', [
            ['NO MATERIAL' => 'M19', 'CONSUMABLE' => 'Carbon Gouging', 'JENIS CONSUMABLE' => 'Kawat Las', 'QTY KONSINYASI' => 1.25, 'QTY NON KONSINYASI' => 12000, 'MIN' => 99999, 'UPD. BY' => 'Hadi', 'UPD. DATE' => '01/09/2026'],
            ['NO MATERIAL' => 'M2', 'CONSUMABLE' => 'Kawat', 'JENIS CONSUMABLE' => 'Kawat Las', 'UPD. BY' => 'Hadi', 'UPD. DATE' => '02/09/2026'],
            ['NO MATERIAL' => 'M1', 'CONSUMABLE' => 'Gerinda', 'JENIS CONSUMABLE' => 'Batu Gerinda', 'UPD. BY' => 'Tester', 'UPD. DATE' => '03/09/2026'],
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.appsheet.stock-consumable-gudang.index', [
            'search' => 'hadi', 'jenis' => 'Kawat Las',
        ]))->assertOk()
            ->assertSee('Stock Consumable BMS')->assertSee('Stock Consumable Gudang')
            ->assertSee('Stock Material BMS')->assertSee('Stock Material Gudang')
            ->assertSee('HISTORY CONSUMABLE')->assertDontSee('STOCK BMS')
            ->assertDontSee('appsheet-submenus')->assertDontSee('name="status"', false)
            ->assertSee('1,25')->assertSee('12.000')->assertSee('Non Konsinyasi')
            ->assertSee('Edit Stock')
            ->assertSee(route('admin.appsheet.stock.update', 'consumable-gudang'), false);

        self::assertSame(['M2', 'M19'], collect($response->viewData('rows')->items())->pluck('code')->all());
        self::assertArrayNotHasKey('status', $response->viewData('rows')->items()[1]);
        $menus = AdminMenuRegistry::sidebarForUser(auth()->user())['appsheet']['children'];
        self::assertFalse($menus[0]['active']);
        self::assertTrue($menus[1]['active']);
        self::assertSame(route('admin.appsheet.stock-consumable.index'), $menus[1]['href']);
    }

    public function test_material_bms_filters_and_sorts_before_pagination_and_preserves_query(): void
    {
        $rows = [];
        foreach (array_reverse(range(1, 30)) as $index) {
            $rows[] = ['UID' => 'BMS-M'.$index, 'JENIS MATERIAL' => 'Plat', 'QTY' => 0.25, 'LOC' => 'Barat', 'UPD. BY' => 'Ali'];
        }
        $rows[] = ['UID' => 'EMPTY', 'JENIS MATERIAL' => 'Plat', 'QTY' => 0, 'LOC' => 'Barat'];
        $rows[] = ['UID' => 'OTHER', 'JENIS MATERIAL' => 'Plat', 'QTY' => 2, 'LOC' => 'Timur'];
        $this->mockRows('stockMaterialBms', $rows);

        $response = $this->actingAs($this->admin())->get(route('admin.appsheet.stock-material-bms.index', [
            'search' => 'plat', 'location' => 'Barat', 'status' => 'tersedia', 'page' => 2,
        ]))->assertOk()->assertSee('0,25')->assertSee('Tersedia')
            ->assertSee('Edit Stock')
            ->assertSee(route('admin.appsheet.stock.update', 'material-bms'), false);
        $paginator = $response->viewData('rows');

        self::assertSame(30, $paginator->total());
        self::assertSame(25, $paginator->perPage());
        self::assertSame(['BMS-M26', 'BMS-M27', 'BMS-M28', 'BMS-M29', 'BMS-M30'], collect($paginator->items())->pluck('code')->all());
        parse_str(parse_url($paginator->url(1), PHP_URL_QUERY), $query);
        self::assertSame('plat', $query['search']);
        self::assertSame('Barat', $query['location']);
        self::assertSame('tersedia', $query['status']);
    }

    public function test_material_gudang_keeps_missing_codes_and_capex_does_not_change_status(): void
    {
        $this->mockRows('stockMaterialGudang', [
            ['NO. MATERIAL' => '-', 'MATERIAL' => 'Plat tanpa kode', 'MRP TYPE' => 'V1', 'DESKRIPSI' => 'SUDAH DI ISSUED', 'QTY CAPEX' => 90, 'QTY' => 0, 'MATERIAL LOC' => 'Gudang'],
            ['NO. MATERIAL' => '', 'MATERIAL' => 'Plat kode kosong', 'MRP TYPE' => 'V1', 'QTY' => -1, 'MATERIAL LOC' => 'Gudang'],
            ['NO. MATERIAL' => 'M2', 'MATERIAL' => 'Plat tersedia', 'MRP TYPE' => 'V1', 'QTY CAPEX' => 0, 'QTY' => 0.5, 'MATERIAL LOC' => 'Gudang'],
            ['QTY' => 0, 'Duplikat' => 1],
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.appsheet.stock-material-gudang.index', [
            'mrp_type' => 'V1', 'location' => 'Gudang', 'status' => 'habis',
        ]))->assertOk()->assertSee('Plat tanpa kode')->assertSee('Plat kode kosong')
            ->assertSee('SUDAH DI ISSUED')->assertSee('Qty Capex')->assertSee('90')
            ->assertSee('Edit Stock')
            ->assertSee(route('admin.appsheet.stock.update', 'material-gudang'), false)
            ->assertDontSee('Plat tersedia')->assertDontSee('Duplikat');

        self::assertSame(3, $response->viewData('totalRows'));
        self::assertSame(2, $response->viewData('rows')->total());
        self::assertSame(['habis'], collect($response->viewData('rows')->items())->pluck('status')->unique()->values()->all());
    }

    public function test_stock_tabs_keep_existing_admin_menu_authorization(): void
    {
        $routes = ['stock-consumable', 'stock-consumable-gudang', 'stock-material-bms', 'stock-material-gudang'];
        foreach ($routes as $route) {
            $this->get(route('admin.appsheet.'.$route.'.index'))->assertRedirect(route('login'));
        }
        foreach ([User::ROLE_USER, User::ROLE_APPROVER, User::ROLE_PKM, User::ROLE_ADMIN] as $role) {
            $user = User::factory()->create(['role' => $role, 'admin_role' => $role === User::ROLE_ADMIN ? User::ADMIN_ROLE_ADMIN : null]);
            foreach ($routes as $route) {
                $this->actingAs($user)->get(route('admin.appsheet.'.$route.'.index'))->assertForbidden();
            }
        }

        AdminRoleMenuAccess::query()->create(['admin_role' => User::ADMIN_ROLE_ADMIN, 'menu_key' => AdminMenuRegistry::MENU_APPSHEET]);
        $this->mockRows('stockMaterialBms', []);
        $this->actingAs($user)->get(route('admin.appsheet.stock-material-bms.index'))->assertOk();
    }

    public function test_disconnected_stock_does_not_read_sheets_and_read_errors_keep_page_available(): void
    {
        $google = Mockery::mock(GoogleOAuthService::class);
        $google->shouldReceive('isConnected')->once()->andReturnFalse();
        $this->app->instance(GoogleOAuthService::class, $google);
        $this->app->instance(GoogleSheetsReader::class, Mockery::mock(GoogleSheetsReader::class));
        $this->actingAs($this->admin())->get(route('admin.appsheet.stock-material-bms.index'))
            ->assertOk()->assertSee('Hubungkan Google');

        $google = Mockery::mock(GoogleOAuthService::class);
        $google->shouldReceive('isConnected')->once()->andReturnTrue();
        $reader = Mockery::mock(GoogleSheetsReader::class);
        $reader->shouldReceive('stockMaterialGudang')->once()->andThrow(new GoogleSheetsException('Data Google Sheets sementara belum dapat dimuat.'));
        $this->app->instance(GoogleOAuthService::class, $google);
        $this->app->instance(GoogleSheetsReader::class, $reader);
        $this->get(route('admin.appsheet.stock-material-gudang.index'))
            ->assertOk()->assertSee('Data Google Sheets sementara belum dapat dimuat.');
    }

    private function mockRows(string $method, array $rows): void
    {
        $google = Mockery::mock(GoogleOAuthService::class);
        $google->shouldReceive('isConnected')->once()->andReturnTrue();
        $reader = Mockery::mock(GoogleSheetsReader::class);
        $reader->shouldReceive($method)->once()->andReturn($rows);
        // Mock hanya mengizinkan reader tab aktif, sehingga eager-load tab lain akan gagal.
        $this->app->instance(GoogleOAuthService::class, $google);
        $this->app->instance(GoogleSheetsReader::class, $reader);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => User::ROLE_ADMIN, 'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN]);
    }
}
