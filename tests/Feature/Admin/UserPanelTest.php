<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_user_with_default_password(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.user-panel.store'), [
                'name' => 'Akun Baru WOMS',
                'email' => 'akun.baru@example.com',
                'nomor_hp' => '08123456789',
                'inisial' => 'ABW',
                'role' => User::ROLE_APPROVER,
            ])
            ->assertRedirect(route('admin.user-panel.index', ['role' => User::ROLE_APPROVER]));

        $user = User::where('email', 'akun.baru@example.com')->firstOrFail();

        $this->assertSame(User::ROLE_APPROVER, $user->role);
        $this->assertTrue(Hash::check('bengkelmesin123', $user->password));
    }
}
