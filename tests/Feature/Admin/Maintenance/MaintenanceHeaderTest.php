<?php

namespace Tests\Feature\Admin\Maintenance;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceHeaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_icon_and_page_are_only_available_to_super_admin(): void
    {
        $superAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('aria-label="Maintenance Sistem"', false)
            ->assertSee('data-lucide="wrench"', false);

        $this->actingAs($superAdmin)
            ->get(route('admin.maintenance.index'))
            ->assertOk();

        foreach ([
            [User::ROLE_ADMIN, User::ADMIN_ROLE_ADMIN],
            [User::ROLE_PKM, null],
            [User::ROLE_USER, null],
            [User::ROLE_APPROVER, null],
        ] as [$role, $adminRole]) {
            $user = User::factory()->create(['role' => $role, 'admin_role' => $adminRole]);

            $this->actingAs($user)
                ->get(route('admin.maintenance.index'))
                ->assertForbidden();
        }
    }

    public function test_regular_admin_does_not_see_header_icon(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('aria-label="Maintenance Sistem"', false);
    }

    private function superAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
    }
}
