<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardBudgetSummaryUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_the_consolidated_budget_summary_layout(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSeeTextInOrder([
            'Ringkasan Kuota Anggaran',
            'Kuota Anggaran',
            'Potensi Biaya',
            'Realisasi Biaya',
            'Sisa Kuota Kontrak',
            'Pemakaian Kuota',
            'Ringkasan Biaya Pemeliharaan',
            'Target Biaya Pemeliharaan',
            'Total Jasa Pemeliharaan',
            'Sisa Target Pemeliharaan',
        ]);
        $response->assertDontSee('Kuota Anggaran Actual');
        $response->assertDontSee('Potensi Biaya + Realisasi Biaya');
        $response->assertSee('role="progressbar"', false);
        $response->assertSee('aria-valuemin="0"', false);
        $response->assertSee('aria-valuemax="100"', false);
    }

    public function test_existing_overhaul_and_top_ten_sections_remain_visible(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Prognosa Biaya Overhaul')
            ->assertSee('Top Ten Unit Kerja Pemicu Biaya');
    }
}
