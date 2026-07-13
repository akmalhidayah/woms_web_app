<?php

namespace Tests\Feature\Pkm;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class VendorManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_pkm_creates_approver_manager_with_safe_json_payload(): void
    {
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $response = $this->actingAs($pkm)->postJson(route('pkm.vendor-managers.store'), [
            'name' => 'Manager Vendor', 'email' => 'manager-vendor@example.com', 'nomor_hp' => '08123', 'inisial' => 'MV', 'role' => User::ROLE_ADMIN,
        ])->assertCreated()->assertJsonPath('manager.name', 'Manager Vendor')->assertJsonMissingPath('manager.password');

        $manager = User::where('email', 'manager-vendor@example.com')->firstOrFail();
        $this->assertSame(User::ROLE_APPROVER, $manager->role);
        $this->assertNull($manager->admin_role);
        $this->assertTrue(Hash::check('bengkelmesin123', $manager->password));
    }

    public function test_manager_name_and_unique_email_are_validated(): void
    {
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        User::factory()->create(['email' => 'used@example.com']);
        $this->actingAs($pkm)->postJson(route('pkm.vendor-managers.store'), ['name' => '', 'email' => 'used@example.com'])
            ->assertUnprocessable()->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_non_pkm_cannot_create_manager(): void
    {
        foreach ([User::ROLE_APPROVER, User::ROLE_USER, User::ROLE_ADMIN] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->postJson(route('pkm.vendor-managers.store'), ['name' => 'No', 'email' => uniqid().'@example.com'])->assertForbidden();
        }
    }
}
