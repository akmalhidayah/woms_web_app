<?php

namespace Tests\Feature\Admin\Orders;

use App\Domain\Orders\Enums\OrderDocumentType;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\OrderScopeOfWork;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderIndexFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_filter_orders_by_document_completeness(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);

        $completeOrder = $this->createOrder($admin, 'ORD-COMPLETE-001', 'Order Dokumen Lengkap');
        $incompleteOrder = $this->createOrder($admin, 'ORD-INCOMPLETE-001', 'Order Dokumen Belum Lengkap');

        foreach ([OrderDocumentType::Abnormalitas, OrderDocumentType::GambarTeknik] as $documentType) {
            OrderDocument::query()->create([
                'order_id' => $completeOrder->id,
                'jenis_dokumen' => $documentType,
                'nama_file_asli' => $documentType->value.'.pdf',
                'path_file' => 'orders/'.$documentType->value.'.pdf',
                'uploaded_by' => $admin->id,
                'uploaded_at' => now(),
            ]);
        }

        OrderScopeOfWork::query()->create([
            'order_id' => $completeOrder->id,
            'nama_penginput' => $admin->name,
            'tanggal_dokumen' => '2026-06-07',
            'scope_items' => [
                ['scope_pekerjaan' => 'Pemeriksaan', 'qty' => '1', 'satuan' => 'lot', 'keterangan' => null],
            ],
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.index', ['document_status' => 'complete']))
            ->assertOk()
            ->assertSee($completeOrder->nomor_order)
            ->assertDontSee($incompleteOrder->nomor_order)
            ->assertSee('Kelengkapan Dokumen');

        $this->actingAs($admin)
            ->get(route('admin.orders.index', ['document_status' => 'incomplete']))
            ->assertOk()
            ->assertSee($incompleteOrder->nomor_order)
            ->assertDontSee($completeOrder->nomor_order);
    }

    private function createOrder(User $admin, string $number, string $name): Order
    {
        return Order::query()->create([
            'nomor_order' => $number,
            'nama_pekerjaan' => $name,
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Detail pekerjaan test',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-06-01',
            'target_selesai' => '2026-06-10',
            'created_by' => $admin->id,
        ]);
    }
}
