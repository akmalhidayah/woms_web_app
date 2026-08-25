<?php

namespace Tests\Feature\Admin;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\BengkelTask;
use App\Models\Order;
use App\Models\OrderWorkshop;
use App\Models\QualityControlReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkshopFlowIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_display_requires_an_order_number_and_creates_linked_records(): void
    {
        $admin = $this->superAdmin();

        $this->actingAs($admin)
            ->post(route('admin.bengkel-tasks.store'), ['job_name' => 'Pekerjaan Uji'])
            ->assertSessionHasErrors('nomor_order');

        $this->actingAs($admin)
            ->post(route('admin.bengkel-tasks.store'), [
                'nomor_order' => 'BENGKEL-INPUT-001',
                'notification_number' => 'NOTIF-001',
                'job_name' => 'Pekerjaan Uji',
                'progress_status' => OrderWorkshop::PROGRESS_MENUNGGU_JADWAL,
            ])
            ->assertRedirect();

        $order = Order::query()->where('nomor_order', 'BENGKEL-INPUT-001')->firstOrFail();
        $this->assertSame('NOTIF-001', $order->notifikasi);
        $this->assertDatabaseHas('order_workshops', ['order_id' => $order->id]);
        $this->assertDatabaseHas('bengkel_tasks', ['order_id' => $order->id]);
        $this->assertStringNotContainsString('MANUAL-BENGKEL-', $order->nomor_order);

        $this->actingAs($admin)
            ->get(route('admin.bengkel-tasks.index'))
            ->assertOk()
            ->assertSee('BENGKEL-INPUT-001')
            ->assertDontSee('@empty', false)
            ->assertDontSee('@include', false);
    }

    public function test_advanced_progress_is_blocked_until_readiness_is_complete(): void
    {
        $admin = $this->superAdmin();
        [$order, $task] = $this->workshopOrder($admin, OrderWorkshop::PROGRESS_MENUNGGU_JADWAL);

        $this->actingAs($admin)
            ->patch(route('admin.bengkel-tasks.progress.update', $task), [
                'progress_status' => OrderWorkshop::PROGRESS_IN_PROGRESS,
            ])
            ->assertSessionHasErrors('progress_status');

        $order->orderWorkshop()->update([
            'konfirmasi_anggaran' => OrderWorkshop::KONFIRMASI_MATERIAL_READY,
            'status_material' => OrderWorkshop::STATUS_MATERIAL_GOOD_ISSUE,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.bengkel-tasks.progress.update', $task), [
                'progress_status' => OrderWorkshop::PROGRESS_IN_PROGRESS,
            ])
            ->assertSessionDoesntHaveErrors();
    }

    public function test_readiness_fields_can_be_saved_incrementally_for_legacy_advanced_progress(): void
    {
        $admin = $this->superAdmin();
        [$order] = $this->workshopOrder($admin, OrderWorkshop::PROGRESS_IN_PROGRESS);

        $this->actingAs($admin)
            ->patchJson(route('admin.orders.workshop.update', $order), [
                'konfirmasi_anggaran' => OrderWorkshop::KONFIRMASI_MATERIAL_READY,
            ])
            ->assertOk();

        $this->assertDatabaseHas('order_workshops', [
            'order_id' => $order->id,
            'konfirmasi_anggaran' => OrderWorkshop::KONFIRMASI_MATERIAL_READY,
            'status_material' => null,
        ]);

        $this->actingAs($admin)
            ->patchJson(route('admin.orders.workshop.update', $order), [
                'status_material' => OrderWorkshop::STATUS_MATERIAL_GOOD_ISSUE,
            ])
            ->assertOk();

        $this->assertDatabaseHas('order_workshops', [
            'order_id' => $order->id,
            'konfirmasi_anggaran' => OrderWorkshop::KONFIRMASI_MATERIAL_READY,
            'status_material' => OrderWorkshop::STATUS_MATERIAL_GOOD_ISSUE,
            'progress_status' => OrderWorkshop::PROGRESS_IN_PROGRESS,
        ]);
    }

    public function test_qc_draft_has_no_signatures_and_real_queues_render(): void
    {
        $admin = $this->superAdmin();
        [$order] = $this->workshopOrder($admin, OrderWorkshop::PROGRESS_QUALITY_CONTROL, true);

        $this->actingAs($admin)
            ->post(route('admin.orders.workshop.quality-control.store', $order), [
                'status' => QualityControlReport::STATUS_DRAFT,
                'report_date' => now()->toDateString(),
            ])
            ->assertRedirect();

        $report = $order->qualityControlReports()->firstOrFail();
        $this->assertSame(0, $report->signatures()->count());

        $this->actingAs($admin)
            ->get(route('admin.workshop-quality-control.index', ['status' => '']))
            ->assertOk()
            ->assertSee($order->nomor_order)
            ->assertSee('Dalam Pemeriksaan');
    }

    public function test_non_critical_done_order_enters_real_handover_queue(): void
    {
        $admin = $this->superAdmin();
        [$order] = $this->workshopOrder($admin, OrderWorkshop::PROGRESS_DONE, true);

        $this->actingAs($admin)
            ->get(route('admin.workshop-handover.index'))
            ->assertOk()
            ->assertSee($order->nomor_order)
            ->assertSee('Non-Critical')
            ->assertSee('Menunggu Bukti Serah Terima')
            ->assertDontSee('MANUAL-BENGKEL-000206');
    }

    private function superAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
    }

    /** @return array{Order, BengkelTask} */
    private function workshopOrder(User $admin, string $progress, bool $ready = false): array
    {
        $order = Order::query()->create([
            'nomor_order' => 'WORKSHOP-'.uniqid(),
            'nama_pekerjaan' => 'Pekerjaan Bengkel Test',
            'unit_kerja' => 'Workshop',
            'seksi' => 'Machine Workshop',
            'deskripsi' => 'Test',
            'prioritas' => Order::PRIORITY_LOW,
            'tanggal_order' => now()->toDateString(),
            'target_selesai' => now()->addDay()->toDateString(),
            'catatan_status' => OrderUserNoteStatus::ApprovedWorkshop->value,
            'catatan' => 'Regu Fabrikasi',
            'created_by' => $admin->id,
        ]);
        $order->orderWorkshop()->create([
            'progress_status' => $progress,
            'konfirmasi_anggaran' => $ready ? OrderWorkshop::KONFIRMASI_MATERIAL_READY : null,
            'status_material' => $ready ? OrderWorkshop::STATUS_MATERIAL_GOOD_ISSUE : null,
        ]);
        $task = BengkelTask::query()->create([
            'order_id' => $order->id,
            'job_name' => $order->nama_pekerjaan,
            'progress_status' => $progress,
            'is_completed' => $progress === OrderWorkshop::PROGRESS_DONE,
        ]);

        return [$order->fresh('orderWorkshop'), $task];
    }
}
