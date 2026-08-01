<?php

namespace Tests\Feature\Pkm;

use App\Models\Garansi;
use App\Models\LhppBast;
use App\Support\BastIndexTabs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BastIndexTabsTest extends TestCase
{
    use RefreshDatabase;

    public function test_pkm_tabs_distinguish_waiting_process_and_required_termin_two_action(): void
    {
        $qcPending = $this->bast('PKM-QC-PENDING', ['quality_control_status' => 'pending']);
        $needsTerminTwo = $this->bast('PKM-NEEDS-T2', [
            'approval_status' => 'approved',
            'termin1_status' => 'sudah',
        ], 3);
        $approved = $this->bast('PKM-APPROVED', ['approval_status' => 'approved']);

        $tabs = app(BastIndexTabs::class);

        $this->assertSame([$needsTerminTwo->id], $this->ids($tabs, BastIndexTabs::TAB_ACTION));
        $this->assertSame([$qcPending->id], $this->ids($tabs, BastIndexTabs::TAB_IN_PROGRESS));
        $this->assertSame([$approved->id], $this->ids($tabs, BastIndexTabs::TAB_APPROVED));
    }

    public function test_termin_two_status_controls_progress_approved_and_history_without_extra_row(): void
    {
        $parent = $this->bast('PKM-T2', [
            'approval_status' => 'approved',
            'termin1_status' => 'sudah',
        ], 3);
        $child = $this->terminTwo($parent, 'in_review');
        $tabs = app(BastIndexTabs::class);

        $this->assertSame([$parent->id], $this->ids($tabs, BastIndexTabs::TAB_IN_PROGRESS));
        $this->assertCount(1, array_merge(...array_map(
            fn (string $tab): array => array_values(array_filter($this->ids($tabs, $tab), fn (int $id): bool => $id === $parent->id)),
            array_keys($tabs->options(BastIndexTabs::CONTEXT_PKM)),
        )));

        $child->update(['approval_status' => 'approved']);
        $this->assertSame([$parent->id], $this->ids($tabs, BastIndexTabs::TAB_APPROVED));

        $parent->update(['termin2_status' => 'sudah']);
        $this->assertSame([$parent->id], $this->ids($tabs, BastIndexTabs::TAB_HISTORY));
        $this->assertSame(1, $tabs->counts(BastIndexTabs::CONTEXT_PKM)['history']);
    }

    public function test_rejected_termin_two_has_action_priority(): void
    {
        $parent = $this->bast('PKM-T2-REJECTED', [
            'approval_status' => 'approved',
            'termin1_status' => 'sudah',
        ], 3);
        $this->terminTwo($parent, 'rejected');

        $this->assertSame([$parent->id], $this->ids(app(BastIndexTabs::class), BastIndexTabs::TAB_ACTION));
    }

    public function test_pkm_view_keeps_pending_orders_termin_two_condition_and_table_headers(): void
    {
        $source = file_get_contents(resource_path('views/pkm/lhpp/index.blade.php'));

        $this->assertStringContainsString('$pendingTerminOneOrders', $source);
        $this->assertStringContainsString('Buat BAST Termin 2', $source);
        $this->assertStringContainsString('Tanggal Selesai', $source);
        $this->assertStringContainsString('Status Payment', $source);
        $this->assertStringNotContainsString('name="unit_kerja"', $source);
        $this->assertStringNotContainsString('name="purchase_order_number"', $source);
        $this->assertStringNotContainsString('name="termin_status"', $source);
        $this->assertStringNotContainsString('Terapkan', $source);
    }

    /** @return list<int> */
    private function ids(BastIndexTabs $tabs, string $tab): array
    {
        $query = LhppBast::query()->where('termin_type', 'termin_1');

        return $tabs->apply($query, $tab, BastIndexTabs::CONTEXT_PKM)
            ->orderBy('id')->pluck('id')->all();
    }

    private function bast(string $number, array $attributes = [], int $warrantyMonths = 0): LhppBast
    {
        $bast = LhppBast::query()->create(array_merge([
            'termin_type' => 'termin_1',
            'nomor_order' => $number,
            'tanggal_bast' => '2026-08-01',
            'quality_control_status' => 'approved',
            'approval_status' => 'in_review',
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
