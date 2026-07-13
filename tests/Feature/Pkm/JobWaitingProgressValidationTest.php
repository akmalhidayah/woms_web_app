<?php

namespace Tests\Feature\Pkm;

use App\Models\Hpp;
use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobWaitingProgressValidationTest extends TestCase
{
    use RefreshDatabase;

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
}
