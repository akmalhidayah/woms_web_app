<?php

namespace Tests\Feature\Pkm;

use App\Models\User;
use App\Models\VendorWorkType;
use App\Models\VendorWorkTypeSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_pkm_saves_section_with_approver_manager(): void
    {
        [$pkm, $vendor] = $this->context();
        $manager = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $this->actingAs($pkm)->put(route('pkm.vendor-structure.update', $vendor), [
            'sections' => [['name' => 'Pengerjaan Mesin', 'manager_id' => $manager->id]],
        ])->assertSessionHas('status');
        $this->assertDatabaseHas('vendor_work_type_sections', ['vendor_work_type_id' => $vendor->id, 'name' => 'Pengerjaan Mesin', 'manager_id' => $manager->id]);
    }

    public function test_non_approver_manager_roles_and_invalid_id_are_rejected(): void
    {
        foreach ([User::ROLE_PKM, User::ROLE_ADMIN, User::ROLE_USER] as $role) {
            [$pkm, $vendor] = $this->context();
            $manager = User::factory()->create(['role' => $role]);
            $this->actingAs($pkm)->from(route('pkm.dashboard'))->put(route('pkm.vendor-structure.update', $vendor), [
                'sections' => [['name' => 'Seksi '.$role, 'manager_id' => $manager->id]],
            ])->assertSessionHasErrors('sections.0.manager_id', null, 'pkmVendorStructure')
                ->assertSessionHas('pkm_vendor_structure_modal', true);
        }

        [$pkm, $vendor] = $this->context();
        $this->actingAs($pkm)->put(route('pkm.vendor-structure.update', $vendor), [
            'sections' => [['name' => 'Invalid', 'manager_id' => 999999]],
        ])->assertSessionHasErrors('sections.0.manager_id', null, 'pkmVendorStructure');
    }

    public function test_layout_only_exposes_assigned_vendor_approvers(): void
    {
        [$pkm, $vendor] = $this->context();
        $assigned = User::factory()->create(['name' => 'Assigned Vendor Manager', 'email' => 'assigned-vendor@example.com', 'role' => User::ROLE_APPROVER]);
        User::factory()->create(['name' => 'Global Approver Hidden', 'email' => 'global-hidden@example.com', 'role' => User::ROLE_APPROVER]);
        VendorWorkTypeSection::query()->create(['vendor_work_type_id' => $vendor->id, 'name' => 'Mesin', 'manager_id' => $assigned->id]);

        $this->actingAs($pkm)->get(route('pkm.dashboard'))->assertOk()
            ->assertSee('assigned-vendor@example.com')->assertDontSee('global-hidden@example.com');
    }

    private function context(): array
    {
        return [
            User::factory()->create(['role' => User::ROLE_PKM]),
            VendorWorkType::query()->firstOrCreate(['name' => VendorWorkType::FIXED_VENDOR_NAME]),
        ];
    }
}
