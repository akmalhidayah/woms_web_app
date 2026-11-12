<?php

namespace Tests\Feature\Pkm;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PkmSidebarRailLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_pkm_layout_uses_rail_with_notched_active_tab(): void
    {
        $response = $this->actingAs(User::factory()->create(['role' => User::ROLE_PKM]))
            ->get(route('pkm.dashboard'))
            ->assertOk()
            ->assertSee('sidebar-rail', false)
            ->assertSee('.sidebar-rail .nav-active::before', false)
            ->assertSee('lg:pl-[18.75rem]', false)
            ->assertSee('lg:pl-[5.75rem]', false)
            ->assertDontSee('lg:pl-60', false)
            ->assertSee('aria-current="page"', false)
            ->assertSee('toggle()', false);

        $html = $response->getContent();
        $this->assertStringContainsString('<body class="bg-white', $html);
        $this->assertStringContainsString('nav-active', $html);
    }

    public function test_every_pkm_menu_keeps_its_route(): void
    {
        $response = $this->actingAs(User::factory()->create(['role' => User::ROLE_PKM]))
            ->get(route('pkm.dashboard'))
            ->assertOk();

        foreach (['pkm.dashboard', 'pkm.hpp.index', 'pkm.jobwaiting', 'pkm.lhpp.index', 'pkm.laporan', 'pkm.user-panel.index'] as $routeName) {
            $response->assertSee('href="'.route($routeName).'"', false);
        }
    }
}
