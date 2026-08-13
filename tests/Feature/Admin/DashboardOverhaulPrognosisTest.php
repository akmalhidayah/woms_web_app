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

class DashboardOverhaulPrognosisTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_always_returns_three_overhaul_categories_in_stable_order(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->overhaulResponse($admin, 2026, 1, 2026, 1);

        $response->assertOk()
            ->assertJsonCount(3, 'overhaul')
            ->assertJsonPath('overhaul.0', [
                'key' => BudgetVerification::COST_CATEGORY_OVERHAUL_TONASA_2_3,
                'label' => 'Tonasa 2/3',
                'amount' => 0,
            ])
            ->assertJsonPath('overhaul.1', [
                'key' => BudgetVerification::COST_CATEGORY_OVERHAUL_TONASA_4,
                'label' => 'Tonasa 4',
                'amount' => 0,
            ])
            ->assertJsonPath('overhaul.2', [
                'key' => BudgetVerification::COST_CATEGORY_OVERHAUL_TONASA_5,
                'label' => 'Tonasa 5',
                'amount' => 0,
            ]);
    }

    public function test_overhaul_prognosis_aggregates_only_eligible_approved_hpps_in_period(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->createVerifiedHpp(
            $admin,
            'OVERHAUL-T4-A',
            500000000,
            Hpp::STATUS_APPROVED,
            '2026-01-05 08:00:00',
            BudgetVerification::STATUS_AVAILABLE,
            BudgetVerification::COST_CATEGORY_OVERHAUL_TONASA_4,
            'jasa',
        );
        $this->createVerifiedHpp(
            $admin,
            'OVERHAUL-T4-B',
            200000000,
            Hpp::STATUS_APPROVED,
            '2026-01-06 08:00:00',
            BudgetVerification::STATUS_WAITING_BAST,
            BudgetVerification::COST_CATEGORY_OVERHAUL_TONASA_4,
            'spare part',
        );
        $this->createVerifiedHpp(
            $admin,
            'OVERHAUL-T5',
            400000000,
            Hpp::STATUS_APPROVED,
            '2026-01-07 08:00:00',
            BudgetVerification::STATUS_AVAILABLE,
            BudgetVerification::COST_CATEGORY_OVERHAUL_TONASA_5,
            'jasa',
        );
        $this->createVerifiedHpp(
            $admin,
            'OVERHAUL-T23',
            '300000000.60',
            Hpp::STATUS_APPROVED,
            '2026-01-08 08:00:00',
            BudgetVerification::STATUS_AVAILABLE,
            BudgetVerification::COST_CATEGORY_OVERHAUL_TONASA_2_3,
            'spare part',
        );

        foreach ([Hpp::STATUS_DRAFT, Hpp::STATUS_IN_REVIEW, Hpp::STATUS_REJECTED] as $status) {
            $this->createVerifiedHpp(
                $admin,
                'OVERHAUL-EXCLUDED-HPP-'.$status,
                900000000,
                $status,
                '2026-01-09 08:00:00',
                BudgetVerification::STATUS_AVAILABLE,
                BudgetVerification::COST_CATEGORY_OVERHAUL_TONASA_4,
            );
        }

        foreach ([
            BudgetVerification::STATUS_WAITING,
            BudgetVerification::STATUS_UNAVAILABLE,
            null,
        ] as $index => $budgetStatus) {
            $this->createVerifiedHpp(
                $admin,
                'OVERHAUL-EXCLUDED-BUDGET-'.$index,
                800000000,
                Hpp::STATUS_APPROVED,
                '2026-01-10 08:00:00',
                $budgetStatus,
                BudgetVerification::COST_CATEGORY_OVERHAUL_TONASA_5,
            );
        }

        foreach (['pemeliharaan', 'non pemeliharaan', 'capex'] as $category) {
            $this->createVerifiedHpp(
                $admin,
                'OVERHAUL-EXCLUDED-CATEGORY-'.$category,
                700000000,
                Hpp::STATUS_APPROVED,
                '2026-01-11 08:00:00',
                BudgetVerification::STATUS_AVAILABLE,
                $category,
            );
        }

        $this->createVerifiedHpp(
            $admin,
            'OVERHAUL-NOT-SUBMITTED',
            600000000,
            Hpp::STATUS_APPROVED,
            null,
            BudgetVerification::STATUS_AVAILABLE,
            BudgetVerification::COST_CATEGORY_OVERHAUL_TONASA_2_3,
        );
        $this->createVerifiedHpp(
            $admin,
            'OVERHAUL-OUTSIDE-PERIOD',
            500000000,
            Hpp::STATUS_APPROVED,
            '2026-02-01 00:00:00',
            BudgetVerification::STATUS_AVAILABLE,
            BudgetVerification::COST_CATEGORY_OVERHAUL_TONASA_2_3,
        );

        $response = $this->overhaulResponse($admin, 2026, 1, 2026, 1);

        $response->assertOk()
            ->assertJsonPath('overhaul.0.amount', 800000001)
            ->assertJsonPath('overhaul.1.amount', 700000000)
            ->assertJsonPath('overhaul.2.amount', 400000000);
    }

    public function test_dashboard_ajax_response_keeps_legacy_shape_without_include_top_ten(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $combinedResponse = $this->overhaulResponse($admin, 2026, 1, 2026, 1);
        $combinedResponse->assertOk()
            ->assertJsonStructure([
                'realization',
                'top_ten',
                'top_ten_maintenance',
                'overhaul',
            ]);

        $legacyResponse = $this->actingAs($admin)->getJson(route('admin.dashboard.realization-chart', [
            'startYear' => 2026,
            'endYear' => 2026,
            'startMonth' => 1,
            'endMonth' => 1,
        ]));

        $legacyResponse->assertOk();
        $this->assertTrue(array_is_list($legacyResponse->json()));
        $this->assertArrayNotHasKey('overhaul', $legacyResponse->json());
    }

    public function test_overhaul_prognosis_excludes_hpps_from_non_active_outline_agreements(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->createVerifiedHpp(
            $admin,
            'OVERHAUL-ACTIVE-OA',
            100000000,
            Hpp::STATUS_APPROVED,
            '2026-01-05 08:00:00',
            BudgetVerification::STATUS_AVAILABLE,
            BudgetVerification::COST_CATEGORY_OVERHAUL_TONASA_4,
        );
        $this->createVerifiedHpp(
            $admin,
            'OVERHAUL-CLOSED-OA',
            900000000,
            Hpp::STATUS_APPROVED,
            '2026-01-06 08:00:00',
            BudgetVerification::STATUS_AVAILABLE,
            BudgetVerification::COST_CATEGORY_OVERHAUL_TONASA_4,
            'jasa',
            OutlineAgreement::STATUS_CLOSED,
        );

        $this->overhaulResponse($admin, 2026, 1, 2026, 1)
            ->assertOk()
            ->assertJsonPath('overhaul.1.amount', 100000000);
    }

    private function overhaulResponse(
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

    private function createVerifiedHpp(
        User $admin,
        string $orderNumber,
        int|string $amount,
        string $hppStatus,
        ?string $submittedAt,
        ?string $budgetStatus,
        ?string $costCategory,
        string $itemCategory = 'jasa',
        string $agreementStatus = OutlineAgreement::STATUS_ACTIVE,
    ): Hpp {
        $agreement = $this->createAgreement($admin, $orderNumber, $agreementStatus);
        $order = Order::query()->create([
            'nomor_order' => $orderNumber,
            'nama_pekerjaan' => 'Pekerjaan '.$orderNumber,
            'unit_kerja' => 'Unit Pengujian Dashboard',
            'seksi' => 'Seksi Pengujian Dashboard',
            'deskripsi' => 'Pekerjaan pengujian prognosis overhaul.',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-01-01',
            'target_selesai' => '2026-12-31',
            'created_by' => $admin->id,
        ]);
        $hpp = Hpp::query()->create([
            'order_id' => $order->id,
            'outline_agreement_id' => $agreement->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Dalam',
            'nilai_hpp_bucket' => 'under',
            'total_keseluruhan' => $amount,
            'status' => $hppStatus,
            'submitted_at' => $submittedAt,
            'created_by' => $admin->id,
        ]);

        BudgetVerification::query()->create([
            'order_id' => $order->id,
            'hpp_id' => $hpp->id,
            'status_anggaran' => $budgetStatus,
            'kategori_item' => $itemCategory,
            'kategori_biaya' => $costCategory,
            'cost_element' => '65340001',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        return $hpp;
    }

    private function createAgreement(User $admin, string $suffix, string $status): OutlineAgreement
    {
        $department = Department::query()->firstOrCreate(['name' => 'Dashboard Overhaul']);
        $unitWork = UnitWork::query()->firstOrCreate([
            'department_id' => $department->id,
            'name' => 'Dashboard Overhaul Unit',
        ]);

        $number = $status === OutlineAgreement::STATUS_ACTIVE
            ? 'OA-DASHBOARD-OVERHAUL-ACTIVE'
            : 'OA-'.$suffix;

        return OutlineAgreement::query()->firstOrCreate([
            'nomor_oa' => $number,
        ], [
            'unit_work_id' => $unitWork->id,
            'jenis_kontrak' => 'Fabrikasi',
            'nama_kontrak' => 'Kontrak '.$number,
            'nilai_kontrak_awal' => 10000000000,
            'periode_awal_start' => '2026-01-01',
            'periode_awal_end' => '2026-12-31',
            'current_total_nilai' => 10000000000,
            'current_period_start' => '2026-01-01',
            'current_period_end' => '2026-12-31',
            'status' => $status,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }
}
