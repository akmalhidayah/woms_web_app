<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\LhppBast;
use App\Models\Order;
use App\Models\OutlineAgreement;
use App\Models\UnitWork;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardMonthlyRealizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_combines_application_and_outline_agreement_realizations(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $agreement = $this->createAgreement($admin, 'OA-DASH-MONTHLY-001', 1000000000);
        $agreement->monthlyRealizations()->create([
            'year' => 2026,
            'month' => 1,
            'kategori_biaya' => 'pemeliharaan',
            'amount' => 120000000,
        ]);
        $this->createBast($admin, 'ORDER-NORMAL-MONTHLY', Order::PRIORITY_MEDIUM, 15000000, true);
        $this->createBast($admin, 'ORDER-URGENT-MONTHLY', Order::PRIORITY_HIGH, 5000000, false);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('documentPRPOAmount', 135000000);
        $response->assertViewHas('urgentAmount', 5000000);
        $response->assertViewHas('totalAmount2', 140000000);
        $response->assertViewHas('totalRealisasiSistem', 0);
        $response->assertViewHas('totalRealisasiManual', 120000000);
        $response->assertViewHas('totalRealisasiBiaya', 120000000);
        $response->assertViewHas('totalSeluruhAmount', 140000000);
        $response->assertViewHas('totalKuotaKontrak', 1000000000);
        $response->assertViewHas('sisaKuotaKontrak', 880000000);
        $response->assertSee('Rp. 880.000.000');
        $response->assertDontSee('Manual');
        $response->assertDontSee('Otomatis');
        $response->assertDontSee('Historis');

        $agreement->refresh();
        $this->assertSame('1000000000.00', $agreement->current_total_nilai);
    }

    public function test_chart_merges_two_sources_per_month_and_respects_filter(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $agreement = $this->createAgreement($admin, 'OA-DASH-MONTHLY-002', 1000000000);
        $agreement->monthlyRealizations()->createMany([
            [
                'year' => 2026,
                'month' => 1,
                'kategori_biaya' => 'pemeliharaan',
                'amount' => 120000000,
            ],
            [
                'year' => 2026,
                'month' => 7,
                'kategori_biaya' => 'capex',
                'amount' => 6000000,
            ],
        ]);
        $this->createBast($admin, 'ORDER-JUL-NORMAL', Order::PRIORITY_MEDIUM, 15871421, true, '2026-07-15');

        $response = $this->actingAs($admin)->getJson(route('admin.dashboard.realization-chart', [
            'startYear' => 2026,
            'endYear' => 2026,
            'startMonth' => 7,
            'endMonth' => 7,
        ]));

        $response->assertOk()->assertExactJson([
            [
                'year' => 2026,
                'month' => 7,
                'label' => 'Jul 2026',
                'total' => 21871421,
                'normal_total' => 21871421,
                'urgent_total' => 0,
            ],
        ]);
    }

    public function test_inactive_outline_agreement_realization_is_not_included(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $activeAgreement = $this->createAgreement($admin, 'OA-DASH-MONTHLY-003', 500000000);
        $closedAgreement = $this->createAgreement($admin, 'OA-DASH-MONTHLY-004', 700000000, OutlineAgreement::STATUS_CLOSED);
        $activeAgreement->monthlyRealizations()->create([
            'year' => 2026,
            'month' => 1,
            'kategori_biaya' => 'pemeliharaan',
            'amount' => 300,
        ]);
        $closedAgreement->monthlyRealizations()->create([
            'year' => 2026,
            'month' => 1,
            'kategori_biaya' => 'pemeliharaan',
            'amount' => 3000,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertViewHas('documentPRPOAmount', 300);
        $response->assertViewHas('urgentAmount', 0);
        $response->assertViewHas('totalKuotaKontrak', 500000000);
    }

    public function test_chart_filter_supports_a_month_range_across_years(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $agreement = $this->createAgreement(
            $admin,
            'OA-DASH-MONTHLY-005',
            1000000000,
            OutlineAgreement::STATUS_ACTIVE,
            '2025-01-01',
            '2026-12-31',
        );
        $agreement->monthlyRealizations()->createMany([
            ['year' => 2025, 'month' => 12, 'kategori_biaya' => 'pemeliharaan', 'amount' => 110],
            ['year' => 2026, 'month' => 1, 'kategori_biaya' => 'capex', 'amount' => 220],
            ['year' => 2026, 'month' => 3, 'kategori_biaya' => 'non pemeliharaan', 'amount' => 330],
        ]);

        $response = $this->actingAs($admin)->getJson(route('admin.dashboard.realization-chart', [
            'startYear' => 2025,
            'endYear' => 2026,
            'startMonth' => 12,
            'endMonth' => 2,
        ]));

        $response->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.total', 110)
            ->assertJsonPath('1.total', 220);
    }

    private function createAgreement(
        User $admin,
        string $number,
        int $value,
        string $status = OutlineAgreement::STATUS_ACTIVE,
        string $periodStart = '2026-01-01',
        string $periodEnd = '2026-12-31',
    ): OutlineAgreement {
        $department = Department::query()->firstOrCreate(['name' => 'Departemen Dashboard Monthly']);
        $unitWork = UnitWork::query()->firstOrCreate([
            'department_id' => $department->id,
            'name' => 'Unit Dashboard Monthly',
        ]);

        return OutlineAgreement::query()->create([
            'nomor_oa' => $number,
            'unit_work_id' => $unitWork->id,
            'jenis_kontrak' => 'Fabrikasi',
            'nama_kontrak' => 'Kontrak '.$number,
            'nilai_kontrak_awal' => $value,
            'periode_awal_start' => $periodStart,
            'periode_awal_end' => $periodEnd,
            'current_total_nilai' => $value,
            'current_period_start' => $periodStart,
            'current_period_end' => $periodEnd,
            'status' => $status,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }

    private function createBast(
        User $admin,
        string $orderNumber,
        string $priority,
        int $amount,
        bool $withPurchaseOrderNumber,
        string $date = '2026-01-15',
    ): LhppBast {
        $order = Order::query()->create([
            'nomor_order' => $orderNumber,
            'nama_pekerjaan' => 'Pekerjaan '.$orderNumber,
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Detail pekerjaan test',
            'prioritas' => $priority,
            'tanggal_order' => $date,
            'target_selesai' => $date,
            'created_by' => $admin->id,
        ]);

        return LhppBast::query()->create([
            'order_id' => $order->id,
            'termin_type' => 'termin_1',
            'nomor_order' => $order->nomor_order,
            'purchase_order_number' => $withPurchaseOrderNumber ? 'PO-'.$orderNumber : null,
            'deskripsi_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'tanggal_bast' => $date,
            'total_aktual_biaya' => $amount,
            'termin_1_nilai' => $amount,
            'termin_2_nilai' => 0,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }
}
