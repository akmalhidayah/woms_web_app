<?php

namespace Tests\Feature\Pkm;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Garansi;
use App\Models\Hpp;
use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobWaitingProgressValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_completed_job_waiting_shows_warranty_waiting_status_until_warranty_is_set(): void
    {
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $order = Order::query()->create([
            'nomor_order' => 'ORD-WAITING-WARRANTY',
            'notifikasi' => 'NOTIF-WAITING-WARRANTY',
            'nama_pekerjaan' => 'Pekerjaan Menunggu Garansi',
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Pengujian status menunggu set garansi',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'catatan_status' => OrderUserNoteStatus::ApprovedJasa->value,
            'tanggal_order' => now()->toDateString(),
            'target_selesai' => now()->addWeek()->toDateString(),
            'created_by' => $pkm->id,
        ]);
        $hpp = Hpp::query()->create([
            'order_id' => $order->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Workshop',
            'nilai_hpp_bucket' => 'under',
            'total_keseluruhan' => 1000000,
            'status' => Hpp::STATUS_APPROVED,
            'created_by' => $pkm->id,
        ]);
        PurchaseOrder::query()->create([
            'order_id' => $order->id,
            'hpp_id' => $hpp->id,
            'purchase_order_number' => 'PO-WAITING-WARRANTY',
            'approve_manager' => true,
            'approval_target' => 'setuju',
            'progress_pekerjaan' => 100,
            'created_by' => $pkm->id,
            'updated_by' => $pkm->id,
        ]);

        $this->actingAs($pkm)
            ->get(route('pkm.jobwaiting', ['search' => $order->nomor_order]))
            ->assertOk()
            ->assertSee('Menunggu Set Garansi')
            ->assertSee('data-job-progress="'.$order->nomor_order.'"', false)
            ->assertSee('aria-label="Progress pekerjaan 100%"', false)
            ->assertSee('bg-emerald-500', false);

        Garansi::query()->create([
            'order_id' => $order->id,
            'garansi_months' => 0,
            'start_date' => now()->toDateString(),
            'created_by' => $pkm->id,
        ]);

        $this->actingAs($pkm)
            ->get(route('pkm.jobwaiting', ['search' => $order->nomor_order]))
            ->assertOk()
            ->assertDontSee('Menunggu Set Garansi');
    }

    public function test_lower_progress_redirects_with_sweetalert_message_instead_of_500(): void
    {
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $order = Order::query()->create([
            'nomor_order' => 'ORD-PROGRESS-NO-ROLLBACK',
            'notifikasi' => 'NOTIF-PROGRESS-001',
            'nama_pekerjaan' => 'Pekerjaan Progress Tidak Mundur',
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Pengujian validasi progress',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => now()->toDateString(),
            'target_selesai' => now()->addWeek()->toDateString(),
            'created_by' => $pkm->id,
        ]);
        $hpp = Hpp::query()->create([
            'order_id' => $order->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Workshop',
            'nilai_hpp_bucket' => 'under',
            'total_keseluruhan' => 1000000,
            'status' => Hpp::STATUS_APPROVED,
            'created_by' => $pkm->id,
        ]);
        $purchaseOrder = PurchaseOrder::query()->create([
            'order_id' => $order->id,
            'hpp_id' => $hpp->id,
            'purchase_order_number' => 'PO-PROGRESS-001',
            'approve_manager' => true,
            'progress_pekerjaan' => 32,
            'created_by' => $pkm->id,
            'updated_by' => $pkm->id,
        ]);

        $response = $this->actingAs($pkm)->patch(route('pkm.jobwaiting.update', $order), [
            'progress_pekerjaan' => 20,
            '_filter_search' => $order->nomor_order,
            '_filter_page' => 1,
        ]);

        $response
            ->assertRedirect(route('pkm.jobwaiting', [
                'priority' => null,
                'search' => $order->nomor_order,
                'page' => 1,
            ]))
            ->assertSessionHas('error', 'Progres pekerjaan tidak dapat diturunkan dari 32% menjadi 20%.')
            ->assertSessionHasErrors('progress_pekerjaan');

        $this->assertSame(32, (int) $purchaseOrder->fresh()->progress_pekerjaan);

        $this->followingRedirects()
            ->actingAs($pkm)
            ->get(route('pkm.jobwaiting', ['search' => $order->nomor_order]))
            ->assertOk()
            ->assertSee('pkm-jobwaiting-error-alert', false)
            ->assertSee('Progress tidak dapat diperbarui');
    }

    public function test_start_requires_estimated_completion_and_admin_approval(): void
    {
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $order = Order::query()->create([
            'nomor_order' => 'ORD-START-READINESS',
            'notifikasi' => 'NOTIF-START-READINESS',
            'nama_pekerjaan' => 'Pekerjaan Dengan Gate Start',
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Pengujian estimasi dan persetujuan sebelum Start',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'catatan_status' => OrderUserNoteStatus::ApprovedJasa->value,
            'tanggal_order' => now()->toDateString(),
            'target_selesai' => now()->addMonth()->toDateString(),
            'created_by' => $pkm->id,
        ]);
        $hpp = Hpp::query()->create([
            'order_id' => $order->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Workshop',
            'nilai_hpp_bucket' => 'under',
            'total_keseluruhan' => 1000000,
            'status' => Hpp::STATUS_APPROVED,
            'created_by' => $pkm->id,
        ]);
        $purchaseOrder = PurchaseOrder::query()->create([
            'order_id' => $order->id,
            'hpp_id' => $hpp->id,
            'purchase_order_number' => 'PO-START-READINESS',
            'approve_manager' => true,
            'progress_pekerjaan' => 0,
            'created_by' => $pkm->id,
            'updated_by' => $pkm->id,
        ]);

        $bothMissingMessage = 'Estimasi penyelesaian belum diisi dan belum disetujui oleh Admin Bengkel.';
        $targetMissingMessage = 'Estimasi penyelesaian wajib diisi sebelum pekerjaan dimulai.';
        $approvalMissingMessage = 'Estimasi penyelesaian belum disetujui oleh Admin Bengkel.';

        $this->actingAs($pkm)
            ->get(route('pkm.jobwaiting', ['search' => $order->nomor_order]))
            ->assertOk()
            ->assertSee('class="pkm-start-blocked', false)
            ->assertSee($bothMissingMessage);

        $this->actingAs($pkm)
            ->patch(route('pkm.jobwaiting.update', $order), ['start_progress' => 1])
            ->assertSessionHas('error', $bothMissingMessage);
        $this->assertSame(0, (int) $purchaseOrder->fresh()->progress_pekerjaan);

        $purchaseOrder->update(['approval_target' => 'setuju']);
        $this->actingAs($pkm)
            ->patch(route('pkm.jobwaiting.update', $order), ['start_progress' => 1])
            ->assertSessionHas('error', $targetMissingMessage);
        $this->assertSame(0, (int) $purchaseOrder->fresh()->progress_pekerjaan);

        $purchaseOrder->update(['approval_target' => null]);
        $this->actingAs($pkm)
            ->patch(route('pkm.jobwaiting.update', $order), [
                'target_penyelesaian' => '2026-10-15',
            ])
            ->assertSessionHas('status');
        $this->actingAs($pkm)
            ->patch(route('pkm.jobwaiting.update', $order), ['start_progress' => 1])
            ->assertSessionHas('error', $approvalMissingMessage);
        $this->assertSame('2026-10-15', $purchaseOrder->fresh()->target_penyelesaian?->toDateString());

        $purchaseOrder->update(['approval_target' => 'setuju']);
        $this->actingAs($pkm)
            ->patch(route('pkm.jobwaiting.update', $order), ['start_progress' => 1])
            ->assertSessionHas('status', 'Progress pekerjaan ORD-START-READINESS berhasil dimulai.');

        $purchaseOrder->refresh();
        $this->assertSame(11, (int) $purchaseOrder->progress_pekerjaan);
        $this->assertNotNull($purchaseOrder->tanggal_mulai_pekerjaan);
        $this->assertSame('2026-10-15', $purchaseOrder->target_penyelesaian?->toDateString());
    }
}
