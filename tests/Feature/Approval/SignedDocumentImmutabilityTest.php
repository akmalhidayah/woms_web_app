<?php

namespace Tests\Feature\Approval;

use App\Models\Department;
use App\Models\InitialWork;
use App\Models\InitialWorkSignature;
use App\Models\LhppBast;
use App\Models\LhppBastSignature;
use App\Models\Order;
use App\Models\OutlineAgreement;
use App\Models\QualityControlReport;
use App\Models\QualityControlReportFile;
use App\Models\QualityControlSignature;
use App\Models\UnitWork;
use App\Models\UnitWorkSection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SignedDocumentImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_work_update_is_blocked_after_a_signature_is_signed(): void
    {
        Log::spy();

        $admin = $this->createAdmin();
        $order = $this->createOrder($admin, 'ORD-IW-LOCK');
        [$outlineAgreement, $unit, $section] = $this->createOutlineAgreement($admin);

        $initialWork = InitialWork::create([
            'order_id' => $order->id,
            'outline_agreement_id' => $outlineAgreement->id,
            'unit_work_id' => $unit->id,
            'unit_work_section_id' => $section->id,
            'nomor_initial_work' => '001/IW/TEST',
            'nomor_order' => $order->nomor_order,
            'notifikasi' => $order->notifikasi,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'kepada_yth' => 'PT. PKM',
            'perihal' => 'Perihal awal',
            'tanggal_initial_work' => now()->toDateString(),
            'functional_location' => ['FL-01'],
            'scope_pekerjaan' => ['Scope awal'],
            'qty' => ['1'],
            'stn' => ['Lot'],
            'keterangan' => [''],
            'created_by' => $admin->id,
        ]);

        $initialWork->signatures()->create([
            'step_order' => 1,
            'role_key' => InitialWorkSignature::ROLE_MANAGER,
            'role_label' => 'Manager',
            'signer_user_id' => $admin->id,
            'status' => InitialWorkSignature::STATUS_SIGNED,
            'signed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->put(
            route('admin.orders.initial-work.update', [$order, $initialWork]),
            [
                'outline_agreement_id' => $outlineAgreement->id,
                'kepada_yth' => 'PT. PKM',
                'perihal' => 'Perihal yang tidak boleh tersimpan',
                'tanggal_initial_work' => now()->toDateString(),
                'functional_location' => ['FL-02'],
                'scope_pekerjaan' => ['Scope berubah'],
                'qty' => ['2'],
                'stn' => ['Lot'],
                'keterangan' => ['Berubah'],
            ],
        );

        $response->assertForbidden();
        $this->assertSame('Perihal awal', $initialWork->fresh()->perihal);

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message, array $context): bool => $message === 'Blocked update to signed Initial Work document.'
                && $context['status_code'] === 403
                && $context['initial_work_id'] === $initialWork->id)
            ->once();
    }

    public function test_quality_control_update_is_blocked_after_a_signature_is_signed(): void
    {
        Log::spy();

        $admin = $this->createAdmin();
        $order = $this->createOrder($admin, 'ORD-QC-LOCK');
        $report = QualityControlReport::create([
            'order_id' => $order->id,
            'type' => QualityControlReport::TYPE_FABRICATION,
            'report_no' => '001/QC/TEST',
            'report_date' => '2026-01-01',
            'status' => QualityControlReport::STATUS_SUBMITTED,
            'payload' => ['notes' => 'Data awal'],
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $report->signatures()->create([
            'step_order' => 1,
            'role_key' => QualityControlSignature::ROLE_WORKSHOP_MANAGER,
            'role_label' => 'Manager Workshop',
            'signer_user_id' => $admin->id,
            'status' => QualityControlSignature::STATUS_SIGNED,
            'signed_at' => now(),
        ]);

        $response = $this->actingAs($admin)->put(
            route('admin.orders.workshop.quality-control.update', [$order, $report]),
            [
                'report_date' => '2026-02-01',
                'status' => QualityControlReport::STATUS_SUBMITTED,
            ],
        );

        $response->assertForbidden();
        $this->assertSame('2026-01-01', $report->fresh()->report_date->toDateString());

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message, array $context): bool => $message === 'Blocked update to signed Quality Control report.'
                && $context['status_code'] === 403
                && $context['quality_control_report_id'] === $report->id)
            ->once();
    }

    public function test_bast_lhpp_update_is_blocked_after_a_signature_is_signed(): void
    {
        Log::spy();

        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $order = $this->createOrder($pkm, 'ORD-BAST-LOCK');
        $lhpp = LhppBast::create([
            'order_id' => $order->id,
            'termin_type' => 'termin_1',
            'nomor_order' => $order->nomor_order,
            'notifikasi' => $order->notifikasi,
            'deskripsi_pekerjaan' => $order->nama_pekerjaan,
            'tipe_pekerjaan' => 'pekerjaan_fabrikasi',
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'tanggal_bast' => '2026-01-01',
            'approval_threshold' => 'under_250',
            'approval_flow' => ['Manager PKM'],
            'approval_status' => LhppBast::APPROVAL_IN_REVIEW,
            'quality_control_status' => 'approved',
            'created_by' => $pkm->id,
            'updated_by' => $pkm->id,
        ]);

        $lhpp->signatures()->create([
            'step_order' => 1,
            'role_key' => 'manager_pkm',
            'role_label' => 'Manager PKM',
            'signer_user_id' => $pkm->id,
            'status' => LhppBastSignature::STATUS_SIGNED,
            'signed_at' => now(),
        ]);

        $response = $this->actingAs($pkm)->patch(
            route('pkm.lhpp.update', [
                'nomorOrder' => $order->nomor_order,
                'termin' => 'termin-1',
            ]),
            [
                'termin_type' => 'termin_1',
                'tanggal_bast' => '2026-02-01',
                'nomor_order' => $order->nomor_order,
                'approval_threshold' => 'under_250',
                'tipe_pekerjaan' => 'pekerjaan_fabrikasi',
                'material_rows' => [],
                'service_rows' => [],
            ],
        );

        $response->assertForbidden();
        $this->assertSame('2026-01-01', $lhpp->fresh()->tanggal_bast->toDateString());

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message, array $context): bool => $message === 'Blocked update to signed BAST/LHPP document.'
                && $context['status_code'] === 403
                && $context['lhpp_bast_id'] === $lhpp->id)
            ->once();
    }

    public function test_quality_control_file_deletion_is_blocked_after_a_signature_is_signed(): void
    {
        Storage::fake('public');
        Log::spy();

        $admin = $this->createAdmin();
        $order = $this->createOrder($admin, 'ORD-QC-FILE-LOCK');
        $report = QualityControlReport::create([
            'order_id' => $order->id,
            'type' => QualityControlReport::TYPE_FABRICATION,
            'report_no' => '002/QC/TEST',
            'report_date' => '2026-01-01',
            'status' => QualityControlReport::STATUS_SUBMITTED,
            'payload' => [],
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        $report->signatures()->create([
            'step_order' => 1,
            'role_key' => QualityControlSignature::ROLE_WORKSHOP_MANAGER,
            'role_label' => 'Manager Workshop',
            'signer_user_id' => $admin->id,
            'status' => QualityControlSignature::STATUS_SIGNED,
            'signed_at' => now(),
        ]);

        Storage::disk('public')->put('quality-control/test/photo.jpg', 'image-content');
        $file = QualityControlReportFile::create([
            'quality_control_report_id' => $report->id,
            'category' => 'fabrication_before',
            'file_path' => 'quality-control/test/photo.jpg',
            'original_name' => 'photo.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 13,
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($admin)->delete(
            route('admin.quality-control.files.destroy', [$report, $file]),
        );

        $response->assertForbidden();
        $this->assertDatabaseHas('quality_control_report_files', ['id' => $file->id]);
        Storage::disk('public')->assertExists($file->file_path);

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message, array $context): bool => $message === 'Blocked file deletion from signed Quality Control report.'
                && $context['status_code'] === 403
                && $context['quality_control_report_file_id'] === $file->id)
            ->once();
    }

    public function test_bast_lhpp_deletion_is_blocked_after_a_signature_is_signed(): void
    {
        Log::spy();

        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $order = $this->createOrder($pkm, 'ORD-BAST-DELETE-LOCK');
        $lhpp = LhppBast::create([
            'order_id' => $order->id,
            'termin_type' => 'termin_1',
            'nomor_order' => $order->nomor_order,
            'notifikasi' => $order->notifikasi,
            'deskripsi_pekerjaan' => $order->nama_pekerjaan,
            'tipe_pekerjaan' => 'pekerjaan_fabrikasi',
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'tanggal_bast' => '2026-01-01',
            'approval_threshold' => 'under_250',
            'approval_flow' => ['Manager PKM'],
            'approval_status' => LhppBast::APPROVAL_IN_REVIEW,
            'quality_control_status' => 'approved',
            'created_by' => $pkm->id,
            'updated_by' => $pkm->id,
        ]);
        $lhpp->signatures()->create([
            'step_order' => 1,
            'role_key' => 'manager_pkm',
            'role_label' => 'Manager PKM',
            'signer_user_id' => $pkm->id,
            'status' => LhppBastSignature::STATUS_SIGNED,
            'signed_at' => now(),
        ]);

        $response = $this->actingAs($pkm)->delete(
            route('pkm.lhpp.destroy', [
                'nomorOrder' => $order->nomor_order,
                'termin' => 'termin-1',
            ]),
        );

        $response->assertForbidden();
        $this->assertDatabaseHas('lhpp_basts', ['id' => $lhpp->id]);

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message, array $context): bool => $message === 'Blocked deletion of signed BAST/LHPP document.'
                && $context['status_code'] === 403
                && $context['lhpp_bast_id'] === $lhpp->id)
            ->once();
    }

    private function createAdmin(): User
    {
        return User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
    }

    private function createOrder(User $creator, string $number): Order
    {
        return Order::create([
            'nomor_order' => $number,
            'notifikasi' => 'NOTIF-'.$number,
            'nama_pekerjaan' => 'Pekerjaan '.$number,
            'unit_kerja' => 'Unit Peminta',
            'seksi' => 'Section Peminta',
            'deskripsi' => 'Deskripsi pekerjaan',
            'prioritas' => Order::PRIORITY_HIGH,
            'tanggal_order' => now()->toDateString(),
            'target_selesai' => now()->addWeek()->toDateString(),
            'created_by' => $creator->id,
        ]);
    }

    /**
     * @return array{OutlineAgreement, UnitWork, UnitWorkSection}
     */
    private function createOutlineAgreement(User $creator): array
    {
        $department = Department::create(['name' => 'Department Approval Test']);
        $unit = UnitWork::create([
            'department_id' => $department->id,
            'name' => 'Unit Approval Test',
        ]);
        $section = UnitWorkSection::create([
            'unit_work_id' => $unit->id,
            'name' => 'Section Approval Test',
        ]);
        $outlineAgreement = OutlineAgreement::create([
            'nomor_oa' => 'OA-APPROVAL-TEST',
            'unit_work_id' => $unit->id,
            'jenis_kontrak' => $section->name,
            'nama_kontrak' => 'Kontrak Approval Test',
            'nilai_kontrak_awal' => 100000000,
            'periode_awal_start' => now()->startOfYear()->toDateString(),
            'periode_awal_end' => now()->endOfYear()->toDateString(),
            'current_total_nilai' => 100000000,
            'current_period_start' => now()->startOfYear()->toDateString(),
            'current_period_end' => now()->endOfYear()->toDateString(),
            'status' => OutlineAgreement::STATUS_ACTIVE,
            'created_by' => $creator->id,
        ]);

        return [$outlineAgreement, $unit, $section];
    }
}
