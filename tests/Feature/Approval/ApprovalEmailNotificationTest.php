<?php

namespace Tests\Feature\Approval;

use App\Models\Department;
use App\Models\Hpp;
use App\Models\HppSignature;
use App\Models\InitialWork;
use App\Models\InitialWorkSignature;
use App\Models\LhppBast;
use App\Models\LhppBastSignature;
use App\Models\Order;
use App\Models\OutlineAgreement;
use App\Models\QualityControlReport;
use App\Models\QualityControlSignature;
use App\Models\UnitWork;
use App\Models\UnitWorkSection;
use App\Models\User;
use App\Notifications\ApprovalRequestedNotification;
use App\Services\Approvals\ApprovalNotificationService;
use App\Services\InitialWorks\InitialWorkSignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ApprovalEmailNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_initial_work_signature_creation_sends_approval_email_to_active_approver(): void
    {
        Notification::fake();

        $admin = $this->createAdmin();
        $manager = User::factory()->create(['role' => User::ROLE_APPROVER, 'email' => 'manager@example.test']);
        $seniorManager = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $order = $this->createOrder($admin, 'ORD-IW-MAIL');
        [$outlineAgreement, $unit, $section] = $this->createOutlineAgreement($admin, $manager, $seniorManager);

        $initialWork = InitialWork::create([
            'order_id' => $order->id,
            'outline_agreement_id' => $outlineAgreement->id,
            'unit_work_id' => $unit->id,
            'unit_work_section_id' => $section->id,
            'nomor_initial_work' => '001/IW/MAIL',
            'nomor_order' => $order->nomor_order,
            'notifikasi' => $order->notifikasi,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'kepada_yth' => 'PT. PKM',
            'perihal' => 'Approval email',
            'tanggal_initial_work' => now()->toDateString(),
            'functional_location' => ['FL-01'],
            'scope_pekerjaan' => ['Scope'],
            'qty' => ['1'],
            'stn' => ['Lot'],
            'keterangan' => [''],
            'created_by' => $admin->id,
        ]);

        app(InitialWorkSignatureService::class)->createSignatureChain($initialWork);

        Notification::assertSentTo(
            $manager,
            ApprovalRequestedNotification::class,
            fn (ApprovalRequestedNotification $notification): bool => $notification->documentType === 'Initial Work'
                && $notification->documentNumber === '001/IW/MAIL'
                && str_contains($notification->approvalUrl, '/approval/initial-work/')
        );
        Notification::assertNotSentTo($seniorManager, ApprovalRequestedNotification::class);
    }

    public function test_initial_work_resend_endpoint_sends_active_approval_email(): void
    {
        Notification::fake();

        $admin = $this->createAdmin();
        $approver = User::factory()->create(['role' => User::ROLE_APPROVER, 'email' => 'approver@example.test']);
        $order = $this->createOrder($admin, 'ORD-IW-RESEND');
        [$outlineAgreement, $unit, $section] = $this->createOutlineAgreement($admin, $approver);
        $initialWork = InitialWork::create([
            'order_id' => $order->id,
            'outline_agreement_id' => $outlineAgreement->id,
            'unit_work_id' => $unit->id,
            'unit_work_section_id' => $section->id,
            'nomor_initial_work' => '002/IW/MAIL',
            'nomor_order' => $order->nomor_order,
            'notifikasi' => $order->notifikasi,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'kepada_yth' => 'PT. PKM',
            'perihal' => 'Resend approval email',
            'tanggal_initial_work' => now()->toDateString(),
            'functional_location' => ['FL-01'],
            'scope_pekerjaan' => ['Scope'],
            'qty' => ['1'],
            'stn' => ['Lot'],
            'keterangan' => [''],
            'created_by' => $admin->id,
        ]);
        $initialWork->signatures()->create([
            'step_order' => 1,
            'role_key' => InitialWorkSignature::ROLE_MANAGER,
            'role_label' => 'Manager Test',
            'signer_user_id' => $approver->id,
            'signer_name' => $approver->name,
            'status' => InitialWorkSignature::STATUS_PENDING,
            'token_hash' => hash('sha256', 'resend-token'),
            'token_encrypted' => 'resend-token',
            'token_expires_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.orders.initial-work.approval.resend', [$order, $initialWork]));

        $response->assertRedirect();
        $response->assertSessionHas('status');
        Notification::assertSentTo(
            $approver,
            ApprovalRequestedNotification::class,
            fn (ApprovalRequestedNotification $notification): bool => $notification->documentType === 'Initial Work'
                && $notification->approvalUrl === route('approval.initial-work.show', 'resend-token')
        );
    }

    public function test_approval_notification_service_supports_all_document_types(): void
    {
        Notification::fake();

        $admin = $this->createAdmin();
        $approver = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $order = $this->createOrder($admin, 'ORD-ALL-MAIL');
        [$outlineAgreement, $unit, $section] = $this->createOutlineAgreement($admin, $approver);
        $initialWork = $this->createInitialWork($order, $outlineAgreement, $unit, $section, $admin);
        $qualityControlReport = QualityControlReport::create([
            'order_id' => $order->id,
            'type' => QualityControlReport::TYPE_FABRICATION,
            'report_no' => 'QC-MAIL',
            'report_date' => now()->toDateString(),
            'status' => QualityControlReport::STATUS_SUBMITTED,
            'payload' => [],
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        $hpp = Hpp::create([
            'order_id' => $order->id,
            'outline_agreement_id' => $outlineAgreement->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'unit_work_id' => $unit->id,
            'cost_centre' => 'CC-MAIL',
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Dalam',
            'nilai_hpp_bucket' => 'under',
            'unit_kerja_pengendali' => $unit->name,
            'outline_agreement' => $outlineAgreement->nomor_oa,
            'periode_outline_agreement' => now()->startOfYear()->format('d/m/Y').' - '.now()->endOfYear()->format('d/m/Y'),
            'approval_case' => 'TEST-MAIL',
            'approval_flow' => ['Manager Test'],
            'item_groups' => [],
            'total_keseluruhan' => 1000000,
            'status' => Hpp::STATUS_IN_REVIEW,
            'created_by' => $admin->id,
        ]);
        $lhpp = LhppBast::create([
            'order_id' => $order->id,
            'hpp_id' => $hpp->id,
            'termin_type' => 'termin_1',
            'nomor_order' => $order->nomor_order,
            'notifikasi' => $order->notifikasi,
            'deskripsi_pekerjaan' => $order->nama_pekerjaan,
            'tipe_pekerjaan' => 'pekerjaan_fabrikasi',
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'tanggal_bast' => now()->toDateString(),
            'approval_threshold' => 'under_250',
            'approval_flow' => ['Manager Test'],
            'approval_status' => LhppBast::APPROVAL_IN_REVIEW,
            'quality_control_status' => 'approved',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $initialSignature = $initialWork->signatures()->create($this->baseInitialSignature($approver, 'iw-all-token'));
        $qualityControlSignature = $qualityControlReport->signatures()->create($this->baseQualityControlSignature($approver, 'qc-all-token'));
        $hppSignature = $hpp->signatures()->create($this->baseHppSignature($approver, 'hpp-all-token'));
        $bastSignature = $lhpp->signatures()->create($this->baseBastSignature($approver, 'bast-all-token'));
        $service = app(ApprovalNotificationService::class);

        $this->assertTrue($service->sendInitialWork($initialSignature));
        $this->assertTrue($service->sendQualityControl($qualityControlSignature));
        $this->assertTrue($service->sendHpp($hppSignature));
        $this->assertTrue($service->sendBast($bastSignature));

        Notification::assertSentToTimes($approver, ApprovalRequestedNotification::class, 4);
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
            'unit_kerja' => 'Unit Approval Test',
            'seksi' => 'Section Approval Test',
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
    private function createOutlineAgreement(User $creator, User $manager, ?User $seniorManager = null): array
    {
        $seniorManager ??= User::factory()->create(['role' => User::ROLE_APPROVER]);
        $department = Department::create(['name' => 'Department Approval Email Test']);
        $unit = UnitWork::create([
            'department_id' => $department->id,
            'name' => 'Unit Approval Test',
            'senior_manager_id' => $seniorManager->id,
        ]);
        $section = UnitWorkSection::create([
            'unit_work_id' => $unit->id,
            'name' => 'Section Approval Test',
            'manager_id' => $manager->id,
        ]);
        $outlineAgreement = OutlineAgreement::create([
            'nomor_oa' => 'OA-MAIL-'.uniqid(),
            'unit_work_id' => $unit->id,
            'jenis_kontrak' => $section->name,
            'nama_kontrak' => 'Kontrak Approval Email Test',
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

    private function createInitialWork(
        Order $order,
        OutlineAgreement $outlineAgreement,
        UnitWork $unit,
        UnitWorkSection $section,
        User $creator,
    ): InitialWork {
        return InitialWork::create([
            'order_id' => $order->id,
            'outline_agreement_id' => $outlineAgreement->id,
            'unit_work_id' => $unit->id,
            'unit_work_section_id' => $section->id,
            'nomor_initial_work' => '003/IW/MAIL',
            'nomor_order' => $order->nomor_order,
            'notifikasi' => $order->notifikasi,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'kepada_yth' => 'PT. PKM',
            'perihal' => 'All approval email',
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
    private function baseInitialSignature(User $approver, string $token): array
    {
        return [
            'step_order' => 1,
            'role_key' => InitialWorkSignature::ROLE_MANAGER,
            'role_label' => 'Manager Test',
            'signer_user_id' => $approver->id,
            'signer_name' => $approver->name,
            'status' => InitialWorkSignature::STATUS_PENDING,
            'token_hash' => hash('sha256', $token),
            'token_encrypted' => $token,
            'token_expires_at' => now()->addDay(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function baseQualityControlSignature(User $approver, string $token): array
    {
        return [
            'step_order' => 1,
            'role_key' => QualityControlSignature::ROLE_WORKSHOP_MANAGER,
            'role_label' => 'Manager Workshop',
            'signer_user_id' => $approver->id,
            'signer_name' => $approver->name,
            'status' => QualityControlSignature::STATUS_PENDING,
            'token_hash' => hash('sha256', $token),
            'token_encrypted' => $token,
            'token_expires_at' => now()->addDay(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function baseHppSignature(User $approver, string $token): array
    {
        return [
            'step_order' => 1,
            'role_key' => 'manager_peminta',
            'role_label' => 'Manager Test',
            'signer_user_id' => $approver->id,
            'signer_name_snapshot' => $approver->name,
            'signer_position_snapshot' => 'Manager Test',
            'status' => HppSignature::STATUS_PENDING,
            'token_hash' => hash('sha256', $token),
            'token' => $token,
            'token_expires_at' => now()->addDay(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function baseBastSignature(User $approver, string $token): array
    {
        return [
            'step_order' => 1,
            'role_key' => 'manager_pkm',
            'role_label' => 'Manager Test',
            'signer_user_id' => $approver->id,
            'signer_name_snapshot' => $approver->name,
            'signer_position_snapshot' => 'Manager Test',
            'status' => LhppBastSignature::STATUS_PENDING,
            'token_hash' => hash('sha256', $token),
            'token' => $token,
            'token_expires_at' => now()->addDay(),
        ];
    }
}
