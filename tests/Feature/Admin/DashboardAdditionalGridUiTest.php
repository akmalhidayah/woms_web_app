<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAdditionalGridUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_overhaul_prognosis_and_top_ten_cost_grids(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('Prognosa Biaya Overhaul')
            ->assertSee('Tonasa 4')
            ->assertSee('Tonasa 5')
            ->assertSee('Minor')
            ->assertSee('Mayor')
            ->assertSee('Top Ten Unit Kerja Pemicu Biaya')
            ->assertSee('id="topTenCostChart"', false)
            ->assertSee('Belum ada data HPP yang telah disubmit.')
            ->assertSee("label: 'Nilai HPP'", false)
            ->assertViewHas('overhaulPrognosis', [
                'tonasa_4' => ['minor' => 0, 'major' => 0],
                'tonasa_5' => ['minor' => 0, 'major' => 0],
            ])
            ->assertViewHas('topTenCostSections', [])
            ->assertViewHas('totalAmount1', 0)
            ->assertViewHas('totalAmount2', 0);
    }
}
