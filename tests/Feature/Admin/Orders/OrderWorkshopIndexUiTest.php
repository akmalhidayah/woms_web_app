<?php

namespace Tests\Feature\Admin\Orders;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Order;
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
}
