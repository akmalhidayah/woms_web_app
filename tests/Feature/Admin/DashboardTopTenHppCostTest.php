<?php

namespace Tests\Feature\Admin;

use App\Models\BudgetVerification;
use App\Models\Department;
use App\Models\Hpp;
use App\Models\Order;
use App\Models\OutlineAgreement;
use App\Models\UnitWork;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class DashboardTopTenHppCostTest extends TestCase
{
    use RefreshDatabase;

    public function test_top_ten_counts_submitted_non_draft_hpps_by_trimmed_requester_section(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->createHpp($admin, 'TOPTEN-REVIEW', 'Seksi Kiln', 100000000, Hpp::STATUS_IN_REVIEW, '2026-01-05 08:00:00', [
            'seksi_pengendali' => 'Seksi Pengendali Lain',
        ]);
        $this->createHpp($admin, 'TOPTEN-APPROVED', ' Seksi Kiln ', 200000000, Hpp::STATUS_APPROVED, '2026-01-06 08:00:00');
        $this->createHpp($admin, 'TOPTEN-REJECTED', 'Seksi Crusher', 50000000, Hpp::STATUS_REJECTED, '2026-01-07 08:00:00');
        $this->createHpp($admin, 'TOPTEN-DRAFT', 'Seksi Crusher', 900000000, Hpp::STATUS_DRAFT, '2026-01-08 08:00:00');
        $this->createHpp($admin, 'TOPTEN-NOT-SUBMITTED', 'Seksi Raw Mill', 800000000, Hpp::STATUS_IN_REVIEW, null);
        $this->createHpp($admin, 'TOPTEN-EMPTY-SECTION', '   ', 700000000, Hpp::STATUS_APPROVED, '2026-01-09 08:00:00');

        $response = $this->topTenResponse($admin, 2026, 1, 2026, 1);

        $response->assertOk()
            ->assertJsonCount(2, 'top_ten')
            ->assertJsonPath('top_ten.0.section', 'Seksi Kiln')
            ->assertJsonPath('top_ten.0.amount', 300000000)
            ->assertJsonPath('top_ten.1.section', 'Seksi Crusher')
            ->assertJsonPath('top_ten.1.amount', 50000000)
            ->assertJsonMissing(['section' => 'Seksi Pengendali Lain'])
            ->assertJsonMissing(['section' => 'Seksi Raw Mill']);
    }

    public function test_only_latest_submitted_hpp_per_order_is_counted_with_id_as_tie_breaker(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $revisedOrder = $this->createOrder($admin, 'TOPTEN-REVISION', 'Seksi Finish Mill');
        $sameTimeOrder = $this->createOrder($admin, 'TOPTEN-SAME-TIME', 'Seksi Finish Mill');

        $this->createHppForOrder($revisedOrder, $admin, 100000000, Hpp::STATUS_REJECTED, '2026-02-01 08:00:00');
        $this->createHppForOrder($revisedOrder, $admin, 120000000, Hpp::STATUS_IN_REVIEW, '2026-02-05 08:00:00');
        $this->createHppForOrder($sameTimeOrder, $admin, 40000000, Hpp::STATUS_REJECTED, '2026-02-10 08:00:00');
        $this->createHppForOrder($sameTimeOrder, $admin, 50000000, Hpp::STATUS_APPROVED, '2026-02-10 08:00:00');

        $response = $this->topTenResponse($admin, 2026, 2, 2026, 2);

        $response->assertOk()
            ->assertJsonCount(1, 'top_ten')
            ->assertJsonPath('top_ten.0.section', 'Seksi Finish Mill')
            ->assertJsonPath('top_ten.0.amount', 170000000);
    }

    public function test_top_ten_uses_submitted_at_filter_orders_descending_and_limits_results(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        foreach (range(1, 12) as $index) {
            $hpp = $this->createHpp(
                $admin,
                sprintf('TOPTEN-LIMIT-%02d', $index),
                sprintf('Seksi %02d', $index),
                $index * 1000000,
                Hpp::STATUS_APPROVED,
                '2026-03-15 08:00:00',
            );
            $this->createBudgetVerification($hpp, $admin, 'pemeliharaan');
        }

        $outsidePeriod = $this->createHpp(
            $admin,
            'TOPTEN-OUTSIDE-PERIOD',
            'Seksi Di Luar Periode',
            999000000,
            Hpp::STATUS_APPROVED,
            '2026-04-01 00:00:00',
        );
        $this->createBudgetVerification($outsidePeriod, $admin, 'pemeliharaan');

        $response = $this->topTenResponse($admin, 2026, 3, 2026, 3);

        $response->assertOk()
            ->assertJsonCount(10, 'top_ten')
            ->assertJsonPath('top_ten.0.section', 'Seksi 12')
            ->assertJsonPath('top_ten.0.amount', 12000000)
            ->assertJsonPath('top_ten.9.section', 'Seksi 03')
            ->assertJsonCount(10, 'top_ten_maintenance')
            ->assertJsonPath('top_ten_maintenance.0.section', 'Seksi 12')
            ->assertJsonPath('top_ten_maintenance.9.section', 'Seksi 03')
            ->assertJsonMissing(['section' => 'Seksi Di Luar Periode']);
    }

    public function test_maintenance_top_ten_filters_by_budget_verification_category_without_changing_general(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $maintenance = $this->createHpp($admin, 'TOPTEN-MAINTENANCE', 'Seksi A', 500000000, Hpp::STATUS_APPROVED, '2026-05-01 08:00:00');
        $capex = $this->createHpp($admin, 'TOPTEN-CAPEX', 'Seksi A', 200000000, Hpp::STATUS_APPROVED, '2026-05-02 08:00:00');
        $otherMaintenance = $this->createHpp($admin, 'TOPTEN-MAINTENANCE-B', 'Seksi B', 300000000, Hpp::STATUS_IN_REVIEW, '2026-05-03 08:00:00');
        $nonMaintenance = $this->createHpp($admin, 'TOPTEN-NON-MAINTENANCE', 'Seksi C', 400000000, Hpp::STATUS_APPROVED, '2026-05-04 08:00:00');
        $overhaul = $this->createHpp($admin, 'TOPTEN-OVERHAUL', 'Seksi D', 600000000, Hpp::STATUS_APPROVED, '2026-05-05 08:00:00');
        $this->createHpp($admin, 'TOPTEN-NO-BUDGET', 'Seksi E', 100000000, Hpp::STATUS_APPROVED, '2026-05-06 08:00:00');

        $this->createBudgetVerification($maintenance, $admin, 'pemeliharaan');
        $this->createBudgetVerification($capex, $admin, 'capex');
        $this->createBudgetVerification($otherMaintenance, $admin, 'pemeliharaan');
        $this->createBudgetVerification($nonMaintenance, $admin, 'non pemeliharaan');
        $this->createBudgetVerification($overhaul, $admin, BudgetVerification::COST_CATEGORY_OVERHAUL_TONASA_4);

        $response = $this->topTenResponse($admin, 2026, 5, 2026, 5);

        $response->assertOk()
            ->assertJsonPath('top_ten.0.section', 'Seksi A')
            ->assertJsonPath('top_ten.0.amount', 700000000)
            ->assertJsonFragment(['section' => 'Seksi E', 'amount' => 100000000])
            ->assertJsonCount(2, 'top_ten_maintenance')
            ->assertJsonPath('top_ten_maintenance.0.section', 'Seksi A')
            ->assertJsonPath('top_ten_maintenance.0.amount', 500000000)
            ->assertJsonPath('top_ten_maintenance.1.section', 'Seksi B')
            ->assertJsonPath('top_ten_maintenance.1.amount', 300000000);
    }

    public function test_maintenance_filter_is_applied_after_latest_submitted_hpp_is_selected(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $order = $this->createOrder($admin, 'TOPTEN-LATEST-CATEGORY', 'Seksi Revisi');
        $oldMaintenance = $this->createHppForOrder($order, $admin, 100000000, Hpp::STATUS_REJECTED, '2026-06-01 08:00:00');
        $latestCapex = $this->createHppForOrder($order, $admin, 150000000, Hpp::STATUS_IN_REVIEW, '2026-06-02 08:00:00');

        $this->createBudgetVerification($oldMaintenance, $admin, 'pemeliharaan');
        $this->createBudgetVerification($latestCapex, $admin, 'capex');

        $response = $this->topTenResponse($admin, 2026, 6, 2026, 6);

        $response->assertOk()
            ->assertJsonCount(1, 'top_ten')
            ->assertJsonPath('top_ten.0.amount', 150000000)
            ->assertJsonCount(0, 'top_ten_maintenance');
    }

    public function test_manual_realization_is_merged_with_system_section_before_sorting_and_limit(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $agreement = $this->createAgreement($admin, 'OA-TOP-TEN-MANUAL', OutlineAgreement::STATUS_ACTIVE);
        $hpp = $this->createHpp(
            $admin,
            'TOPTEN-MERGED-MANUAL',
            'CUS Maintenance',
            700000000,
            Hpp::STATUS_APPROVED,
            '2026-08-01 08:00:00',
        );
        $this->createBudgetVerification($hpp, $admin, 'pemeliharaan');
        $agreement->monthlyRealizations()->create([
            'year' => 2026,
            'month' => 8,
            'kategori_biaya' => 'pemeliharaan',
            'unit_kerja' => 'Power Plant Maintenance',
            'seksi' => ' CUS Maintenance ',
            'amount' => 500000000,
        ]);
        $agreement->monthlyRealizations()->create([
            'year' => 2026,
            'month' => 8,
            'kategori_biaya' => 'capex',
            'unit_kerja' => 'Packing',
            'seksi' => 'Legacy Packing',
            'amount' => 1500000000,
        ]);

        $response = $this->topTenResponse($admin, 2026, 8, 2026, 8);

        $response->assertOk()
            ->assertJsonPath('top_ten.0.section', 'Legacy Packing')
            ->assertJsonPath('top_ten.0.amount', 1500000000)
            ->assertJsonPath('top_ten.1.section', 'CUS Maintenance')
            ->assertJsonPath('top_ten.1.amount', 1200000000)
            ->assertJsonCount(1, 'top_ten_maintenance')
            ->assertJsonPath('top_ten_maintenance.0.section', 'CUS Maintenance')
            ->assertJsonPath('top_ten_maintenance.0.amount', 1200000000);
    }

    public function test_manual_top_ten_ignores_unknown_section_inactive_agreement_and_outside_period(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $active = $this->createAgreement($admin, 'OA-TOP-TEN-ACTIVE', OutlineAgreement::STATUS_ACTIVE);
        $inactive = $this->createAgreement($admin, 'OA-TOP-TEN-CLOSED', OutlineAgreement::STATUS_CLOSED);

        foreach ([
            [$active, 2026, 11, 'Dalam Periode', 300],
            [$active, 2027, 2, 'Lintas Tahun', 400],
            [$active, 2027, 3, 'Di Luar Periode', 900],
            [$inactive, 2026, 12, 'OA Tidak Aktif', 1000],
            [$active, 2026, 12, null, 2000],
        ] as [$agreement, $year, $month, $section, $amount]) {
            $agreement->monthlyRealizations()->create([
                'year' => $year,
                'month' => $month,
                'kategori_biaya' => 'pemeliharaan',
                'unit_kerja' => $section ? 'Unit Manual' : null,
                'seksi' => $section,
                'amount' => $amount,
            ]);
        }

        $response = $this->topTenResponse($admin, 2026, 11, 2027, 2);

        $response->assertOk()
            ->assertJsonCount(2, 'top_ten')
            ->assertJsonPath('top_ten.0.section', 'Lintas Tahun')
            ->assertJsonPath('top_ten.1.section', 'Dalam Periode')
            ->assertJsonMissing(['section' => 'Di Luar Periode'])
            ->assertJsonMissing(['section' => 'OA Tidak Aktif']);
    }

    public function test_limit_is_applied_after_manual_amount_promotes_an_hpp_section_outside_the_original_top_ten(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $agreement = $this->createAgreement($admin, 'OA-TOP-TEN-RANKING', OutlineAgreement::STATUS_ACTIVE);

        for ($rank = 1; $rank <= 11; $rank++) {
            $this->createHpp(
                $admin,
                'TOPTEN-RANK-'.$rank,
                'Seksi Ranking '.$rank,
                (12 - $rank) * 1000000,
                Hpp::STATUS_APPROVED,
                '2026-08-01 08:00:00',
            );
        }

        $agreement->monthlyRealizations()->create([
            'year' => 2026,
            'month' => 8,
            'kategori_biaya' => 'capex',
            'unit_kerja' => 'Unit Ranking',
            'seksi' => 'Seksi Ranking 11',
            'amount' => 100000000,
        ]);

        $response = $this->topTenResponse($admin, 2026, 8, 2026, 8);

        $response->assertOk()
            ->assertJsonCount(10, 'top_ten')
            ->assertJsonPath('top_ten.0.section', 'Seksi Ranking 11')
            ->assertJsonPath('top_ten.0.amount', 101000000)
            ->assertJsonMissing(['section' => 'Seksi Ranking 10']);
    }

    private function topTenResponse(
        User $admin,
        int $startYear,
        int $startMonth,
        int $endYear,
        int $endMonth,
    ): TestResponse {
        return $this->actingAs($admin)->getJson(route('admin.dashboard.realization-chart', [
            'startYear' => $startYear,
            'endYear' => $endYear,
            'startMonth' => $startMonth,
            'endMonth' => $endMonth,
            'includeTopTen' => 1,
        ]));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createHpp(
        User $admin,
        string $orderNumber,
        string $section,
        int $amount,
        string $status,
        ?string $submittedAt,
        array $attributes = [],
    ): Hpp {
        $order = $this->createOrder($admin, $orderNumber, $section);

        return $this->createHppForOrder($order, $admin, $amount, $status, $submittedAt, $attributes);
    }

    private function createOrder(User $admin, string $orderNumber, string $section): Order
    {
        return Order::query()->create([
            'nomor_order' => $orderNumber,
            'nama_pekerjaan' => 'Pekerjaan '.$orderNumber,
            'unit_kerja' => 'Unit Pengujian Dashboard',
            'seksi' => $section,
            'deskripsi' => 'Pekerjaan pengujian Top Ten Dashboard.',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-01-01',
            'target_selesai' => '2026-12-31',
            'created_by' => $admin->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createHppForOrder(
        Order $order,
        User $admin,
        int $amount,
        string $status,
        ?string $submittedAt,
        array $attributes = [],
    ): Hpp {
        return Hpp::query()->create(array_merge([
            'order_id' => $order->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Dalam',
            'nilai_hpp_bucket' => 'under',
            'total_keseluruhan' => $amount,
            'status' => $status,
            'submitted_at' => $submittedAt,
            'created_by' => $admin->id,
        ], $attributes));
    }

    private function createBudgetVerification(Hpp $hpp, User $admin, string $costCategory): BudgetVerification
    {
        return BudgetVerification::query()->create([
            'order_id' => $hpp->order_id,
            'hpp_id' => $hpp->id,
            'status_anggaran' => BudgetVerification::STATUS_AVAILABLE,
            'kategori_item' => 'jasa',
            'kategori_biaya' => $costCategory,
            'cost_element' => '65340001',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }

    private function createAgreement(User $admin, string $number, string $status): OutlineAgreement
    {
        $department = Department::query()->firstOrCreate(['name' => 'Top Ten Manual']);
        $unitWork = UnitWork::query()->firstOrCreate([
            'department_id' => $department->id,
            'name' => 'Top Ten Manual Unit',
        ]);

        return OutlineAgreement::query()->create([
            'nomor_oa' => $number,
            'unit_work_id' => $unitWork->id,
            'jenis_kontrak' => 'Fabrikasi',
            'nama_kontrak' => $number,
            'nilai_kontrak_awal' => 10000000000,
            'periode_awal_start' => '2026-01-01',
            'periode_awal_end' => '2027-12-31',
            'current_total_nilai' => 10000000000,
            'current_period_start' => '2026-01-01',
            'current_period_end' => '2027-12-31',
            'status' => $status,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }
}
