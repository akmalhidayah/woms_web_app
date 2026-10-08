<?php

namespace Tests\Feature\Admin;

use App\Models\BudgetVerification;
use App\Models\Hpp;
use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Support\BudgetVerificationIndexTabs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetVerificationIndexTabsTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_and_invalid_tab_normalize_to_action(): void
    {
        $tabs = app(BudgetVerificationIndexTabs::class);

        $this->assertSame(BudgetVerificationIndexTabs::TAB_ACTION, $tabs->normalize(null));
        $this->assertSame(BudgetVerificationIndexTabs::TAB_ACTION, $tabs->normalize('invalid'));
    }

    public function test_action_contains_missing_waiting_and_incomplete_verifications(): void
    {
        [, $missing] = $this->hpp('BUDGET-ACTION-MISSING');
        [, $waiting] = $this->hpp('BUDGET-ACTION-WAITING');
        [, $missingItem] = $this->hpp('BUDGET-ACTION-ITEM');
        [, $missingCostCategory] = $this->hpp('BUDGET-ACTION-CATEGORY');
        [, $missingCostElement] = $this->hpp('BUDGET-ACTION-COST');

        $this->verification($waiting, ['status_anggaran' => 'Menunggu']);
        $this->verification($missingItem, $this->readyData(['kategori_item' => null]));
        $this->verification($missingCostCategory, $this->readyData(['kategori_biaya' => null]));
        $this->verification($missingCostElement, $this->readyData(['cost_element' => null]));

        $this->assertSame(
            [$missing->id, $waiting->id, $missingItem->id, $missingCostCategory->id, $missingCostElement->id],
            $this->ids(BudgetVerificationIndexTabs::TAB_ACTION),
        );
    }

    public function test_ready_po_accepts_missing_null_empty_and_whitespace_purchase_order_number(): void
    {
        [, $withoutPo] = $this->hpp('BUDGET-READY-NO-PO');
        [, $nullPo] = $this->hpp('BUDGET-READY-NULL');
        [, $emptyPo] = $this->hpp('BUDGET-READY-EMPTY');
        [, $spacePo] = $this->hpp('BUDGET-READY-SPACE');

        foreach ([$withoutPo, $nullPo, $emptyPo, $spacePo] as $hpp) {
            $this->verification($hpp, $this->readyData());
        }

        $this->purchaseOrder($nullPo, null);
        $this->purchaseOrder($emptyPo, '');
        $this->purchaseOrder($spacePo, '   ');

        $this->assertSame(
            [$withoutPo->id, $nullPo->id, $emptyPo->id, $spacePo->id],
            $this->ids(BudgetVerificationIndexTabs::TAB_READY_PO),
        );
    }

    public function test_history_contains_unavailable_or_numbered_purchase_orders(): void
    {
        [, $unavailable] = $this->hpp('BUDGET-HISTORY-UNAVAILABLE');
        [, $numbered] = $this->hpp('BUDGET-HISTORY-PO');
        $this->verification($unavailable, ['status_anggaran' => 'Tidak Tersedia']);
        $this->verification($numbered, $this->readyData());
        $this->purchaseOrder($numbered, 'PO-HISTORY-001');

        $this->assertSame([$unavailable->id, $numbered->id], $this->ids(BudgetVerificationIndexTabs::TAB_HISTORY));
    }

    public function test_non_approved_hpp_never_appears_and_each_approved_hpp_appears_once(): void
    {
        foreach ([Hpp::STATUS_DRAFT, Hpp::STATUS_IN_REVIEW, Hpp::STATUS_REJECTED] as $status) {
            [, $hpp] = $this->hpp('BUDGET-'.$status, $status);
            $this->verification($hpp, $this->readyData());
        }
        [, $approved] = $this->hpp('BUDGET-ONLY-ONCE');

        $allIds = array_merge(
            $this->ids(BudgetVerificationIndexTabs::TAB_ACTION),
            $this->ids(BudgetVerificationIndexTabs::TAB_READY_PO),
            $this->ids(BudgetVerificationIndexTabs::TAB_HISTORY),
        );

        $this->assertSame([$approved->id], $allIds);
    }

    public function test_counts_use_the_same_rules_and_are_independent_from_search(): void
    {
        [, $action] = $this->hpp('BUDGET-COUNT-ACTION');
        [, $ready] = $this->hpp('BUDGET-COUNT-READY');
        [, $history] = $this->hpp('BUDGET-COUNT-HISTORY');
        $this->verification($ready, $this->readyData());
        $this->verification($history, ['status_anggaran' => 'Tidak Tersedia']);

        $this->assertSame([
            'action' => 1,
            'ready_po' => 1,
            'history' => 1,
        ], app(BudgetVerificationIndexTabs::class)->counts());
        $this->assertSame([$action->id], $this->ids(BudgetVerificationIndexTabs::TAB_ACTION));
    }

    public function test_index_search_stays_inside_active_tab_and_preserves_query_on_pagination(): void
    {
        [$admin, $ready] = $this->hpp('BUDGET-SEARCH-READY');
        [, $action] = $this->hpp('BUDGET-SEARCH-ACTION');
        $this->verification($ready, $this->readyData());

        $this->actingAs($admin)
            ->get(route('admin.budget-verification.index', [
                'tab' => 'ready_po',
                'search' => 'BUDGET-SEARCH',
            ]))
            ->assertOk()
            ->assertSee($ready->nomor_order)
            ->assertDontSee($action->nomor_order)
            ->assertSee('name="tab" value="ready_po"', false);
    }

    public function test_latest_verification_or_purchase_order_activity_controls_ordering(): void
    {
        [, $first] = $this->hpp('BUDGET-ACTIVITY-FIRST');
        [, $second] = $this->hpp('BUDGET-ACTIVITY-SECOND');
        $firstVerification = $this->verification($first, $this->readyData());
        $this->verification($second, $this->readyData());
        $firstVerification->forceFill(['updated_at' => now()->addMinute()])->saveQuietly();
        $tabs = app(BudgetVerificationIndexTabs::class);
        $query = $tabs->apply($tabs->baseQuery(), BudgetVerificationIndexTabs::TAB_READY_PO);
        $tabs->applyLatestActivityOrder($query);

        $this->assertSame([$first->id, $second->id], $query->pluck('hpps.id')->all());
    }

    public function test_view_keeps_six_headers_and_autosave_contract_without_old_filters_or_green_rows(): void
    {
        $source = file_get_contents(resource_path('views/admin/budget-verification/index.blade.php'));

        foreach (['Nomor Order', 'Detail Pekerjaan', 'Anggaran', 'Kategori', 'Cost Element', 'Catatan'] as $header) {
            $this->assertStringContainsString($header, $source);
        }

        $this->assertStringContainsString('data-purchase-order-eligible', $source);
        $this->assertStringContainsString('is_purchase_order_eligible', $source);
        $this->assertStringNotContainsString('name="unit"', $source);
        $this->assertStringNotContainsString('_filter_unit', $source);
        $this->assertStringNotContainsString('_filter_kategori_item', $source);
        $this->assertStringNotContainsString('bg-emerald-50/70', $source);
        $this->assertStringNotContainsString("classList.toggle('hover:bg-emerald-100/60'", $source);
    }

    /** @return list<int> */
    private function ids(string $tab): array
    {
        $tabs = app(BudgetVerificationIndexTabs::class);

        return $tabs->apply($tabs->baseQuery(), $tab)->orderBy('id')->pluck('id')->all();
    }

    /** @return array{User, Hpp} */
    private function hpp(string $number, string $status = Hpp::STATUS_APPROVED): array
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
        $order = Order::query()->create([
            'nomor_order' => $number,
            'notifikasi' => 'NOTIF-'.$number,
            'nama_pekerjaan' => 'Pekerjaan '.$number,
            'deskripsi' => 'Deskripsi '.$number,
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-08-01',
            'target_selesai' => '2026-08-10',
            'created_by' => $admin->id,
        ]);
        $hpp = Hpp::query()->create([
            'order_id' => $order->id,
            'nomor_order' => $number,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Dalam',
            'nilai_hpp_bucket' => 'under',
            'item_groups' => [],
            'total_keseluruhan' => 1000000,
            'status' => $status,
            'created_by' => $admin->id,
        ]);

        return [$admin, $hpp];
    }

    private function verification(Hpp $hpp, array $attributes): BudgetVerification
    {
        return BudgetVerification::query()->create(array_merge([
            'order_id' => $hpp->order_id,
            'hpp_id' => $hpp->id,
        ], $attributes));
    }

    private function purchaseOrder(Hpp $hpp, ?string $number): PurchaseOrder
    {
        return PurchaseOrder::query()->create([
            'order_id' => $hpp->order_id,
            'hpp_id' => $hpp->id,
            'purchase_order_number' => $number,
        ]);
    }

    private function readyData(array $overrides = []): array
    {
        return array_merge([
            'status_anggaran' => 'Tersedia',
            'kategori_item' => 'jasa',
            'kategori_biaya' => 'pemeliharaan',
            'cost_element' => '65340001',
        ], $overrides);
    }
}
