<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\HppApprovalSetting;
use App\Models\UnitWork;
use App\Models\UnitWorkSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StructureOrganizationSectionStabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_updating_unit_preserves_existing_section_ids_and_hpp_setting(): void
    {
        $admin = $this->createSuperAdmin();
        $department = Department::query()->create(['name' => 'Departemen Teknik']);
        $unit = UnitWork::query()->create([
            'department_id' => $department->id,
            'name' => 'Unit Teknik',
        ]);
        $firstSection = UnitWorkSection::query()->create([
            'unit_work_id' => $unit->id,
            'name' => 'Seksi Fabrikasi',
        ]);
        $secondSection = UnitWorkSection::query()->create([
            'unit_work_id' => $unit->id,
            'name' => 'Seksi Konstruksi',
        ]);
        $setting = HppApprovalSetting::query()->create([
            'counter_part_unit_work_id' => $unit->id,
            'counter_part_section_id' => $firstSection->id,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.structure.update', $unit), [
                'department_id' => $department->id,
                'unit_name' => 'Unit Teknik Diperbarui',
                'sections' => [
                    ['id' => $firstSection->id, 'name' => 'Seksi Fabrikasi Baru'],
                    ['id' => $secondSection->id, 'name' => 'Seksi Konstruksi'],
                    ['name' => 'Seksi Perencanaan'],
                ],
            ])
            ->assertRedirect(route('admin.structure.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('unit_work_sections', [
            'id' => $firstSection->id,
            'unit_work_id' => $unit->id,
            'name' => 'Seksi Fabrikasi Baru',
        ]);
        $this->assertDatabaseHas('unit_work_sections', [
            'id' => $secondSection->id,
            'unit_work_id' => $unit->id,
            'name' => 'Seksi Konstruksi',
        ]);
        $this->assertSame($firstSection->id, $setting->fresh()->counter_part_section_id);
        $this->assertSame(3, $unit->sections()->count());
    }

    public function test_section_used_by_approval_setting_cannot_be_removed(): void
    {
        $admin = $this->createSuperAdmin();
        $department = Department::query()->create(['name' => 'Departemen Pengendali']);
        $unit = UnitWork::query()->create([
            'department_id' => $department->id,
            'name' => 'Unit Pengendali',
        ]);
        $usedSection = UnitWorkSection::query()->create([
            'unit_work_id' => $unit->id,
            'name' => 'Seksi Counter Part',
        ]);
        $retainedSection = UnitWorkSection::query()->create([
            'unit_work_id' => $unit->id,
            'name' => 'Seksi Lain',
        ]);
        HppApprovalSetting::query()->create([
            'counter_part_unit_work_id' => $unit->id,
            'counter_part_section_id' => $usedSection->id,
        ]);

        $this->actingAs($admin)
            ->put(route('admin.structure.update', $unit), [
                'department_id' => $department->id,
                'unit_name' => 'Nama yang Harus Di-rollback',
                'sections' => [
                    ['id' => $retainedSection->id, 'name' => $retainedSection->name],
                ],
            ])
            ->assertRedirect(route('admin.structure.index'))
            ->assertSessionHasErrors('sections');

        $this->assertDatabaseHas('unit_works', [
            'id' => $unit->id,
            'name' => 'Unit Pengendali',
        ]);
        $this->assertDatabaseHas('unit_work_sections', [
            'id' => $usedSection->id,
            'unit_work_id' => $unit->id,
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
