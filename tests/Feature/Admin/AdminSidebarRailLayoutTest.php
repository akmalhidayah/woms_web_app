<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Support\AdminMenuRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSidebarRailLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_floating_rail_layout_keeps_toggle_state_and_notch_styles(): void
    {
        $response = $this->actingAs($this->superAdmin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('sidebar-rail', false)
            ->assertSee('.sidebar-rail .nav-active::before', false)
            ->assertSee('lg:pl-[18.75rem]', false)
            ->assertSee('lg:pl-[5.75rem]', false)
            ->assertDontSee('lg:pl-60', false)
            ->assertSee('sidebarOpen: true', false)
            ->assertSee('mobileOpen: false', false)
            ->assertSee('toggleSidebar()', false);

        $this->assertStringContainsString('<body class="bg-white', $response->getContent());
    }

    public function test_active_menu_uses_notched_tab_and_every_menu_keeps_its_route(): void
    {
        $admin = $this->superAdmin();
        $sidebar = AdminMenuRegistry::sidebarForUser($admin);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $html = $response->getContent();

        $this->assertStringContainsString('nav-active', $html);
        $this->assertStringContainsString('aria-current="page"', $html);

        $menus = collect([$sidebar['dashboard'], $sidebar['appsheet']])
            ->merge($sidebar['main'])
            ->merge($sidebar['workshop'])
            ->merge($sidebar['support'])
            ->merge($sidebar['other'])
            ->filter();

        foreach ($menus as $menu) {
            foreach ($menu['children'] ?? [$menu] as $item) {
                $response->assertSee('href="'.$item['href'].'"', false);
            }
        }
    }

    public function test_dropdown_expands_the_collapsed_sidebar_and_menu_ids_are_preserved(): void
    {
        $this->actingAs($this->superAdmin())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('if (!sidebarOpen) { sidebarOpen = true; open = true } else { open = !open }', false)
            ->assertSee('aria-controls="stock-submenus"', false)
            ->assertSee('id="stock-submenus"', false)
            ->assertSee(':title="sidebarOpen ? \'\' :', false);
    }

    public function test_menu_hidden_by_permission_is_not_rendered_in_the_rail(): void
    {
        $limited = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_ADMIN,
        ]);
        $hidden = collect(AdminMenuRegistry::sidebarForUser($this->superAdmin())['support'])
            ->merge(AdminMenuRegistry::sidebarForUser($this->superAdmin())['other'])
            ->map(fn (array $menu) => $menu['href'])
            ->diff(collect(AdminMenuRegistry::sidebarForUser($limited)['support'])
                ->merge(AdminMenuRegistry::sidebarForUser($limited)['other'])
                ->map(fn (array $menu) => $menu['href']));

        $response = $this->actingAs($limited)->get(route('admin.dashboard'))->assertOk();

        foreach ($hidden as $href) {
            $response->assertDontSee('href="'.$href.'"', false);
        }
    }

    private function superAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
    }
}
