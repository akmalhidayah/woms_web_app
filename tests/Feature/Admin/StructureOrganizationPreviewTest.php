<?php

namespace Tests\Feature\Admin;

use App\Models\Department;
use App\Models\HppApprovalSetting;
use App\Models\UnitWork;
use App\Models\UnitWorkSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StructureOrganizationPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_preview_the_complete_organization_chart(): void
    {
        $admin = $this->createSuperAdmin();
        $dirops = User::factory()->create(['name' => 'Direktur Operasi Test']);
        $generalManager = User::factory()->create(['name' => 'General Manager Test']);
        $seniorManager = User::factory()->create(['name' => 'Senior Manager Test']);
        $manager = User::factory()->create(['name' => 'Manager Seksi Test']);
        $department = Department::query()->create([
            'name' => 'Departemen Project Test',
            'general_manager_id' => $generalManager->id,
        ]);
        $unit = UnitWork::query()->create([
            'department_id' => $department->id,
            'name' => 'Unit Workshop Test',
            'senior_manager_id' => $seniorManager->id,
        ]);
        UnitWorkSection::query()->create([
            'unit_work_id' => $unit->id,
            'name' => 'Seksi Fabrikasi Test',
            'manager_id' => $manager->id,
        ]);
        HppApprovalSetting::query()->create(['dirops_user_id' => $dirops->id]);

        $this->actingAs($admin)
            ->get(route('admin.structure.preview'))
            ->assertOk()
            ->assertSee('Direktur Operasi Test')
            ->assertSee('General Manager Test')
            ->assertSee('Departemen Project Test')
            ->assertSee('Senior Manager Test')
            ->assertSee('Unit Workshop Test')
            ->assertSee('Manager Seksi Test')
            ->assertSee('Seksi Fabrikasi Test')
            ->assertSee('data-structure-root', false)
            ->assertSee('data-structure-department', false)
            ->assertSee('data-structure-unit', false)
            ->assertSee('data-structure-chart-scroller', false);
    }

    public function test_preview_does_not_create_hpp_setting_as_a_read_side_effect(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)
            ->get(route('admin.structure.preview'))
            ->assertOk()
            ->assertSee('DIROPS belum dikonfigurasi');

        $this->assertDatabaseCount('hpp_approval_settings', 0);
    }

    public function test_admin_without_structure_permission_cannot_open_preview(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_ADMIN,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.structure.preview'))
            ->assertForbidden();
    }

    public function test_header_structure_icon_opens_preview_modal(): void
    {
        $admin = $this->createSuperAdmin();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('data-structure-preview-trigger', false)
            ->assertSee('Bagan Struktur Organisasi')
            ->assertSee('DIROPS Saja')
            ->assertSee('Sampai GM')
            ->assertSee('Sampai SM')
            ->assertSee('Zoom out bagan')
            ->assertSee('Zoom in bagan')
            ->assertSee('Geser bagan ke kanan');
    }

    private function createSuperAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
    }
}
