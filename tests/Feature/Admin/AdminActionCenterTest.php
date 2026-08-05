<?php

namespace Tests\Feature\Admin;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\AdminRoleMenuAccess;
use App\Models\BudgetVerification;
use App\Models\Hpp;
use App\Models\LhppBast;
use App\Models\Order;
use App\Models\OrderScopeOfWork;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Support\AdminActionCenter;
use App\Support\AdminMenuRegistry;
use App\Support\AdminSidebarBadgeCounter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminActionCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_actions_are_filtered_by_admin_menu_access_and_super_admin_sees_all_visible_types(): void
    {
        $regularAdmin = $this->admin(User::ADMIN_ROLE_ADMIN);
        $superAdmin = $this->admin(User::ADMIN_ROLE_SUPER_ADMIN);
        $incomplete = $this->order($regularAdmin, 'ACTION-INCOMPLETE');
        $ready = $this->order($regularAdmin, 'ACTION-HPP');
        $this->scope($ready, $regularAdmin);

        AdminRoleMenuAccess::query()->create([
            'admin_role' => User::ADMIN_ROLE_ADMIN,
            'menu_key' => AdminMenuRegistry::MENU_CREATE_HPP,
        ]);

        $regularActions = app(AdminActionCenter::class)->actions($regularAdmin, 20);
        $this->assertSame(['create-hpp:'.$ready->id], $regularActions->pluck('key')->all());
        $this->assertSame(1, app(AdminActionCenter::class)->pendingActionCount($regularAdmin));
        $this->actingAs($regularAdmin)->get($regularActions->first()['url'])->assertOk();

        $superActions = app(AdminActionCenter::class)->actions($superAdmin, 20);
        $this->assertTrue($superActions->contains('key', 'order-sow:'.$incomplete->id));
        $this->assertTrue($superActions->contains('key', 'create-hpp:'.$ready->id));
    }

    public function test_action_key_is_stable_and_action_disappears_only_after_business_condition_is_completed(): void
    {
        $admin = $this->admin(User::ADMIN_ROLE_SUPER_ADMIN);
        $order = $this->order($admin, 'ACTION-STABLE');
        $this->scope($order, $admin);

        $before = app(AdminActionCenter::class)->actions($admin, 20)->firstWhere('key', 'create-hpp:'.$order->id);
        $order->forceFill(['updated_at' => now()->addHour()])->saveQuietly();
        $after = app(AdminActionCenter::class)->actions($admin, 20)->firstWhere('key', 'create-hpp:'.$order->id);

        $this->assertSame($before['key'], $after['key']);

        $order->hpps()->create([
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Dalam',
            'nilai_hpp_bucket' => 'under',
            'item_groups' => [],
            'total_keseluruhan' => 1000000,
            'status' => 'draft',
            'created_by' => $admin->id,
        ]);

        $this->assertFalse(app(AdminActionCenter::class)->actions($admin, 20)->contains('key', 'create-hpp:'.$order->id));
    }

    public function test_super_admin_receives_every_supported_business_action_type(): void
    {
        $admin = $this->admin(User::ADMIN_ROLE_SUPER_ADMIN);
        $incomplete = $this->order($admin, 'ACTION-ALL-INCOMPLETE');
        $ready = $this->order($admin, 'ACTION-ALL-HPP');
        $this->scope($ready, $admin);

        $verificationOrder = $this->order($admin, 'ACTION-ALL-BV', OrderUserNoteStatus::Pending);
        $verificationHpp = $this->hpp($admin, $verificationOrder, Hpp::STATUS_APPROVED);

        $purchaseOrderOrder = $this->order($admin, 'ACTION-ALL-PO', OrderUserNoteStatus::Pending);
        $purchaseOrderHpp = $this->hpp($admin, $purchaseOrderOrder, Hpp::STATUS_APPROVED);
        BudgetVerification::query()->create([
            'order_id' => $purchaseOrderOrder->id,
            'hpp_id' => $purchaseOrderHpp->id,
            'status_anggaran' => 'Tersedia',
            'kategori_item' => 'jasa',
            'kategori_biaya' => 'pemeliharaan',
            'cost_element' => '65340001',
            'created_by' => $admin->id,
        ]);

        $warrantyOrder = $this->order($admin, 'ACTION-ALL-GARANSI', OrderUserNoteStatus::Pending);
        $warrantyHpp = $this->hpp($admin, $warrantyOrder, Hpp::STATUS_APPROVED);
        PurchaseOrder::query()->create([
            'order_id' => $warrantyOrder->id,
            'hpp_id' => $warrantyHpp->id,
            'purchase_order_number' => 'PO-ACTION-ALL-GARANSI',
            'progress_pekerjaan' => 100,
            'created_by' => $admin->id,
        ]);

        $bastOrder = $this->order($admin, 'ACTION-ALL-BAST', OrderUserNoteStatus::Pending);
        $bast = LhppBast::query()->create([
            'order_id' => $bastOrder->id,
            'termin_type' => 'termin_1',
            'nomor_order' => $bastOrder->nomor_order,
            'notifikasi' => $bastOrder->notifikasi,
            'deskripsi_pekerjaan' => $bastOrder->nama_pekerjaan,
            'unit_kerja' => $bastOrder->unit_kerja,
            'seksi' => $bastOrder->seksi,
            'tanggal_bast' => '2026-08-02',
            'tanggal_mulai_pekerjaan' => '2026-08-01',
            'tanggal_selesai_pekerjaan' => '2026-08-02',
            'quality_control_status' => 'pending',
            'created_by' => $admin->id,
        ]);

        $keys = app(AdminActionCenter::class)->actions($admin, 20)->pluck('key');

        $this->assertTrue($keys->contains('order-sow:'.$incomplete->id));
        $this->assertTrue($keys->contains('create-hpp:'.$ready->id));
        $this->assertTrue($keys->contains('budget-verification:'.$verificationHpp->id));
        $this->assertTrue($keys->contains('purchase-order:'.$purchaseOrderHpp->id));
        $this->assertTrue($keys->contains('set-garansi:'.$warrantyOrder->id));
        $this->assertTrue($keys->contains('check-bast:'.$bast->id));
        $this->assertTrue($keys->contains('lpj-ppl:'.$bast->id));
    }

    public function test_sidebar_counts_and_bell_count_use_the_same_resolver_values(): void
    {
        $admin = $this->admin(User::ADMIN_ROLE_SUPER_ADMIN);
        $this->order($admin, 'ACTION-SYNC');

        $actionCenter = app(AdminActionCenter::class);
        $this->assertSame($actionCenter->sidebarCounts(), app(AdminSidebarBadgeCounter::class)->counts());
        $this->assertSame(array_sum($actionCenter->moduleCounts($admin)), $actionCenter->pendingActionCount($admin));
    }

    public function test_waiting_levels_are_safe_and_danger_actions_are_ordered_first(): void
    {
        Carbon::setTestNow('2026-08-05 12:00:00');
        $center = app(AdminActionCenter::class);

        $this->assertSame('normal', $center->waitingState(now()->subHours(23))['overdue_level']);
        $this->assertSame('warning', $center->waitingState(now()->subHours(24))['overdue_level']);
        $this->assertSame('danger', $center->waitingState(now()->subHours(48))['overdue_level']);
        $this->assertSame('normal', $center->waitingState(null)['overdue_level']);

        $admin = $this->admin(User::ADMIN_ROLE_SUPER_ADMIN);
        $normal = $this->order($admin, 'ACTION-NORMAL');
        $danger = $this->order($admin, 'ACTION-DANGER');
        Order::query()->whereKey($normal)->update(['updated_at' => now()->subHour()]);
        Order::query()->whereKey($danger)->update(['updated_at' => now()->subDays(3)]);

        $actions = app(AdminActionCenter::class)->actions($admin, 20);
        $this->assertSame('order-sow:'.$danger->id, $actions->first()['key']);

        Carbon::setTestNow();
    }

    private function admin(string $adminRole): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => $adminRole,
        ]);
    }

    private function order(
        User $creator,
        string $number,
        OrderUserNoteStatus $status = OrderUserNoteStatus::ApprovedJasa,
    ): Order
    {
        return Order::query()->create([
            'nomor_order' => $number,
            'notifikasi' => 'NOTIF-'.$number,
            'nama_pekerjaan' => 'Pekerjaan '.$number,
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Deskripsi test',
            'catatan_status' => $status->value,
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-08-01',
            'target_selesai' => '2026-08-10',
            'created_by' => $creator->id,
        ]);
    }

    private function scope(Order $order, User $creator): void
    {
        OrderScopeOfWork::query()->create([
            'order_id' => $order->id,
            'tanggal_dokumen' => '2026-08-01',
            'scope_items' => [['pekerjaan' => 'Scope test']],
            'created_by' => $creator->id,
        ]);
    }

    private function hpp(User $creator, Order $order, string $status): Hpp
    {
        return Hpp::query()->create([
            'order_id' => $order->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Dalam',
            'nilai_hpp_bucket' => 'under',
            'item_groups' => [],
            'total_keseluruhan' => 1000000,
            'status' => $status,
            'created_by' => $creator->id,
        ]);
    }
}
