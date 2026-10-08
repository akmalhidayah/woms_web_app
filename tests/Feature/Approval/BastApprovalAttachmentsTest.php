<?php

namespace Tests\Feature\Approval;

use App\Domain\Orders\Enums\OrderDocumentType;
use App\Models\Hpp;
use App\Models\LhppBast;
use App\Models\LhppBastSignature;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BastApprovalAttachmentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_termin_one_approval_shows_bast_hpp_and_abnormalitas_tabs_without_extra_termin_one_tab(): void
    {
        [$signer, $order, $hpp] = $this->approvalContext('ATTACH-T1');
        $bast = $this->bast($order, $signer, 'termin_1', hppId: $hpp->id);
        $token = $this->signature($bast, $signer, 'attach-t1-token');
        $this->abnormalitas($order, $signer);

        $this->actingAs($signer)
            ->get(route('approval.bast.show', $token))
            ->assertOk()
            ->assertSee('data-preview-target="bast"', false)
            ->assertSee('data-preview-target="hpp"', false)
            ->assertSee('data-preview-target="abnormalitas"', false)
            ->assertDontSee('data-preview-target="bast-termin-1"', false);
    }

    public function test_termin_two_approval_shows_tabs_in_required_order(): void
    {
        [$signer, $order, $hpp] = $this->approvalContext('ATTACH-T2');
        $terminOne = $this->bast($order, $signer, 'termin_1', hppId: $hpp->id);
        $terminTwo = $this->bast($order, $signer, 'termin_2', $terminOne->id, $hpp->id);
        $token = $this->signature($terminTwo, $signer, 'attach-t2-token');
        $this->abnormalitas($order, $signer);

        $response = $this->actingAs($signer)->get(route('approval.bast.show', $token));

        $response->assertOk()->assertSeeInOrder([
            'data-preview-target="bast"',
            'data-preview-target="bast-termin-1"',
            'data-preview-target="hpp"',
            'data-preview-target="abnormalitas"',
        ], false);
    }

    public function test_termin_two_legacy_fallback_uses_termin_one_from_exact_same_order(): void
    {
        [$signer, $order, $hpp] = $this->approvalContext('ATTACH-LEGACY');
        $terminOne = $this->bast($order, $signer, 'termin_1', hppId: $hpp->id);
        $terminTwo = $this->bast($order, $signer, 'termin_2', null, $hpp->id);
        $token = $this->signature($terminTwo, $signer, 'attach-legacy-token');

        $this->actingAs($signer)
            ->get(route('approval.bast.show', $token))
            ->assertOk()
            ->assertViewHas('terminOneBastPdfUrl', route('approval.bast.termin-one', $token));

        $this->assertSame($order->id, $terminOne->order_id);
    }

    public function test_other_user_cannot_access_bast_attachment_endpoints(): void
    {
        [$signer, $order, $hpp] = $this->approvalContext('ATTACH-AUTH');
        $otherUser = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $terminOne = $this->bast($order, $signer, 'termin_1', hppId: $hpp->id);
        $terminTwo = $this->bast($order, $signer, 'termin_2', $terminOne->id, $hpp->id);
        $token = $this->signature($terminTwo, $signer, 'attach-auth-token');
        $this->abnormalitas($order, $signer);

        foreach (['approval.bast.hpp', 'approval.bast.abnormalitas', 'approval.bast.termin-one'] as $routeName) {
            $this->actingAs($otherUser)
                ->get(route($routeName, $token))
                ->assertForbidden();
        }
    }

    public function test_termin_one_preview_never_uses_parent_from_another_order(): void
    {
        [$signer, $order, $hpp] = $this->approvalContext('ATTACH-SAME-ORDER');
        $sameOrderTerminOne = $this->bast($order, $signer, 'termin_1', hppId: $hpp->id);
        [, $otherOrder, $otherHpp] = $this->approvalContext('ATTACH-OTHER-ORDER');
        $otherTerminOne = $this->bast($otherOrder, $signer, 'termin_1', hppId: $otherHpp->id);
        $terminTwo = $this->bast($order, $signer, 'termin_2', $otherTerminOne->id, $hpp->id);
        $token = $this->signature($terminTwo, $signer, 'attach-same-order-token');

        $this->actingAs($signer)
            ->get(route('approval.bast.termin-one', $token))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename="bast-termin-1-'.$sameOrderTerminOne->nomor_order.'.pdf"');
    }

    public function test_missing_abnormalitas_keeps_approval_page_available_and_disables_tab(): void
    {
        [$signer, $order, $hpp] = $this->approvalContext('ATTACH-NO-ABNORMAL');
        $bast = $this->bast($order, $signer, 'termin_1', hppId: $hpp->id);
        $token = $this->signature($bast, $signer, 'attach-no-abnormal-token');

        $this->actingAs($signer)
            ->get(route('approval.bast.show', $token))
            ->assertOk()
            ->assertSee('data-preview-target="abnormalitas"', false)
            ->assertSee('disabled', false);
    }

    public function test_bast_signature_pad_uses_pointer_events_without_waiting_for_pdf_preview(): void
    {
        [$signer, $order, $hpp] = $this->approvalContext('BAST-STABLE-PAD');
        $bast = $this->bast($order, $signer, 'termin_1', hppId: $hpp->id);
        $token = $this->signature($bast, $signer, 'bast-stable-pad-token');

        $this->actingAs($signer)
            ->get(route('approval.bast.show', $token))
            ->assertOk()
            ->assertSee('touch-none', false)
            ->assertSee('initializeSignaturePad();', false)
            ->assertSee("canvas.addEventListener('pointerdown', start);", false)
            ->assertSee("canvas.addEventListener('pointercancel', stop);", false)
            ->assertSee('canvas.setPointerCapture?.(event.pointerId);', false)
            ->assertSee('const minimumStrokePoints = 8;', false)
            ->assertSee('const minimumStrokeDistance = 40;', false)
            ->assertDontSee("canvas.addEventListener('touchstart', start", false)
            ->assertDontSee("canvas.addEventListener('mousedown', start", false);
    }

    /**
     * @return array{User, Order, Hpp}
     */
    private function approvalContext(string $number): array
    {
        $signer = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $order = Order::query()->create([
            'nomor_order' => $number,
            'nama_pekerjaan' => 'Pekerjaan lampiran BAST',
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Pengujian lampiran approval BAST',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => now()->toDateString(),
            'target_selesai' => now()->addWeek()->toDateString(),
            'created_by' => $signer->id,
        ]);
        $hpp = Hpp::query()->create([
            'order_id' => $order->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Dalam',
            'nilai_hpp_bucket' => 'under',
            'total_keseluruhan' => 1000000,
            'status' => Hpp::STATUS_APPROVED,
            'created_by' => $signer->id,
        ]);

        return [$signer, $order, $hpp];
    }

    private function bast(
        Order $order,
        User $creator,
        string $terminType,
        ?int $parentId = null,
        ?int $hppId = null,
    ): LhppBast {
        return LhppBast::query()->create([
            'order_id' => $order->id,
            'hpp_id' => $hppId,
            'parent_lhpp_bast_id' => $parentId,
            'termin_type' => $terminType,
            'nomor_order' => $order->nomor_order,
            'deskripsi_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'tanggal_bast' => now()->toDateString(),
            'approval_status' => LhppBast::APPROVAL_IN_REVIEW,
            'quality_control_status' => 'approved',
            'created_by' => $creator->id,
        ]);
    }

    private function signature(LhppBast $bast, User $signer, string $token): string
    {
        $bast->signatures()->create([
            'step_order' => 1,
            'role_key' => 'manager_pkm',
            'role_label' => 'Manager PKM',
            'signer_user_id' => $signer->id,
            'signer_name_snapshot' => $signer->name,
            'signer_position_snapshot' => 'Manager PKM',
            'status' => LhppBastSignature::STATUS_PENDING,
            'token' => $token,
            'token_hash' => hash('sha256', $token),
            'token_expires_at' => now()->addDay(),
        ]);

        return $token;
    }

    private function abnormalitas(Order $order, User $uploader): void
    {
        OrderDocument::query()->create([
            'order_id' => $order->id,
            'jenis_dokumen' => OrderDocumentType::Abnormalitas->value,
            'nama_file_asli' => 'abnormalitas.pdf',
            'path_file' => 'orders/abnormalitas.pdf',
            'uploaded_by' => $uploader->id,
            'uploaded_at' => now(),
        ]);
    }
}
