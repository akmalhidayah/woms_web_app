<?php

namespace Tests\Feature\Inventory\Admin;

use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryTransaction;
use App\Models\Inventory\InventoryUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class InventoryAdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_admin_can_open_list_and_create_form_without_password_input(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.inventory.users.index'))
            ->assertOk()
            ->assertSeeText('Tambah User');
        $this->actingAs($admin)->get(route('admin.inventory.users.create'))
            ->assertOk()
            ->assertSeeText('Tambah User Aplikasi')
            ->assertDontSee('name="password"', false);

        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $this->actingAs($user)->get(route('admin.inventory.users.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.inventory.users.create'))->assertForbidden();
    }

    public function test_admin_creates_hashed_inventory_user_with_server_controlled_fields(): void
    {
        $admin = $this->admin();
        $usersBefore = User::query()->count();

        $response = $this->actingAs($admin)->post(route('admin.inventory.users.store'), [
            'name' => 'User Flutter',
            'email' => 'USER.FLUTTER@EXAMPLE.TEST',
            'employee_number' => 'EMP-001',
            'phone' => '+628123',
            'position' => 'Teknisi',
            'department' => 'Workshop',
            'is_active' => '1',
            'password' => 'forged-password',
            'role' => 'admin',
            'must_change_password' => false,
        ])->assertRedirect(route('admin.inventory.users.index'))->assertSessionHas('success')->assertSessionHas('temporary_password');
        $temporaryPassword = $response->getSession()->get('temporary_password');

        $this->actingAs($admin)
            ->get(route('admin.inventory.users.index'))
            ->assertOk()
            ->assertSee($temporaryPassword)
            ->assertSee('Salin Password');

        $inventoryUser = InventoryUser::query()->sole();
        $this->assertSame('user.flutter@example.test', $inventoryUser->email);
        $this->assertSame('user', $inventoryUser->role);
        $this->assertTrue($inventoryUser->is_active);
        $this->assertTrue($inventoryUser->must_change_password);
        $this->assertNull($inventoryUser->last_login_at);
        $this->assertIsString($temporaryPassword);
        $this->assertTrue(Hash::check($temporaryPassword, $inventoryUser->password));
        $this->assertNotSame($temporaryPassword, $inventoryUser->password);
        $this->assertSame($usersBefore, User::query()->count());
    }

    public function test_validation_and_temporary_passwords_are_unique(): void
    {
        $admin = $this->admin();
        InventoryUser::query()->create([
            'name' => 'Existing',
            'email' => 'existing@example.test',
            'password' => 'secret',
            'role' => 'user',
            'is_active' => true,
        ]);

        $this->actingAs($admin)->post(route('admin.inventory.users.store'), [
            'name' => '',
            'email' => 'invalid',
            'department' => '',
            'is_active' => '1',
        ])->assertSessionHasErrors(['name', 'email', 'department']);

        $this->actingAs($admin)->post(route('admin.inventory.users.store'), [
            'name' => 'Duplicate',
            'email' => 'existing@example.test',
            'department' => 'Workshop',
            'is_active' => '1',
        ])->assertSessionHasErrors('email');

        $first = $this->actingAs($admin)->post(route('admin.inventory.users.store'), [
            'name' => 'Random One',
            'email' => 'random-one@example.test',
            'department' => 'Workshop',
            'is_active' => '1',
        ])->getSession()->get('temporary_password');
        $second = $this->actingAs($admin)->post(route('admin.inventory.users.store'), [
            'name' => 'Random Two',
            'email' => 'random-two@example.test',
            'department' => 'Workshop',
            'is_active' => '1',
        ])->getSession()->get('temporary_password');
        $this->assertNotSame($first, $second);
    }

    public function test_list_searches_filters_and_never_exposes_credentials(): void
    {
        $admin = $this->admin();
        $active = $this->inventoryUser([
            'name' => 'Nama Dicari',
            'email' => 'find@example.test',
            'employee_number' => 'EMP-FIND',
            'department' => 'Maintenance',
            'position' => 'Planner',
            'is_active' => true,
            'must_change_password' => true,
        ]);
        $inactive = $this->inventoryUser([
            'name' => 'Inactive Account',
            'email' => 'inactive@example.test',
            'is_active' => false,
            'must_change_password' => false,
        ]);
        $active->createToken('secret-token', ['inventory-mobile']);

        foreach (['Nama Dicari', 'find@example.test', 'EMP-FIND', 'Maintenance', 'Planner'] as $search) {
            $this->actingAs($admin)->get(route('admin.inventory.users.index', ['search' => $search]))
                ->assertOk()->assertSeeText('Nama Dicari')->assertDontSeeText('Inactive Account');
        }

        $this->actingAs($admin)->get(route('admin.inventory.users.index', ['status' => 'active']))
            ->assertSeeText($active->name)->assertDontSeeText($inactive->name);
        $this->actingAs($admin)->get(route('admin.inventory.users.index', ['status' => 'inactive']))
            ->assertSeeText($inactive->name)->assertDontSeeText($active->name);
        $response = $this->actingAs($admin)->get(route('admin.inventory.users.index', ['status' => 'must_change_password']));
        $response->assertSeeText($active->name)->assertDontSeeText($inactive->name);
        $response->assertDontSee($active->password)->assertDontSee('secret-token');
    }

    public function test_deactivation_revokes_tokens_and_reactivation_requires_new_login(): void
    {
        $admin = $this->admin();
        $inventoryUser = $this->inventoryUser();
        $inventoryUser->createToken('device', ['inventory-mobile']);

        $this->actingAs($admin)->patch(route('admin.inventory.users.status', $inventoryUser), [
            'is_active' => false,
        ])->assertRedirect(route('admin.inventory.users.index'));
        $this->assertFalse($inventoryUser->fresh()->is_active);
        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->actingAs($admin)->patch(route('admin.inventory.users.status', $inventoryUser), [
            'is_active' => true,
        ])->assertRedirect(route('admin.inventory.users.index'));
        $this->assertTrue($inventoryUser->fresh()->is_active);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_reset_password_revokes_tokens_and_preserves_profile_and_transactions(): void
    {
        $admin = $this->admin();
        $inventoryUser = $this->inventoryUser([
            'email' => 'preserved@example.test',
            'password' => 'old-password',
            'must_change_password' => false,
        ]);
        $inventoryUser->createToken('device', ['inventory-mobile']);
        $item = InventoryItem::query()->create([
            'uid' => 'RESET-ITEM',
            'item_type' => 'consumable',
            'name' => 'Reset Test Item',
            'unit' => 'EA',
            'current_stock' => '0',
            'minimum_stock' => '0',
            'is_active' => true,
        ]);
        InventoryTransaction::query()->create([
            'transaction_number' => 'INV-RESET-TEST',
            'inventory_item_id' => $item->id,
            'inventory_user_id' => $inventoryUser->id,
            'transaction_type' => 'stock_out',
            'quantity' => '1',
            'stock_before' => '1',
            'stock_after' => '0',
            'source' => 'flutter',
            'item_uid_snapshot' => $item->uid,
            'item_name_snapshot' => $item->name,
            'unit_snapshot' => $item->unit,
            'transaction_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.inventory.users.reset-password', $inventoryUser))
            ->assertRedirect(route('admin.inventory.users.index'))
            ->assertSessionHas('temporary_password');
        $temporaryPassword = $response->getSession()->get('temporary_password');

        $this->actingAs($admin)
            ->get(route('admin.inventory.users.index'))
            ->assertOk()
            ->assertSee($temporaryPassword)
            ->assertSee('Salin Password');

        $inventoryUser->refresh();
        $this->assertTrue(Hash::check($temporaryPassword, $inventoryUser->password));
        $this->assertTrue($inventoryUser->must_change_password);
        $this->assertSame('preserved@example.test', $inventoryUser->email);
        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertDatabaseHas('inventory_transactions', ['inventory_user_id' => $inventoryUser->id]);
    }

    public function test_mutating_routes_are_not_get_and_api_route_count_stays_seventeen(): void
    {
        $this->assertSame(['PATCH'], Route::getRoutes()->getByName('admin.inventory.users.status')?->methods());
        $this->assertSame(['POST'], Route::getRoutes()->getByName('admin.inventory.users.reset-password')?->methods());
        $this->assertCount(17, collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with($route->uri(), 'api/v1/inventory')));
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
    }

    private function inventoryUser(array $attributes = []): InventoryUser
    {
        return InventoryUser::query()->create(array_merge([
            'name' => 'Inventory User',
            'email' => str()->ulid().'@inventory.test',
            'password' => 'original-password',
            'role' => 'user',
            'is_active' => true,
            'must_change_password' => true,
        ], $attributes));
    }
}
