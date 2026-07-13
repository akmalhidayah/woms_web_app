<?php

namespace Tests\Feature\Approval;

use App\Models\Garansi;
use App\Models\LhppBast;
use App\Models\LhppBastSignature;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BastRejectFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_reject_preserves_bast_files_history_and_warranty_until_pkm_deletes_it(): void
    {
        Storage::fake('public');
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $signedApprover = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $rejectingApprover = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $nextApprover = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $order = $this->order($pkm);
        $parent = $this->bast($order, $pkm, 'termin_1');
        $child = $this->bast($order, $pkm, 'termin_2', $parent->id);
        $parent->images()->create(['file_path' => 'bast/rejected-image.jpg', 'created_by' => $pkm->id]);
        Storage::disk('public')->put('bast/rejected-image.jpg', 'image');
        Storage::disk('public')->put('bast/signed.png', 'signature');

        $signed = $parent->signatures()->create($this->signaturePayload(1, $signedApprover, LhppBastSignature::STATUS_SIGNED, [
            'signature_data' => 'bast/signed.png', 'signed_at' => now(),
        ]));
        $token = 'bast-reject-token';
        $active = $parent->signatures()->create($this->signaturePayload(2, $rejectingApprover, LhppBastSignature::STATUS_PENDING, [
            'token' => $token, 'token_hash' => hash('sha256', $token), 'token_expires_at' => now()->addDay(),
        ]));
        $next = $parent->signatures()->create($this->signaturePayload(3, $nextApprover, LhppBastSignature::STATUS_LOCKED, [
            'token' => 'unused-token', 'token_hash' => hash('sha256', 'unused-token'), 'token_expires_at' => now()->addDay(),
        ]));
        $garansi = Garansi::query()->create([
            'order_id' => $order->id, 'lhpp_bast_id' => $parent->id, 'garansi_months' => 3,
            'start_date' => now(), 'end_date' => now()->addMonths(3),
        ]);

        $this->actingAs($rejectingApprover)->post(route('approval.bast.sign', $token), [
            'approval_action' => 'reject', 'approval_note' => 'Volume aktual tidak sesuai bukti pekerjaan.',
        ])->assertRedirect(route('approver.dashboard'))
            ->assertSessionHas('status', 'BAST berhasil ditolak. Dokumen tetap tersimpan dan harus dihapus oleh PKM sebelum dibuat ulang.');

        $this->assertDatabaseHas('lhpp_basts', ['id' => $parent->id, 'approval_status' => LhppBast::APPROVAL_REJECTED]);
        $this->assertDatabaseHas('lhpp_basts', ['id' => $child->id]);
        $this->assertDatabaseHas('lhpp_bast_signatures', ['id' => $signed->id, 'status' => LhppBastSignature::STATUS_SIGNED]);
        $this->assertDatabaseHas('lhpp_bast_signatures', [
            'id' => $active->id, 'status' => LhppBastSignature::STATUS_SKIPPED,
            'approval_note' => 'Volume aktual tidak sesuai bukti pekerjaan.', 'token_hash' => null,
        ]);
        $this->assertDatabaseHas('lhpp_bast_signatures', ['id' => $next->id, 'status' => LhppBastSignature::STATUS_SKIPPED, 'token_hash' => null]);
        $this->assertDatabaseHas('garansis', ['id' => $garansi->id, 'order_id' => $order->id, 'lhpp_bast_id' => $parent->id]);
        Storage::disk('public')->assertExists('bast/rejected-image.jpg');
        Storage::disk('public')->assertExists('bast/signed.png');

        $this->actingAs($pkm)->get(route('pkm.lhpp.index'))
            ->assertOk()
            ->assertSeeText('Ditolak')
            ->assertSeeText('Alasan penolakan:')
            ->assertSeeText('Volume aktual tidak sesuai bukti pekerjaan.')
            ->assertSee('Hapus BAST', false);

        $this->actingAs($pkm)->delete(route('pkm.lhpp.destroy', ['nomorOrder' => $order->nomor_order, 'termin' => 'termin-1']))
            ->assertRedirect(route('pkm.lhpp.index'));

        $this->assertDatabaseMissing('lhpp_basts', ['id' => $parent->id]);
        $this->assertDatabaseMissing('lhpp_basts', ['id' => $child->id]);
        $this->assertDatabaseMissing('lhpp_bast_signatures', ['lhpp_bast_id' => $parent->id]);
        $this->assertDatabaseHas('garansis', ['id' => $garansi->id, 'order_id' => $order->id, 'lhpp_bast_id' => null]);
        Storage::disk('public')->assertMissing('bast/rejected-image.jpg');
        Storage::disk('public')->assertMissing('bast/signed.png');
    }

    public function test_non_pkm_cannot_delete_and_non_rejected_started_bast_remains_locked(): void
    {
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $approver = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $order = $this->order($pkm);
        $bast = $this->bast($order, $pkm, 'termin_1');
        $bast->signatures()->create($this->signaturePayload(1, $approver, LhppBastSignature::STATUS_PENDING));
        $route = route('pkm.lhpp.destroy', ['nomorOrder' => $order->nomor_order, 'termin' => 'termin-1']);

        $this->actingAs($approver)->delete($route)->assertForbidden();
        $this->actingAs($pkm)->delete($route)->assertForbidden();
        $this->assertDatabaseHas('lhpp_basts', ['id' => $bast->id]);
    }

    private function order(User $creator): Order
    {
        return Order::query()->create([
            'nomor_order' => 'BAST-REJECT-001', 'nama_pekerjaan' => 'Test reject', 'unit_kerja' => 'Unit',
            'seksi' => 'Seksi', 'deskripsi' => 'Test', 'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => now(), 'target_selesai' => now()->addDays(7), 'created_by' => $creator->id,
        ]);
    }

    private function bast(Order $order, User $creator, string $termin, ?int $parentId = null): LhppBast
    {
        return LhppBast::query()->create([
            'order_id' => $order->id, 'parent_lhpp_bast_id' => $parentId, 'termin_type' => $termin,
            'nomor_order' => $order->nomor_order, 'deskripsi_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja, 'seksi' => $order->seksi, 'tanggal_bast' => now(),
            'approval_status' => LhppBast::APPROVAL_IN_REVIEW, 'quality_control_status' => 'approved',
            'created_by' => $creator->id,
        ]);
    }

    private function signaturePayload(int $step, User $signer, string $status, array $extra = []): array
    {
        return array_merge([
            'step_order' => $step, 'role_key' => 'step_'.$step, 'role_label' => 'Step '.$step,
            'signer_user_id' => $signer->id, 'signer_name_snapshot' => $signer->name,
            'signer_position_snapshot' => 'Approver', 'status' => $status,
        ], $extra);
    }
}
