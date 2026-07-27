<?php

namespace Tests\Feature;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Hpp;
use App\Models\InitialWork;
use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Support\PkmSidebarBadgeCounter;
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

    public function test_completed_job_waiting_progress_is_finished_on_dashboard_while_documents_remain_pending(): void
    {
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $creator = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $order = $this->createEmergencyOrder($creator, 'ORD-PKM-DONE');
        $hpp = Hpp::query()->create([
            'order_id' => $order->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Workshop',
            'nilai_hpp_bucket' => 'under',
            'total_keseluruhan' => '1000000.00',
            'status' => Hpp::STATUS_APPROVED,
            'submitted_at' => now(),
            'created_by' => $creator->id,
        ]);
        PurchaseOrder::query()->create([
            'order_id' => $order->id,
            'hpp_id' => $hpp->id,
            'purchase_order_number' => 'PO-PKM-DONE',
            'approve_manager' => true,
            'target_penyelesaian' => now()->subDays(5)->toDateString(),
            'progress_pekerjaan' => 100,
            'tanggal_selesai_pekerjaan' => now()->toDateString(),
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
        ]);

        $this->actingAs($pkm)
            ->get(route('pkm.dashboard'))
            ->assertOk()
            ->assertViewHas('pekerjaanSelesai', 1)
            ->assertViewHas('overdueCount', 0)
            ->assertViewHas('jobHighlights', fn (array $items): bool => $items[0]['status_key'] === 'selesai'
                && $items[0]['status_label'] === 'Selesai');

        $this->assertSame(1, app(PkmSidebarBadgeCounter::class)->counts()['jobwaiting']);
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
