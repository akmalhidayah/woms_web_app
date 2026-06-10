<?php

namespace Tests\Feature\Admin;

use App\Models\Hpp;
use App\Models\HppSignature;
use App\Models\InitialWork;
use App\Models\InitialWorkSignature;
use App\Models\LhppBast;
use App\Models\LhppBastSignature;
use App\Models\Order;
use App\Models\QualityControlReport;
use App\Models\QualityControlSignature;
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

    public function test_active_initial_work_quality_control_and_bast_signers_cannot_be_deleted(): void
    {
        $admin = $this->createSuperAdmin();
        $order = $this->createOrder($admin);

        $initialWorkSigner = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $initialWork = InitialWork::query()->create([
            'order_id' => $order->id,
            'nomor_initial_work' => 'IW-USER-DELETE',
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'perihal' => 'Pengujian signer aktif',
            'tanggal_initial_work' => now()->toDateString(),
            'functional_location' => ['FL-01'],
            'scope_pekerjaan' => ['Scope'],
            'qty' => ['1'],
            'stn' => ['Lot'],
            'created_by' => $admin->id,
        ]);
        $initialWork->signatures()->create([
            'step_order' => 1,
            'role_key' => InitialWorkSignature::ROLE_MANAGER,
            'role_label' => 'Manager Test',
            'signer_user_id' => $initialWorkSigner->id,
            'signer_name' => $initialWorkSigner->name,
            'status' => InitialWorkSignature::STATUS_PENDING,
        ]);

        $qcSigner = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $report = QualityControlReport::query()->create([
            'order_id' => $order->id,
            'type' => QualityControlReport::TYPE_FABRICATION,
            'report_no' => 'QC-USER-DELETE',
            'report_date' => now()->toDateString(),
            'status' => QualityControlReport::STATUS_SUBMITTED,
            'payload' => [],
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        $report->signatures()->create([
            'step_order' => 1,
            'role_key' => QualityControlSignature::ROLE_WORKSHOP_MANAGER,
            'role_label' => 'Manager Workshop',
            'signer_user_id' => $qcSigner->id,
            'signer_name' => $qcSigner->name,
            'status' => QualityControlSignature::STATUS_LOCKED,
        ]);

        $bastSigner = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $bast = LhppBast::query()->create([
            'order_id' => $order->id,
            'termin_type' => 'termin_1',
            'nomor_order' => $order->nomor_order,
            'deskripsi_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'tanggal_bast' => now()->toDateString(),
            'approval_threshold' => 'under_250',
            'approval_status' => LhppBast::APPROVAL_IN_REVIEW,
            'quality_control_status' => 'approved',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        $bast->signatures()->create([
            'step_order' => 1,
            'role_key' => 'manager_pkm',
            'role_label' => 'Manager PKM',
            'signer_user_id' => $bastSigner->id,
            'signer_name_snapshot' => $bastSigner->name,
            'signer_position_snapshot' => 'Manager PKM',
            'status' => LhppBastSignature::STATUS_PENDING,
        ]);

        foreach ([
            [$initialWorkSigner, 'Initial Work'],
            [$qcSigner, 'Quality Control'],
            [$bastSigner, 'BAST'],
        ] as [$signer, $documentName]) {
            $this->actingAs($admin)
                ->delete(route('admin.user-panel.destroy', $signer))
                ->assertRedirect(route('admin.user-panel.index', ['role' => User::ROLE_APPROVER]))
                ->assertSessionHas('error', fn (string $message): bool => str_contains($message, $documentName));

            $this->assertDatabaseHas('users', ['id' => $signer->id]);
        }
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
        $order = $this->createOrder($creator);

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

    private function createOrder(User $creator): Order
    {
        return Order::query()->create([
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
