<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\Garansi;
use App\Models\Hpp;
use App\Models\LhppBast;
use App\Models\LpjPpl;
use App\Models\Order;
use App\Models\OutlineAgreement;
use App\Models\UnitWork;
use App\Models\User;
use App\Services\Admin\DashboardFinancialSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardFinancialSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_general_summary_uses_only_active_agreement_scope_and_latest_eligible_hpp(): void
    {
        $user = User::factory()->create();
        $active = $this->agreement($user, 'OA-ACTIVE', 1_000, OutlineAgreement::STATUS_ACTIVE);
        $inactive = $this->agreement($user, 'OA-CLOSED', 9_000, OutlineAgreement::STATUS_CLOSED);

        $active->monthlyRealizations()->create([
            'year' => 2026,
            'month' => 1,
            'kategori_biaya' => 'pemeliharaan',
            'amount' => 100,
        ]);
        $inactive->monthlyRealizations()->create([
            'year' => 2026,
            'month' => 1,
            'kategori_biaya' => 'pemeliharaan',
            'amount' => 900,
        ]);

        $this->hpp($user, $active, Hpp::STATUS_DRAFT, 200, 'DRAFT');
        $this->hpp($user, $active, Hpp::STATUS_IN_REVIEW, 300, 'REVIEW');
        $this->hpp($user, $active, Hpp::STATUS_APPROVED, 400, 'APPROVED');
        $this->hpp($user, $active, Hpp::STATUS_REJECTED, 500, 'REJECTED');
        $this->hpp($user, $inactive, Hpp::STATUS_APPROVED, 800, 'INACTIVE');

        $revisionOrder = $this->order($user, 'REVISION');
        $this->createHpp($user, $active, $revisionOrder, Hpp::STATUS_APPROVED, 600);
        $this->createHpp($user, $active, $revisionOrder, Hpp::STATUS_REJECTED, 700);

        $summary = app(DashboardFinancialSummaryService::class)->resolve();

        $this->assertSame(1_000, $summary['contract_budget']);
        $this->assertSame(100, $summary['manual_realization']);
        $this->assertSame(0, $summary['system_realization']);
        $this->assertSame(900, $summary['outstanding']);
        $this->assertSame(1_000, $summary['prognosis']);
        $this->assertSame(0, $summary['available_budget']);
    }

    public function test_paid_lpj_ppl_realization_reduces_only_its_related_hpp_outstanding(): void
    {
        $user = User::factory()->create();
        $agreement = $this->agreement($user, 'OA-PAID', 1_000, OutlineAgreement::STATUS_ACTIVE);
        $agreement->monthlyRealizations()->create([
            'year' => 2026,
            'month' => 1,
            'kategori_biaya' => 'capex',
            'amount' => 50,
        ]);

        $withoutWarranty = $this->hpp($user, $agreement, Hpp::STATUS_APPROVED, 100, 'NO-WARRANTY');
        $this->paidBast($user, $withoutWarranty, 98, 98, 0, 0, true, false);

        $withWarranty = $this->hpp($user, $agreement, Hpp::STATUS_APPROVED, 200, 'WITH-WARRANTY');
        $this->paidBast($user, $withWarranty, 190, 180, 10, 6, true, false);

        $summary = app(DashboardFinancialSummaryService::class)->resolve();

        $this->assertSame(278, $summary['system_realization']);
        $this->assertSame(50, $summary['manual_realization']);
        $this->assertSame(328, $summary['realization']);
        $this->assertSame(22, $summary['outstanding']);
        $this->assertSame(350, $summary['prognosis']);
        $this->assertSame(650, $summary['available_budget']);
        $this->assertSame(3_500, $summary['prognosis_percentage_hundredths']);
    }

    public function test_incomplete_payment_package_is_not_recognized_as_system_realization(): void
    {
        $user = User::factory()->create();
        $agreement = $this->agreement($user, 'OA-INCOMPLETE', 500, OutlineAgreement::STATUS_ACTIVE);
        $hpp = $this->hpp($user, $agreement, Hpp::STATUS_APPROVED, 100, 'INCOMPLETE');
        $bast = $this->paidBast($user, $hpp, 100, 100, 0, 0, true, false);
        $bast->lpjPpl()->update(['ppl_document_path_termin1' => null]);

        $summary = app(DashboardFinancialSummaryService::class)->resolve();

        $this->assertSame(0, $summary['system_realization']);
        $this->assertSame(100, $summary['outstanding']);
    }

    public function test_both_warranty_terms_are_capped_at_actual_bast_amount(): void
    {
        $user = User::factory()->create();
        $agreement = $this->agreement($user, 'OA-CAPPED', 500, OutlineAgreement::STATUS_ACTIVE);
        $hpp = $this->hpp($user, $agreement, Hpp::STATUS_APPROVED, 90, 'CAPPED');
        $this->paidBast($user, $hpp, 100, 95, 10, 6, true, true);

        $summary = app(DashboardFinancialSummaryService::class)->resolve();

        $this->assertSame(100, $summary['system_realization']);
        $this->assertSame(0, $summary['outstanding']);
        $this->assertSame(100, $summary['prognosis']);
    }

    private function agreement(User $user, string $number, int $amount, string $status): OutlineAgreement
    {
        $department = Department::query()->firstOrCreate(['name' => 'Dashboard Financial']);
        $unit = UnitWork::query()->firstOrCreate([
            'department_id' => $department->id,
            'name' => 'Dashboard Financial Unit',
        ]);

        return OutlineAgreement::query()->create([
            'nomor_oa' => $number,
            'unit_work_id' => $unit->id,
            'jenis_kontrak' => 'Fabrikasi',
            'nama_kontrak' => $number,
            'nilai_kontrak_awal' => $amount,
            'periode_awal_start' => '2026-01-01',
            'periode_awal_end' => '2026-12-31',
            'current_total_nilai' => $amount,
            'current_period_start' => '2026-01-01',
            'current_period_end' => '2026-12-31',
            'status' => $status,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function hpp(
        User $user,
        OutlineAgreement $agreement,
        string $status,
        int $amount,
        string $suffix,
    ): Hpp {
        return $this->createHpp($user, $agreement, $this->order($user, $suffix), $status, $amount);
    }

    private function order(User $user, string $suffix): Order
    {
        return Order::query()->create([
            'nomor_order' => 'ORDER-'.$suffix,
            'nama_pekerjaan' => 'Pekerjaan '.$suffix,
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Pengujian financial summary.',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-01-01',
            'target_selesai' => '2026-01-31',
            'created_by' => $user->id,
        ]);
    }

    private function createHpp(
        User $user,
        OutlineAgreement $agreement,
        Order $order,
        string $status,
        int $amount,
    ): Hpp {
        return Hpp::query()->create([
            'order_id' => $order->id,
            'outline_agreement_id' => $agreement->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Workshop',
            'nilai_hpp_bucket' => 'under',
            'total_keseluruhan' => $amount,
            'status' => $status,
            'submitted_at' => $status === Hpp::STATUS_DRAFT ? null : '2026-01-02 08:00:00',
            'created_by' => $user->id,
        ]);
    }

    private function paidBast(
        User $user,
        Hpp $hpp,
        int $actual,
        int $terminOne,
        int $terminTwo,
        int $warrantyMonths,
        bool $terminOnePaid,
        bool $terminTwoPaid,
    ): LhppBast {
        $bast = LhppBast::query()->create([
            'order_id' => $hpp->order_id,
            'hpp_id' => $hpp->id,
            'termin_type' => 'termin_1',
            'nomor_order' => $hpp->nomor_order,
            'tanggal_bast' => '2026-01-10',
            'total_aktual_biaya' => $actual,
            'termin_1_nilai' => $terminOne,
            'termin_2_nilai' => $terminTwo,
            'termin1_status' => $terminOnePaid ? 'sudah' : 'belum',
            'termin2_status' => $terminTwoPaid ? 'sudah' : 'belum',
            'created_by' => $user->id,
        ]);

        Garansi::query()->create([
            'order_id' => $hpp->order_id,
            'lhpp_bast_id' => $bast->id,
            'garansi_months' => $warrantyMonths,
            'start_date' => '2026-01-10',
            'end_date' => $warrantyMonths > 0 ? '2026-07-10' : null,
            'created_by' => $user->id,
        ]);

        LpjPpl::query()->create([
            'lhpp_bast_id' => $bast->id,
            'lpj_number_termin1' => 'LPJ-T1',
            'ppl_number_termin1' => 'PPL-T1',
            'lpj_document_path_termin1' => 'lpj/t1.pdf',
            'ppl_document_path_termin1' => 'ppl/t1.pdf',
            'lpj_number_termin2' => 'LPJ-T2',
            'ppl_number_termin2' => 'PPL-T2',
            'lpj_document_path_termin2' => 'lpj/t2.pdf',
            'ppl_document_path_termin2' => 'ppl/t2.pdf',
            'created_by' => $user->id,
        ]);

        return $bast;
    }
}
