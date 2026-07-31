<?php

namespace Tests\Feature\Inventory\Admin;

use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryTransaction;
use App\Models\Inventory\InventoryUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class InventoryAdminPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_seven_inventory_pages_are_available_to_super_admin_via_get_only(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);

        $routes = [
            'admin.inventory.dashboard' => 'Dashboard Gudang',
            'admin.inventory.items.index' => 'Master Barang',
            'admin.inventory.stock-in.index' => 'Stok Masuk',
            'admin.inventory.adjustments.index' => 'Koreksi Stok',
            'admin.inventory.transactions.index' => 'Riwayat Transaksi',
            'admin.inventory.users.index' => 'User Aplikasi',
            'admin.inventory.master-data.index' => 'Master Data',
        ];

        foreach ($routes as $routeName => $heading) {
            $this->assertSame(['GET', 'HEAD'], Route::getRoutes()->getByName($routeName)?->methods());
            $this->actingAs($superAdmin)
                ->get(route($routeName))
                ->assertOk()
                ->assertSeeText($heading);
        }
    }

    public function test_opening_inventory_pages_does_not_change_stock_or_create_transactions(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
        $item = InventoryItem::query()->create([
            'uid' => 'INV-READ-ONLY',
            'item_type' => 'consumable',
            'name' => 'Barang Read Only',
            'unit' => 'EA',
            'minimum_stock' => 2,
            'is_active' => true,
        ]);
        $item->forceFill(['current_stock' => 12])->save();

        $transactionCount = InventoryTransaction::query()->count();

        foreach ([
            'admin.inventory.dashboard',
            'admin.inventory.items.index',
            'admin.inventory.stock-in.index',
            'admin.inventory.adjustments.index',
            'admin.inventory.transactions.index',
        ] as $routeName) {
            $this->actingAs($superAdmin)->get(route($routeName))->assertOk();
        }

        $this->assertSame($transactionCount, InventoryTransaction::query()->count());
        $this->assertSame(12, $item->fresh()->current_stock);
    }

    public function test_inventory_user_page_never_displays_password_or_access_tokens(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
        InventoryUser::query()->create([
            'name' => 'User Inventory Aman',
            'email' => 'inventory-safe@example.test',
            'password' => 'rahasia-jangan-tampil',
            'role' => 'user',
            'is_active' => true,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('admin.inventory.users.index'))
            ->assertOk()
            ->assertSeeText('User Inventory Aman')
            ->assertDontSee('rahasia-jangan-tampil')
            ->assertDontSee('personal_access_token');
    }

    public function test_existing_inventory_api_route_count_is_unchanged(): void
    {
        $apiRoutes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route): bool => str_starts_with($route->uri(), 'api/v1/inventory'));

        $this->assertCount(17, $apiRoutes);
    }
}
