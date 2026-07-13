<?php

namespace Tests\Feature\Pkm;

use App\Models\Hpp;
use App\Models\HppSignature;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_pkm_can_open_user_panel(): void
    {
        $this->actingAs($this->pkm())->get(route('pkm.user-panel.index'))
            ->assertOk()->assertSee('User Panel')->assertSee('Tambah User');
    }

    public function test_page_only_lists_pkm_and_approver_users(): void
    {
        $actor = $this->pkm(['name' => 'PKM Visible']);
        User::factory()->create(['name' => 'Admin Hidden', 'role' => User::ROLE_ADMIN]);
        User::factory()->create(['name' => 'Order User Hidden', 'role' => User::ROLE_USER]);
        User::factory()->create(['name' => 'Approval Visible', 'role' => User::ROLE_APPROVER]);

        $pkmResponse = $this->actingAs($actor)->get(route('pkm.user-panel.index', ['role' => User::ROLE_PKM]))->assertOk();
        $this->assertSame(['PKM Visible'], $pkmResponse->viewData('users')->pluck('name')->all());

        $approverResponse = $this->actingAs($actor)->get(route('pkm.user-panel.index', ['role' => User::ROLE_APPROVER]))->assertOk();
        $this->assertSame(['Approval Visible'], $approverResponse->viewData('users')->pluck('name')->all());
    }

    public function test_pkm_can_create_pkm_account_with_default_password(): void
    {
        $this->createAccount(User::ROLE_PKM, 'new-pkm@example.com');
        $created = User::where('email', 'new-pkm@example.com')->firstOrFail();
        $this->assertSame(User::ROLE_PKM, $created->role);
        $this->assertNull($created->admin_role);
        $this->assertTrue(Hash::check('bengkelmesin123', $created->password));
    }

    public function test_pkm_can_create_approver_account(): void
    {
        $this->createAccount(User::ROLE_APPROVER, 'new-approval@example.com');
        $this->assertDatabaseHas('users', ['email' => 'new-approval@example.com', 'role' => User::ROLE_APPROVER, 'admin_role' => null]);
    }

    public function test_pkm_cannot_create_admin_role(): void
    {
        $this->assertForgedCreateRoleRejected(User::ROLE_ADMIN);
    }

    public function test_pkm_cannot_create_order_user_role(): void
    {
        $this->assertForgedCreateRoleRejected(User::ROLE_USER);
    }

    public function test_pkm_can_edit_another_pkm_account(): void
    {
        $actor = $this->pkm();
        $target = $this->pkm(['email' => 'target-pkm@example.com']);
        $this->actingAs($actor)->put(route('pkm.user-panel.update', $target), $this->updatePayload($target, ['name' => 'PKM Updated']))
            ->assertSessionHas('success');
        $this->assertSame('PKM Updated', $target->refresh()->name);
    }

    public function test_pkm_can_edit_approver_account(): void
    {
        $actor = $this->pkm();
        $target = $this->approver(['email' => 'target-approval@example.com']);
        $this->actingAs($actor)->put(route('pkm.user-panel.update', $target), $this->updatePayload($target, ['inisial' => 'APX']))
            ->assertSessionHas('success');
        $this->assertSame('APX', $target->refresh()->inisial);
    }

    public function test_pkm_cannot_edit_admin_through_direct_url(): void
    {
        $this->assertTargetCannotBeEdited(User::ROLE_ADMIN);
    }

    public function test_pkm_cannot_edit_order_user_through_direct_url(): void
    {
        $this->assertTargetCannotBeEdited(User::ROLE_USER);
    }

    public function test_pkm_cannot_change_target_role_to_admin(): void
    {
        $this->assertForgedUpdateRoleRejected(User::ROLE_ADMIN);
    }

    public function test_pkm_cannot_change_target_role_to_order_user(): void
    {
        $this->assertForgedUpdateRoleRejected(User::ROLE_USER);
    }

    public function test_pkm_cannot_change_own_role_to_approver(): void
    {
        $actor = $this->pkm();
        $this->actingAs($actor)->put(route('pkm.user-panel.update', $actor), $this->updatePayload($actor, ['role' => User::ROLE_APPROVER]))
            ->assertSessionHas('error', 'Akun PKM yang sedang digunakan tidak dapat diubah menjadi Approval.');
        $this->assertSame(User::ROLE_PKM, $actor->refresh()->role);
    }

    public function test_pkm_cannot_delete_own_account(): void
    {
        $actor = $this->pkm();
        $this->actingAs($actor)->delete(route('pkm.user-panel.destroy', $actor))->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $actor->id]);
    }

    public function test_pkm_cannot_delete_admin(): void
    {
        $this->assertTargetCannotBeDeleted(User::ROLE_ADMIN);
    }

    public function test_pkm_cannot_delete_order_user(): void
    {
        $this->assertTargetCannotBeDeleted(User::ROLE_USER);
    }

    public function test_pkm_cannot_delete_approver_with_pending_hpp_signature(): void
    {
        $actor = $this->pkm();
        $target = $this->approver();
        $hpp = $this->hpp($actor);
        $hpp->signatures()->create([
            'step_order' => 1,
            'role_key' => 'manager_pengendali',
            'role_label' => 'Manager Pengendali',
            'signer_user_id' => $target->id,
            'signer_name_snapshot' => $target->name,
            'signer_position_snapshot' => 'Manager',
            'status' => HppSignature::STATUS_PENDING,
        ]);

        $this->actingAs($actor)->delete(route('pkm.user-panel.destroy', $target))
            ->assertSessionHas('error', fn (string $message): bool => str_contains($message, 'HPP'));
        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_pkm_can_delete_approver_without_active_approval(): void
    {
        $actor = $this->pkm();
        $target = $this->approver();
        $this->actingAs($actor)->delete(route('pkm.user-panel.destroy', $target))->assertSessionHas('success');
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_search_finds_pkm_user(): void
    {
        $actor = $this->pkm();
        $this->pkm(['name' => 'PKM Search Target', 'email' => 'search-target@example.com']);
        $this->actingAs($actor)->get(route('pkm.user-panel.index', ['role' => User::ROLE_PKM, 'search' => 'Search Target']))
            ->assertOk()->assertSee('PKM Search Target');
    }

    public function test_invalid_role_filter_falls_back_to_pkm(): void
    {
        $actor = $this->pkm(['name' => 'PKM Filter Default']);
        $this->approver(['name' => 'Approver Should Not Show']);
        $response = $this->actingAs($actor)->get(route('pkm.user-panel.index', ['role' => User::ROLE_ADMIN]))->assertOk();
        $this->assertSame(User::ROLE_PKM, $response->viewData('role'));
        $this->assertSame(['PKM Filter Default'], $response->viewData('users')->pluck('name')->all());
    }

    public function test_approver_cannot_access_pkm_user_panel_routes(): void
    {
        $this->actingAs($this->approver())->get(route('pkm.user-panel.index'))->assertForbidden();
    }

    public function test_order_user_cannot_access_pkm_user_panel_routes(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_USER]))->get(route('pkm.user-panel.index'))->assertForbidden();
    }

    public function test_admin_cannot_access_pkm_user_panel_routes(): void
    {
        $this->actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]))->get(route('pkm.user-panel.index'))->assertForbidden();
    }

    private function createAccount(string $role, string $email): void
    {
        $this->actingAs($this->pkm())->post(route('pkm.user-panel.store'), [
            'name' => 'New Account', 'email' => $email, 'nomor_hp' => '08123', 'inisial' => 'NA', 'role' => $role,
        ])->assertRedirect(route('pkm.user-panel.index', ['role' => $role]))->assertSessionHas('success');
    }

    private function assertForgedCreateRoleRejected(string $role): void
    {
        $this->actingAs($this->pkm())->post(route('pkm.user-panel.store'), [
            'name' => 'Forged', 'email' => 'forged-'.$role.'@example.com', 'role' => $role,
        ])->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'forged-'.$role.'@example.com']);
    }

    private function assertTargetCannotBeEdited(string $role): void
    {
        $actor = $this->pkm();
        $target = User::factory()->create(['role' => $role]);
        $this->actingAs($actor)->put(route('pkm.user-panel.update', $target), $this->updatePayload($target, ['name' => 'Forbidden Change']))->assertSessionHas('error');
        $this->assertNotSame('Forbidden Change', $target->refresh()->name);
    }

    private function assertForgedUpdateRoleRejected(string $role): void
    {
        $actor = $this->pkm();
        $target = $this->approver();
        $this->actingAs($actor)->put(route('pkm.user-panel.update', $target), $this->updatePayload($target, ['role' => $role]))->assertSessionHasErrors('role');
        $this->assertSame(User::ROLE_APPROVER, $target->refresh()->role);
    }

    private function assertTargetCannotBeDeleted(string $role): void
    {
        $actor = $this->pkm();
        $target = User::factory()->create(['role' => $role]);
        $this->actingAs($actor)->delete(route('pkm.user-panel.destroy', $target))->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    private function updatePayload(User $user, array $overrides = []): array
    {
        return array_merge(['name' => $user->name, 'email' => $user->email, 'nomor_hp' => $user->nomor_hp, 'inisial' => $user->inisial, 'role' => $user->role], $overrides);
    }

    private function pkm(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => User::ROLE_PKM], $attributes));
    }

    private function approver(array $attributes = []): User
    {
        return User::factory()->create(array_merge(['role' => User::ROLE_APPROVER], $attributes));
    }

    private function hpp(User $creator): Hpp
    {
        $order = Order::query()->create([
            'nomor_order' => 'PKM-USER-PANEL-'.uniqid(), 'nama_pekerjaan' => 'Test HPP', 'unit_kerja' => 'Unit', 'seksi' => 'Seksi',
            'deskripsi' => 'Test', 'prioritas' => Order::PRIORITY_MEDIUM, 'tanggal_order' => '2026-07-01', 'target_selesai' => '2026-07-10', 'created_by' => $creator->id,
        ]);

        return Hpp::query()->create([
            'order_id' => $order->id, 'nomor_order' => $order->nomor_order, 'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja, 'kategori_pekerjaan' => 'Fabrikasi', 'area_pekerjaan' => 'Dalam',
            'nilai_hpp_bucket' => 'under', 'item_groups' => [], 'total_keseluruhan' => 1000000, 'status' => Hpp::STATUS_IN_REVIEW, 'created_by' => $creator->id,
        ]);
    }
}
