<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DashboardProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_profile_uses_admin_url_and_layout(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.profile.edit'))
            ->assertOk()
            ->assertSee('Profil Admin')
            ->assertSee(route('admin.profile.update'))
            ->assertDontSee(route('settings.profile'));
    }

    public function test_pkm_profile_uses_pkm_url_and_layout(): void
    {
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);

        $this->actingAs($pkm)
            ->get(route('pkm.profile.edit'))
            ->assertOk()
            ->assertSee('Profil PKM')
            ->assertSee(route('pkm.profile.update'))
            ->assertDontSee(route('settings.profile'));
    }

    public function test_admin_can_update_profile_and_receives_success_message(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->patch(route('admin.profile.update'), [
                'name' => 'Admin Baru',
                'email' => 'admin.baru@example.com',
                'nomor_hp' => '081234567890',
                'inisial' => 'AB',
            ])
            ->assertRedirect(route('admin.profile.edit'))
            ->assertSessionHas('success', 'Perubahan profil berhasil disimpan.');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'name' => 'Admin Baru',
            'email' => 'admin.baru@example.com',
            'nomor_hp' => '081234567890',
            'inisial' => 'AB',
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_admin_can_update_password_from_dashboard_profile(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.profile.password.update'), [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect(route('admin.profile.edit'))
            ->assertSessionHas('success', 'Password berhasil diperbarui.');

        $this->assertTrue(Hash::check('new-password', $admin->refresh()->password));
    }

    public function test_admin_password_update_requires_current_password(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($admin)
            ->from(route('admin.profile.edit'))
            ->patch(route('admin.profile.password.update'), [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect(route('admin.profile.edit'))
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('password', $admin->refresh()->password));
    }

    public function test_pkm_can_update_profile_and_receives_success_message(): void
    {
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);

        $this->actingAs($pkm)
            ->patch(route('pkm.profile.update'), [
                'name' => 'Admin PKM Baru',
                'email' => $pkm->email,
                'nomor_hp' => '089876543210',
                'inisial' => 'AP',
            ])
            ->assertRedirect(route('pkm.profile.edit'))
            ->assertSessionHas('success', 'Perubahan profil berhasil disimpan.');

        $this->assertDatabaseHas('users', [
            'id' => $pkm->id,
            'name' => 'Admin PKM Baru',
            'nomor_hp' => '089876543210',
            'inisial' => 'AP',
            'role' => User::ROLE_PKM,
        ]);
        $this->assertNotNull($pkm->refresh()->email_verified_at);
    }

    public function test_pkm_can_update_password_from_dashboard_profile(): void
    {
        $pkm = User::factory()->create([
            'role' => User::ROLE_PKM,
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($pkm)
            ->patch(route('pkm.profile.password.update'), [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertRedirect(route('pkm.profile.edit'))
            ->assertSessionHas('success', 'Password berhasil diperbarui.');

        $this->assertTrue(Hash::check('new-password', $pkm->refresh()->password));
    }

    public function test_profile_routes_reject_the_wrong_dashboard_role(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);

        $this->actingAs($admin)->get(route('pkm.profile.edit'))->assertForbidden();
        $this->actingAs($pkm)->get(route('admin.profile.edit'))->assertForbidden();
    }
}
