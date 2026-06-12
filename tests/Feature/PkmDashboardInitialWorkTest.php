<?php

namespace Tests\Feature;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Hpp;
use App\Models\InitialWork;
use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PkmDashboardInitialWorkTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_counts_emergency_initial_work_as_separate_jobwaiting_flow(): void
    {
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $creator = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $initialWorkOrder = $this->createEmergencyOrder($creator, 'ORD-PKM-IW');
        InitialWork::query()->create([
            'order_id' => $initialWorkOrder->id,
            'nomor_initial_work' => 'IW-PKM-001',
            'nomor_order' => $initialWorkOrder->nomor_order,
            'nama_pekerjaan' => $initialWorkOrder->nama_pekerjaan,
            'unit_kerja' => $initialWorkOrder->unit_kerja,
            'seksi' => $initialWorkOrder->seksi,
            'perihal' => 'Emergency Initial Work',
            'tanggal_initial_work' => now()->toDateString(),
            'target_penyelesaian' => now()->addDays(5)->toDateString(),
            'progress_pekerjaan' => 25,
            'functional_location' => ['FL-001'],
            'scope_pekerjaan' => ['Perbaikan emergency'],
            'qty' => [1],
            'stn' => ['Lot'],
            'created_by' => $creator->id,
        ]);

        $purchaseOrder = $this->createEmergencyOrder($creator, 'ORD-PKM-PO');
        $hpp = Hpp::query()->create([
            'order_id' => $purchaseOrder->id,
            'nomor_order' => $purchaseOrder->nomor_order,
            'nama_pekerjaan' => $purchaseOrder->nama_pekerjaan,
            'unit_kerja' => $purchaseOrder->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Workshop',
            'nilai_hpp_bucket' => 'under',
            'total_keseluruhan' => '1000000.00',
            'status' => Hpp::STATUS_APPROVED,
            'submitted_at' => now(),
            'created_by' => $creator->id,
        ]);
        PurchaseOrder::query()->create([
            'order_id' => $purchaseOrder->id,
            'hpp_id' => $hpp->id,
            'purchase_order_number' => 'PO-PKM-001',
            'approve_manager' => true,
            'progress_pekerjaan' => 50,
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
        ]);

        $this->actingAs($pkm)
            ->get(route('pkm.dashboard'))
            ->assertOk()
            ->assertViewHas('totalPekerjaan', 2)
            ->assertViewHas('emergencyInitialWorkCount', 1)
            ->assertSee('Emergency Initial Work');
    }

    private function createEmergencyOrder(User $creator, string $number): Order
    {
        return Order::query()->create([
            'nomor_order' => $number,
            'nama_pekerjaan' => 'Pekerjaan '.$number,
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Pekerjaan emergency dashboard PKM',
            'prioritas' => Order::PRIORITY_URGENT,
            'tanggal_order' => now()->toDateString(),
            'target_selesai' => now()->addWeek()->toDateString(),
            'catatan_status' => OrderUserNoteStatus::ApprovedJasa->value,
            'created_by' => $creator->id,
        ]);
    }
}
