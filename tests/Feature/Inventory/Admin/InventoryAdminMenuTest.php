<?php

namespace Tests\Feature\Inventory\Admin;

use App\Models\AdminRoleMenuAccess;
use App\Models\User;
use App\Support\AdminMenuRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryAdminMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_menu_is_registered_immediately_after_dashboard_with_seven_children(): void
    {
        $definitions = AdminMenuRegistry::definitions();
        $keys = array_keys($definitions);
        $dashboardPosition = array_search(AdminMenuRegistry::MENU_DASHBOARD, $keys, true);

        $this->assertSame(AdminMenuRegistry::MENU_INVENTORY, $keys[$dashboardPosition + 1]);
        $this->assertSame('warehouse', $definitions[AdminMenuRegistry::MENU_INVENTORY]['icon']);
        $this->assertCount(7, $definitions[AdminMenuRegistry::MENU_INVENTORY]['children']);
        $this->assertSame([
            'Dashboard Gudang',
            'Master Barang',
            'Stok Masuk',
            'Koreksi Stok',
            'Riwayat Transaksi',
            'User Aplikasi',
            'Master Data',
        ], array_column($definitions[AdminMenuRegistry::MENU_INVENTORY]['children'], 'label'));
    }

    public function test_super_admin_sees_inventory_menu_and_active_submenu(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('admin.inventory.items.index'))
            ->assertOk()
            ->assertSeeText('Inventory')
            ->assertSeeText('Dashboard Gudang')
            ->assertSeeText('Master Barang')
            ->assertSee('inventoryOpen: true', false)
            ->assertSee('bg-white text-blue-900', false);
    }

    public function test_regular_admin_needs_single_inventory_permission_for_menu_and_routes(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSeeText('Inventory');

        $this->actingAs($admin)
            ->get(route('admin.inventory.dashboard'))
            ->assertForbidden();

        AdminRoleMenuAccess::query()->create([
            'admin_role' => User::ADMIN_ROLE_ADMIN,
            'menu_key' => AdminMenuRegistry::MENU_INVENTORY,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.inventory.dashboard'))
            ->assertOk()
            ->assertSeeText('Inventory')
            ->assertSeeText('Master Data');
    }

    public function test_non_admin_cannot_access_inventory_admin_page(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($user)
            ->get(route('admin.inventory.dashboard'))
            ->assertForbidden();
    }
}
