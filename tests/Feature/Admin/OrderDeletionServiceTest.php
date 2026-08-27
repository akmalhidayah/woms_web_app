<?php

namespace Tests\Feature\Admin;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\BengkelTask;
use App\Models\Order;
use App\Models\OrderWorkshop;
use App\Services\BengkelTasks\WorkshopWorkPackageService;
use App\Services\Orders\OrderDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderDeletionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_workshop_order_and_package_children_are_deleted_together(): void
    {
        $admin = $this->admin();
        $order = Order::query()->create([
            'nomor_order' => 'DELETE-PACKAGE-001',
            'notifikasi' => 'DELETE-NOTIF-001',
            'nama_pekerjaan' => 'Pekerjaan Bengkel',
            'unit_kerja' => 'Workshop',
            'seksi' => 'Machine Workshop',
            'deskripsi' => 'Pengujian penghapusan order',
            'prioritas' => Order::PRIORITY_LOW,
            'tanggal_order' => now()->toDateString(),
            'target_selesai' => now()->addDay()->toDateString(),
            'catatan_status' => OrderUserNoteStatus::ApprovedWorkshop->value,
            'created_by' => $admin->id,
        ]);
        $order->orderWorkshop()->create([
            'progress_status' => OrderWorkshop::PROGRESS_IN_PROGRESS,
        ]);
        $package = app(WorkshopWorkPackageService::class)->create($order, [
            'job_name' => 'Paket pengujian',
        ], $admin->id);
        $task = BengkelTask::query()->create([
            'order_id' => $order->id,
            'job_name' => 'Pekerjaan Bengkel',
            'progress_status' => OrderWorkshop::PROGRESS_IN_PROGRESS,
        ]);

        app(OrderDeletionService::class)->delete($order);

        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
        $this->assertDatabaseMissing('order_workshops', ['order_id' => $order->id]);
        $this->assertDatabaseMissing('workshop_work_packages', ['id' => $package->id]);
        $this->assertDatabaseMissing('bengkel_tasks', ['id' => $task->id]);
    }

    private function admin(): \App\Models\User
    {
        return \App\Models\User::factory()->create([
            'role' => \App\Models\User::ROLE_ADMIN,
            'admin_role' => \App\Models\User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
    }
}
