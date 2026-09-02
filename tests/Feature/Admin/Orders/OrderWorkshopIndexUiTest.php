<?php

namespace Tests\Feature\Admin\Orders;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Order;
use App\Models\OrderWorkshop;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderWorkshopIndexUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_workshop_order_filters_use_fixed_pagination_and_compact_controls(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);

        foreach (range(1, 11) as $index) {
            Order::query()->create([
                'nomor_order' => sprintf('WORKSHOP-%03d', $index),
                'nama_pekerjaan' => 'Pekerjaan Bengkel '.$index,
                'unit_kerja' => 'Unit Test',
                'seksi' => 'Seksi Test',
                'deskripsi' => 'Detail pekerjaan bengkel',
                'prioritas' => Order::PRIORITY_MEDIUM,
                'catatan_status' => OrderUserNoteStatus::ApprovedWorkshop,
                'catatan' => 'Regu Fabrikasi',
                'tanggal_order' => '2026-06-01',
                'target_selesai' => '2026-06-10',
                'created_by' => $admin->id,
            ]);
        }

        $this->actingAs($admin)
            ->get(route('admin.orders.workshop.index', ['perPage' => 50]))
            ->assertOk()
            ->assertViewHas('orders', fn ($orders) => $orders->perPage() === 10 && $orders->count() === 10)
            ->assertSee('Semua Progress')
            ->assertSee('Semua Regu')
            ->assertDontSee('Per Halaman')
            ->assertDontSee('Pilih regu untuk langsung memfilter tabel.');
    }

    public function test_estimator_regu_is_available_in_workshop_orders_and_cannot_enter_quality_control(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
        $estimatorOrder = $this->makeWorkshopOrder($admin, 'WORKSHOP-ESTIMATOR-001', Order::WORKSHOP_REGU_ESTIMATOR);
        $fabrikasiOrder = $this->makeWorkshopOrder($admin, 'WORKSHOP-FABRIKASI-001', Order::WORKSHOP_REGU_FABRIKASI);
        $estimatorOrder->orderWorkshop()->create([
            'preparation_status' => OrderWorkshop::PREPARATION_COMPLETED,
            'progress_status' => OrderWorkshop::PROGRESS_IN_PROGRESS,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.workshop.index', ['regu' => Order::WORKSHOP_REGU_ESTIMATOR]))
            ->assertOk()
            ->assertSee('Regu Estimator')
            ->assertSee($estimatorOrder->nomor_order)
            ->assertDontSee($fabrikasiOrder->nomor_order);

        $this->actingAs($admin)
            ->patchJson(route('admin.orders.workshop.update', $estimatorOrder), [
                'progress_status' => OrderWorkshop::PROGRESS_QUALITY_CONTROL,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('progress_status');

        $this->assertDatabaseHas('order_workshops', [
            'order_id' => $estimatorOrder->id,
            'progress_status' => OrderWorkshop::PROGRESS_IN_PROGRESS,
        ]);

        $this->actingAs($admin)
            ->patchJson(route('admin.orders.workshop.update', $estimatorOrder), [
                'progress_status' => OrderWorkshop::PROGRESS_DONE,
            ])
            ->assertOk();

        $this->assertDatabaseHas('order_workshops', [
            'order_id' => $estimatorOrder->id,
            'progress_status' => OrderWorkshop::PROGRESS_DONE,
        ]);
    }

    public function test_workshop_document_page_returns_to_workshop_index_and_preserves_its_context_after_upload(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
        $order = $this->makeWorkshopOrder($admin, 'WORKSHOP-DOCUMENT-001', Order::WORKSHOP_REGU_FABRIKASI);
        $order->orderWorkshop()->create([
            'progress_status' => OrderWorkshop::PROGRESS_MENUNGGU_JADWAL,
        ]);
        $workshopIndexUrl = route('admin.orders.workshop.index', ['search' => $order->nomor_order]);

        $this->actingAs($admin)
            ->get(route('admin.orders.documents.index', $order))
            ->assertOk()
            ->assertSee($workshopIndexUrl, false);

        $this->actingAs($admin)
            ->post(route('admin.orders.documents.store', $order))
            ->assertRedirect(route('admin.orders.documents.index', $order));
    }

    private function makeWorkshopOrder(User $admin, string $nomorOrder, string $regu): Order
    {
        return Order::query()->create([
            'nomor_order' => $nomorOrder,
            'nama_pekerjaan' => 'Pekerjaan Bengkel '.$nomorOrder,
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Detail pekerjaan bengkel',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'catatan_status' => OrderUserNoteStatus::ApprovedWorkshop,
            'catatan' => $regu,
            'tanggal_order' => '2026-06-01',
            'target_selesai' => '2026-06-10',
            'created_by' => $admin->id,
        ]);
    }
}
