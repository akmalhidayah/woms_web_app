<?php

namespace Tests\Feature\Admin;

use App\Models\LhppBast;
use App\Models\LhppBastSignature;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BastApprovalModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_bast_approval_flow_includes_whatsapp_action(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
        $signature = $this->createPendingBastSignature($admin, 'bast-admin-wa-token');

        $response = $this->actingAs($admin)->get(route('admin.lhpp.index'));

        $response
            ->assertOk()
            ->assertSee('whatsapp_url', false)
            ->assertSee('wa.me', false)
            ->assertSee('6281234567890', false)
            ->assertSee('No WA', false)
            ->assertSee('admin\/lhpp\/'.$signature->lhpp_bast_id.'\/resend-active-approval', false)
            ->assertDontSee('admin\/lhpp\/'.$signature->lhpp_bast_id.'\/signatures', false);
    }

    public function test_admin_bast_approval_flow_includes_rollback_for_signed_step(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
        $signature = $this->createPendingBastSignature($admin, 'bast-admin-signed-token', LhppBastSignature::STATUS_SIGNED);

        $response = $this->actingAs($admin)->get(route('admin.lhpp.index'));

        $response
            ->assertOk()
            ->assertSee('bast-modal-rollback', false)
            ->assertSee('admin\/lhpp\/'.$signature->lhpp_bast_id.'\/signatures\/'.$signature->id.'\/rollback', false);
    }

    public function test_admin_can_delete_bast_and_its_approval_data_for_recreation(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
        $signature = $this->createPendingBastSignature($admin, 'bast-admin-delete-token');
        $bastId = $signature->lhpp_bast_id;

        $this->actingAs($admin)
            ->delete(route('admin.lhpp.destroy', $bastId))
            ->assertRedirect(route('admin.lhpp.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseMissing('lhpp_basts', ['id' => $bastId]);
        $this->assertDatabaseMissing('lhpp_bast_signatures', ['id' => $signature->id]);
    }

    public function test_non_admin_cannot_use_admin_bast_delete_endpoint(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
        $signature = $this->createPendingBastSignature($admin, 'bast-admin-delete-forbidden-token');
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);

        $this->actingAs($pkm)
            ->delete(route('admin.lhpp.destroy', $signature->lhpp_bast_id))
            ->assertForbidden();

        $this->assertDatabaseHas('lhpp_basts', ['id' => $signature->lhpp_bast_id]);
    }

    public function test_pkm_bast_approval_flow_includes_whatsapp_action(): void
    {
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $signature = $this->createPendingBastSignature($pkm, 'bast-pkm-wa-token');

        $response = $this->actingAs($pkm)->get(route('pkm.lhpp.index'));

        $response
            ->assertOk()
            ->assertSee('whatsapp_url', false)
            ->assertSee('wa.me', false)
            ->assertSee('6281234567890', false)
            ->assertSee('No WA', false)
            ->assertSee('pkm\/lhpp\/'.$signature->lhpp_bast_id.'\/resend-active-approval', false)
            ->assertDontSee('bast-modal-rollback', false)
            ->assertDontSee('rollback_url', false);
    }

    private function createPendingBastSignature(
        User $creator,
        string $token,
        string $status = LhppBastSignature::STATUS_PENDING,
    ): LhppBastSignature
    {
        $order = Order::query()->create([
            'nomor_order' => 'ORD-BAST-WA-'.$creator->role,
            'notifikasi' => 'NOTIF-BAST-WA-'.$creator->role,
            'nama_pekerjaan' => 'Pekerjaan BAST WhatsApp',
            'unit_kerja' => 'Unit BAST',
            'seksi' => 'Seksi BAST',
            'deskripsi' => 'Deskripsi BAST',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-06-01',
            'target_selesai' => '2026-06-10',
            'created_by' => $creator->id,
        ]);

        $approver = User::factory()->create([
            'role' => User::ROLE_APPROVER,
            'nomor_hp' => '081234567890',
        ]);

        $lhpp = LhppBast::query()->create([
            'order_id' => $order->id,
            'termin_type' => 'termin_1',
            'nomor_order' => $order->nomor_order,
            'notifikasi' => $order->notifikasi,
            'deskripsi_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'tanggal_bast' => '2026-06-05',
            'tanggal_mulai_pekerjaan' => '2026-06-01',
            'tanggal_selesai_pekerjaan' => '2026-06-05',
            'total_aktual_biaya' => '1000000.00',
            'approval_threshold' => 'under_250',
            'approval_flow' => ['Manager PKM'],
            'approval_status' => LhppBast::APPROVAL_IN_REVIEW,
            'quality_control_status' => 'approved',
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
        ]);

        return $lhpp->signatures()->create([
            'step_order' => 1,
            'role_key' => 'manager_pkm',
            'role_label' => 'Manager PKM',
            'signer_user_id' => $approver->id,
            'signer_name_snapshot' => $approver->name,
            'signer_position_snapshot' => 'Manager PKM',
            'status' => $status,
            'signed_at' => $status === LhppBastSignature::STATUS_SIGNED ? now() : null,
            'signature_data' => $status === LhppBastSignature::STATUS_SIGNED ? 'data:image/png;base64,test' : null,
            'token_hash' => hash('sha256', $token),
            'token' => $token,
            'token_expires_at' => now()->addDay(),
        ]);
    }
}
