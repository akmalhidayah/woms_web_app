<?php

namespace Tests\Feature\Admin;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\BengkelTask;
use App\Models\Order;
use App\Models\OrderWorkshop;
use App\Models\QualityControlReport;
use App\Models\QualityControlSignature;
use App\Models\User;
use App\Services\BengkelTasks\WorkshopHandoverQueue;
use App\Support\WorkshopReadiness;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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

    public function test_sementara_proses_does_not_require_readiness(): void
    {
        $admin = $this->superAdmin();
        [$order, $task] = $this->workshopOrder($admin, OrderWorkshop::PROGRESS_MENUNGGU_JADWAL);

        $this->actingAs($admin)
            ->patch(route('admin.bengkel-tasks.progress.update', $task), [
                'progress_status' => OrderWorkshop::PROGRESS_IN_PROGRESS,
            ])
            ->assertSessionDoesntHaveErrors();

        $order->orderWorkshop()->update([
            'konfirmasi_anggaran' => OrderWorkshop::KONFIRMASI_MATERIAL_READY,
            'status_material' => OrderWorkshop::STATUS_MATERIAL_GOOD_ISSUE,
        ]);

        $this->assertDatabaseHas('order_workshops', [
            'order_id' => $order->id,
            'progress_status' => OrderWorkshop::PROGRESS_IN_PROGRESS,
        ]);
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

    public function test_qc_progress_counts_maker_workshop_and_user_stages(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('quality-control-maker-signatures/qc-maker.png', 'signature');

        $admin = $this->superAdmin();
        [$order] = $this->workshopOrder($admin, OrderWorkshop::PROGRESS_QUALITY_CONTROL, true);
        $report = QualityControlReport::create([
            'order_id' => $order->id,
            'type' => QualityControlReport::TYPE_FABRICATION,
            'report_no' => 'QC-3-STAGE',
            'report_date' => now()->toDateString(),
            'status' => QualityControlReport::STATUS_SUBMITTED,
            'payload' => ['signature' => [
                'signature_data' => 'quality-control-maker-signatures/qc-maker.png',
                'signer_name' => $admin->name,
                'signed_at' => now()->toDateString(),
            ]],
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        $report->signatures()->createMany([
            ['step_order' => 1, 'role_key' => QualityControlSignature::ROLE_WORKSHOP_MANAGER, 'role_label' => 'Manager Workshop', 'signer_user_id' => $admin->id, 'status' => QualityControlSignature::STATUS_PENDING],
            ['step_order' => 2, 'role_key' => QualityControlSignature::ROLE_USER_MANAGER, 'role_label' => 'Manager User', 'signer_user_id' => $admin->id, 'status' => QualityControlSignature::STATUS_LOCKED],
        ]);

        $this->assertSame(3, $report->fresh('signatures')->approvalStepCount());
        $this->assertSame(1, $report->fresh('signatures')->approvalSignedCount());
        $this->assertSame(33, $report->fresh('signatures')->approvalProgressPercent());

        $report->signatures()->where('role_key', QualityControlSignature::ROLE_WORKSHOP_MANAGER)->update([
            'status' => QualityControlSignature::STATUS_SIGNED,
            'signed_at' => now(),
        ]);
        $this->assertSame(2, $report->fresh('signatures')->approvalSignedCount());

        $report->signatures()->where('role_key', QualityControlSignature::ROLE_USER_MANAGER)->update([
            'status' => QualityControlSignature::STATUS_SIGNED,
            'signed_at' => now(),
        ]);
        $completed = $report->fresh('signatures');
        $this->assertSame(3, $completed->approvalSignedCount());
        $this->assertTrue($completed->approvalCompleted());
    }

    public function test_qc_submit_without_maker_signature_is_rejected(): void
    {
        $admin = $this->superAdmin();
        [$order] = $this->workshopOrder($admin, OrderWorkshop::PROGRESS_QUALITY_CONTROL, true);

        $this->actingAs($admin)
            ->post(route('admin.orders.workshop.quality-control.store', $order), [
                'intent' => 'submit',
                'report_date' => now()->toDateString(),
            ])
            ->assertSessionHasErrors('signature.signature_data');

        $this->assertDatabaseCount('quality_control_reports', 0);
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

    public function test_completed_legacy_order_skips_readiness_queue_and_enters_handover(): void
    {
        $admin = $this->superAdmin();
        [$completedOrder] = $this->workshopOrder($admin, OrderWorkshop::PROGRESS_DONE);
        [$activeOrder] = $this->workshopOrder($admin, OrderWorkshop::PROGRESS_IN_PROGRESS);

        $incompleteOrderIds = app(WorkshopReadiness::class)
            ->applyIncomplete(Order::query()->whereKey([$completedOrder->id, $activeOrder->id]))
            ->pluck('id');

        $this->assertFalse($incompleteOrderIds->contains($completedOrder->id));
        $this->assertTrue($incompleteOrderIds->contains($activeOrder->id));

        $handoverOrderIds = app(WorkshopHandoverQueue::class)
            ->query()
            ->whereKey([$completedOrder->id, $activeOrder->id])
            ->pluck('id');

        $this->assertTrue($handoverOrderIds->contains($completedOrder->id));
        $this->assertFalse($handoverOrderIds->contains($activeOrder->id));

        $this->actingAs($admin)
            ->get(route('admin.orders.workshop.index'))
            ->assertOk()
            ->assertSee('Perlu Tindakan')
            ->assertSee('Riwayat')
            ->assertDontSee($completedOrder->nomor_order)
            ->assertSee($activeOrder->nomor_order);

        $this->actingAs($admin)
            ->get(route('admin.orders.workshop.index', [
                'tab' => 'history',
            ]))
            ->assertOk()
            ->assertSee($completedOrder->nomor_order)
            ->assertDontSee($activeOrder->nomor_order);
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
