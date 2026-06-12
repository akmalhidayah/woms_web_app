<?php

namespace Tests\Feature\Admin;

use App\Models\AdminRoleMenuAccess;
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
                    AdminMenuRegistry::MENU_ORDERS,
                    AdminMenuRegistry::MENU_CREATE_HPP,
                ],
            ])
            ->assertRedirect(route('admin.access-control.index'))
            ->assertSessionHas('status', 'Permission role Admin berhasil diperbarui.');

        $this->assertDatabaseHas('admin_role_menu_accesses', [
            'admin_role' => User::ADMIN_ROLE_ADMIN,
            'menu_key' => AdminMenuRegistry::MENU_ORDERS,
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
}
