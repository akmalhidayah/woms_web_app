<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Services\Auth\UserImpersonationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserImpersonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_super_admin_can_start_impersonation(): void
    {
        $target = User::factory()->create();

        foreach ([User::ROLE_USER, User::ROLE_PKM, User::ROLE_APPROVER] as $role) {
            $actor = User::factory()->create(['role' => $role]);

            $this->actingAs($actor)
                ->post(route('admin.user-panel.impersonate', $target))
                ->assertForbidden();
        }

        $regularAdmin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_ADMIN,
        ]);

        $this->actingAs($regularAdmin)
            ->post(route('admin.user-panel.impersonate', $target))
            ->assertForbidden();
    }

    public function test_super_admin_can_start_and_target_credentials_are_unchanged(): void
    {
        $superAdmin = $this->superAdmin();
        $target = User::factory()->create([
            'role' => User::ROLE_PKM,
            'remember_token' => 'target-remember-token',
        ]);
        $password = $target->password;

        $this->actingAs($superAdmin)
            ->post(route('admin.user-panel.impersonate', $target))
            ->assertRedirect(route('pkm.dashboard'))
            ->assertSessionHas(UserImpersonationService::SESSION_IMPERSONATOR_ID, $superAdmin->id)
            ->assertSessionHas(UserImpersonationService::SESSION_TARGET_ID, $target->id)
            ->assertSessionHas(UserImpersonationService::SESSION_STARTED_AT);

        $this->assertAuthenticatedAs($target);
        $target->refresh();
        $this->assertSame($password, $target->password);
        $this->assertSame('target-remember-token', $target->remember_token);
        $this->assertSame(User::ROLE_PKM, $target->role);
    }

    public function test_super_admin_cannot_impersonate_self_or_another_super_admin(): void
    {
        $superAdmin = $this->superAdmin();
        $otherSuperAdmin = $this->superAdmin();

        $this->actingAs($superAdmin)
            ->post(route('admin.user-panel.impersonate', $superAdmin))
            ->assertForbidden();

        $this->actingAs($superAdmin)
            ->post(route('admin.user-panel.impersonate', $otherSuperAdmin))
            ->assertForbidden();
    }

    public function test_nested_impersonation_is_rejected(): void
    {
        $superAdmin = $this->superAdmin();
        $firstTarget = User::factory()->create(['role' => User::ROLE_ADMIN, 'admin_role' => User::ADMIN_ROLE_ADMIN]);
        $secondTarget = User::factory()->create();

        $this->actingAs($superAdmin)
            ->post(route('admin.user-panel.impersonate', $firstTarget))
            ->assertRedirect(route('admin.dashboard'));

        $this->post(route('admin.user-panel.impersonate', $secondTarget))
            ->assertForbidden();

        $this->assertAuthenticatedAs($firstTarget);
    }

    public function test_target_uses_its_own_routes_without_super_admin_bypass(): void
    {
        $superAdmin = $this->superAdmin();
        $target = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($superAdmin)
            ->post(route('admin.user-panel.impersonate', $target));

        $this->get(route('settings.profile'))->assertOk();
        $this->get(route('dashboard'))->assertRedirect(route('user.dashboard'));
        $this->get(route('admin.dashboard'))->assertForbidden();
    }

    public function test_any_target_role_can_stop_and_return_to_super_admin(): void
    {
        foreach ([User::ROLE_USER, User::ROLE_APPROVER, User::ROLE_PKM, User::ROLE_ADMIN] as $role) {
            $superAdmin = $this->superAdmin();
            $target = User::factory()->create([
                'role' => $role,
                'admin_role' => $role === User::ROLE_ADMIN ? User::ADMIN_ROLE_ADMIN : null,
            ]);

            $this->actingAs($superAdmin)
                ->post(route('admin.user-panel.impersonate', $target));

            $this->post(route('impersonation.stop'))
                ->assertRedirect(route('admin.user-panel.index'))
                ->assertSessionMissing(UserImpersonationService::SESSION_IMPERSONATOR_ID)
                ->assertSessionMissing(UserImpersonationService::SESSION_TARGET_ID)
                ->assertSessionMissing(UserImpersonationService::SESSION_STARTED_AT)
                ->assertSessionHas('success', 'Anda telah kembali ke akun Super Admin.');

            $this->assertAuthenticatedAs($superAdmin);
        }
    }

    public function test_stop_without_context_is_forbidden_and_keeps_current_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('impersonation.stop'))
            ->assertForbidden();

        $this->assertAuthenticatedAs($user);
    }

    public function test_corrupt_context_logs_out_safely(): void
    {
        $target = User::factory()->create();

        $this->actingAs($target)
            ->withSession([
                UserImpersonationService::SESSION_IMPERSONATOR_ID => 999999,
                UserImpersonationService::SESSION_TARGET_ID => $target->id,
                UserImpersonationService::SESSION_STARTED_AT => now()->toDateTimeString(),
            ])
            ->post(route('impersonation.stop'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_logout_during_impersonation_returns_to_super_admin(): void
    {
        $superAdmin = $this->superAdmin();
        $target = User::factory()->create();

        $this->actingAs($superAdmin)
            ->post(route('admin.user-panel.impersonate', $target));

        $this->post(route('logout'))
            ->assertRedirect(route('admin.user-panel.index'));

        $this->assertAuthenticatedAs($superAdmin);
        $this->assertFalse(session()->has(UserImpersonationService::SESSION_IMPERSONATOR_ID));
    }

    public function test_normal_logout_still_logs_user_out(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    private function superAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
    }
}
