<?php

namespace Tests\Feature\Approval;

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
use App\Services\Approvals\ApprovalSignatureReassignmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ApprovalSignatureReassignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_approval_signature_can_be_reassigned_to_plt_for_all_documents(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
        $oldSigner = User::factory()->create(['name' => 'Pejabat Lama', 'role' => User::ROLE_APPROVER]);
        $pltSigner = User::factory()->create(['name' => 'Pejabat PLT', 'role' => User::ROLE_APPROVER]);
        $order = $this->createOrder($admin);
        $service = app(ApprovalSignatureReassignmentService::class);

        foreach ($this->pendingSignatures($order, $oldSigner) as $signature) {
            $originalTokenHash = $signature->token_hash;

            $reassigned = $service->reassign(
                $signature,
                $pltSigner,
                $admin,
                'Pejabat definitif sedang dinas.',
                false,
            );

            $this->assertSame($pltSigner->id, $reassigned->signer_user_id);
            $this->assertSame($oldSigner->id, $reassigned->delegated_from_user_id);
            $this->assertSame('Pejabat Lama', $reassigned->delegated_from_name);
            $this->assertSame($admin->id, $reassigned->delegated_by_user_id);
            $this->assertSame('Pejabat definitif sedang dinas.', $reassigned->delegation_reason);
            $this->assertSame('PLT '.$signature->role_label, $reassigned->acting_as_label);
            $this->assertSame('PLT '.$signature->role_label, $reassigned->displayRoleLabel());
            $this->assertTrue($reassigned->isPending());
            $this->assertNotNull($reassigned->token_hash);
            $this->assertNotSame($originalTokenHash, $reassigned->token_hash);
            $this->assertNotNull($reassigned->token_expires_at);
        }
    }

    public function test_signed_signature_cannot_be_reassigned(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
        $oldSigner = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $pltSigner = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $order = $this->createOrder($admin);
        $signature = $this->createInitialWork($order, $admin)->signatures()->create([
            'step_order' => 1,
            'role_key' => InitialWorkSignature::ROLE_MANAGER,
            'role_label' => 'Manager Workshop Machine',
            'signer_user_id' => $oldSigner->id,
            'signer_name' => $oldSigner->name,
            'status' => InitialWorkSignature::STATUS_SIGNED,
            'signed_at' => now(),
        ]);

        $this->expectException(ValidationException::class);

        app(ApprovalSignatureReassignmentService::class)->reassign(
            $signature,
            $pltSigner,
            $admin,
            'Pejabat definitif sedang dinas.',
            false,
        );
    }

    /**
     * @return list<InitialWorkSignature|QualityControlSignature|HppSignature|LhppBastSignature>
     */
    private function pendingSignatures(Order $order, User $signer): array
    {
        $initialWork = $this->createInitialWork($order, $signer);
        $qualityControl = QualityControlReport::create([
            'order_id' => $order->id,
            'type' => QualityControlReport::TYPE_FABRICATION,
            'report_no' => 'QC-PLT-001',
            'report_date' => now()->toDateString(),
            'status' => QualityControlReport::STATUS_SUBMITTED,
            'payload' => [],
            'created_by' => $signer->id,
        ]);
        $hpp = Hpp::create([
            'order_id' => $order->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Jasa Fabrikasi',
            'area_pekerjaan' => 'Area PLT',
            'nilai_hpp_bucket' => 'under_250',
            'approval_case' => 'TEST',
            'approval_flow' => [],
            'item_groups' => [],
            'total_keseluruhan' => 1000000,
            'status' => Hpp::STATUS_IN_REVIEW,
            'submitted_at' => now(),
            'created_by' => $signer->id,
        ]);
        $bast = LhppBast::create([
            'order_id' => $order->id,
            'hpp_id' => $hpp->id,
            'termin_type' => 'termin_1',
            'nomor_order' => $order->nomor_order,
            'notifikasi' => $order->notifikasi,
            'deskripsi_pekerjaan' => $order->nama_pekerjaan,
            'tipe_pekerjaan' => 'Jasa Fabrikasi',
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'tanggal_bast' => now()->toDateString(),
            'quality_control_status' => 'approved',
            'approval_status' => LhppBast::APPROVAL_IN_REVIEW,
            'approval_case' => 'TEST',
            'approval_flow' => [],
            'created_by' => $signer->id,
        ]);

        return [
            $initialWork->signatures()->create($this->initialWorkSignatureAttributes($signer)),
            $qualityControl->signatures()->create($this->qualityControlSignatureAttributes($signer)),
            $hpp->signatures()->create($this->snapshotSignatureAttributes($signer, 'Manager Pengendali')),
            $bast->signatures()->create($this->snapshotSignatureAttributes($signer, 'Manager PKM')),
        ];
    }

    private function createOrder(User $creator): Order
    {
        return Order::create([
            'nomor_order' => 'ORD-PLT-'.uniqid(),
            'notifikasi' => 'NOTIF-PLT',
            'nama_pekerjaan' => 'Pekerjaan PLT',
            'unit_kerja' => 'Unit PLT',
            'seksi' => 'Seksi PLT',
            'deskripsi' => 'Deskripsi pekerjaan PLT',
            'prioritas' => Order::PRIORITY_HIGH,
            'tanggal_order' => now()->toDateString(),
            'target_selesai' => now()->addWeek()->toDateString(),
            'created_by' => $creator->id,
        ]);
    }

    private function createInitialWork(Order $order, User $creator): InitialWork
    {
        return InitialWork::create([
            'order_id' => $order->id,
            'nomor_initial_work' => 'IW-PLT-'.uniqid(),
            'nomor_order' => $order->nomor_order,
            'notifikasi' => $order->notifikasi,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'kepada_yth' => 'PT. Prima Karya Manunggal',
            'perihal' => 'Pekerjaan PLT',
            'tanggal_initial_work' => now()->toDateString(),
            'functional_location' => ['FL-01'],
            'scope_pekerjaan' => ['Scope'],
            'qty' => ['1'],
            'stn' => ['Lot'],
            'keterangan' => [''],
            'created_by' => $creator->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function initialWorkSignatureAttributes(User $signer): array
    {
        return [
            'step_order' => 1,
            'role_key' => InitialWorkSignature::ROLE_MANAGER,
            'role_label' => 'Manager Workshop Machine',
            'signer_user_id' => $signer->id,
            'signer_name' => $signer->name,
            'status' => InitialWorkSignature::STATUS_PENDING,
            'token_hash' => hash('sha256', 'old-initial-work-token'),
            'token_encrypted' => 'old-initial-work-token',
            'token_expires_at' => now()->addDay(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function qualityControlSignatureAttributes(User $signer): array
    {
        return [
            'step_order' => 1,
            'role_key' => QualityControlSignature::ROLE_WORKSHOP_MANAGER,
            'role_label' => 'Manager Bengkel',
            'signer_user_id' => $signer->id,
            'signer_name' => $signer->name,
            'status' => QualityControlSignature::STATUS_PENDING,
            'token_hash' => hash('sha256', 'old-qc-token'),
            'token_encrypted' => 'old-qc-token',
            'token_expires_at' => now()->addDay(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotSignatureAttributes(User $signer, string $roleLabel): array
    {
        return [
            'step_order' => 1,
            'role_key' => str($roleLabel)->lower()->replace(' ', '_')->toString(),
            'role_label' => $roleLabel,
            'signer_user_id' => $signer->id,
            'signer_name_snapshot' => $signer->name,
            'signer_position_snapshot' => $roleLabel,
            'status' => HppSignature::STATUS_PENDING,
            'token' => 'old-'.str($roleLabel)->slug()->toString().'-token',
            'token_hash' => hash('sha256', 'old-'.str($roleLabel)->slug()->toString().'-token'),
            'token_expires_at' => now()->addDay(),
        ];
    }
}
