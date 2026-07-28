<?php

namespace Tests\Feature\Admin;

use App\Models\AdminRoleMenuAccess;
use App\Models\User;
use App\Support\AdminMenuRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserImpersonationUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_sees_action_for_valid_target_and_only_one_modal(): void
    {
        $superAdmin = $this->superAdmin();
        $target = User::factory()->create([
            'name' => 'Target Maintenance',
            'email' => 'target-maintenance@example.test',
            'role' => User::ROLE_USER,
        ]);
        User::factory()->create(['role' => User::ROLE_USER]);

        $response = $this->actingAs($superAdmin)
            ->get(route('admin.user-panel.index', ['role' => User::ROLE_USER]))
            ->assertOk()
            ->assertSee('Masuk sebagai User')
            ->assertSee('Target Maintenance')
            ->assertSee('target-maintenance@example.test');

        $this->assertSame(1, substr_count($response->getContent(), 'data-impersonation-modal'));
    }

    public function test_regular_admin_does_not_see_impersonation_action(): void
    {
        AdminRoleMenuAccess::query()->create([
            'admin_role' => User::ADMIN_ROLE_ADMIN,
            'menu_key' => AdminMenuRegistry::MENU_USER_PANEL,
        ]);
        $regularAdmin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_ADMIN,
        ]);
        User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($regularAdmin)
            ->get(route('admin.user-panel.index', ['role' => User::ROLE_USER]))
            ->assertOk()
            ->assertDontSee('title="Masuk sebagai User"', false);
    }

    public function test_self_and_other_super_admin_do_not_have_action(): void
    {
        $superAdmin = $this->superAdmin();
        $otherSuperAdmin = $this->superAdmin();

        $response = $this->actingAs($superAdmin)
            ->get(route('admin.user-panel.index', ['role' => User::ROLE_ADMIN]))
            ->assertOk();

        $response->assertDontSee(route('admin.user-panel.impersonate', $superAdmin), false);
        $response->assertDontSee(route('admin.user-panel.impersonate', $otherSuperAdmin), false);
    }

    public function test_banner_appears_once_on_target_layout_and_not_during_normal_login(): void
    {
        $superAdmin = $this->superAdmin();
        $target = User::factory()->create([
            'name' => 'Target Banner',
            'role' => User::ROLE_PKM,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('MODE MAINTENANCE');

        $this->post(route('admin.user-panel.impersonate', $target));

        $response = $this->get(route('pkm.dashboard'))
            ->assertOk()
            ->assertSee('Mode Maintenance')
            ->assertSee('Target Banner')
            ->assertSee($superAdmin->name)
            ->assertSee('Kembali ke Super Admin');

        $this->assertSame(1, substr_count($response->getContent(), 'data-impersonation-banner'));
    }

    private function superAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
    }
}
