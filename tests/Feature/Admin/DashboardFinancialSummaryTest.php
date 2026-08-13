<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\BudgetVerification;
use App\Models\Department;
use App\Models\Garansi;
use App\Models\Hpp;
use App\Models\LhppBast;
use App\Models\LpjPpl;
use App\Models\Order;
use App\Models\OutlineAgreement;
use App\Models\OutlineAgreementTarget;
use App\Models\PurchaseOrder;
use App\Models\UnitWork;
use App\Models\User;
use App\Services\Admin\DashboardFinancialSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
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

    public function test_lpj_complete_is_recognized_without_complete_ppl_or_paid_status(): void
    {
        $user = User::factory()->create();
        $agreement = $this->agreement($user, 'OA-INCOMPLETE', 500, OutlineAgreement::STATUS_ACTIVE);
        $hpp = $this->hpp($user, $agreement, Hpp::STATUS_APPROVED, 100, 'INCOMPLETE');
        $bast = $this->paidBast($user, $hpp, 100, 100, 0, 0, true, false);
        $bast->lpjPpl()->update(['ppl_document_path_termin1' => null]);

        $summary = app(DashboardFinancialSummaryService::class)->resolve();

        $this->assertSame(100, $summary['system_realization']);
        $this->assertSame(0, $summary['outstanding']);
        $this->assertSame(100, $summary['lpj_status_amount']);
        $this->assertSame(0, $summary['invoice_status_amount']);
    }

    public function test_both_warranty_terms_are_capped_at_actual_bast_amount(): void
    {
        $user = User::factory()->create();
        $agreement = $this->agreement($user, 'OA-CAPPED', 500, OutlineAgreement::STATUS_ACTIVE);
        $hpp = $this->hpp($user, $agreement, Hpp::STATUS_APPROVED, 90, 'CAPPED');
        $bast = $this->paidBast($user, $hpp, 100, 95, 10, 6, true, true);
        LhppBast::query()->create([
            'order_id' => $hpp->order_id,
            'hpp_id' => $hpp->id,
            'parent_lhpp_bast_id' => $bast->id,
            'termin_type' => 'termin_2',
            'nomor_order' => $hpp->nomor_order,
            'tanggal_bast' => '2026-07-10',
            'total_aktual_biaya' => 100,
            'termin_1_nilai' => 95,
            'termin_2_nilai' => 10,
            'approval_status' => LhppBast::APPROVAL_APPROVED,
            'created_by' => $user->id,
        ]);

        $summary = app(DashboardFinancialSummaryService::class)->resolve();

        $this->assertSame(100, $summary['system_realization']);
        $this->assertSame(0, $summary['outstanding']);
        $this->assertSame(100, $summary['prognosis']);
    }

    public function test_realization_and_invoice_require_approved_parent_and_approved_termin_two(): void
    {
        $user = User::factory()->create();
        $agreement = $this->agreement($user, 'OA-APPROVAL-GUARD', 1_000, OutlineAgreement::STATUS_ACTIVE);
        $hpp = $this->hpp($user, $agreement, Hpp::STATUS_APPROVED, 100, 'APPROVAL-GUARD');
        $bast = $this->paidBast($user, $hpp, 100, 95, 5, 6, false, false);
        $terminTwo = LhppBast::query()->create([
            'order_id' => $hpp->order_id,
            'hpp_id' => $hpp->id,
            'parent_lhpp_bast_id' => $bast->id,
            'termin_type' => 'termin_2',
            'nomor_order' => $hpp->nomor_order,
            'tanggal_bast' => '2026-01-10',
            'total_aktual_biaya' => 100,
            'termin_1_nilai' => 95,
            'termin_2_nilai' => 5,
            'approval_status' => LhppBast::APPROVAL_IN_REVIEW,
            'created_by' => $user->id,
        ]);

        $bast->update(['approval_status' => LhppBast::APPROVAL_IN_REVIEW]);
        $inReview = app(DashboardFinancialSummaryService::class)->resolve();
        $this->assertSame(0, $inReview['system_realization']);
        $this->assertSame(0, $inReview['invoice_status_amount']);

        $bast->update(['approval_status' => LhppBast::APPROVAL_REJECTED]);
        $rejected = app(DashboardFinancialSummaryService::class)->resolve();
        $this->assertSame(0, $rejected['system_realization']);
        $this->assertSame(0, $rejected['invoice_status_amount']);

        $bast->update(['approval_status' => LhppBast::APPROVAL_APPROVED]);
        $terminTwoInReview = app(DashboardFinancialSummaryService::class)->resolve();
        $this->assertSame(95, $terminTwoInReview['system_realization']);
        $this->assertSame(95, $terminTwoInReview['invoice_status_amount']);

        $terminTwo->update(['approval_status' => LhppBast::APPROVAL_APPROVED]);
        $fullyApproved = app(DashboardFinancialSummaryService::class)->resolve();
        $this->assertSame(100, $fullyApproved['system_realization']);
        $this->assertSame(100, $fullyApproved['invoice_status_amount']);
    }

    public function test_realization_requires_both_lpj_number_and_document_path(): void
    {
        $user = User::factory()->create();
        $agreement = $this->agreement($user, 'OA-LPJ-COMPLETENESS', 1_000, OutlineAgreement::STATUS_ACTIVE);
        $hpp = $this->hpp($user, $agreement, Hpp::STATUS_APPROVED, 100, 'LPJ-COMPLETENESS');
        $bast = $this->paidBast($user, $hpp, 100, 100, 0, 0, false, false);

        $bast->lpjPpl()->update(['lpj_document_path_termin1' => null]);
        $missingDocument = app(DashboardFinancialSummaryService::class)->resolve();
        $this->assertSame(0, $missingDocument['system_realization']);

        $bast->lpjPpl()->update([
            'lpj_number_termin1' => null,
            'lpj_document_path_termin1' => 'lpj/t1.pdf',
        ]);
        $missingNumber = app(DashboardFinancialSummaryService::class)->resolve();
        $this->assertSame(0, $missingNumber['system_realization']);

        $bast->lpjPpl()->update(['lpj_number_termin1' => 'LPJ-T1']);
        $complete = app(DashboardFinancialSummaryService::class)->resolve();
        $this->assertSame(100, $complete['system_realization']);
    }

    public function test_maintenance_summary_filters_manual_and_latest_hpp_by_budget_verification_category(): void
    {
        $user = User::factory()->create();
        $agreement = $this->agreement($user, 'OA-MAINTENANCE', 2_000, OutlineAgreement::STATUS_ACTIVE);
        $agreement->monthlyRealizations()->create([
            'year' => 2026,
            'month' => 1,
            'kategori_biaya' => 'pemeliharaan',
            'amount' => 100,
        ]);
        $agreement->monthlyRealizations()->create([
            'year' => 2026,
            'month' => 1,
            'kategori_biaya' => 'capex',
            'amount' => 900,
        ]);

        $maintenance = $this->hpp($user, $agreement, Hpp::STATUS_APPROVED, 500, 'MAINTENANCE');
        $capex = $this->hpp($user, $agreement, Hpp::STATUS_APPROVED, 700, 'CAPEX');
        $this->hpp($user, $agreement, Hpp::STATUS_DRAFT, 800, 'UNCATEGORIZED');
        $this->verifyCategory($maintenance, $user, 'pemeliharaan');
        $this->verifyCategory($capex, $user, 'capex');

        $summary = app(DashboardFinancialSummaryService::class)->resolveForCategory('pemeliharaan');
        $general = app(DashboardFinancialSummaryService::class)->resolve();

        $this->assertSame(100, $summary['manual_realization']);
        $this->assertSame(500, $summary['outstanding']);
        $this->assertSame(600, $summary['prognosis']);
        $this->assertSame(2_000, $general['outstanding']);
    }

    public function test_maintenance_target_uses_current_year_and_approved_bast_enters_lpj_ppl_process(): void
    {
        Carbon::setTestNow('2026-06-01 08:00:00');

        try {
            $user = User::factory()->create();
            $agreement = $this->agreement($user, 'OA-MAINTENANCE-TARGET', 2_000, OutlineAgreement::STATUS_ACTIVE);
            OutlineAgreementTarget::query()->create([
                'outline_agreement_id' => $agreement->id,
                'tahun' => 2026,
                'nilai_target' => 1_500,
            ]);
            OutlineAgreementTarget::query()->create([
                'outline_agreement_id' => $agreement->id,
                'tahun' => 2027,
                'nilai_target' => 9_000,
            ]);

            $hpp = $this->hpp($user, $agreement, Hpp::STATUS_APPROVED, 500, 'LPJ-PROCESS');
            $this->verifyCategory($hpp, $user, 'pemeliharaan');
            $bast = LhppBast::query()->create([
                'order_id' => $hpp->order_id,
                'hpp_id' => $hpp->id,
                'termin_type' => 'termin_1',
                'nomor_order' => $hpp->nomor_order,
                'tanggal_bast' => '2026-01-10',
                'total_aktual_biaya' => 500,
                'termin_1_nilai' => 500,
                'termin_2_nilai' => 0,
                'termin1_status' => 'belum',
                'termin2_status' => 'belum',
                'approval_status' => LhppBast::APPROVAL_APPROVED,
                'created_by' => $user->id,
            ]);
            Garansi::query()->create([
                'order_id' => $hpp->order_id,
                'lhpp_bast_id' => $bast->id,
                'garansi_months' => 0,
                'start_date' => '2026-01-10',
                'created_by' => $user->id,
            ]);

            $summary = app(DashboardFinancialSummaryService::class)->resolveMaintenanceSummary(Carbon::now()->year);

            $this->assertSame(2026, $summary['target_year']);
            $this->assertSame(1_500, $summary['annual_target']);
            $this->assertSame(500, $summary['outstanding']);
            $this->assertSame(0, $summary['lpj_status_amount']);
            $this->assertSame(0, $summary['invoice_status_amount']);
            $this->assertSame(500, $summary['prognosis']);
            $this->assertSame(1_000, $summary['remaining_target']);
            $this->assertSame(3_333, $summary['target_usage_percentage_hundredths']);
            $this->assertSame($summary['realization'], $summary['already_realized']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_maintenance_warranty_realization_and_invoice_follow_lpj_and_ppl_documents(): void
    {
        $user = User::factory()->create();
        $agreement = $this->agreement($user, 'OA-MAINTENANCE-T2', 1_000, OutlineAgreement::STATUS_ACTIVE);
        $hpp = $this->hpp($user, $agreement, Hpp::STATUS_APPROVED, 200, 'MAINTENANCE-T2');
        $this->verifyCategory($hpp, $user, 'pemeliharaan');
        $bast = $this->paidBast($user, $hpp, 200, 190, 10, 6, true, false);
        $bast->update(['approval_status' => LhppBast::APPROVAL_APPROVED]);

        $beforeTerminTwo = app(DashboardFinancialSummaryService::class)->resolveForCategory('pemeliharaan');
        $this->assertSame(190, $beforeTerminTwo['system_realization']);
        $this->assertSame(10, $beforeTerminTwo['outstanding']);
        $this->assertSame(0, $beforeTerminTwo['lpj_status_amount']);
        $this->assertSame(190, $beforeTerminTwo['invoice_status_amount']);

        LhppBast::query()->create([
            'order_id' => $hpp->order_id,
            'hpp_id' => $hpp->id,
            'parent_lhpp_bast_id' => $bast->id,
            'termin_type' => 'termin_2',
            'nomor_order' => $hpp->nomor_order,
            'tanggal_bast' => '2026-07-10',
            'total_aktual_biaya' => 200,
            'termin_1_nilai' => 190,
            'termin_2_nilai' => 10,
            'approval_status' => LhppBast::APPROVAL_APPROVED,
            'created_by' => $user->id,
        ]);

        $afterTerminTwoDocumentsComplete = app(DashboardFinancialSummaryService::class)
            ->resolveForCategory('pemeliharaan');
        $this->assertSame(200, $afterTerminTwoDocumentsComplete['system_realization']);
        $this->assertSame(0, $afterTerminTwoDocumentsComplete['outstanding']);
        $this->assertSame(0, $afterTerminTwoDocumentsComplete['lpj_status_amount']);
        $this->assertSame(200, $afterTerminTwoDocumentsComplete['invoice_status_amount']);
    }

    public function test_approved_warranty_term_two_with_complete_lpj_and_incomplete_ppl_is_monitored_as_lpj_status(): void
    {
        $user = User::factory()->create();
        $agreement = $this->agreement($user, 'OA-MAINTENANCE-T2-LPJ', 1_000, OutlineAgreement::STATUS_ACTIVE);
        $hpp = $this->hpp($user, $agreement, Hpp::STATUS_APPROVED, 200, 'MAINTENANCE-T2-LPJ');
        $this->verifyCategory($hpp, $user, 'pemeliharaan');
        $bast = $this->paidBast($user, $hpp, 200, 190, 10, 6, false, false);
        $bast->update(['approval_status' => LhppBast::APPROVAL_APPROVED]);
        $bast->lpjPpl()->update([
            'ppl_number_termin2' => null,
            'ppl_document_path_termin2' => null,
        ]);
        LhppBast::query()->create([
            'order_id' => $hpp->order_id,
            'hpp_id' => $hpp->id,
            'parent_lhpp_bast_id' => $bast->id,
            'termin_type' => 'termin_2',
            'nomor_order' => $hpp->nomor_order,
            'tanggal_bast' => '2026-07-10',
            'total_aktual_biaya' => 200,
            'termin_1_nilai' => 190,
            'termin_2_nilai' => 10,
            'approval_status' => LhppBast::APPROVAL_APPROVED,
            'created_by' => $user->id,
        ]);

        $summary = app(DashboardFinancialSummaryService::class)->resolveForCategory('pemeliharaan');

        $this->assertSame(200, $summary['system_realization']);
        $this->assertSame(0, $summary['outstanding']);
        $this->assertSame(10, $summary['lpj_status_amount']);
        $this->assertSame(190, $summary['invoice_status_amount']);
        $this->assertSame(
            $summary['realization'] + $summary['outstanding'],
            $summary['prognosis'],
        );
    }

    public function test_non_maintenance_summary_classifies_outstanding_into_five_exclusive_stages(): void
    {
        $user = User::factory()->create();
        $agreement = $this->agreement($user, 'OA-NON-MAINTENANCE-STAGES', 5_000, OutlineAgreement::STATUS_ACTIVE);
        $agreement->monthlyRealizations()->create([
            'year' => 2026,
            'month' => 1,
            'kategori_biaya' => 'non pemeliharaan',
            'amount' => 200,
        ]);

        $draft = $this->hpp($user, $agreement, Hpp::STATUS_DRAFT, 100, 'NON-MAINT-DRAFT');
        $this->verifyCategory($draft, $user, 'non pemeliharaan');

        $inReview = $this->hpp($user, $agreement, Hpp::STATUS_IN_REVIEW, 200, 'NON-MAINT-REVIEW');
        $this->verifyCategory($inReview, $user, 'non pemeliharaan');

        $withoutPo = $this->hpp($user, $agreement, Hpp::STATUS_APPROVED, 300, 'NON-MAINT-NO-PO');
        $this->verifyCategory($withoutPo, $user, 'non pemeliharaan');

        $waitingBast = $this->hpp($user, $agreement, Hpp::STATUS_APPROVED, 400, 'NON-MAINT-PO');
        $this->verifyCategory($waitingBast, $user, 'non pemeliharaan');
        $this->purchaseOrder($user, $waitingBast, 'PO-NON-MAINT', 'po/non-maint.pdf');

        $lpjProcess = $this->hpp($user, $agreement, Hpp::STATUS_APPROVED, 500, 'NON-MAINT-LPJ');
        $this->verifyCategory($lpjProcess, $user, 'non pemeliharaan');
        $this->purchaseOrder($user, $lpjProcess, 'PO-NON-MAINT-LPJ', 'po/non-maint-lpj.pdf');
        $this->parentBast($user, $lpjProcess, LhppBast::APPROVAL_APPROVED, 500, 500, 0);

        $capex = $this->hpp($user, $agreement, Hpp::STATUS_APPROVED, 900, 'CAPEX-EXCLUDED');
        $this->verifyCategory($capex, $user, 'capex');

        $summary = app(DashboardFinancialSummaryService::class)->resolveForCategory('non pemeliharaan');

        $this->assertSame(0, $summary['system_realization']);
        $this->assertSame(200, $summary['manual_realization']);
        $this->assertSame(200, $summary['realization']);
        $this->assertSame(1_500, $summary['outstanding']);
        $this->assertSame(1_700, $summary['prognosis']);
        $this->assertSame([
            'hpp_draft' => 100,
            'hpp_in_review' => 200,
            'hpp_approved' => 300,
            'purchase_order' => 400,
            'lpj_process' => 500,
        ], $summary['outstanding_stages']);
        $this->assertSame($summary['outstanding'], $summary['classified_outstanding']);
        $this->assertSame(0, $summary['unclassified_outstanding']);
    }

    public function test_incomplete_purchase_orders_and_unapproved_basts_remain_in_the_correct_stage(): void
    {
        $user = User::factory()->create();
        $agreement = $this->agreement($user, 'OA-CAPEX-STAGES', 5_000, OutlineAgreement::STATUS_ACTIVE);

        foreach ([
            ['NO-PO', 10, null, null],
            ['NULL-NUMBER', 20, null, 'po/null-number.pdf'],
            ['BLANK-NUMBER', 30, '   ', 'po/blank-number.pdf'],
            ['NO-DOCUMENT', 40, 'PO-NO-DOCUMENT', null],
        ] as [$suffix, $amount, $number, $document]) {
            $hpp = $this->hpp($user, $agreement, Hpp::STATUS_APPROVED, $amount, 'CAPEX-'.$suffix);
            $this->verifyCategory($hpp, $user, 'capex');
            if ($suffix !== 'NO-PO') {
                $this->purchaseOrder($user, $hpp, $number, $document);
            }
        }

        foreach ([
            ['NO-BAST', 50, null],
            ['IN-REVIEW', 60, LhppBast::APPROVAL_IN_REVIEW],
            ['REJECTED', 70, LhppBast::APPROVAL_REJECTED],
        ] as [$suffix, $amount, $approvalStatus]) {
            $hpp = $this->hpp($user, $agreement, Hpp::STATUS_APPROVED, $amount, 'CAPEX-'.$suffix);
            $this->verifyCategory($hpp, $user, 'capex');
            $this->purchaseOrder($user, $hpp, 'PO-'.$suffix, 'po/'.$suffix.'.pdf');
            if ($approvalStatus !== null) {
                $this->parentBast($user, $hpp, $approvalStatus, $amount, $amount, 0);
            }
        }

        $approved = $this->hpp($user, $agreement, Hpp::STATUS_APPROVED, 80, 'CAPEX-BAST-APPROVED');
        $this->verifyCategory($approved, $user, 'capex');
        $this->purchaseOrder($user, $approved, 'PO-BAST-APPROVED', 'po/approved.pdf');
        $this->parentBast($user, $approved, LhppBast::APPROVAL_APPROVED, 80, 80, 0);

        $summary = app(DashboardFinancialSummaryService::class)->resolveForCategory('capex');

        $this->assertSame([
            'hpp_draft' => 0,
            'hpp_in_review' => 0,
            'hpp_approved' => 100,
            'purchase_order' => 180,
            'lpj_process' => 80,
        ], $summary['outstanding_stages']);
        $this->assertSame(360, $summary['outstanding']);
        $this->assertSame(360, $summary['classified_outstanding']);
        $this->assertSame(0, $summary['unclassified_outstanding']);
    }

    public function test_warranty_residual_after_termin_one_lpj_stays_in_lpj_process(): void
    {
        $user = User::factory()->create();
        $agreement = $this->agreement($user, 'OA-CAPEX-RESIDUAL', 1_000, OutlineAgreement::STATUS_ACTIVE);
        $hpp = $this->hpp($user, $agreement, Hpp::STATUS_APPROVED, 100, 'CAPEX-RESIDUAL');
        $this->verifyCategory($hpp, $user, 'capex');
        $this->purchaseOrder($user, $hpp, 'PO-CAPEX-RESIDUAL', 'po/capex-residual.pdf');
        $bast = $this->paidBast($user, $hpp, 100, 95, 5, 6, false, false);
        $bast->update(['approval_status' => LhppBast::APPROVAL_APPROVED]);
        $bast->lpjPpl()->update([
            'lpj_number_termin2' => null,
            'lpj_document_path_termin2' => null,
            'ppl_number_termin2' => null,
            'ppl_document_path_termin2' => null,
        ]);

        $summary = app(DashboardFinancialSummaryService::class)->resolveForCategory('capex');

        $this->assertSame(95, $summary['system_realization']);
        $this->assertSame(5, $summary['outstanding']);
        $this->assertSame(5, $summary['outstanding_stages']['lpj_process']);
        $this->assertSame(100, $summary['prognosis']);
    }

    public function test_dashboard_renders_category_totals_and_two_distinct_outstanding_charts(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $this->agreement($admin, 'OA-CATEGORY-UI', 1_000, OutlineAgreement::STATUS_ACTIVE);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('id="nonMaintenanceOutstandingChart"', false)
            ->assertSee('id="capexOutstandingChart"', false)
            ->assertSee('HPP Approved')
            ->assertSee('Purchase Order')
            ->assertSee('LPJ Process')
            ->assertDontSee('WAITING APPROVAL')
            ->assertDontSee('Rp -');
    }

    private function verifyCategory(Hpp $hpp, User $user, string $category): BudgetVerification
    {
        return BudgetVerification::query()->create([
            'order_id' => $hpp->order_id,
            'hpp_id' => $hpp->id,
            'kategori_biaya' => $category,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function purchaseOrder(
        User $user,
        Hpp $hpp,
        ?string $number,
        ?string $documentPath,
    ): PurchaseOrder {
        return PurchaseOrder::query()->create([
            'order_id' => $hpp->order_id,
            'hpp_id' => $hpp->id,
            'purchase_order_number' => $number,
            'po_document_path' => $documentPath,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    private function parentBast(
        User $user,
        Hpp $hpp,
        string $approvalStatus,
        int $actual,
        int $terminOne,
        int $terminTwo,
    ): LhppBast {
        return LhppBast::query()->create([
            'order_id' => $hpp->order_id,
            'hpp_id' => $hpp->id,
            'termin_type' => 'termin_1',
            'nomor_order' => $hpp->nomor_order,
            'tanggal_bast' => '2026-01-10',
            'total_aktual_biaya' => $actual,
            'termin_1_nilai' => $terminOne,
            'termin_2_nilai' => $terminTwo,
            'approval_status' => $approvalStatus,
            'created_by' => $user->id,
        ]);
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
            'approval_status' => LhppBast::APPROVAL_APPROVED,
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
