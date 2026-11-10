<?php

namespace Tests\Feature\Admin\Orders;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Department;
use App\Models\Order;
use App\Models\OrderWorkshop;
use App\Models\UnitWork;
use App\Models\User;
use App\Services\BengkelTasks\WorkshopHandoverQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderWorkshopMaterialServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private array $payload;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
        $department = Department::query()->create(['name' => 'Department Material']);
        $unit = UnitWork::query()->create(['department_id' => $department->id, 'name' => 'Unit Material']);
        $section = $unit->sections()->create(['name' => 'Seksi Material']);
        $this->payload = [
            'nomor_order' => 'WORKSHOP-MAT-001',
            'notifikasi' => 'NOTIF-MAT-001',
            'nama_pekerjaan' => 'Pelayanan Material Plat',
            'unit_kerja' => $unit->name,
            'seksi' => $section->name,
            'deskripsi' => 'Order pekerjaan bengkel',
            'prioritas' => Order::PRIORITY_LOW,
            'tanggal_order' => '2026-10-10',
            'target_selesai' => '2026-10-20',
            'catatan_status' => OrderUserNoteStatus::ApprovedWorkshop->value,
            'catatan' => Order::WORKSHOP_REGU_FABRIKASI,
        ];
        $this->actingAs($this->admin);
    }

    public function test_material_service_order_is_completed_immediately_and_skips_handover(): void
    {
        $this->post(route('admin.orders.workshop.store'), [...$this->payload, 'pelayanan_material' => '1'])
            ->assertRedirect(route('admin.orders.workshop.index', ['search' => 'WORKSHOP-MAT-001', 'tab' => 'history']))
            ->assertSessionHas('status')
            ->assertSessionMissing('work_package_order');

        $order = Order::query()->where('nomor_order', 'WORKSHOP-MAT-001')->firstOrFail();
        $workshop = $order->orderWorkshop;

        $this->assertSame(OrderWorkshop::PROGRESS_DONE, $workshop->progress_status);
        $this->assertNotNull($workshop->legacy_completed_at);
        $this->assertTrue($order->bengkelTasks()->firstOrFail()->is_completed);
        $this->assertFalse(app(WorkshopHandoverQueue::class)->query()->whereKey($order->id)->exists());
        $this->assertDatabaseMissing('workshop_handovers', ['order_id' => $order->id]);

        $this->get(route('admin.workshop-handover.index', ['tab' => 'history']))
            ->assertOk()
            ->assertSee('WORKSHOP-MAT-001')
            ->assertSee('Pelayanan Material')
            ->assertDontSee('Data Legacy');
    }

    public function test_order_without_the_checkbox_follows_the_normal_flow(): void
    {
        $this->post(route('admin.orders.workshop.store'), $this->payload)
            ->assertRedirect(route('admin.orders.workshop.index', ['search' => 'WORKSHOP-MAT-001']))
            ->assertSessionHas('work_package_order');

        $workshop = Order::query()->where('nomor_order', 'WORKSHOP-MAT-001')->firstOrFail()->orderWorkshop;

        $this->assertNotSame(OrderWorkshop::PROGRESS_DONE, $workshop->progress_status);
        $this->assertNull($workshop->legacy_completed_at);
    }

    public function test_unchecked_value_is_not_treated_as_material_service(): void
    {
        $this->post(route('admin.orders.workshop.store'), [...$this->payload, 'pelayanan_material' => '0'])
            ->assertSessionHasNoErrors();

        $this->assertNull(Order::query()->where('nomor_order', 'WORKSHOP-MAT-001')->firstOrFail()->orderWorkshop->legacy_completed_at);
    }

    public function test_material_service_flag_is_rejected_on_service_order_creation(): void
    {
        $this->post(route('admin.orders.store'), [
            ...$this->payload,
            'catatan_status' => OrderUserNoteStatus::Pending->value,
            'catatan' => null,
            'pelayanan_material' => '1',
        ])->assertSessionHasErrors('pelayanan_material');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_create_modal_shows_the_checkbox_next_to_biaya(): void
    {
        $this->get(route('admin.orders.workshop.index'))
            ->assertOk()
            ->assertSee('id="createPelayananMaterial"', false)
            ->assertSee('name="pelayanan_material"', false);
    }
}
