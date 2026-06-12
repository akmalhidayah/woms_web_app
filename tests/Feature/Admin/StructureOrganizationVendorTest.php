<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\VendorWorkType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StructureOrganizationVendorTest extends TestCase
{
    use RefreshDatabase;

    public function test_structure_page_only_exposes_fixed_vendor_section_management(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)
            ->get(route('admin.structure.index'))
            ->assertOk()
            ->assertSee(VendorWorkType::FIXED_VENDOR_NAME)
            ->assertSee('Kelola Seksi '.VendorWorkType::FIXED_VENDOR_NAME)
            ->assertSee('Edit Master HPP')
            ->assertSee('Tambah Unit')
            ->assertDontSee('Master Approval Khusus HPP')
            ->assertDontSee('Struktur Organisasi Vendor')
            ->assertDontSee('Seksi Vendor Aktif')
            ->assertDontSee('Tambah Vendor')
            ->assertDontSee('Nama Vendor');
    }

    public function test_new_vendor_creation_and_fixed_vendor_deletion_are_blocked(): void
    {
        $admin = $this->createSuperAdmin();
        $vendor = VendorWorkType::query()
            ->where('name', VendorWorkType::FIXED_VENDOR_NAME)
            ->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.structure.vendor-structures.store'), [
                'name' => 'Vendor Baru',
                'sections' => [],
            ])
            ->assertStatus(405);

        $this->actingAs($admin)
            ->delete(route('admin.structure.vendor-structures.destroy', $vendor))
            ->assertStatus(405);

        $this->assertDatabaseCount('vendor_work_types', 1);
        $this->assertDatabaseHas('vendor_work_types', [
            'id' => $vendor->id,
            'name' => VendorWorkType::FIXED_VENDOR_NAME,
        ]);
    }

    public function test_admin_can_only_update_sections_under_fixed_vendor(): void
    {
        $admin = $this->createSuperAdmin();
        $managerOne = User::factory()->create();
        $managerTwo = User::factory()->create();
        $vendor = VendorWorkType::query()
            ->where('name', VendorWorkType::FIXED_VENDOR_NAME)
            ->firstOrFail();

        $this->actingAs($admin)
            ->put(route('admin.structure.vendor-structures.update', $vendor), [
                'name' => 'Nama yang harus diabaikan',
                'sections' => [
                    ['name' => 'Pekerjaan Fabrikasi', 'manager_id' => $managerOne->id],
                    ['name' => 'Pekerjaan Konstruksi', 'manager_id' => $managerTwo->id],
                ],
            ])
            ->assertRedirect(route('admin.structure.index'));

        $this->assertSame(VendorWorkType::FIXED_VENDOR_NAME, $vendor->fresh()->name);
        $this->assertDatabaseHas('vendor_work_type_sections', [
            'vendor_work_type_id' => $vendor->id,
            'name' => 'Pekerjaan Fabrikasi',
            'manager_id' => $managerOne->id,
        ]);
        $this->assertDatabaseHas('vendor_work_type_sections', [
            'vendor_work_type_id' => $vendor->id,
            'name' => 'Pekerjaan Konstruksi',
            'manager_id' => $managerTwo->id,
        ]);
    }

    private function createSuperAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
    }
}
