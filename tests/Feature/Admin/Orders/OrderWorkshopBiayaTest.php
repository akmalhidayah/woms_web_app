<?php

namespace Tests\Feature\Admin\Orders;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Department;
use App\Models\Order;
use App\Models\UnitWork;
use App\Models\UnitWorkSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderWorkshopBiayaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private UnitWork $unit;

    private UnitWorkSection $section;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);

        $department = Department::query()->create([
            'name' => 'Department Biaya Bengkel',
        ]);
        $this->unit = UnitWork::query()->create([
            'department_id' => $department->id,
            'name' => 'Unit Biaya Bengkel',
        ]);
        $this->section = UnitWorkSection::query()->create([
            'unit_work_id' => $this->unit->id,
            'name' => 'Seksi Biaya Bengkel',
        ]);
    }

    public function test_workshop_order_can_store_numeric_biaya_or_null(): void
    {
        $this->assertTrue(Schema::hasColumn('orders', 'biaya'));

        $this->actingAs($this->admin)
            ->get(route('admin.orders.workshop.index'))
            ->assertOk()
            ->assertSee('id="createBiayaDisplay"', false)
            ->assertSee('id="editBiayaDisplay"', false)
            ->assertSee('name="biaya"', false);

        $this->actingAs($this->admin)
            ->post(route('admin.orders.workshop.store'), $this->payload([
                'nomor_order' => 'WORKSHOP-BIAYA-001',
                'biaya' => '1250000',
            ]))
            ->assertRedirect();

        $orderWithBiaya = Order::query()->where('nomor_order', 'WORKSHOP-BIAYA-001')->firstOrFail();

        $this->assertSame('1250000.00', $orderWithBiaya->biaya);
        $this->assertNotNull($orderWithBiaya->orderWorkshop);

        $this->actingAs($this->admin)
            ->post(route('admin.orders.workshop.store'), $this->payload([
                'nomor_order' => 'WORKSHOP-BIAYA-NULL-001',
                'notifikasi' => 'NOTIF-BIAYA-NULL-001',
                'biaya' => '',
            ]))
            ->assertRedirect();

        $orderWithoutBiaya = Order::query()->where('nomor_order', 'WORKSHOP-BIAYA-NULL-001')->firstOrFail();

        $this->assertNull($orderWithoutBiaya->biaya);
    }

    public function test_workshop_order_biaya_can_be_updated_and_cleared(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.orders.workshop.store'), $this->payload([
                'nomor_order' => 'WORKSHOP-BIAYA-EDIT-001',
                'notifikasi' => 'NOTIF-BIAYA-EDIT-001',
                'biaya' => '1000000',
            ]))
            ->assertRedirect();

        $order = Order::query()->where('nomor_order', 'WORKSHOP-BIAYA-EDIT-001')->firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('admin.orders.update', $order), $this->updatePayload($order, '2750000'))
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertSame('2750000.00', $order->refresh()->biaya);

        $this->actingAs($this->admin)
            ->put(route('admin.orders.update', $order), $this->updatePayload($order, ''))
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertNull($order->refresh()->biaya);
    }

    public function test_workshop_biaya_only_accepts_unformatted_non_negative_numbers(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.orders.workshop.store'), $this->payload([
                'nomor_order' => 'WORKSHOP-BIAYA-FORMAT-001',
                'biaya' => 'Rp 1.250.000',
            ]))
            ->assertSessionHasErrors('biaya');

        $this->actingAs($this->admin)
            ->post(route('admin.orders.workshop.store'), $this->payload([
                'nomor_order' => 'WORKSHOP-BIAYA-NEGATIF-001',
                'biaya' => '-1',
            ]))
            ->assertSessionHasErrors('biaya');
    }

    public function test_biaya_is_not_accepted_by_order_jasa_store(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.orders.store'), $this->payload([
                'nomor_order' => 'ORDER-JASA-BIAYA-001',
                'biaya' => '1250000',
            ]))
            ->assertSessionHasErrors('biaya');

        $this->assertDatabaseMissing('orders', [
            'nomor_order' => 'ORDER-JASA-BIAYA-001',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'nomor_order' => 'WORKSHOP-BIAYA-BASE-001',
            'notifikasi' => 'NOTIF-BIAYA-BASE-001',
            'nama_pekerjaan' => 'Pekerjaan dengan biaya',
            'unit_kerja' => $this->unit->name,
            'seksi' => $this->section->name,
            'deskripsi' => 'Order pekerjaan bengkel',
            'prioritas' => Order::PRIORITY_LOW,
            'tanggal_order' => '2026-09-04',
            'target_selesai' => '2026-09-10',
            'catatan_status' => OrderUserNoteStatus::ApprovedWorkshop->value,
            'catatan' => Order::WORKSHOP_REGU_FABRIKASI,
            ...$overrides,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function updatePayload(Order $order, mixed $biaya): array
    {
        return [
            'nomor_order' => $order->nomor_order,
            'notifikasi' => $order->notifikasi,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'deskripsi' => $order->deskripsi,
            'prioritas' => $order->prioritas,
            'tanggal_order' => $order->tanggal_order?->format('Y-m-d'),
            'target_selesai' => $order->target_selesai?->format('Y-m-d'),
            'catatan_status' => $order->catatan_status?->value,
            'catatan' => $order->catatan,
            'biaya' => $biaya,
        ];
    }
}
