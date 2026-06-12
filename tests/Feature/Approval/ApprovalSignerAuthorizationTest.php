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
use App\Models\QualityControlReport;
use App\Models\QualityControlSignature;
use App\Models\UnitWork;
use App\Models\UnitWorkSection;
use App\Models\User;
use App\Notifications\ApprovalRequestedNotification;
use App\Services\QualityControl\QualityControlSignatureService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ApprovalSignerAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_assigned_signer_can_open_each_approval_link(): void
    {
        $creator = User::factory()->create();
        $signer = User::factory()->create(['role' => User::ROLE_USER]);
        $otherApprover = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $order = $this->createOrder($creator);

        $links = [
            'initial-work' => $this->createInitialWorkApprovalLink($order, $creator, $signer),
            'hpp' => $this->createHppApprovalLink($order, $creator, $signer),
            'bast' => $this->createBastApprovalLink($order, $creator, $signer),
            'quality-control' => $this->createQualityControlApprovalLink($order, $creator, $signer),
        ];

        foreach ($links as $documentType => $link) {
            $this->actingAs($otherApprover)->get($link)->assertForbidden();
            $response = $this->actingAs($signer)->get($link);

            $response->assertOk();

            if (in_array($documentType, ['hpp', 'bast'], true)) {
                $response
                    ->assertSee('submissionLoadingOverlay', false)
                    ->assertSee('Memproses approval...');
            }
        }
    }

    public function test_qc_signer_change_rotates_token_and_notifies_new_signer(): void
    {
        Notification::fake();

        $creator = User::factory()->create();
        $oldWorkshopManager = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $newWorkshopManager = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $userManager = User::factory()->create(['role' => User::ROLE_APPROVER]);

        $workshopDepartment = Department::query()->create(['name' => 'Workshop Department']);
        $workshopUnit = UnitWork::query()->create([
            'department_id' => $workshopDepartment->id,
            'name' => 'Workshop',
        ]);
        $workshopSection = UnitWorkSection::query()->create([
            'unit_work_id' => $workshopUnit->id,
            'name' => 'Machine Workshop',
            'manager_id' => $oldWorkshopManager->id,
        ]);

        $userDepartment = Department::query()->create(['name' => 'User Department']);
        $userUnit = UnitWork::query()->create([
            'department_id' => $userDepartment->id,
            'name' => 'User Unit',
        ]);
        UnitWorkSection::query()->create([
            'unit_work_id' => $userUnit->id,
            'name' => 'User Section',
            'manager_id' => $userManager->id,
        ]);

        $order = $this->createOrder($creator, 'ORD-QC-ROTATE', 'User Unit', 'User Section');
        $report = QualityControlReport::query()->create([
            'order_id' => $order->id,
            'type' => QualityControlReport::TYPE_FABRICATION,
            'report_no' => 'QC-ROTATE',
            'report_date' => now()->toDateString(),
            'status' => QualityControlReport::STATUS_SUBMITTED,
            'payload' => [],
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
        ]);

        $service = app(QualityControlSignatureService::class);
        $created = $service->createSignatureChain($report);
        $oldToken = $created['workshop_signature']->token_encrypted;

        $workshopSection->update(['manager_id' => $newWorkshopManager->id]);
        $repaired = $service->ensureSignatureChain($report->fresh());
        $newSignature = $repaired['workshop_signature']->fresh();
        $newToken = $newSignature->token_encrypted;

        $this->assertSame($newWorkshopManager->id, $newSignature->signer_user_id);
        $this->assertNotSame($oldToken, $newToken);
        $this->assertNotNull($newSignature->approvalUrl());

        Notification::assertSentTo(
            $newWorkshopManager,
            ApprovalRequestedNotification::class,
            fn (ApprovalRequestedNotification $notification): bool => $notification->approvalUrl === $newSignature->approvalUrl()
        );

        $this->actingAs($oldWorkshopManager)
            ->get(route('approval.quality-control.show', $oldToken))
            ->assertNotFound();

        $this->actingAs($newWorkshopManager)
            ->get(route('approval.quality-control.show', $newToken))
            ->assertOk();
    }

    private function createOrder(
        User $creator,
        string $number = 'ORD-AUTH-APPROVAL',
        string $unit = 'Unit Test',
        string $section = 'Section Test',
    ): Order {
        return Order::query()->create([
            'nomor_order' => $number,
            'nama_pekerjaan' => 'Approval authorization test',
            'unit_kerja' => $unit,
            'seksi' => $section,
            'deskripsi' => 'Approval authorization test',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => now()->toDateString(),
            'target_selesai' => now()->addWeek()->toDateString(),
            'created_by' => $creator->id,
        ]);
    }

    private function createInitialWorkApprovalLink(Order $order, User $creator, User $signer): string
    {
        $initialWork = InitialWork::query()->create([
            'order_id' => $order->id,
            'nomor_initial_work' => 'IW-AUTH-001',
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'perihal' => 'Authorization test',
            'tanggal_initial_work' => now()->toDateString(),
            'functional_location' => ['FL-01'],
            'scope_pekerjaan' => ['Scope'],
            'qty' => ['1'],
            'stn' => ['Lot'],
            'created_by' => $creator->id,
        ]);
        $token = 'initial-work-auth-token';

        $initialWork->signatures()->create([
            'step_order' => 1,
            'role_key' => InitialWorkSignature::ROLE_MANAGER,
            'role_label' => 'Manager Test',
            'signer_user_id' => $signer->id,
            'signer_name' => $signer->name,
            'status' => InitialWorkSignature::STATUS_PENDING,
            'token_hash' => hash('sha256', $token),
            'token_encrypted' => $token,
            'token_expires_at' => now()->addDay(),
        ]);

        return route('approval.initial-work.show', $token);
    }

    private function createHppApprovalLink(Order $order, User $creator, User $signer): string
    {
        $hpp = Hpp::query()->create([
            'order_id' => $order->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Dalam',
            'nilai_hpp_bucket' => 'under',
            'total_keseluruhan' => 1000000,
            'status' => Hpp::STATUS_IN_REVIEW,
            'created_by' => $creator->id,
        ]);
        $token = 'hpp-auth-token';

        $hpp->signatures()->create([
            'step_order' => 1,
            'role_key' => 'manager_pengendali',
            'role_label' => 'Manager Pengendali',
            'signer_user_id' => $signer->id,
            'signer_name_snapshot' => $signer->name,
            'signer_position_snapshot' => 'Manager Pengendali',
            'status' => HppSignature::STATUS_PENDING,
            'token_hash' => hash('sha256', $token),
            'token' => $token,
            'token_expires_at' => now()->addDay(),
        ]);

        return route('approval.hpp.show', $token);
    }

    private function createBastApprovalLink(Order $order, User $creator, User $signer): string
    {
        $lhpp = LhppBast::query()->create([
            'order_id' => $order->id,
            'termin_type' => 'termin_1',
            'nomor_order' => $order->nomor_order,
            'deskripsi_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'tanggal_bast' => now()->toDateString(),
            'approval_threshold' => 'under_250',
            'approval_status' => LhppBast::APPROVAL_IN_REVIEW,
            'quality_control_status' => 'approved',
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
        ]);
        $token = 'bast-auth-token';

        $lhpp->signatures()->create([
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

        return route('approval.bast.show', $token);
    }

    private function createQualityControlApprovalLink(Order $order, User $creator, User $signer): string
    {
        $report = QualityControlReport::query()->create([
            'order_id' => $order->id,
            'type' => QualityControlReport::TYPE_FABRICATION,
            'report_no' => 'QC-AUTH-001',
            'report_date' => now()->toDateString(),
            'status' => QualityControlReport::STATUS_SUBMITTED,
            'payload' => [],
            'created_by' => $creator->id,
            'updated_by' => $creator->id,
        ]);
        $token = 'qc-auth-token';

        $report->signatures()->create([
            'step_order' => 1,
            'role_key' => QualityControlSignature::ROLE_WORKSHOP_MANAGER,
            'role_label' => 'Manager Workshop',
            'signer_user_id' => $signer->id,
            'signer_name' => $signer->name,
            'status' => QualityControlSignature::STATUS_PENDING,
            'token_hash' => hash('sha256', $token),
            'token_encrypted' => $token,
            'token_expires_at' => now()->addDay(),
        ]);

        return route('approval.quality-control.show', $token);
    }
}
