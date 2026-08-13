<?php

namespace Tests\Feature\Admin;

use App\Models\BudgetVerification;
use App\Models\Department;
use App\Models\Hpp;
use App\Models\LhppBast;
use App\Models\LpjPpl;
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
        $this->createBast($admin, $agreement, 'ORDER-NORMAL-MONTHLY', Order::PRIORITY_MEDIUM, 15000000, true);
        $this->createBast($admin, $agreement, 'ORDER-URGENT-MONTHLY', Order::PRIORITY_HIGH, 5000000, false);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('documentPRPOAmount', 135000000);
        $response->assertViewHas('urgentAmount', 5000000);
        $response->assertViewHas('totalAmount2', 140000000);
        $response->assertViewHas('totalRealisasiSistem', 0);
        $response->assertViewHas('totalRealisasiManual', 120000000);
        $response->assertViewHas('totalRealisasiBiaya', 120000000);
        $response->assertViewHas('totalSeluruhAmount', 160000000);
        $response->assertViewHas('totalKuotaKontrak', 1000000000);
        $response->assertViewHas('sisaKuotaKontrak', 860000000);
        $response->assertSee('Rp. 860.000.000');
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
        $this->createBast($admin, $agreement, 'ORDER-JUL-NORMAL', Order::PRIORITY_MEDIUM, 15871421, true, '2026-07-15', true);

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
                'general' => 21871421,
                'maintenance' => 15871421,
                'non_maintenance' => 0,
                'capex' => 6000000,
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
            ->assertJsonCount(3)
            ->assertJsonPath('0.total', 110)
            ->assertJsonPath('1.total', 220)
            ->assertJsonPath('2.total', 0);
    }

    public function test_chart_and_summary_only_recognize_approved_bast_with_complete_lpj(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $agreement = $this->createAgreement($admin, 'OA-DASH-LPJ-CONSISTENCY', 500000000);
        $bast = $this->createBast(
            $admin,
            $agreement,
            'ORDER-LPJ-CONSISTENCY',
            Order::PRIORITY_MEDIUM,
            100000000,
            true,
            '2026-07-15',
        );

        $before = $this->actingAs($admin)->getJson(route('admin.dashboard.realization-chart', [
            'startYear' => 2026,
            'endYear' => 2026,
            'startMonth' => 7,
            'endMonth' => 7,
        ]));
        $before->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.total', 0)
            ->assertJsonPath('0.general', 0);
        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertViewHas('totalRealisasiSistem', 0);

        LpjPpl::query()->create([
            'lhpp_bast_id' => $bast->id,
            'lpj_number_termin1' => 'LPJ-001',
            'lpj_document_path_termin1' => 'lpj/LPJ-001.pdf',
            'created_by' => $admin->id,
        ]);

        $after = $this->actingAs($admin)->getJson(route('admin.dashboard.realization-chart', [
            'startYear' => 2026,
            'endYear' => 2026,
            'startMonth' => 7,
            'endMonth' => 7,
        ]));
        $after->assertOk()->assertJsonPath('0.total', 100000000);
        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertViewHas('totalRealisasiSistem', 100000000);
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
        OutlineAgreement $agreement,
        string $orderNumber,
        string $priority,
        int $amount,
        bool $withPurchaseOrderNumber,
        string $date = '2026-01-15',
        bool $completeLpj = false,
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

        $hpp = Hpp::query()->create([
            'order_id' => $order->id,
            'outline_agreement_id' => $agreement->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Workshop',
            'nilai_hpp_bucket' => 'under',
            'total_keseluruhan' => $amount,
            'status' => Hpp::STATUS_APPROVED,
            'submitted_at' => $date,
            'created_by' => $admin->id,
        ]);

        BudgetVerification::query()->create([
            'order_id' => $order->id,
            'hpp_id' => $hpp->id,
            'kategori_biaya' => 'pemeliharaan',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $bast = LhppBast::query()->create([
            'order_id' => $order->id,
            'hpp_id' => $hpp->id,
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
            'approval_status' => LhppBast::APPROVAL_APPROVED,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        if ($completeLpj) {
            LpjPpl::query()->create([
                'lhpp_bast_id' => $bast->id,
                'lpj_number_termin1' => 'LPJ-'.$orderNumber,
                'lpj_document_path_termin1' => 'lpj/'.$orderNumber.'.pdf',
                'created_by' => $admin->id,
            ]);
        }

        return $bast;
    }
}
