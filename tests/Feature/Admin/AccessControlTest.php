<?php

namespace Tests\Feature\Admin;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\AdminRoleMenuAccess;
use App\Models\Order;
use App\Models\User;
use App\Support\AdminMenuRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_access_control_shows_role_level_matrix_only(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
        User::factory()->create([
            'name' => 'Admin Operasional',
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_ADMIN,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('admin.access-control.index'))
            ->assertOk()
            ->assertSeeText('Role')
            ->assertSeeText('Permission')
            ->assertSee('Menu Access Matrix')
            ->assertSee('Super Admin')
            ->assertSee('Admin')
            ->assertSee('Order Pekerjaan Jasa')
            ->assertSee('Order Pekerjaan Bengkel')
            ->assertSee('Quality Control')
            ->assertSee('Serah Terima')
            ->assertSeeInOrder([
                'Dashboard',
                'Pekerjaan Jasa',
                'Pekerjaan Bengkel',
                'Menu Pendukung',
                'Lainnya',
            ])
            ->assertDontSee('Admin Operasional')
            ->assertDontSee('Approval');
    }

    public function test_super_admin_can_update_global_admin_menu_permissions(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
        User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_ADMIN,
        ]);
        User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_ADMIN,
        ]);

        AdminRoleMenuAccess::query()->create([
            'admin_role' => User::ADMIN_ROLE_ADMIN,
            'menu_key' => AdminMenuRegistry::MENU_ORDERS,
        ]);
        AdminRoleMenuAccess::query()->create([
            'admin_role' => User::ADMIN_ROLE_ADMIN,
            'menu_key' => AdminMenuRegistry::MENU_PURCHASE_ORDER,
        ]);

        $this->actingAs($superAdmin)
            ->put(route('admin.access-control.update'), [
                'menu_keys' => [
                    AdminMenuRegistry::MENU_ORDER_JASA,
                    AdminMenuRegistry::MENU_CREATE_HPP,
                ],
            ])
            ->assertRedirect(route('admin.access-control.index'))
            ->assertSessionHas('status', 'Permission role Admin berhasil diperbarui.');

        $this->assertDatabaseHas('admin_role_menu_accesses', [
            'admin_role' => User::ADMIN_ROLE_ADMIN,
            'menu_key' => AdminMenuRegistry::MENU_ORDER_JASA,
        ]);
        $this->assertDatabaseHas('admin_role_menu_accesses', [
            'admin_role' => User::ADMIN_ROLE_ADMIN,
            'menu_key' => AdminMenuRegistry::MENU_CREATE_HPP,
        ]);
        $this->assertDatabaseMissing('admin_role_menu_accesses', [
            'admin_role' => User::ADMIN_ROLE_ADMIN,
            'menu_key' => AdminMenuRegistry::MENU_PURCHASE_ORDER,
        ]);
    }

    public function test_admin_access_uses_global_role_permission_not_per_user_rows(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_ADMIN,
        ]);

        AdminRoleMenuAccess::query()->create([
            'admin_role' => User::ADMIN_ROLE_ADMIN,
            'menu_key' => AdminMenuRegistry::MENU_CREATE_HPP,
        ]);

        $this->assertTrue($admin->hasAdminMenuAccess(AdminMenuRegistry::MENU_CREATE_HPP));
        $this->assertFalse($admin->hasAdminMenuAccess(AdminMenuRegistry::MENU_PURCHASE_ORDER));
    }

    public function test_workshop_quality_control_menu_has_independent_frontend_access(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_ADMIN,
        ]);

        AdminRoleMenuAccess::query()->create([
            'admin_role' => User::ADMIN_ROLE_ADMIN,
            'menu_key' => AdminMenuRegistry::MENU_QUALITY_CONTROL_BENGKEL,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeText('Pekerjaan Bengkel')
            ->assertSeeText('Quality Control');

        $this->actingAs($admin)
            ->get(route('admin.workshop-quality-control.index'))
            ->assertOk()
            ->assertSeeText('Monitoring pemeriksaan kualitas pekerjaan bengkel.')
            ->assertSeeText('Perlu Pemeriksaan')
            ->assertSeeText('Dalam Pemeriksaan');
    }

    public function test_workshop_handover_menu_has_independent_frontend_access(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_ADMIN,
        ]);

        AdminRoleMenuAccess::query()->create([
            'admin_role' => User::ADMIN_ROLE_ADMIN,
            'menu_key' => AdminMenuRegistry::MENU_SERAH_TERIMA_BENGKEL,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSeeText('Pekerjaan Bengkel')
            ->assertSeeText('Serah Terima');

        $this->actingAs($admin)
            ->get(route('admin.workshop-handover.index'))
            ->assertOk()
            ->assertSeeText('Monitoring proses penyerahan hasil pekerjaan bengkel.')
            ->assertSeeText('Menunggu Serah Terima')
            ->assertSeeText('Dalam Proses');
    }

    public function test_order_jasa_and_bengkel_permissions_control_sidebar_and_backend_separately(): void
    {
        $jasaAdmin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_ADMIN,
        ]);
        $jasaOrder = $this->makeOrder($jasaAdmin, 'ORDER-JASA', OrderUserNoteStatus::ApprovedJasa);
        $workshopOrder = $this->makeOrder($jasaAdmin, 'ORDER-BENGKEL', OrderUserNoteStatus::ApprovedWorkshop);

        AdminRoleMenuAccess::query()->create([
            'admin_role' => User::ADMIN_ROLE_ADMIN,
            'menu_key' => AdminMenuRegistry::MENU_ORDER_JASA,
        ]);

        $this->actingAs($jasaAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Order Pekerjaan Jasa')
            ->assertDontSee('Order Pekerjaan Bengkel');
        $this->actingAs($jasaAdmin)->get(route('admin.orders.index'))->assertOk();
        $this->actingAs($jasaAdmin)->get(route('admin.orders.workshop.index'))->assertForbidden();
        $this->actingAs($jasaAdmin)->get(route('admin.orders.show', $jasaOrder))->assertOk();
        $this->actingAs($jasaAdmin)->get(route('admin.orders.show', $workshopOrder))->assertForbidden();

        AdminRoleMenuAccess::query()->delete();
        AdminRoleMenuAccess::query()->create([
            'admin_role' => User::ADMIN_ROLE_ADMIN,
            'menu_key' => AdminMenuRegistry::MENU_ORDER_BENGKEL,
        ]);

        $this->actingAs($jasaAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Order Pekerjaan Jasa')
            ->assertSee('Order Pekerjaan Bengkel');
        $this->actingAs($jasaAdmin)->get(route('admin.orders.index'))->assertForbidden();
        $this->actingAs($jasaAdmin)->get(route('admin.orders.workshop.index'))->assertOk();
        $this->actingAs($jasaAdmin)->get(route('admin.orders.show', $jasaOrder))->assertForbidden();
        $this->actingAs($jasaAdmin)->get(route('admin.orders.show', $workshopOrder))->assertOk();
    }

    public function test_legacy_orders_permission_temporarily_grants_both_split_order_menus(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_ADMIN,
        ]);

        AdminRoleMenuAccess::query()->create([
            'admin_role' => User::ADMIN_ROLE_ADMIN,
            'menu_key' => AdminMenuRegistry::MENU_ORDERS,
        ]);

        $this->assertTrue(AdminMenuRegistry::canAccess($admin, AdminMenuRegistry::MENU_ORDER_JASA));
        $this->assertTrue(AdminMenuRegistry::canAccess($admin, AdminMenuRegistry::MENU_ORDER_BENGKEL));
    }

    public function test_admin_header_shortcuts_follow_global_menu_permission(): void
    {
        $superAdmin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_ADMIN,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('aria-label="Upload Informasi"', false)
            ->assertSee('aria-label="Struktur Organisasi"', false);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('aria-label="Upload Informasi"', false)
            ->assertDontSee('aria-label="Struktur Organisasi"', false);

        AdminRoleMenuAccess::query()->create([
            'admin_role' => User::ADMIN_ROLE_ADMIN,
            'menu_key' => AdminMenuRegistry::MENU_UPLOAD_INFORMASI,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('aria-label="Upload Informasi"', false)
            ->assertDontSee('aria-label="Struktur Organisasi"', false);
    }

    private function makeOrder(User $admin, string $number, OrderUserNoteStatus $status): Order
    {
        return Order::query()->create([
            'nomor_order' => $number,
            'notifikasi' => 'NOTIF-'.$number,
            'nama_pekerjaan' => 'Pekerjaan '.$number,
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Deskripsi',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'catatan_status' => $status->value,
            'tanggal_order' => '2026-08-01',
            'target_selesai' => '2026-08-10',
            'created_by' => $admin->id,
        ]);
    }
}
