<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\Hpp;
use App\Models\Order;
use App\Models\OutlineAgreement;
use App\Models\OutlineAgreementTarget;
use App\Models\UnitWork;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardBudgetSummaryCalculationTest extends TestCase
{
    use RefreshDatabase;

    public function test_budget_summary_reuses_existing_potential_and_combined_realization_values(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $agreement = $this->createAgreement($admin, 'OA-BUDGET-SUMMARY-001', 1000);
        $agreement->monthlyRealizations()->create([
            'year' => 2026,
            'month' => 1,
            'kategori_biaya' => 'pemeliharaan',
            'amount' => 400,
        ]);
        OutlineAgreementTarget::query()->create([
            'outline_agreement_id' => $agreement->id,
            'tahun' => 2026,
            'nilai_target' => 700,
        ]);
        $this->createPendingHpp($admin, 'ORDER-BUDGET-SUMMARY-001', 200);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('totalKuotaKontrak', 1000);
        $response->assertViewHas('totalAmount1', 200);
        $response->assertViewHas('totalRealisasiBiaya', 400);
        $response->assertViewHas('totalPemakaianKuota', 600);
        $response->assertViewHas('sisaKuotaKontrak', 400);
        $response->assertViewHas('budgetUsagePercentageHundredths', 6000);
        $response->assertViewHas('budgetUsagePercentageLabel', '60');
        $response->assertViewHas('budgetUsageProgressWidth', '60');
        $response->assertViewHas('targetPemeliharaan', 700);
        $response->assertViewHas('totalJasaPemeliharaan', 0);
        $response->assertViewHas('sisaBiayaPemeliharaan', 700);
        $response->assertSee('Rp. 600 dari Rp. 1.000');

        $agreement->refresh();
        $this->assertSame('1000.00', $agreement->current_total_nilai);
    }

    public function test_budget_usage_percentage_is_safe_when_contract_budget_is_zero(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->createPendingHpp($admin, 'ORDER-BUDGET-SUMMARY-ZERO', 200);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('totalKuotaKontrak', 0);
        $response->assertViewHas('totalPemakaianKuota', 200);
        $response->assertViewHas('sisaKuotaKontrak', -200);
        $response->assertViewHas('budgetUsagePercentageHundredths', 0);
        $response->assertViewHas('budgetUsagePercentageLabel', '0');
        $response->assertViewHas('budgetUsageProgressWidth', '0');
        $response->assertSee('style="width: 0%"', false);
        $response->assertSee('Rp. -200');
        $response->assertSee('border-rose-200 bg-rose-50', false);
    }

    public function test_percentage_text_can_exceed_one_hundred_while_visual_width_is_capped(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->createAgreement($admin, 'OA-BUDGET-SUMMARY-OVER', 100);
        $this->createPendingHpp($admin, 'ORDER-BUDGET-SUMMARY-OVER', 200);

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertViewHas('budgetUsagePercentageHundredths', 20000);
        $response->assertViewHas('budgetUsagePercentageLabel', '200');
        $response->assertViewHas('budgetUsageProgressWidth', '100');
        $response->assertViewHas('sisaKuotaKontrak', -100);
        $response->assertSee('200%');
        $response->assertSee('style="width: 100%"', false);
        $response->assertSee('Rp. -100');
    }

    private function createAgreement(User $admin, string $number, int $value): OutlineAgreement
    {
        $department = Department::query()->firstOrCreate(['name' => 'Departemen Budget Summary']);
        $unitWork = UnitWork::query()->firstOrCreate([
            'department_id' => $department->id,
            'name' => 'Unit Budget Summary',
        ]);

        return OutlineAgreement::query()->create([
            'nomor_oa' => $number,
            'unit_work_id' => $unitWork->id,
            'jenis_kontrak' => 'Fabrikasi',
            'nama_kontrak' => 'Kontrak '.$number,
            'nilai_kontrak_awal' => $value,
            'periode_awal_start' => '2026-01-01',
            'periode_awal_end' => '2026-12-31',
            'current_total_nilai' => $value,
            'current_period_start' => '2026-01-01',
            'current_period_end' => '2026-12-31',
            'status' => OutlineAgreement::STATUS_ACTIVE,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }

    private function createPendingHpp(User $admin, string $orderNumber, int $amount): Hpp
    {
        $order = Order::query()->create([
            'nomor_order' => $orderNumber,
            'nama_pekerjaan' => 'Pekerjaan '.$orderNumber,
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Detail pekerjaan test',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-01-15',
            'target_selesai' => '2026-01-31',
            'created_by' => $admin->id,
        ]);

        return Hpp::query()->create([
            'order_id' => $order->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Workshop',
            'nilai_hpp_bucket' => 'under',
            'total_keseluruhan' => $amount,
            'status' => Hpp::STATUS_IN_REVIEW,
            'submitted_at' => '2026-01-15 08:00:00',
            'created_by' => $admin->id,
        ]);
    }
}
