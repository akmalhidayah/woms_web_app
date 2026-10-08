<?php

namespace Tests\Feature\Admin;

use App\Models\Garansi;
use App\Models\Hpp;
use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GaransiIndexTabsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tabs_separate_orders_that_need_action_and_have_been_set(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
        $needsAction = $this->makeEligibleOrder($admin, 'GARANSI-ACTION');
        $alreadySet = $this->makeEligibleOrder($admin, 'GARANSI-SET');

        Garansi::query()->create([
            'order_id' => $alreadySet->id,
            'garansi_months' => 0,
            'start_date' => '2026-08-24',
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.garansi.index'))
            ->assertOk()
            ->assertSee('Perlu Tindakan')
            ->assertSee('Sudah Set')
            ->assertSee('data-garansi-order="'.$needsAction->id.'"', false)
            ->assertDontSee('data-garansi-order="'.$alreadySet->id.'"', false);

        $this->actingAs($admin)
            ->get(route('admin.garansi.index', ['tab' => 'set']))
            ->assertOk()
            ->assertSee('data-garansi-order="'.$alreadySet->id.'"', false)
            ->assertDontSee('data-garansi-order="'.$needsAction->id.'"', false);
    }

    public function test_invalid_tab_falls_back_to_action(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
        $order = $this->makeEligibleOrder($admin, 'GARANSI-FALLBACK');

        $this->actingAs($admin)
            ->get(route('admin.garansi.index', ['tab' => 'tidak-valid']))
            ->assertOk()
            ->assertSee('data-garansi-order="'.$order->id.'"', false)
            ->assertSee('aria-current="page"', false);
    }

    private function makeEligibleOrder(User $admin, string $number): Order
    {
        $order = Order::query()->create([
            'nomor_order' => $number,
            'notifikasi' => 'NOTIF-'.$number,
            'nama_pekerjaan' => 'Pekerjaan '.$number,
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Deskripsi',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-08-01',
            'target_selesai' => '2026-08-10',
            'created_by' => $admin->id,
        ]);
        $hpp = Hpp::query()->create([
            'order_id' => $order->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Dalam',
            'nilai_hpp_bucket' => 'under',
            'item_groups' => [],
            'total_keseluruhan' => 1000000,
            'status' => Hpp::STATUS_APPROVED,
            'created_by' => $admin->id,
        ]);

        PurchaseOrder::query()->create([
            'order_id' => $order->id,
            'hpp_id' => $hpp->id,
            'purchase_order_number' => 'PO-'.$number,
            'progress_pekerjaan' => 100,
            'created_by' => $admin->id,
        ]);

        return $order;
    }
}
