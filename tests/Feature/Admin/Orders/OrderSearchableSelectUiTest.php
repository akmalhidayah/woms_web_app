<?php

namespace Tests\Feature\Admin\Orders;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Department;
use App\Models\Order;
use App\Models\UnitWork;
use App\Models\UnitWorkSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderSearchableSelectUiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
    }

    public function test_service_order_index_loads_one_reusable_searchable_support(): void
    {
        $this->structure();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('createOrderModal')
            ->assertSee('editOrderModal')
            ->assertSee('createPrioritas')
            ->assertSee('editPrioritas')
            ->assertSee('createCatatanStatus')
            ->assertSee('editCatatanStatus')
            ->assertSee('Cari atau pilih Unit Kerja...')
            ->assertSee('Cari atau pilih Seksi...')
            ->assertSee('data-order-unit-select', false)
            ->assertSee('data-order-section-select', false)
            ->assertSee('window.OrderStructureSelectPair', false);

        $this->assertSame(
            1,
            substr_count($response->getContent(), 'tom-select.complete.min.js')
        );
    }

    public function test_workshop_index_uses_the_same_searchable_support(): void
    {
        $this->structure();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.orders.workshop.index'))
            ->assertOk()
            ->assertSee('createOrderModal')
            ->assertSee('editOrderModal')
            ->assertSee('Cari atau pilih Unit Kerja...')
            ->assertSee('Cari atau pilih Seksi...')
            ->assertSee('data-order-unit-select', false)
            ->assertSee('data-order-section-select', false)
            ->assertSee('window.OrderStructureSelectPair', false);

        $this->assertSame(
            1,
            substr_count($response->getContent(), 'tom-select.complete.min.js')
        );
    }

    public function test_direct_create_renders_structure_selects(): void
    {
        [$unit, $section] = $this->structure();

        $this->actingAs($this->admin)
            ->get(route('admin.orders.create'))
            ->assertOk()
            ->assertSee('name="unit_kerja"', false)
            ->assertSee('name="seksi"', false)
            ->assertSee('data-order-unit-select', false)
            ->assertSee('data-order-section-select', false)
            ->assertSee($unit->name)
            ->assertSee($section->name)
            ->assertDontSee('type="text" value="" class="w-full', false);
    }

    public function test_direct_edit_preserves_active_structure_values(): void
    {
        [$unit, $section] = $this->structure();
        $order = $this->order($unit->name, $section->name);

        $this->actingAs($this->admin)
            ->get(route('admin.orders.edit', $order))
            ->assertOk()
            ->assertSee('value="'.$unit->name.'"', false)
            ->assertSee('value="'.$section->name.'"', false)
            ->assertSee('{ allowLegacy: false }', false);
    }

    public function test_direct_edit_renders_dynamic_fallback_for_legacy_pair(): void
    {
        $order = $this->order('Legacy Unit', 'Legacy Section');

        $this->actingAs($this->admin)
            ->get(route('admin.orders.edit', $order))
            ->assertOk()
            ->assertSee('value="Legacy Unit"', false)
            ->assertSee('value="Legacy Section"', false)
            ->assertSee('{ allowLegacy: true }', false);
    }

    /**
     * @return array{UnitWork, UnitWorkSection}
     */
    private function structure(): array
    {
        $department = Department::query()->create([
            'name' => 'Department UI',
        ]);
        $unit = UnitWork::query()->create([
            'department_id' => $department->id,
            'name' => 'Unit UI',
        ]);
        $section = UnitWorkSection::query()->create([
            'unit_work_id' => $unit->id,
            'name' => 'Seksi UI',
        ]);

        return [$unit, $section];
    }

    private function order(string $unit, string $section): Order
    {
        return Order::query()->create([
            'nomor_order' => 'ORDER-UI-'.uniqid(),
            'notifikasi' => 'NOTIF-UI-'.uniqid(),
            'nama_pekerjaan' => 'Pekerjaan searchable',
            'unit_kerja' => $unit,
            'seksi' => $section,
            'deskripsi' => 'Test UI searchable',
            'prioritas' => Order::PRIORITY_LOW,
            'tanggal_order' => '2026-07-27',
            'target_selesai' => '2026-08-01',
            'catatan_status' => OrderUserNoteStatus::Pending,
            'created_by' => $this->admin->id,
        ]);
    }
}
