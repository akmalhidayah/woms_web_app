<?php

namespace Tests\Feature\Pkm;

use App\Models\Department;
use App\Models\Hpp;
use App\Models\LhppBast;
use App\Models\LhppBastSignature;
use App\Models\Order;
use App\Models\OutlineAgreement;
use App\Models\UnitWork;
use App\Models\User;
use App\Services\Approvals\ApprovalSignatureRollbackService;
use App\Services\Approvals\BastSmPengendaliSynchronizer;
use App\Support\BastApprovalFlow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BastSmPengendaliFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_flows_put_sm_between_controller_manager_and_gm(): void
    {
        $this->assertSame([
            'Manager PKM',
            'Manager Peminta',
            'Manager Pengendali',
            'SM Pengendali',
            'GM Pengendali',
        ], BastApprovalFlow::resolveApprovalFlow('under_250'));
        $this->assertSame([
            'Manager PKM',
            'Manager Peminta',
            'Manager Pengendali',
            'SM Pengendali',
            'GM Pengendali',
            'DIROPS',
        ], BastApprovalFlow::resolveApprovalFlow('over_250'));
    }

    public function test_legacy_signatures_are_synchronized_idempotently_and_gm_pending_moves_to_sm(): void
    {
        [$bast, $seniorManager] = $this->legacyBast(LhppBastSignature::STATUS_PENDING);
        $synchronizer = app(BastSmPengendaliSynchronizer::class);

        $first = $synchronizer->sync($bast);
        $second = $synchronizer->sync($bast->fresh());
        $signatures = $bast->signatures()->orderBy('step_order')->get();
        $sm = $signatures->firstWhere('role_key', 'sm_pengendali');
        $gm = $signatures->firstWhere('role_key', 'gm_pengendali');

        $this->assertTrue($first['sm_added']);
        $this->assertTrue($first['gm_pending_redirected']);
        $this->assertSame('unchanged', $second['status']);
        $this->assertSame(1, $signatures->where('role_key', 'sm_pengendali')->count());
        $this->assertSame($seniorManager->id, $sm->signer_user_id);
        $this->assertSame(LhppBastSignature::STATUS_PENDING, $sm->status);
        $this->assertNotNull($sm->token_hash);
        $this->assertSame(LhppBastSignature::STATUS_LOCKED, $gm->status);
        $this->assertNull($gm->token_hash);
        $this->assertSame([
            'manager_pkm',
            'manager_peminta',
            'manager_pengendali',
            'sm_pengendali',
            'gm_pengendali',
        ], $signatures->pluck('role_key')->all());
    }

    public function test_rollback_from_signed_gm_activates_unsigned_sm_without_changing_prior_steps(): void
    {
        [$bast] = $this->legacyBast(LhppBastSignature::STATUS_SIGNED);
        app(BastSmPengendaliSynchronizer::class)->sync($bast);
        $gm = $bast->signatures()->where('role_key', 'gm_pengendali')->firstOrFail();
        $manager = $bast->signatures()->where('role_key', 'manager_pengendali')->firstOrFail();
        $managerSignedAt = $manager->signed_at;
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);

        $active = app(ApprovalSignatureRollbackService::class)->rollbackBast(
            $bast,
            $gm,
            $admin,
            'SM Pengendali wajib menandatangani.',
            false,
        );

        $this->assertSame('sm_pengendali', $active->role_key);
        $this->assertSame(LhppBastSignature::STATUS_PENDING, $active->status);
        $this->assertSame(LhppBastSignature::STATUS_LOCKED, $gm->fresh()->status);
        $this->assertSame(LhppBastSignature::STATUS_SIGNED, $manager->fresh()->status);
        $this->assertEquals($managerSignedAt, $manager->fresh()->signed_at);
        $this->assertSame(LhppBast::APPROVAL_IN_REVIEW, $bast->fresh()->approval_status);
    }

    public function test_sync_command_is_idempotent(): void
    {
        [$bast] = $this->legacyBast(LhppBastSignature::STATUS_LOCKED);

        $this->artisan('bast:sync-sm-pengendali', ['--bast-id' => $bast->id])
            ->assertSuccessful();
        $this->artisan('bast:sync-sm-pengendali', ['--bast-id' => $bast->id])
            ->assertSuccessful();

        $this->assertSame(1, $bast->signatures()->where('role_key', 'sm_pengendali')->count());
    }

    public function test_sync_fails_clearly_when_controller_senior_manager_is_not_configured(): void
    {
        [$bast] = $this->legacyBast(LhppBastSignature::STATUS_LOCKED);
        $bast->hpp->outlineAgreement->unitWork->update(['senior_manager_id' => null]);

        try {
            app(BastSmPengendaliSynchronizer::class)->sync($bast);
            $this->fail('Sinkronisasi seharusnya gagal tanpa SM Pengendali.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'SM Pengendali tidak ditemukan pada struktur unit pengendali HPP.',
                $exception->errors()['approval'][0],
            );
        }
    }

    public function test_bast_pdf_layout_contains_sm_cell_for_both_thresholds(): void
    {
        $resolverSource = file_get_contents(app_path('Support/BastPdfSignatureLayoutResolver.php'));
        $templateSource = file_get_contents(resource_path('views/pkm/lhpp/pdf.blade.php'));

        $this->assertStringContainsString("['sm_pengendali', 'SM Pengendali']", $resolverSource);
        $this->assertStringContainsString(
            "\$approvalColumnWidth = 100 / \$approvalColumnCount",
            $templateSource,
        );
        $this->assertStringContainsString(
            '<col style="width: {{ number_format($approvalColumnWidth',
            $templateSource,
        );
    }

    /**
     * @return array{LhppBast,User}
     */
    private function legacyBast(string $gmStatus): array
    {
        $creator = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $seniorManager = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $department = Department::query()->create(['name' => 'Departemen Pengendali']);
        $unit = UnitWork::query()->create([
            'department_id' => $department->id,
            'name' => 'Unit Pengendali',
            'senior_manager_id' => $seniorManager->id,
        ]);
        $oa = OutlineAgreement::query()->create([
            'nomor_oa' => 'OA-SM-'.uniqid(),
            'unit_work_id' => $unit->id,
            'jenis_kontrak' => 'Jasa',
            'nama_kontrak' => 'Kontrak SM',
            'nilai_kontrak_awal' => 1000000,
            'periode_awal_start' => '2026-01-01',
            'periode_awal_end' => '2026-12-31',
            'current_total_nilai' => 1000000,
            'current_period_start' => '2026-01-01',
            'current_period_end' => '2026-12-31',
            'status' => OutlineAgreement::STATUS_ACTIVE,
            'created_by' => $creator->id,
        ]);
        $order = Order::query()->create([
            'nomor_order' => 'ORD-SM-'.uniqid(),
            'nama_pekerjaan' => 'Pekerjaan SM',
            'unit_kerja' => 'Unit',
            'seksi' => 'Seksi',
            'deskripsi' => 'Test',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-07-01',
            'target_selesai' => '2026-07-10',
            'created_by' => $creator->id,
        ]);
        $hpp = Hpp::query()->create([
            'order_id' => $order->id,
            'outline_agreement_id' => $oa->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Dalam',
            'nilai_hpp_bucket' => 'under',
            'item_groups' => [],
            'total_keseluruhan' => 1000000,
            'status' => Hpp::STATUS_APPROVED,
            'created_by' => $creator->id,
        ]);
        $bast = LhppBast::query()->create([
            'order_id' => $order->id,
            'hpp_id' => $hpp->id,
            'termin_type' => 'termin_1',
            'nomor_order' => $order->nomor_order,
            'deskripsi_pekerjaan' => 'Pekerjaan SM',
            'unit_kerja' => 'Unit',
            'seksi' => 'Seksi',
            'tanggal_bast' => '2026-07-13',
            'approval_threshold' => 'under_250',
            'approval_flow' => ['Manager PKM', 'Manager Peminta', 'Manager Pengendali', 'GM Pengendali'],
            'approval_status' => LhppBast::APPROVAL_APPROVED,
            'quality_control_status' => 'approved',
            'created_by' => $creator->id,
        ]);

        foreach ([
            ['manager_pkm', 'Manager PKM'],
            ['manager_peminta', 'Manager Peminta'],
            ['manager_pengendali', 'Manager Pengendali'],
            ['gm_pengendali', 'GM Pengendali'],
        ] as $index => [$roleKey, $roleLabel]) {
            $status = $roleKey === 'gm_pengendali' ? $gmStatus : LhppBastSignature::STATUS_SIGNED;
            $token = $status === LhppBastSignature::STATUS_PENDING ? 'gm-token-'.uniqid() : null;
            $bast->signatures()->create([
                'step_order' => $index + 1,
                'role_key' => $roleKey,
                'role_label' => $roleLabel,
                'signer_user_id' => $creator->id,
                'signer_name_snapshot' => $creator->name,
                'status' => $status,
                'token' => $token,
                'token_hash' => $token ? hash('sha256', $token) : null,
                'token_expires_at' => $token ? now()->addDay() : null,
                'signed_at' => $status === LhppBastSignature::STATUS_SIGNED ? now() : null,
                'signature_data' => $status === LhppBastSignature::STATUS_SIGNED ? 'data:image/png;base64,test' : null,
                'signed_ip' => $status === LhppBastSignature::STATUS_SIGNED ? '127.0.0.1' : null,
                'signed_user_agent' => $status === LhppBastSignature::STATUS_SIGNED ? 'PHPUnit' : null,
            ]);
        }

        return [$bast, $seniorManager];
    }
}
