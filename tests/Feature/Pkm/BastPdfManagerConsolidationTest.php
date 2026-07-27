<?php

namespace Tests\Feature\Pkm;

use App\Models\LhppBast;
use App\Models\LhppBastSignature;
use App\Models\Order;
use App\Models\User;
use App\Support\BastPdfSignatureLayoutResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BastPdfManagerConsolidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_legacy_same_manager_is_collapsed_without_mutating_signatures(): void
    {
        [$bast, $manager] = $this->bastWithManagers(sameManager: true);
        $before = $bast->signatures()->get()->map->getAttributes()->all();

        $layout = app(BastPdfSignatureLayoutResolver::class)->resolve($bast);

        $this->assertTrue($layout['managers_collapsed']);
        $this->assertCount(1, collect($layout['approval_cells'])->whereIn('role_key', ['manager_peminta', 'manager_pengendali']));
        $this->assertSame($manager->id, collect($layout['approval_cells'])->firstWhere('role_key', 'manager_pengendali')['signature']->signer_user_id);
        $this->assertSame($before, $bast->signatures()->get()->map->getAttributes()->all());
    }

    public function test_collapsed_manager_prefers_the_signed_signature_with_image(): void
    {
        [$bast] = $this->bastWithManagers(sameManager: true);
        $peminta = $bast->signatures()->where('role_key', 'manager_peminta')->firstOrFail();
        $pengendali = $bast->signatures()->where('role_key', 'manager_pengendali')->firstOrFail();
        $peminta->update([
            'status' => LhppBastSignature::STATUS_SIGNED,
            'signed_at' => now(),
            'signature_data' => 'signatures/manager-peminta.png',
        ]);
        $pengendali->update(['status' => LhppBastSignature::STATUS_PENDING]);
        $bast->unsetRelation('signatures');

        $layout = app(BastPdfSignatureLayoutResolver::class)->resolve($bast);
        $selected = collect($layout['approval_cells'])->firstWhere('role_key', 'manager_pengendali')['signature'];

        $this->assertSame($peminta->id, $selected->id);
    }

    public function test_different_or_reassigned_managers_are_not_collapsed(): void
    {
        [$differentBast] = $this->bastWithManagers(sameManager: false);
        $this->assertFalse(app(BastPdfSignatureLayoutResolver::class)->resolve($differentBast)['managers_collapsed']);

        [$legacyBast] = $this->bastWithManagers(sameManager: true);
        $replacement = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $legacyBast->signatures()->where('role_key', 'manager_pengendali')->update([
            'signer_user_id' => $replacement->id,
            'signer_name_snapshot' => $replacement->name,
        ]);
        $legacyBast->unsetRelation('signatures');

        $layout = app(BastPdfSignatureLayoutResolver::class)->resolve($legacyBast);
        $this->assertFalse($layout['managers_collapsed']);
        $this->assertCount(2, collect($layout['approval_cells'])->whereIn('role_key', ['manager_peminta', 'manager_pengendali']));
    }

    public function test_null_user_fallback_requires_all_non_empty_snapshots_to_match(): void
    {
        [$same] = $this->bastWithManagers(sameManager: true);
        $same->signatures()->whereIn('role_key', ['manager_peminta', 'manager_pengendali'])->update(['signer_user_id' => null]);
        $same->unsetRelation('signatures');
        $this->assertTrue(app(BastPdfSignatureLayoutResolver::class)->resolve($same)['managers_collapsed']);

        [$different] = $this->bastWithManagers(sameManager: true);
        $different->signatures()->where('role_key', 'manager_pengendali')->update([
            'signer_user_id' => null,
            'signer_unit_snapshot' => 'Unit Berbeda',
        ]);
        $different->signatures()->where('role_key', 'manager_peminta')->update(['signer_user_id' => null]);
        $different->unsetRelation('signatures');
        $this->assertFalse(app(BastPdfSignatureLayoutResolver::class)->resolve($different)['managers_collapsed']);
    }

    public function test_pdf_uses_equal_dynamic_columns_and_keeps_manager_pkm_separate(): void
    {
        [$bast, $manager] = $this->bastWithManagers(sameManager: true, includeSupportingRoles: true);
        $html = view('pkm.lhpp.pdf', [
            'lhpp' => $bast->fresh([
                'order.purchaseOrder',
                'purchaseOrder',
                'garansi',
                'images',
                'parentLhppBast.images',
                'signatures',
            ]),
            'materialItems' => [],
            'serviceItems' => [],
        ])->render();

        $this->assertSame(3, substr_count($html, '<col style="width: 33.3333%;">'));
        $this->assertStringContainsString('class="signature-pkm"', $html);
        $this->assertSame(1, substr_count($html, e($manager->name)));
    }

    /** @return array{LhppBast, User} */
    private function bastWithManagers(bool $sameManager, bool $includeSupportingRoles = false): array
    {
        $creator = User::factory()->create();
        $manager = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $otherManager = $sameManager ? $manager : User::factory()->create(['role' => User::ROLE_APPROVER]);
        $order = Order::query()->create([
            'nomor_order' => 'BAST-PDF-'.uniqid(),
            'nama_pekerjaan' => 'PDF Manager',
            'unit_kerja' => 'Workshop',
            'seksi' => 'Machine Workshop',
            'deskripsi' => 'Test',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-07-01',
            'target_selesai' => '2026-07-10',
            'created_by' => $creator->id,
        ]);
        $flow = ['Manager PKM', 'Manager Peminta', 'Manager Pengendali'];

        if ($includeSupportingRoles) {
            $flow = ['Manager PKM', 'Manager Peminta', 'Manager Pengendali', 'SM Pengendali', 'GM Pengendali'];
        }

        $bast = LhppBast::query()->create([
            'order_id' => $order->id,
            'termin_type' => 'termin_1',
            'nomor_order' => $order->nomor_order,
            'deskripsi_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'tanggal_bast' => '2026-07-13',
            'approval_threshold' => 'under_250',
            'approval_flow' => $flow,
            'approval_status' => LhppBast::APPROVAL_IN_REVIEW,
            'quality_control_status' => 'approved',
            'created_by' => $creator->id,
        ]);

        $this->signature($bast, 1, 'manager_pkm', User::factory()->create(), 'MANAGER PKM');
        $this->signature($bast, 2, 'manager_peminta', $manager, 'MANAGER SAME PERSON');
        $this->signature($bast, 3, 'manager_pengendali', $otherManager, $sameManager ? 'MANAGER SAME PERSON' : 'MANAGER CONTROLLER');

        if ($includeSupportingRoles) {
            $this->signature($bast, 4, 'sm_pengendali', User::factory()->create(), 'SM PENGENDALI');
            $this->signature($bast, 5, 'gm_pengendali', User::factory()->create(), 'GM PENGENDALI');
        }

        return [$bast, $manager];
    }

    private function signature(
        LhppBast $bast,
        int $step,
        string $roleKey,
        User $user,
        string $position
    ): LhppBastSignature {
        return $bast->signatures()->create([
            'step_order' => $step,
            'role_key' => $roleKey,
            'role_label' => str($roleKey)->replace('_', ' ')->title()->toString(),
            'signer_user_id' => $user->id,
            'signer_name_snapshot' => $user->name,
            'signer_position_snapshot' => $position,
            'signer_department_snapshot' => 'PMMS',
            'signer_unit_snapshot' => 'Workshop',
            'signer_section_snapshot' => 'Machine Workshop',
            'status' => LhppBastSignature::STATUS_LOCKED,
        ]);
    }
}
