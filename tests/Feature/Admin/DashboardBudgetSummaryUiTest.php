<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardBudgetSummaryUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_the_current_general_financial_summary_layout(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('body:has(.dashboard-header)', false);
        $response->assertSee('dashboard-header grid gap-3 rounded-xl border border-slate-200 bg-white p-4 lg:sticky lg:top-[52px] lg:z-10', false);
        $response->assertSeeTextInOrder([
            'GENERAL BIAYA JASA',
            'Total Prognosa Biaya',
            'Realisasi Biaya',
            'Outstanding Biaya',
            'Anggaran Tersedia',
        ]);
        $response->assertDontSee('Kuota Anggaran Actual');
        $response->assertDontSee('Potensi Biaya + Realisasi Biaya');
        $response->assertDontSee('Ringkasan Kuota Anggaran');
    }

    public function test_existing_overhaul_and_top_ten_sections_remain_visible(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Prognosa Biaya Overhaul')
            ->assertSee('TOP TEN PEMICU BIAYA');
    }
}
