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
            ->assertSee('PROGNOSA OVERHAUL')
            ->assertSee('Tonasa 4')
            ->assertSee('Tonasa 5')
            ->assertSee('Tonasa 2\\/3', false)
            ->assertSee('id="overhaulPrognosisChart"', false)
            ->assertSee("label: 'Prognosa Biaya'", false)
            ->assertDontSee('Minor')
            ->assertDontSee('Mayor')
            ->assertSee('TOP TEN PEMICU BIAYA')
            ->assertSee('GENERAL')
            ->assertSee('PEMELIHARAAN')
            ->assertSee('id="topTenGeneralCostChart"', false)
            ->assertSee('id="topTenMaintenanceCostChart"', false)
            ->assertSee('id="monthlyRealizationChart"', false)
            ->assertSee('id="monthlyStartMonth"', false)
            ->assertSee('id="monthlyEndYear"', false)
            ->assertDontSee('monthly-realization-toggle', false)
            ->assertSee('display: true', false)
            ->assertSee('maintenance-panel flex min-w-0 flex-col rounded-xl border border-slate-200 bg-white p-3 shadow-sm xl:col-span-8', false)
            ->assertSee('top-ten-panel flex min-w-0 flex-col rounded-xl border border-slate-200 bg-white p-3 shadow-sm xl:col-span-8', false)
            ->assertSee('overhaul-panel flex min-w-0 flex-col rounded-xl border border-slate-200 bg-white p-3 shadow-sm xl:col-span-4', false)
            ->assertDontSee('id="topTenCombinedCostChart"', false)
            ->assertSee('Belum ada data HPP pada periode ini.')
            ->assertSee("datasetLabel: 'General'", false)
            ->assertSee("datasetLabel: 'Pemeliharaan'", false)
            ->assertViewHas('overhaulPrognosis', [
                [
                    'key' => 'overhaul_tonasa_2_3',
                    'label' => 'Tonasa 2/3',
                    'amount' => 0,
                ],
                [
                    'key' => 'overhaul_tonasa_4',
                    'label' => 'Tonasa 4',
                    'amount' => 0,
                ],
                [
                    'key' => 'overhaul_tonasa_5',
                    'label' => 'Tonasa 5',
                    'amount' => 0,
                ],
            ])
            ->assertViewHas('topTenCostSections', [])
            ->assertViewHas('topTenMaintenanceCostSections', [])
            ->assertViewHas('totalAmount1', 0)
            ->assertViewHas('totalAmount2', 0);
    }
}
