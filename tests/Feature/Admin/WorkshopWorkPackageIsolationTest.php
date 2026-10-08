<?php

namespace Tests\Feature\Admin;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Order;
use App\Models\OrderWorkshop;
use App\Models\User;
use App\Models\WorkshopWorkPackage;
use App\Services\BengkelTasks\WorkshopWorkPackageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkshopWorkPackageIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_jasa_order_cannot_open_work_package_route_or_service(): void
    {
        $admin = $this->admin();
        $order = $this->order($admin, OrderUserNoteStatus::ApprovedJasa);

        $this->actingAs($admin)
            ->get(route('admin.orders.workshop.work-packages.index', $order))
            ->assertNotFound();

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        app(WorkshopWorkPackageService::class)->assertWorkshopOrder($order);
    }

    public function test_workshop_package_number_is_server_generated_and_sequence_survives_delete(): void
    {
        $admin = $this->admin();
        $order = $this->order($admin, OrderUserNoteStatus::ApprovedWorkshop);
        $service = app(WorkshopWorkPackageService::class);

        $first = $service->create($order, ['job_name' => 'Satu'], $admin->id);
        $second = $service->create($order, ['job_name' => 'Dua'], $admin->id);
        $this->assertSame($order->nomor_order.'-01', $first->display_no);
        $this->assertSame($order->nomor_order.'-02', $second->display_no);

        $service->delete($first);
        $third = $service->create($order, ['job_name' => 'Tiga', 'display_no' => 'PALSU', 'sequence' => 1], $admin->id);
        $this->assertSame($order->nomor_order.'-03', $third->display_no);
    }

    public function test_incomplete_packages_block_parent_qc_transition(): void
    {
        $admin = $this->admin();
        $order = $this->order($admin, OrderUserNoteStatus::ApprovedWorkshop);
        app(WorkshopWorkPackageService::class)->create($order, ['job_name' => 'Belum selesai'], $admin->id);

        $this->actingAs($admin)
            ->patchJson(route('admin.orders.workshop.update', $order), [
                'progress_status' => OrderWorkshop::PROGRESS_QUALITY_CONTROL,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('progress_status');
    }

    public function test_empty_package_keeps_existing_workshop_flow_available(): void
    {
        $admin = $this->admin();
        $order = $this->order($admin, OrderUserNoteStatus::ApprovedWorkshop);

        app(WorkshopWorkPackageService::class)->assertParentMayAdvance($order);
        $this->assertTrue($order->allWorkPackagesCompleted());
    }

    public function test_jasa_order_does_not_expose_package_progress_even_if_legacy_package_exists(): void
    {
        $admin = $this->admin();
        $order = $this->order($admin, OrderUserNoteStatus::ApprovedJasa);

        WorkshopWorkPackage::query()->create([
            'order_id' => $order->getKey(),
            'sequence' => 1,
            'display_no' => $order->nomor_order.'-01',
            'job_name' => 'Legacy package',
            'status' => WorkshopWorkPackage::STATUS_COMPLETED,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $this->assertFalse($order->isWorkshopOrder());
        $this->assertTrue($order->allWorkPackagesCompleted());
        $this->assertSame('Tidak dibagi', $order->workPackageProgressLabel());
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
    }

    private function order(User $admin, OrderUserNoteStatus $status): Order
    {
        $order = Order::query()->create([
            'nomor_order' => 'PKG-'.uniqid(),
            'nama_pekerjaan' => 'Pekerjaan Uji',
            'unit_kerja' => 'Workshop',
            'seksi' => 'Machine Workshop',
            'deskripsi' => 'Test',
            'prioritas' => Order::PRIORITY_LOW,
            'tanggal_order' => now()->toDateString(),
            'target_selesai' => now()->addDay()->toDateString(),
            'catatan_status' => $status->value,
            'catatan' => 'Regu Fabrikasi',
            'created_by' => $admin->id,
        ]);
        $order->orderWorkshop()->create(['progress_status' => OrderWorkshop::PROGRESS_IN_PROGRESS]);

        return $order->fresh('orderWorkshop');
    }
}
