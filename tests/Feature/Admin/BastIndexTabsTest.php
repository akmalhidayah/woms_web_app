<?php

namespace Tests\Feature\Admin;

use App\Models\Garansi;
use App\Models\LhppBast;
use App\Support\BastIndexTabs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BastIndexTabsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_tabs_classify_each_termin_one_cycle_once(): void
    {
        $pendingQc = $this->bast('ADMIN-ACTION-QC', ['quality_control_status' => 'pending']);
        $rejected = $this->bast('ADMIN-ACTION-REJECTED', ['approval_status' => 'rejected']);
        $inProgress = $this->bast('ADMIN-PROGRESS', [
            'quality_control_status' => 'approved',
            'approval_status' => 'in_review',
        ]);
        $approved = $this->bast('ADMIN-APPROVED', [
            'quality_control_status' => 'approved',
            'approval_status' => 'approved',
        ]);
        $history = $this->bast('ADMIN-HISTORY', [
            'quality_control_status' => 'approved',
            'approval_status' => 'approved',
            'termin1_status' => 'sudah',
        ], 0);

        $tabs = app(BastIndexTabs::class);

        $this->assertSame([$pendingQc->id, $rejected->id], $this->ids($tabs, BastIndexTabs::TAB_ACTION));
        $this->assertSame([$inProgress->id], $this->ids($tabs, BastIndexTabs::TAB_IN_PROGRESS));
        $this->assertSame([$approved->id], $this->ids($tabs, BastIndexTabs::TAB_APPROVED));
        $this->assertSame([$history->id], $this->ids($tabs, BastIndexTabs::TAB_HISTORY));
        $this->assertSame([
            'action' => 2,
            'in_progress' => 1,
            'approved' => 1,
            'history' => 1,
        ], $tabs->counts(BastIndexTabs::CONTEXT_ADMIN));
    }

    public function test_admin_keeps_paid_warranty_cycle_without_termin_two_in_approved(): void
    {
        $bast = $this->bast('ADMIN-NEEDS-T2', [
            'quality_control_status' => 'approved',
            'approval_status' => 'approved',
            'termin1_status' => 'sudah',
        ], 3);

        $this->assertSame([$bast->id], $this->ids(app(BastIndexTabs::class), BastIndexTabs::TAB_APPROVED));
    }

    public function test_admin_history_requires_completed_payment_cycle(): void
    {
        $parent = $this->bast('ADMIN-T2-HISTORY', [
            'quality_control_status' => 'approved',
            'approval_status' => 'approved',
            'termin1_status' => 'sudah',
            'termin2_status' => 'sudah',
        ], 3);
        $this->terminTwo($parent, 'approved');

        $this->assertSame([$parent->id], $this->ids(app(BastIndexTabs::class), BastIndexTabs::TAB_HISTORY));
    }

    public function test_invalid_tab_falls_back_to_action_and_view_keeps_existing_headers(): void
    {
        $tabs = app(BastIndexTabs::class);
        $source = file_get_contents(resource_path('views/admin/lhpp/index.blade.php'));

        $this->assertSame(BastIndexTabs::TAB_ACTION, $tabs->normalize('invalid'));
        $this->assertStringContainsString('Quality Control / Approval', $source);
        $this->assertStringContainsString('PDF BAST', $source);
        $this->assertStringNotContainsString('Terapkan', $source);
        $this->assertStringNotContainsString('>Reset<', $source);
    }

    /** @return list<int> */
    private function ids(BastIndexTabs $tabs, string $tab): array
    {
        $query = LhppBast::query()->where('termin_type', 'termin_1');

        return $tabs->apply($query, $tab, BastIndexTabs::CONTEXT_ADMIN)
            ->orderBy('id')->pluck('id')->all();
    }

    private function bast(string $number, array $attributes = [], int $warrantyMonths = 0): LhppBast
    {
        $bast = LhppBast::query()->create(array_merge([
            'termin_type' => 'termin_1',
            'nomor_order' => $number,
            'tanggal_bast' => '2026-08-01',
            'quality_control_status' => 'approved',
            'approval_status' => 'approved',
            'termin1_status' => 'belum',
            'termin2_status' => 'belum',
            'total_aktual_biaya' => '1000000.00',
        ], $attributes));

        Garansi::query()->create([
            'lhpp_bast_id' => $bast->id,
            'garansi_months' => $warrantyMonths,
            'start_date' => '2026-08-01',
        ]);

        return $bast;
    }

    private function terminTwo(LhppBast $parent, string $status): LhppBast
    {
        return LhppBast::query()->create([
            'termin_type' => 'termin_2',
            'parent_lhpp_bast_id' => $parent->id,
            'nomor_order' => $parent->nomor_order,
            'tanggal_bast' => '2026-08-02',
            'quality_control_status' => 'approved',
            'approval_status' => $status,
            'total_aktual_biaya' => '1000000.00',
        ]);
    }
}
