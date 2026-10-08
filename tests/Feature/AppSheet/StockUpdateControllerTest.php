<?php

namespace Tests\Feature\AppSheet;

use App\Exceptions\AppSheet\GoogleSheetsException;
use App\Models\User;
use App\Services\AppSheet\GoogleSheetsWriter;
use App\Support\AppSheet\StockSheetMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class StockUpdateControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_uses_authenticated_user_and_redirects_back_with_filters(): void
    {
        $admin = $this->admin(['name' => 'Admin Stock']);
        $writer = Mockery::mock(GoogleSheetsWriter::class);
        $writer->shouldReceive('updateStock')->once()->with(
            StockSheetMap::CONSUMABLE_BMS,
            'BMS-C03',
            ['spare_stock' => '900.5'],
            ['spare_stock' => '896.25'],
            'Admin Stock',
        )->andReturn([
            'changed' => true,
            'item_name' => 'KAPUR BESI',
            'changes' => ['spare_stock' => ['old' => 896.25, 'new' => 900.5]],
        ]);
        $this->app->instance(GoogleSheetsWriter::class, $writer);
        $returnUrl = route('admin.appsheet.stock-consumable.index', ['search' => 'kapur', 'page' => 2]);

        $this->actingAs($admin)
            ->from($returnUrl)
            ->patch(route('admin.appsheet.stock.update', StockSheetMap::CONSUMABLE_BMS), [
                'identifier' => 'BMS-C03',
                'spare_stock' => '900.5',
                'original_spare_stock' => '896.25',
            ])
            ->assertRedirect($returnUrl)
            ->assertSessionHas('appsheet_stock_success', fn (string $message): bool => str_contains($message, 'KAPUR BESI'));
    }

    public function test_invalid_blank_nan_infinity_and_text_quantities_are_rejected(): void
    {
        $writer = Mockery::mock(GoogleSheetsWriter::class);
        $writer->shouldNotReceive('updateStock');
        $this->app->instance(GoogleSheetsWriter::class, $writer);
        $admin = $this->admin();

        foreach (['', 'NaN', 'INF', '-INF', 'bukan-angka'] as $invalid) {
            $this->actingAs($admin)
                ->from(route('admin.appsheet.stock-material-bms.index'))
                ->patch(route('admin.appsheet.stock.update', StockSheetMap::MATERIAL_BMS), [
                    'identifier' => 'BMS-M1',
                    'quantity' => $invalid,
                    'original_quantity' => '2.5',
                ])
                ->assertRedirect()
                ->assertSessionHasErrors('quantity');
        }
    }

    public function test_all_stock_kinds_require_their_server_defined_fields(): void
    {
        $writer = Mockery::mock(GoogleSheetsWriter::class);
        $writer->shouldNotReceive('updateStock');
        $this->app->instance(GoogleSheetsWriter::class, $writer);
        $admin = $this->admin();

        foreach ([
            StockSheetMap::CONSUMABLE_BMS => ['spare_stock', 'original_spare_stock'],
            StockSheetMap::CONSUMABLE_GUDANG => ['qty_consignment', 'original_qty_consignment', 'qty_non_consignment', 'original_qty_non_consignment'],
            StockSheetMap::MATERIAL_BMS => ['quantity', 'original_quantity'],
            StockSheetMap::MATERIAL_GUDANG => ['quantity', 'original_quantity', 'qty_capex', 'original_qty_capex'],
        ] as $stockKind => $requiredFields) {
            $response = $this->actingAs($admin)
                ->patch(route('admin.appsheet.stock.update', $stockKind), ['identifier' => 'ITEM-1']);

            $response->assertSessionHasErrors($requiredFields);
        }
    }

    public function test_conflict_and_missing_write_scope_return_safe_feedback(): void
    {
        $admin = $this->admin();
        $writer = Mockery::mock(GoogleSheetsWriter::class);
        $writer->shouldReceive('updateStock')->once()->andThrow(new GoogleSheetsException(
            'Stock telah berubah sejak halaman ini dibuka. Nilai terbaru adalah 900.',
        ));
        $this->app->instance(GoogleSheetsWriter::class, $writer);

        $this->actingAs($admin)
            ->patch(route('admin.appsheet.stock.update', StockSheetMap::MATERIAL_BMS), [
                'identifier' => 'BMS-M1',
                'quantity' => 3,
                'original_quantity' => 2,
            ])
            ->assertRedirect()
            ->assertSessionHas('appsheet_stock_error', fn (string $message): bool => str_contains($message, 'telah berubah'));

        $writer = Mockery::mock(GoogleSheetsWriter::class);
        $writer->shouldReceive('updateStock')->once()->andThrow(new GoogleSheetsException(
            'Koneksi Google belum memiliki izin edit Google Sheets. Silakan Hubungkan Ulang Google.',
            true,
        ));
        $this->app->instance(GoogleSheetsWriter::class, $writer);

        $this->actingAs($admin)
            ->patch(route('admin.appsheet.stock.update', StockSheetMap::MATERIAL_BMS), [
                'identifier' => 'BMS-M1',
                'quantity' => 3,
                'original_quantity' => 2,
            ])
            ->assertRedirect()
            ->assertSessionHas('appsheet_google_error', fn (string $message): bool => str_contains($message, 'izin edit'));
    }

    public function test_stock_update_endpoint_keeps_existing_module_authorization(): void
    {
        $route = route('admin.appsheet.stock.update', StockSheetMap::MATERIAL_BMS);
        $payload = ['identifier' => 'BMS-M1', 'quantity' => 3, 'original_quantity' => 2];

        $this->patch($route, $payload)->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create(['role' => User::ROLE_USER]))
            ->patch($route, $payload)
            ->assertForbidden();
        $this->actingAs(User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_ADMIN,
        ]))->patch($route, $payload)->assertForbidden();
        $this->actingAs($this->admin())->patch('/admin/appsheet/stock/not-supported', $payload)->assertNotFound();
    }

    private function admin(array $attributes = []): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
            ...$attributes,
        ]);
    }
}
