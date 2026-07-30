<?php

namespace Tests\Feature\Inventory\Api;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class InventoryAuthenticationApiTest extends InventoryApiTestCase
{
    public function test_inventory_user_can_login_and_same_device_replaces_old_token(): void
    {
        $user = $this->inventoryUser([
            'email' => 'mobile@example.test',
            'must_change_password' => true,
        ]);

        $first = $this->postJson('/api/v1/inventory/auth/login', [
            'email' => 'MOBILE@example.test',
            'password' => 'password-lama',
            'device_name' => 'Android Akmal',
        ])->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.must_change_password', true)
            ->assertJsonMissingPath('data.user.password');

        $this->assertNotEmpty($first->json('data.token'));
        $this->assertSame(1, $user->tokens()->count());
        $this->assertTrue($user->tokens()->firstOrFail()->can('inventory-mobile'));
        $this->assertNotNull($user->refresh()->last_login_at);

        $this->postJson('/api/v1/inventory/auth/login', [
            'email' => 'mobile@example.test',
            'password' => 'password-lama',
            'device_name' => 'Android Akmal',
        ])->assertOk();

        $this->assertSame(1, $user->tokens()->count());
    }

    public function test_invalid_inactive_deleted_and_woms_credentials_cannot_login(): void
    {
        $inactive = $this->inventoryUser(['email' => 'inactive@example.test', 'is_active' => false]);
        $deleted = $this->inventoryUser(['email' => 'deleted@example.test']);
        $deleted->delete();
        $woms = User::factory()->create([
            'email' => 'admin@example.test',
            'password' => 'password-lama',
            'role' => User::ROLE_ADMIN,
        ]);

        foreach ([
            [$inactive->email, 'password-lama'],
            [$deleted->email, 'password-lama'],
            [$woms->email, 'password-lama'],
            ['missing@example.test', 'wrong'],
        ] as [$email, $password]) {
            $this->postJson('/api/v1/inventory/auth/login', [
                'email' => $email,
                'password' => $password,
                'device_name' => 'Test',
            ])->assertUnauthorized()
                ->assertJsonPath('message', 'Email atau password tidak valid.');
        }
    }

    public function test_password_gate_change_password_and_logout_flows(): void
    {
        $user = $this->inventoryUser(['must_change_password' => true]);
        $token = $user->createToken('inventory-flutter:test', ['inventory-mobile']);

        $this->withToken($token->plainTextToken)
            ->getJson('/api/v1/inventory/dashboard')
            ->assertForbidden()
            ->assertJsonPath('message', 'Anda harus mengganti password sebelum menggunakan aplikasi.');

        $this->withToken($token->plainTextToken)
            ->getJson('/api/v1/inventory/auth/me')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email);

        $otherToken = $user->createToken('inventory-flutter:other', ['inventory-mobile']);
        $this->withToken($token->plainTextToken)
            ->postJson('/api/v1/inventory/auth/change-password', [
                'current_password' => 'password-lama',
                'password' => 'password-baru',
                'password_confirmation' => 'password-baru',
            ])->assertOk()
            ->assertJsonPath('data.must_change_password', false);

        $this->assertTrue(Hash::check('password-baru', $user->refresh()->password));
        $this->assertDatabaseHas('personal_access_tokens', ['id' => $token->accessToken->id]);
        $this->assertDatabaseMissing('personal_access_tokens', ['id' => $otherToken->accessToken->id]);

        $this->withToken($token->plainTextToken)
            ->postJson('/api/v1/inventory/auth/logout')
            ->assertOk();
        $this->assertSame(0, $user->tokens()->count());
    }

    public function test_missing_invalid_and_wrong_ability_tokens_are_rejected(): void
    {
        $this->getJson('/api/v1/inventory/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Token tidak tersedia atau tidak valid.');

        $this->withToken('invalid-token')->getJson('/api/v1/inventory/auth/me')->assertUnauthorized();

        $user = $this->inventoryUser();
        $token = $user->createToken('wrong', ['other-ability']);
        $this->withToken($token->plainTextToken)
            ->getJson('/api/v1/inventory/auth/me')
            ->assertUnauthorized();
    }

    public function test_logout_all_removes_every_token(): void
    {
        $user = $this->inventoryUser();
        $current = $user->createToken('current', ['inventory-mobile']);
        $user->createToken('other', ['inventory-mobile']);

        $this->withToken($current->plainTextToken)
            ->postJson('/api/v1/inventory/auth/logout-all')
            ->assertOk();

        $this->assertSame(0, $user->tokens()->count());
    }
}
