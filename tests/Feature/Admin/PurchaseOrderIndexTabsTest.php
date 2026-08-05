<?php

namespace Tests\Feature\Admin;

use App\Models\BudgetVerification;
use App\Models\Hpp;
use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Support\PurchaseOrderIndexTabs;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderIndexTabsTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_and_invalid_tabs_fall_back_to_action(): void
    {
        [$admin, $actionHpp] = $this->makeEligibleHpp('PO-TAB-ACTION');
        [, $readyHpp] = $this->makeEligibleHpp('PO-TAB-READY');
        $this->makePurchaseOrder($readyHpp, ['purchase_order_number' => 'PO-READY', 'approve_manager' => true]);

        $this->actingAs($admin)
            ->get(route('admin.purchase-order.index'))
            ->assertOk()
            ->assertSee($actionHpp->nomor_order)
            ->assertDontSee($readyHpp->nomor_order);

        $this->actingAs($admin)
            ->get(route('admin.purchase-order.index', ['tab' => 'invalid']))
            ->assertOk()
            ->assertViewHas('activeTab', PurchaseOrderIndexTabs::TAB_ACTION)
            ->assertSee($actionHpp->nomor_order)
            ->assertDontSee($readyHpp->nomor_order);
    }

    public function test_action_contains_missing_incomplete_or_unapproved_purchase_orders(): void
    {
        [, $withoutPo] = $this->makeEligibleHpp('PO-ACTION-NONE');
        [, $nullNumber] = $this->makeEligibleHpp('PO-ACTION-NULL');
        [, $blankNumber] = $this->makeEligibleHpp('PO-ACTION-BLANK');
        [, $spaceNumber] = $this->makeEligibleHpp('PO-ACTION-SPACE');
        [, $managerPending] = $this->makeEligibleHpp('PO-ACTION-MANAGER');
        [, $progressWithoutActivePo] = $this->makeEligibleHpp('PO-ACTION-PROGRESS');

        $this->makePurchaseOrder($nullNumber, ['purchase_order_number' => null, 'approve_manager' => true]);
        $this->makePurchaseOrder($blankNumber, ['purchase_order_number' => '', 'approve_manager' => true]);
        $this->makePurchaseOrder($spaceNumber, ['purchase_order_number' => '   ', 'approve_manager' => true]);
        $this->makePurchaseOrder($managerPending, ['purchase_order_number' => 'PO-PENDING', 'approve_manager' => false]);
        $this->makePurchaseOrder($progressWithoutActivePo, [
            'purchase_order_number' => '   ',
            'approve_manager' => true,
            'progress_pekerjaan' => 45,
        ]);

        $rows = $this->tabRows(PurchaseOrderIndexTabs::TAB_ACTION);

        foreach ([$withoutPo, $nullNumber, $blankNumber, $spaceNumber, $managerPending, $progressWithoutActivePo] as $hpp) {
            $this->assertTrue($rows->contains('id', $hpp->id));
        }
    }

    public function test_active_purchase_orders_are_split_exclusively_by_progress(): void
    {
        [, $readyZero] = $this->makeEligibleHpp('PO-READY-ZERO');
        [, $progressOne] = $this->makeEligibleHpp('PO-PROGRESS-ONE');
        [, $progressFortyFive] = $this->makeEligibleHpp('PO-PROGRESS-45');
        [, $progressNinetyNine] = $this->makeEligibleHpp('PO-PROGRESS-99');
        [, $history] = $this->makeEligibleHpp('PO-HISTORY-100');
        [, $completedButUnapproved] = $this->makeEligibleHpp('PO-ACTION-100');

        $this->makePurchaseOrder($readyZero, ['purchase_order_number' => 'PO-0', 'approve_manager' => true, 'progress_pekerjaan' => 0]);
        $this->makePurchaseOrder($progressOne, ['purchase_order_number' => 'PO-1', 'approve_manager' => true, 'progress_pekerjaan' => 1]);
        $this->makePurchaseOrder($progressFortyFive, ['purchase_order_number' => 'PO-45', 'approve_manager' => true, 'progress_pekerjaan' => 45]);
        $this->makePurchaseOrder($progressNinetyNine, ['purchase_order_number' => 'PO-99', 'approve_manager' => true, 'progress_pekerjaan' => 99]);
        $this->makePurchaseOrder($history, ['purchase_order_number' => 'PO-100', 'approve_manager' => true, 'progress_pekerjaan' => 100]);
        $this->makePurchaseOrder($completedButUnapproved, ['purchase_order_number' => 'PO-NOT-ACTIVE', 'approve_manager' => false, 'progress_pekerjaan' => 100]);

        $expected = [
            PurchaseOrderIndexTabs::TAB_ACTION => [$completedButUnapproved->id],
            PurchaseOrderIndexTabs::TAB_READY => [$readyZero->id],
            PurchaseOrderIndexTabs::TAB_IN_PROGRESS => [$progressOne->id, $progressFortyFive->id, $progressNinetyNine->id],
            PurchaseOrderIndexTabs::TAB_HISTORY => [$history->id],
        ];

        foreach ($expected as $tab => $ids) {
            $actualIds = $this->tabRows($tab)->pluck('id')->all();

            foreach ($ids as $id) {
                $this->assertContains($id, $actualIds);
            }
        }

        foreach ([$readyZero, $progressOne, $progressFortyFive, $progressNinetyNine, $history, $completedButUnapproved] as $hpp) {
            $matches = collect(array_keys($expected))
                ->filter(fn (string $tab): bool => $this->tabRows($tab)->contains('id', $hpp->id));

            $this->assertCount(1, $matches);
        }
    }

    public function test_search_applies_only_to_active_tab_and_does_not_change_counts(): void
    {
        [$admin, $matchingAction] = $this->makeEligibleHpp('SEARCH-ACTION', [
            'nama_pekerjaan' => 'Pompa Khusus Alpha',
        ]);
        [, $otherAction] = $this->makeEligibleHpp('OTHER-ACTION');
        [, $matchingReady] = $this->makeEligibleHpp('SEARCH-READY', [
            'nama_pekerjaan' => 'Pompa Khusus Alpha',
        ]);
        $this->makePurchaseOrder($matchingReady, ['purchase_order_number' => 'PO-ALPHA', 'approve_manager' => true]);

        $response = $this->actingAs($admin)->get(route('admin.purchase-order.index', [
            'tab' => PurchaseOrderIndexTabs::TAB_ACTION,
            'search' => 'Pompa Khusus Alpha',
        ]));

        $response
            ->assertOk()
            ->assertSee($matchingAction->nomor_order)
            ->assertDontSee($otherAction->nomor_order)
            ->assertDontSee($matchingReady->nomor_order)
            ->assertViewHas('tabCounts', fn (array $counts): bool => $counts[PurchaseOrderIndexTabs::TAB_ACTION] === 2
                && $counts[PurchaseOrderIndexTabs::TAB_READY] === 1);
    }

    public function test_waiting_bast_budget_status_is_eligible_for_purchase_order_tabs(): void
    {
        [, $hpp] = $this->makeEligibleHpp('PO-WAITING-BAST');
        $hpp->budgetVerification()->update([
            'status_anggaran' => BudgetVerification::STATUS_WAITING_BAST,
        ]);

        $this->assertTrue(
            $this->tabRows(PurchaseOrderIndexTabs::TAB_ACTION)->contains('id', $hpp->id)
        );

        $purchaseOrder = $this->makePurchaseOrder($hpp, [
            'purchase_order_number' => 'PO-WAITING-BAST-001',
            'approve_manager' => true,
        ]);

        $this->assertTrue(
            $this->tabRows(PurchaseOrderIndexTabs::TAB_READY)->contains('id', $hpp->id)
        );
        $this->assertSame(0, $purchaseOrder->progress_pekerjaan);
    }

    public function test_view_has_four_tabs_search_only_existing_table_and_new_hidden_filters(): void
    {
        [$admin, $hpp] = $this->makeEligibleHpp('PO-VIEW-TABS');

        $this->actingAs($admin)
            ->get(route('admin.purchase-order.index', [
                'tab' => PurchaseOrderIndexTabs::TAB_ACTION,
                'search' => $hpp->nomor_order,
            ]))
            ->assertOk()
            ->assertSee('Perlu Tindakan')
            ->assertSee('Siap Dikerjakan')
            ->assertSee('Dalam Proses')
            ->assertSee('Riwayat')
            ->assertDontSee('Semua Status')
            ->assertDontSee('Semua Unit')
            ->assertSee('class="purchase-order-responsive-table', false)
            ->assertSee('min-width: 1480px;', false)
            ->assertSee('name="_filter_tab" value="action"', false)
            ->assertSee('name="_filter_search" value="'.$hpp->nomor_order.'"', false)
            ->assertSee('name="_filter_page" value="1"', false)
            ->assertDontSee('_filter_status', false)
            ->assertDontSee('_filter_unit', false)
            ->assertDontSee('_filter_from', false)
            ->assertDontSee('_filter_to', false);
    }

    public function test_latest_purchase_order_activity_is_sorted_first_with_hpp_fallback(): void
    {
        [, $olderPoHpp] = $this->makeEligibleHpp('PO-SORT-OLDER');
        [, $newerPoHpp] = $this->makeEligibleHpp('PO-SORT-NEWER');
        $olderPo = $this->makePurchaseOrder($olderPoHpp, ['purchase_order_number' => null]);
        $newerPo = $this->makePurchaseOrder($newerPoHpp, ['purchase_order_number' => null]);
        $olderPo->timestamps = false;
        $newerPo->timestamps = false;
        $olderPo->forceFill(['updated_at' => '2026-07-01 08:00:00'])->saveQuietly();
        $newerPo->forceFill(['updated_at' => '2026-07-02 08:00:00'])->saveQuietly();

        $ids = app(PurchaseOrderIndexTabs::class)
            ->applyLatestActivityOrder(
                app(PurchaseOrderIndexTabs::class)->apply(
                    app(PurchaseOrderIndexTabs::class)->baseQuery(),
                    PurchaseOrderIndexTabs::TAB_ACTION,
                )
            )
            ->pluck('hpps.id')
            ->all();

        $this->assertLessThan(array_search($olderPoHpp->id, $ids, true), array_search($newerPoHpp->id, $ids, true));
    }

    public function test_update_redirect_preserves_tab_search_and_page(): void
    {
        [$admin, $hpp] = $this->makeEligibleHpp('PO-REDIRECT');

        $this->actingAs($admin)
            ->patch(route('admin.purchase-order.update', ['hpp' => $hpp->nomor_order]), [
                'purchase_order_number' => 'PO-REDIRECT-001',
                '_filter_tab' => PurchaseOrderIndexTabs::TAB_ACTION,
                '_filter_search' => 'needle',
                '_filter_page' => 3,
            ])
            ->assertRedirect(route('admin.purchase-order.index', [
                'tab' => PurchaseOrderIndexTabs::TAB_ACTION,
                'search' => 'needle',
                'page' => 3,
            ]));
    }

    private function tabRows(string $tab)
    {
        $tabs = app(PurchaseOrderIndexTabs::class);

        return $tabs->apply($tabs->baseQuery(), $tab)->get();
    }

    /** @return array{User, Hpp} */
    private function makeEligibleHpp(string $suffix, array $orderAttributes = []): array
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
        $order = Order::query()->create([
            'nomor_order' => $suffix,
            'notifikasi' => 'NOTIF-'.$suffix,
            'nama_pekerjaan' => 'Pekerjaan '.$suffix,
            'unit_kerja' => 'Unit '.$suffix,
            'seksi' => 'Seksi '.$suffix,
            'deskripsi' => 'Deskripsi '.$suffix,
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-07-01',
            'target_selesai' => '2026-08-01',
            'created_by' => $admin->id,
            ...$orderAttributes,
        ]);
        $hpp = Hpp::query()->create([
            'order_id' => $order->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Dalam',
            'nilai_hpp_bucket' => 'under',
            'approval_flow' => [],
            'item_groups' => [],
            'total_keseluruhan' => 1000000,
            'status' => Hpp::STATUS_APPROVED,
            'created_by' => $admin->id,
        ]);
        BudgetVerification::query()->create([
            'order_id' => $order->id,
            'hpp_id' => $hpp->id,
            'status_anggaran' => 'Tersedia',
            'kategori_item' => 'jasa',
            'kategori_biaya' => 'pemeliharaan',
            'cost_element' => '65340001',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        return [$admin, $hpp];
    }

    private function makePurchaseOrder(Hpp $hpp, array $attributes = []): PurchaseOrder
    {
        return PurchaseOrder::query()->create([
            'order_id' => $hpp->order_id,
            'hpp_id' => $hpp->id,
            'progress_pekerjaan' => 0,
            'created_by' => $hpp->created_by,
            ...$attributes,
        ]);
    }
}
