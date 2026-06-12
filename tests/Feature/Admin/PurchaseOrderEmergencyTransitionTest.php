<?php

namespace Tests\Feature\Admin;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\BudgetVerification;
use App\Models\Hpp;
use App\Models\InitialWork;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderEmergencyTransitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_emergency_progress_is_preserved_when_purchase_order_becomes_active(): void
    {
        [$admin, $pkm, $hpp, $initialWork] = $this->createEmergencyOrderFlow();

        $this->actingAs($admin)
            ->patch(route('admin.purchase-order.update', ['hpp' => $hpp->nomor_order]), [
                'purchase_order_number' => 'PO-EMERGENCY-001',
                'target_penyelesaian' => '2026-07-15',
                'approve_manager' => '1',
            ])
            ->assertRedirect();

        $purchaseOrder = $hpp->fresh()->purchaseOrder;

        $this->assertNotNull($purchaseOrder);
        $this->assertSame(45, $purchaseOrder->progress_pekerjaan);
        $this->assertSame('2026-06-10', $purchaseOrder->tanggal_mulai_pekerjaan?->toDateString());
        $this->assertSame($initialWork->vendor_note, $purchaseOrder->vendor_note);
        $this->assertSame('2026-07-15', $purchaseOrder->target_penyelesaian?->toDateString());

        $this->actingAs($pkm)
            ->get(route('pkm.jobwaiting'))
            ->assertOk()
            ->assertViewHas('notifications', function ($notifications): bool {
                $notification = $notifications->first();

                return $notification['progress'] === 45
                    && $notification['target_penyelesaian'] === '2026-07-15'
                    && $notification['is_initial_work_flow'] === false;
            });
    }

    public function test_purchase_order_target_is_shown_while_emergency_still_uses_initial_work(): void
    {
        [$admin, $pkm, $hpp] = $this->createEmergencyOrderFlow();

        $this->actingAs($admin)
            ->patch(route('admin.purchase-order.update', ['hpp' => $hpp->nomor_order]), [
                'purchase_order_number' => 'PO-DRAFT-EMERGENCY',
                'target_penyelesaian' => '2026-07-20',
            ])
            ->assertRedirect();

        $this->actingAs($pkm)
            ->get(route('pkm.jobwaiting'))
            ->assertOk()
            ->assertViewHas('notifications', function ($notifications): bool {
                $notification = $notifications->first();

                return $notification['progress'] === 45
                    && $notification['target_penyelesaian'] === '2026-07-20'
                    && $notification['is_initial_work_flow'] === true;
            });
    }

    /**
     * @return array{User, User, Hpp, InitialWork}
     */
    private function createEmergencyOrderFlow(): array
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $order = Order::query()->create([
            'nomor_order' => 'ORD-EMERGENCY-PO',
            'nama_pekerjaan' => 'Pekerjaan Emergency PO',
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Transisi Initial Work ke Purchase Order',
            'prioritas' => Order::PRIORITY_URGENT,
            'tanggal_order' => '2026-06-01',
            'target_selesai' => '2026-07-30',
            'catatan_status' => OrderUserNoteStatus::ApprovedJasa->value,
            'created_by' => $admin->id,
        ]);
        $initialWork = InitialWork::query()->create([
            'order_id' => $order->id,
            'nomor_initial_work' => 'IW-EMERGENCY-001',
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'perihal' => 'Initial Work Emergency',
            'tanggal_initial_work' => '2026-06-05',
            'target_penyelesaian' => '2026-07-01',
            'progress_pekerjaan' => 45,
            'tanggal_mulai_pekerjaan' => '2026-06-10',
            'vendor_note' => 'Progress dari Initial Work',
            'functional_location' => ['FL-001'],
            'scope_pekerjaan' => ['Pekerjaan emergency'],
            'qty' => [1],
            'stn' => ['Lot'],
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
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        return [$admin, $pkm, $hpp, $initialWork];
    }
}
