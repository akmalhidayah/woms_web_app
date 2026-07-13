<?php

namespace Tests\Feature\Pkm;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_panel_only_returns_pkm_even_with_approver_role_parameter(): void
    {
        $actor = $this->pkm(['name' => 'PKM Visible']);
        $this->approver(['name' => 'Approver Hidden']);
        User::factory()->create(['name' => 'Admin Hidden', 'role' => User::ROLE_ADMIN]);
        User::factory()->create(['name' => 'Order User Hidden', 'role' => User::ROLE_USER]);

        $response = $this->actingAs($actor)->get(route('pkm.user-panel.index', ['role' => 'approver']))->assertOk();
        $this->assertSame(['PKM Visible'], $response->viewData('users')->pluck('name')->all());
        $response->assertSee('Tambah User PKM')->assertDontSee('User Type');
    }

    public function test_pkm_can_create_pkm_with_default_password(): void
    {
        $this->actingAs($this->pkm())->post(route('pkm.user-panel.store'), $this->payload(['email' => 'new-pkm@example.com']))->assertSessionHas('success');
        $created = User::where('email', 'new-pkm@example.com')->firstOrFail();
        $this->assertSame(User::ROLE_PKM, $created->role);
        $this->assertNull($created->admin_role);
        $this->assertTrue(Hash::check('bengkelmesin123', $created->password));
    }

    public function test_forged_roles_are_rejected(): void
    {
        foreach ([User::ROLE_APPROVER, User::ROLE_ADMIN, User::ROLE_USER] as $role) {
            $email = "forged-{$role}@example.com";
            $this->actingAs($this->pkm())->post(route('pkm.user-panel.store'), $this->payload(['email' => $email, 'role' => $role]))->assertSessionHasErrors('role');
            $this->assertDatabaseMissing('users', ['email' => $email]);
        }
    }

    public function test_pkm_can_edit_pkm_without_changing_role(): void
    {
        $actor = $this->pkm();
        $target = $this->pkm(['email' => 'target@example.com']);
        $this->actingAs($actor)->put(route('pkm.user-panel.update', $target), $this->payload(['name' => 'Updated', 'email' => $target->email]))->assertSessionHas('success');
        $this->assertSame('Updated', $target->refresh()->name);
        $this->assertSame(User::ROLE_PKM, $target->role);
    }

    public function test_pkm_cannot_edit_non_pkm_accounts(): void
    {
        foreach ([User::ROLE_APPROVER, User::ROLE_ADMIN, User::ROLE_USER] as $role) {
            $actor = $this->pkm();
            $target = User::factory()->create(['role' => $role]);
            $this->actingAs($actor)->put(route('pkm.user-panel.update', $target), $this->payload(['name' => 'Forbidden', 'email' => $target->email]))->assertSessionHas('error');
            $this->assertNotSame('Forbidden', $target->refresh()->name);
        }
    }

    public function test_pkm_cannot_delete_self_or_non_pkm_accounts(): void
    {
        $actor = $this->pkm();
        $this->actingAs($actor)->delete(route('pkm.user-panel.destroy', $actor))->assertSessionHas('error');
        foreach ([User::ROLE_APPROVER, User::ROLE_ADMIN, User::ROLE_USER] as $role) {
            $target = User::factory()->create(['role' => $role]);
            $this->actingAs($actor)->delete(route('pkm.user-panel.destroy', $target))->assertSessionHas('error');
            $this->assertDatabaseHas('users', ['id' => $target->id]);
        }
    }

    public function test_pkm_can_delete_another_pkm(): void
    {
        $actor = $this->pkm();
        $target = $this->pkm();
        $this->actingAs($actor)->delete(route('pkm.user-panel.destroy', $target))->assertSessionHas('success');
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_search_only_searches_pkm_accounts(): void
    {
        $actor = $this->pkm();
        $this->pkm(['name' => 'Search PKM Target']);
        $this->approver(['name' => 'Search Approver Target']);
        $response = $this->actingAs($actor)->get(route('pkm.user-panel.index', ['search' => 'Search']))->assertOk();
        $this->assertSame(['Search PKM Target'], $response->viewData('users')->pluck('name')->all());
    }

    private function payload(array $overrides = []): array
    {
        return array_merge(['name' => 'PKM Test', 'email' => 'pkm-'.uniqid().'@example.com', 'nomor_hp' => '08123', 'inisial' => 'PT'], $overrides);
    }

    private function pkm(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => User::ROLE_PKM], $attributes));
    }

    private function approver(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => User::ROLE_APPROVER], $attributes));
    }
}
