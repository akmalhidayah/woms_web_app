<?php

namespace Tests\Feature\Admin\Orders;

use App\Models\Department;
use App\Models\Order;
use App\Models\UnitWork;
use App\Models\UnitWorkSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderStructureSelectionTest extends TestCase
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

    public function test_store_accepts_registered_unit_and_section_pair(): void
    {
        [$unit, $section] = $this->structurePair('Unit A', 'Seksi A1');

        $this->actingAs($this->admin)
            ->post(route('admin.orders.store'), $this->storePayload([
                'unit_kerja' => $unit->name,
                'seksi' => $section->name,
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'nomor_order' => 'ORDER-STRUCTURE-001',
            'unit_kerja' => 'Unit A',
            'seksi' => 'Seksi A1',
        ]);
    }

    public function test_store_rejects_section_from_another_unit(): void
    {
        [$unitA] = $this->structurePair('Unit A', 'Seksi A1');
        [, $sectionB] = $this->structurePair('Unit B', 'Seksi B1');

        $this->actingAs($this->admin)
            ->from(route('admin.orders.create'))
            ->post(route('admin.orders.store'), $this->storePayload([
                'unit_kerja' => $unitA->name,
                'seksi' => $sectionB->name,
            ]))
            ->assertRedirect(route('admin.orders.create'))
            ->assertSessionHasErrors('seksi');
    }

    public function test_store_rejects_unknown_unit(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.orders.create'))
            ->post(route('admin.orders.store'), $this->storePayload([
                'unit_kerja' => 'Unit Palsu',
                'seksi' => 'Seksi Palsu',
            ]))
            ->assertRedirect(route('admin.orders.create'))
            ->assertSessionHasErrors('unit_kerja');
    }

    public function test_store_accepts_no_section_value_for_unit_without_sections(): void
    {
        $unit = $this->unitWithoutSections('Unit Tanpa Seksi');

        $this->actingAs($this->admin)
            ->post(route('admin.orders.store'), $this->storePayload([
                'unit_kerja' => $unit->name,
                'seksi' => 'Tidak ada seksi',
            ]))
            ->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'nomor_order' => 'ORDER-STRUCTURE-001',
            'unit_kerja' => 'Unit Tanpa Seksi',
            'seksi' => 'Tidak ada seksi',
        ]);
    }

    public function test_store_rejects_arbitrary_section_for_unit_without_sections(): void
    {
        $unit = $this->unitWithoutSections('Unit Tanpa Seksi');

        $this->actingAs($this->admin)
            ->from(route('admin.orders.create'))
            ->post(route('admin.orders.store'), $this->storePayload([
                'unit_kerja' => $unit->name,
                'seksi' => 'Seksi Palsu',
            ]))
            ->assertRedirect(route('admin.orders.create'))
            ->assertSessionHasErrors('seksi');
    }

    public function test_update_accepts_a_new_registered_pair(): void
    {
        [$oldUnit, $oldSection] = $this->structurePair('Unit Lama', 'Seksi Lama');
        [$newUnit, $newSection] = $this->structurePair('Unit Baru', 'Seksi Baru');
        $order = $this->order($oldUnit->name, $oldSection->name);

        $this->actingAs($this->admin)
            ->put(route('admin.orders.update', $order), $this->updatePayload($order, [
                'unit_kerja' => $newUnit->name,
                'seksi' => $newSection->name,
            ]))
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'unit_kerja' => 'Unit Baru',
            'seksi' => 'Seksi Baru',
        ]);
    }

    public function test_update_allows_unchanged_legacy_pair(): void
    {
        $order = $this->order('Legacy Unit', 'Legacy Section');

        $this->actingAs($this->admin)
            ->put(route('admin.orders.update', $order), $this->updatePayload($order, [
                'nama_pekerjaan' => 'Nama pekerjaan diperbarui',
            ]))
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'nama_pekerjaan' => 'Nama pekerjaan diperbarui',
            'unit_kerja' => 'Legacy Unit',
            'seksi' => 'Legacy Section',
        ]);
    }

    public function test_update_rejects_active_unit_with_legacy_section(): void
    {
        [$activeUnit] = $this->structurePair('Unit Aktif', 'Seksi Aktif');
        $order = $this->order('Legacy Unit', 'Legacy Section');

        $this->actingAs($this->admin)
            ->from(route('admin.orders.edit', $order))
            ->put(route('admin.orders.update', $order), $this->updatePayload($order, [
                'unit_kerja' => $activeUnit->name,
                'seksi' => 'Legacy Section',
            ]))
            ->assertRedirect(route('admin.orders.edit', $order))
            ->assertSessionHasErrors('seksi');
    }

    /**
     * @return array{UnitWork, UnitWorkSection}
     */
    private function structurePair(string $unitName, string $sectionName): array
    {
        $unit = $this->unitWithoutSections($unitName);
        $section = UnitWorkSection::query()->create([
            'unit_work_id' => $unit->id,
            'name' => $sectionName,
        ]);

        return [$unit, $section];
    }

    private function unitWithoutSections(string $unitName): UnitWork
    {
        $department = Department::query()->create([
            'name' => 'Department '.$unitName,
        ]);

        return UnitWork::query()->create([
            'department_id' => $department->id,
            'name' => $unitName,
        ]);
    }

    private function order(string $unit, string $section): Order
    {
        return Order::query()->create([
            ...$this->storePayload([
                'nomor_order' => 'ORDER-LEGACY-001',
                'unit_kerja' => $unit,
                'seksi' => $section,
            ]),
            'created_by' => $this->admin->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function storePayload(array $overrides = []): array
    {
        return [
            'nomor_order' => 'ORDER-STRUCTURE-001',
            'notifikasi' => 'NOTIF-STRUCTURE-001',
            'nama_pekerjaan' => 'Pekerjaan struktur',
            'unit_kerja' => 'Unit A',
            'seksi' => 'Seksi A1',
            'deskripsi' => 'Pengujian pasangan struktur organisasi',
            'prioritas' => Order::PRIORITY_LOW,
            'tanggal_order' => '2026-07-27',
            'target_selesai' => '2026-08-01',
            'catatan_status' => 'pending',
            'catatan' => null,
            ...$overrides,
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function updatePayload(Order $order, array $overrides = []): array
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
            ...$overrides,
        ];
    }
}
