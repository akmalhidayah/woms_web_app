<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleDashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_admin_dashboard(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($user)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('Admin Dashboard');
    }

    public function test_pkm_can_access_pkm_dashboard(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_PKM]);

        $response = $this->actingAs($user)->get('/pkm/dashboard');

        $response->assertOk();
        $response->assertSee('PKM Dashboard');
    }

    public function test_approver_can_access_approver_dashboard(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_APPROVER]);

        $response = $this->actingAs($user)->get('/approver/dashboard');

        $response->assertOk();
        $response->assertSee('Approver Dashboard');
    }
}
