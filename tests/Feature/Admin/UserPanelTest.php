<?php

namespace Tests\Feature\Admin;

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

    public function test_active_hpp_signer_cannot_be_deleted(): void
    {
        $admin = $this->createSuperAdmin();
        $signer = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $hpp = $this->createHpp($admin);

        $hpp->signatures()->create($this->signatureAttributes(
            $signer,
            HppSignature::STATUS_PENDING,
        ));

        $this->actingAs($admin)
            ->delete(route('admin.user-panel.destroy', $signer))
            ->assertRedirect(route('admin.user-panel.index', ['role' => User::ROLE_APPROVER]))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $signer->id]);
        $this->assertDatabaseHas('hpp_signatures', [
            'hpp_id' => $hpp->id,
            'signer_user_id' => $signer->id,
        ]);
    }

    public function test_deleting_completed_hpp_signer_preserves_signature_snapshot(): void
    {
        $admin = $this->createSuperAdmin();
        $signer = User::factory()->create([
            'name' => 'Signer Historis',
            'role' => User::ROLE_APPROVER,
        ]);
        $hpp = $this->createHpp($admin);
        $signature = $hpp->signatures()->create([
            ...$this->signatureAttributes($signer, HppSignature::STATUS_SIGNED),
            'signed_at' => now(),
        ]);

        $this->actingAs($admin)
            ->delete(route('admin.user-panel.destroy', $signer))
            ->assertRedirect(route('admin.user-panel.index', ['role' => User::ROLE_APPROVER]))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $signer->id]);
        $this->assertDatabaseHas('hpp_signatures', [
            'id' => $signature->id,
            'hpp_id' => $hpp->id,
            'signer_user_id' => null,
            'signer_name_snapshot' => 'Signer Historis',
            'status' => HppSignature::STATUS_SIGNED,
        ]);
    }

    private function createSuperAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
    }

    private function createHpp(User $creator): Hpp
    {
        $order = Order::query()->create([
            'nomor_order' => 'ORD-USER-DELETE-'.uniqid(),
            'nama_pekerjaan' => 'Audit signer HPP',
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Section Test',
            'deskripsi' => 'Pengujian histori signer HPP',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => now()->toDateString(),
            'target_selesai' => now()->addWeek()->toDateString(),
            'created_by' => $creator->id,
        ]);

        return Hpp::query()->create([
            'order_id' => $order->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Dalam',
            'nilai_hpp_bucket' => 'under',
            'total_keseluruhan' => 1000000,
            'status' => Hpp::STATUS_IN_REVIEW,
            'created_by' => $creator->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function signatureAttributes(User $signer, string $status): array
    {
        return [
            'step_order' => 1,
            'role_key' => 'manager_pengendali',
            'role_label' => 'Manager Pengendali',
            'signer_user_id' => $signer->id,
            'signer_name_snapshot' => $signer->name,
            'signer_position_snapshot' => 'Manager Pengendali',
            'status' => $status,
        ];
    }
}
