<?php

namespace Tests\Feature\Admin\Hpp;

use App\Models\Hpp;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateHppTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_hpp_from_selected_order_and_snapshots_order_fields(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);

        $order = Order::query()->create([
            'nomor_order' => 'ORD-2026-0001',
            'nama_pekerjaan' => 'Perbaikan conveyor raw mill',
            'unit_kerja' => 'Unit Produksi Raw Mill',
            'seksi' => 'Maintenance',
            'deskripsi' => 'Perbaikan roller dan housing conveyor.',
            'prioritas' => Order::PRIORITY_HIGH,
            'tanggal_order' => '2026-04-04',
            'target_selesai' => '2026-04-10',
            'created_by' => $admin->id,
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.hpp.store'), [
                'action' => 'submit',
                'order_id' => $order->id,
                'kategori_pekerjaan' => 'Fabrikasi',
                'area_pekerjaan' => 'Dalam',
                'nilai_hpp_bucket' => 'under',
                'cost_centre' => 'CC-RM-001',
                'unit_kerja_pengendali' => 'Unit Workshop',
                'outline_agreement' => 'OA/2026/001',
                'periode_outline_agreement' => '01/01/2026 - 31/12/2026',
                'jenis_label_visible' => [
                    0 => 'Material Utama',
                ],
                'nama_item' => [
                    0 => ['Plat baja'],
                ],
                'jumlah_item' => [
                    0 => ['2 lembar'],
                ],
                'qty' => [
                    0 => [2],
                ],
                'satuan' => [
                    0 => ['Lembar'],
                ],
                'harga_satuan' => [
                    0 => [1500000],
                ],
                'keterangan' => [
                    0 => ['Untuk repair conveyor'],
                ],
            ]);

        $response
            ->assertRedirect(route('admin.hpp.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseCount('hpps', 1);

        $hpp = Hpp::query()->firstOrFail();

        $this->assertSame($order->id, $hpp->order_id);
        $this->assertSame('ORD-2026-0001', $hpp->nomor_order);
        $this->assertSame('Perbaikan conveyor raw mill', $hpp->nama_pekerjaan);
        $this->assertSame('Unit Produksi Raw Mill', $hpp->unit_kerja);
        $this->assertSame(Hpp::STATUS_IN_REVIEW, $hpp->status);
        $this->assertSame(3000000.0, (float) $hpp->total_keseluruhan);
        $this->assertSame('FAB-DALAM-UNDER250', $hpp->approval_case);
        $this->assertSame('Material Utama', $hpp->item_groups[0]['jenis_item']);
        $this->assertSame('Plat baja', $hpp->item_groups[0]['items'][0]['nama_item']);
    }
}
