<?php

namespace Tests\Feature\Approval;

use App\Models\ApprovalSignatureRollback;
use App\Models\Hpp;
use App\Models\HppSignature;
use App\Models\LhppBast;
use App\Models\LhppBastSignature;
use App\Models\Order;
use App\Models\User;
use App\Services\Approvals\ApprovalSignatureRollbackService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApprovalSignatureRollbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_rollback_hpp_signature_step(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
        $signer = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $hpp = $this->createHpp($admin);

        $stepOne = $hpp->signatures()->create($this->signatureAttributes($signer, 1, HppSignature::STATUS_SIGNED, 'step-one-token'));
        $target = $hpp->signatures()->create($this->signatureAttributes($signer, 2, HppSignature::STATUS_SIGNED, 'target-token'));
        $afterTarget = $hpp->signatures()->create($this->signatureAttributes($signer, 3, HppSignature::STATUS_PENDING, 'after-token'));
        $oldTargetTokenHash = $target->token_hash;

        $rolledBack = app(ApprovalSignatureRollbackService::class)->rollbackHpp(
            $hpp,
            $target,
            $admin,
            'Tanda tangan step target salah.',
            false,
        );

        $this->assertSame(HppSignature::STATUS_SIGNED, $stepOne->fresh()->status);
        $this->assertNotNull($stepOne->fresh()->signature_data);

        $this->assertSame(HppSignature::STATUS_PENDING, $rolledBack->status);
        $this->assertNotNull($rolledBack->token_hash);
        $this->assertNotSame($oldTargetTokenHash, $rolledBack->token_hash);
        $this->assertNull($rolledBack->signature_data);
        $this->assertNull($rolledBack->signed_at);
        $this->assertNull($rolledBack->signed_ip);

        $afterTarget->refresh();
        $this->assertSame(HppSignature::STATUS_LOCKED, $afterTarget->status);
        $this->assertNull($afterTarget->token_hash);
        $this->assertNull($afterTarget->signature_data);
        $this->assertNull($afterTarget->signed_at);

        $this->assertSame(Hpp::STATUS_IN_REVIEW, $hpp->fresh()->status);
        $this->assertDatabaseHas('approval_signature_rollbacks', [
            'document_type' => 'hpp',
            'document_id' => $hpp->id,
            'signature_id' => $target->id,
            'rollback_by' => $admin->id,
        ]);

        $audit = ApprovalSignatureRollback::query()->firstOrFail();
        $this->assertSame([$target->id, $afterTarget->id], $audit->affected_signature_ids);
    }

    public function test_admin_can_rollback_bast_signature_step(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
        $signer = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $bast = $this->createBast($admin);

        $stepOne = $bast->signatures()->create($this->bastSignatureAttributes($signer, 1, LhppBastSignature::STATUS_SIGNED, 'bast-step-one-token'));
        $target = $bast->signatures()->create($this->bastSignatureAttributes($signer, 2, LhppBastSignature::STATUS_SIGNED, 'bast-target-token'));
        $afterTarget = $bast->signatures()->create($this->bastSignatureAttributes($signer, 3, LhppBastSignature::STATUS_SIGNED, 'bast-after-token'));
        $oldTargetTokenHash = $target->token_hash;

        $rolledBack = app(ApprovalSignatureRollbackService::class)->rollbackBast(
            $bast,
            $target,
            $admin,
            'Tanda tangan BAST perlu diulang.',
            false,
        );

        $this->assertSame(LhppBastSignature::STATUS_SIGNED, $stepOne->fresh()->status);
        $this->assertNotNull($stepOne->fresh()->signature_data);

        $this->assertSame(LhppBastSignature::STATUS_PENDING, $rolledBack->status);
        $this->assertNotNull($rolledBack->token_hash);
        $this->assertNotSame($oldTargetTokenHash, $rolledBack->token_hash);
        $this->assertNull($rolledBack->signature_data);
        $this->assertNull($rolledBack->signed_at);
        $this->assertNull($rolledBack->approval_note);

        $afterTarget->refresh();
        $this->assertSame(LhppBastSignature::STATUS_LOCKED, $afterTarget->status);
        $this->assertNull($afterTarget->token_hash);
        $this->assertNull($afterTarget->signature_data);
        $this->assertNull($afterTarget->signed_at);

        $this->assertSame(LhppBast::APPROVAL_IN_REVIEW, $bast->fresh()->approval_status);
        $this->assertDatabaseHas('approval_signature_rollbacks', [
            'document_type' => 'bast_lhpp',
            'document_id' => $bast->id,
            'signature_id' => $target->id,
            'rollback_by' => $admin->id,
        ]);
    }

    private function createHpp(User $admin): Hpp
    {
        $order = $this->createOrder($admin);

        return Hpp::query()->create([
            'order_id' => $order->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Jasa Fabrikasi',
            'area_pekerjaan' => 'Dalam Area',
            'nilai_hpp_bucket' => 'under_250',
            'approval_case' => 'TEST-ROLLBACK',
            'approval_flow' => ['Step 1', 'Step 2', 'Step 3'],
            'item_groups' => [],
            'total_keseluruhan' => 1000000,
            'status' => Hpp::STATUS_APPROVED,
            'submitted_at' => now(),
            'created_by' => $admin->id,
        ]);
    }

    private function createBast(User $admin): LhppBast
    {
        $order = $this->createOrder($admin);

        return LhppBast::query()->create([
            'order_id' => $order->id,
            'termin_type' => 'termin_1',
            'nomor_order' => $order->nomor_order,
            'notifikasi' => $order->notifikasi,
            'deskripsi_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'tanggal_bast' => now()->toDateString(),
            'approval_threshold' => 'under_250',
            'approval_flow' => ['Step 1', 'Step 2', 'Step 3'],
            'approval_status' => LhppBast::APPROVAL_APPROVED,
            'quality_control_status' => 'approved',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
    }

    private function createOrder(User $creator): Order
    {
        return Order::query()->create([
            'nomor_order' => 'ORD-ROLLBACK-'.uniqid(),
            'notifikasi' => 'NOTIF-ROLLBACK',
            'nama_pekerjaan' => 'Pekerjaan Rollback',
            'unit_kerja' => 'Unit Rollback',
            'seksi' => 'Seksi Rollback',
            'deskripsi' => 'Deskripsi rollback',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => now()->toDateString(),
            'target_selesai' => now()->addWeek()->toDateString(),
            'created_by' => $creator->id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function signatureAttributes(User $signer, int $stepOrder, string $status, string $token): array
    {
        return [
            'step_order' => $stepOrder,
            'role_key' => 'step_'.$stepOrder,
            'role_label' => 'Step '.$stepOrder,
            'signer_user_id' => $signer->id,
            'signer_name_snapshot' => $signer->name,
            'signer_position_snapshot' => 'Approver Step '.$stepOrder,
            'status' => $status,
            'token' => $token,
            'token_hash' => hash('sha256', $token),
            'token_expires_at' => now()->addDay(),
            'opened_at' => now(),
            'signed_at' => now(),
            'signature_data' => 'data:image/png;base64,test',
            'approval_note' => 'Catatan approval',
            'signed_ip' => '127.0.0.1',
            'signed_user_agent' => 'PHPUnit',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function bastSignatureAttributes(User $signer, int $stepOrder, string $status, string $token): array
    {
        return $this->signatureAttributes($signer, $stepOrder, $status, $token);
    }
}
