<?php

namespace Tests\Feature\Approval;

use App\Models\Hpp;
use App\Models\HppSignature;
use App\Models\LhppBast;
use App\Models\LhppBastSignature;
use App\Models\Order;
use App\Models\User;
use App\Notifications\ApprovalRequestedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BulkApprovalEmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_resend_all_valid_active_hpp_approvals(): void
    {
        Notification::fake();

        $admin = $this->admin();
        $activeApprover = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $expiredApprover = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $diropsApprover = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $draftApprover = User::factory()->create(['role' => User::ROLE_APPROVER]);

        $this->hppSignature($this->hpp($admin, 'ACTIVE-HPP', Hpp::STATUS_IN_REVIEW), $activeApprover, 'active-hpp-token');
        $this->hppSignature($this->hpp($admin, 'EXPIRED-HPP', Hpp::STATUS_IN_REVIEW), $expiredApprover, 'expired-hpp-token', expiresAt: now()->subMinute());
        $this->hppSignature($this->hpp($admin, 'DIROPS-HPP', Hpp::STATUS_IN_REVIEW), $diropsApprover, 'dirops-hpp-token', roleKey: 'dirops');
        $this->hppSignature($this->hpp($admin, 'DRAFT-HPP', Hpp::STATUS_DRAFT), $draftApprover, 'draft-hpp-token');

        $this->actingAs($admin)
            ->post(route('admin.hpp.approval.resend-all'))
            ->assertRedirect()
            ->assertSessionHas('status', fn (string $message): bool => str_contains($message, '1 email berhasil dikirim'));

        Notification::assertSentTo($activeApprover, ApprovalRequestedNotification::class);
        Notification::assertNotSentTo($expiredApprover, ApprovalRequestedNotification::class);
        Notification::assertNotSentTo($diropsApprover, ApprovalRequestedNotification::class);
        Notification::assertNotSentTo($draftApprover, ApprovalRequestedNotification::class);
    }

    public function test_admin_bast_bulk_resend_includes_active_termin_one_and_termin_two(): void
    {
        Notification::fake();

        $admin = $this->admin();
        $terminOneApprover = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $terminTwoApprover = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $order = $this->order($admin, 'ACTIVE-BAST');
        $terminOne = $this->bast($admin, $order, 'termin_1');
        $terminTwo = $this->bast($admin, $order, 'termin_2', $terminOne);

        $this->bastSignature($terminOne, $terminOneApprover, 'bast-t1-token');
        $this->bastSignature($terminTwo, $terminTwoApprover, 'bast-t2-token');

        $this->actingAs($admin)
            ->post(route('admin.lhpp.approval.resend-all'))
            ->assertRedirect()
            ->assertSessionHas('status', fn (string $message): bool => str_contains($message, '2 email berhasil dikirim'));

        Notification::assertSentTo($terminOneApprover, ApprovalRequestedNotification::class);
        Notification::assertSentTo($terminTwoApprover, ApprovalRequestedNotification::class);
    }

    public function test_non_admin_cannot_use_bulk_resend_endpoints(): void
    {
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);

        $this->actingAs($pkm)
            ->post(route('admin.hpp.approval.resend-all'))
            ->assertForbidden();

        $this->actingAs($pkm)
            ->post(route('admin.lhpp.approval.resend-all'))
            ->assertForbidden();
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
    }

    private function order(User $creator, string $suffix): Order
    {
        return Order::query()->create([
            'nomor_order' => 'ORDER-'.$suffix,
            'notifikasi' => 'NOTIF-'.$suffix,
            'nama_pekerjaan' => 'Pekerjaan '.$suffix,
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Deskripsi Test',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => now()->toDateString(),
            'target_selesai' => now()->addWeek()->toDateString(),
            'created_by' => $creator->id,
        ]);
    }

    private function hpp(User $creator, string $suffix, string $status): Hpp
    {
        $order = $this->order($creator, $suffix);

        return Hpp::query()->create([
            'order_id' => $order->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Dalam',
            'nilai_hpp_bucket' => 'under',
            'item_groups' => [],
            'approval_flow' => [],
            'total_keseluruhan' => 1000000,
            'status' => $status,
            'created_by' => $creator->id,
        ]);
    }

    private function hppSignature(
        Hpp $hpp,
        User $signer,
        string $token,
        ?\DateTimeInterface $expiresAt = null,
        string $roleKey = 'manager_peminta',
    ): HppSignature {
        return $hpp->signatures()->create([
            'step_order' => 1,
            'role_key' => $roleKey,
            'role_label' => $roleKey === 'dirops' ? 'DIROPS' : 'Manager Peminta',
            'signer_user_id' => $signer->id,
            'signer_name_snapshot' => $signer->name,
            'signer_position_snapshot' => 'Approver',
            'status' => HppSignature::STATUS_PENDING,
            'token_hash' => hash('sha256', $token),
            'token' => $token,
            'token_expires_at' => $expiresAt ?: now()->addDay(),
        ]);
    }

    private function bast(User $creator, Order $order, string $terminType, ?LhppBast $parent = null): LhppBast
    {
        return LhppBast::query()->create([
            'order_id' => $order->id,
            'termin_type' => $terminType,
            'parent_lhpp_bast_id' => $parent?->id,
            'nomor_order' => $order->nomor_order,
            'notifikasi' => $order->notifikasi,
            'deskripsi_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'tanggal_bast' => now()->toDateString(),
            'total_aktual_biaya' => 1000000,
            'termin_1_nilai' => 950000,
            'termin_2_nilai' => 50000,
            'approval_threshold' => 'under_250',
            'approval_flow' => [],
            'approval_status' => LhppBast::APPROVAL_IN_REVIEW,
            'quality_control_status' => 'approved',
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
        ]);
    }

    private function bastSignature(LhppBast $bast, User $signer, string $token): LhppBastSignature
    {
        return $bast->signatures()->create([
            'step_order' => 1,
            'role_key' => 'manager_pkm',
            'role_label' => 'Manager PKM',
            'signer_user_id' => $signer->id,
            'signer_name_snapshot' => $signer->name,
            'signer_position_snapshot' => 'Manager PKM',
            'status' => LhppBastSignature::STATUS_PENDING,
            'token_hash' => hash('sha256', $token),
            'token' => $token,
            'token_expires_at' => now()->addDay(),
        ]);
    }
}
