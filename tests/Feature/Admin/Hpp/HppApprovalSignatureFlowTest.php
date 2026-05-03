<?php

namespace Tests\Feature\Admin\Hpp;

use App\Models\Department;
use App\Models\Hpp;
use App\Models\HppApprovalSetting;
use App\Models\HppSignature;
use App\Models\Order;
use App\Models\OutlineAgreement;
use App\Models\UnitWork;
use App\Models\UnitWorkSection;
use App\Models\User;
use App\Support\HppApprovalFlow;
use App\Support\HppApprovalSignatureBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HppApprovalSignatureFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private UnitWork $controllerUnit;
    private OutlineAgreement $outlineAgreement;

    public function test_all_hpp_approval_cases_create_expected_signature_chain(): void
    {
        $this->setUpApprovalStructure();

        foreach ($this->hppCases() as $case) {
            $hpp = $this->createHppForCase($case);

            app(HppApprovalSignatureBuilder::class)->ensureSignatures($hpp);

            $signatures = $hpp->refresh()->signatures;

            $this->assertSame($case['flow'], $hpp->approval_flow, $case['case']);
            $this->assertSame($this->expectedRoleKeys($case), $signatures->pluck('role_key')->all(), $case['case']);
            $this->assertSame(HppSignature::STATUS_PENDING, $signatures->first()->status, $case['case']);
            $this->assertNotNull($signatures->first()->token_hash, $case['case']);
            $this->assertNotNull($signatures->first()->approvalUrl(), $case['case']);

            foreach ($signatures->skip(1) as $signature) {
                $this->assertSame(HppSignature::STATUS_LOCKED, $signature->status, $case['case'].' step '.$signature->step_order);
                $this->assertNull($signature->token_hash, $case['case'].' step '.$signature->step_order);
            }
        }
    }

    public function test_all_hpp_approval_cases_can_be_signed_until_complete(): void
    {
        Storage::fake('public');
        $this->setUpApprovalStructure();

        foreach ($this->hppCases() as $case) {
            $hpp = $this->createHppForCase($case);
            app(HppApprovalSignatureBuilder::class)->ensureSignatures($hpp);

            for ($guard = 0; $guard < 12; $guard++) {
                $pending = $hpp->refresh()->signatures->first(
                    fn (HppSignature $signature): bool => $signature->isPending()
                );

                if (! $pending) {
                    break;
                }

                if ($pending->role_key === 'dirops') {
                    $this->actingAs($pending->signer)
                        ->post(route('approval.hpp.sign', $pending->token), [
                            'approval_action' => 'sign',
                            'signature_data' => $this->signatureData(),
                        ])
                        ->assertRedirect(route('approval.hpp.show', $pending->token));

                    $this->assertTrue($pending->refresh()->isPending(), $case['case'].' DIROPS must wait for final upload');

                    $this->actingAs($this->admin)
                        ->post(route('admin.hpp.dirops-document.upload', ['hpp' => $hpp->nomor_order]), [
                            'signed_document' => UploadedFile::fake()->create('dirops-'.$hpp->id.'.pdf', 24, 'application/pdf'),
                        ])
                        ->assertRedirect(route('admin.hpp.index'));

                    continue;
                }

                $this->actingAs($pending->signer)
                    ->post(route('approval.hpp.sign', $pending->token), [
                        'approval_action' => 'sign',
                        'signature_data' => $this->signatureData(),
                    ])
                    ->assertRedirect(route('approval.hpp.show', $pending->token));
            }

            $hpp->refresh()->load('signatures');

            $this->assertSame(Hpp::STATUS_APPROVED, $hpp->status, $case['case']);
            $this->assertTrue($hpp->signatures->every(fn (HppSignature $signature): bool => $signature->isSigned()), $case['case']);
        }
    }

    /**
     * @return list<array{kategori: string, area: string, bucket: string, case: string, flow: list<string>}>
     */
    private function hppCases(): array
    {
        $cases = [];

        foreach (HppApprovalFlow::flowMatrix() as $kategori => $areas) {
            foreach ($areas as $area => $buckets) {
                foreach ($buckets as $bucket => $flow) {
                    $cases[] = [
                        'kategori' => $kategori,
                        'area' => $area,
                        'bucket' => $bucket,
                        'case' => HppApprovalFlow::resolvePreviewCase($kategori, $area, $bucket),
                        'flow' => $flow,
                    ];
                }
            }
        }

        return $cases;
    }

    /**
     * @param array{kategori: string, area: string, bucket: string, case: string, flow: list<string>} $case
     * @return list<string>
     */
    private function expectedRoleKeys(array $case): array
    {
        $workshop = str_starts_with($case['case'], 'FAB-WORKSHOP');

        return array_map(static function (string $role) use ($workshop): string {
            if ($workshop) {
                return match ($role) {
                    'Manager' => 'workshop_manager_pengendali',
                    'SM' => 'workshop_sm_pengendali',
                    'GM' => 'workshop_gm_pengendali',
                    'DIROPS' => 'dirops',
                    default => $role,
                };
            }

            return match ($role) {
                'Planner Control' => 'planner_control',
                'Manager Counter Part' => 'manager_counter_part',
                'SM Counter Part' => 'sm_counter_part',
                'Manager Pengendali' => 'manager_pengendali',
                'SM Pengendali' => 'sm_pengendali',
                'Manager Peminta' => 'manager_peminta',
                'SM Peminta' => 'sm_peminta',
                'GM Peminta' => 'gm_peminta',
                'GM Pengendali' => 'gm_pengendali',
                'DIROPS' => 'dirops',
                default => $role,
            };
        }, $case['flow']);
    }

    private function setUpApprovalStructure(): void
    {
        $this->admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);

        $requesterManager = $this->approver('Requester Manager');
        $requesterSm = $this->approver('Requester SM');
        $requesterGm = $this->approver('Requester GM');
        $controllerManager = $this->approver('Controller Manager');
        $controllerSm = $this->approver('Controller SM');
        $controllerGm = $this->approver('Controller GM');
        $counterManager = $this->approver('Counter Manager');
        $counterSm = $this->approver('Counter SM');
        $counterGm = $this->approver('Counter GM');
        $planner = $this->approver('Planner Control');
        $dirops = $this->approver('DIROPS');

        $requesterDepartment = Department::query()->create([
            'name' => 'Requester Department',
            'general_manager_id' => $requesterGm->id,
        ]);

        $requesterUnit = UnitWork::query()->create([
            'department_id' => $requesterDepartment->id,
            'name' => 'Requester Unit',
            'senior_manager_id' => $requesterSm->id,
        ]);

        UnitWorkSection::query()->create([
            'unit_work_id' => $requesterUnit->id,
            'name' => 'Requester Section',
            'manager_id' => $requesterManager->id,
        ]);

        $controllerDepartment = Department::query()->create([
            'name' => 'Controller Department',
            'general_manager_id' => $controllerGm->id,
        ]);

        $this->controllerUnit = UnitWork::query()->create([
            'department_id' => $controllerDepartment->id,
            'name' => 'Controller Unit',
            'senior_manager_id' => $controllerSm->id,
        ]);

        UnitWorkSection::query()->create([
            'unit_work_id' => $this->controllerUnit->id,
            'name' => 'Controller Section',
            'manager_id' => $controllerManager->id,
        ]);

        $counterDepartment = Department::query()->create([
            'name' => 'Counter Department',
            'general_manager_id' => $counterGm->id,
        ]);

        $counterUnit = UnitWork::query()->create([
            'department_id' => $counterDepartment->id,
            'name' => 'Counter Unit',
            'senior_manager_id' => $counterSm->id,
        ]);

        $counterSection = UnitWorkSection::query()->create([
            'unit_work_id' => $counterUnit->id,
            'name' => 'Counter Section',
            'manager_id' => $counterManager->id,
        ]);

        HppApprovalSetting::query()->create([
            'planner_control_user_id' => $planner->id,
            'counter_part_unit_work_id' => $counterUnit->id,
            'counter_part_section_id' => $counterSection->id,
            'dirops_user_id' => $dirops->id,
        ]);

        $this->outlineAgreement = OutlineAgreement::query()->create([
            'nomor_oa' => 'OA-HPP-TEST',
            'unit_work_id' => $this->controllerUnit->id,
            'jenis_kontrak' => 'Controller Section',
            'nama_kontrak' => 'Kontrak Test HPP',
            'nilai_kontrak_awal' => 1000000000,
            'periode_awal_start' => '2026-01-01',
            'periode_awal_end' => '2026-12-31',
            'current_total_nilai' => 1000000000,
            'current_period_start' => '2026-01-01',
            'current_period_end' => '2026-12-31',
            'status' => OutlineAgreement::STATUS_ACTIVE,
            'created_by' => $this->admin->id,
        ]);
    }

    private function approver(string $name): User
    {
        return User::factory()->create([
            'name' => $name,
            'role' => User::ROLE_APPROVER,
        ]);
    }

    /**
     * @param array{kategori: string, area: string, bucket: string, case: string, flow: list<string>} $case
     */
    private function createHppForCase(array $case): Hpp
    {
        $order = Order::query()->create([
            'nomor_order' => 'ORD-'.$case['case'],
            'nama_pekerjaan' => 'Pekerjaan '.$case['case'],
            'unit_kerja' => 'Requester Unit',
            'seksi' => 'Requester Section',
            'deskripsi' => 'Test flow HPP '.$case['case'],
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-05-01',
            'target_selesai' => '2026-05-10',
            'created_by' => $this->admin->id,
        ]);

        return Hpp::query()->create([
            'order_id' => $order->id,
            'outline_agreement_id' => $this->outlineAgreement->id,
            'unit_work_id' => $this->controllerUnit->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'cost_centre' => 'CC-TEST',
            'kategori_pekerjaan' => $case['kategori'],
            'area_pekerjaan' => HppApprovalFlow::displayArea($case['area']),
            'nilai_hpp_bucket' => $case['bucket'],
            'unit_kerja_pengendali' => $this->controllerUnit->name,
            'outline_agreement' => $this->outlineAgreement->nomor_oa,
            'periode_outline_agreement' => '01/01/2026 - 31/12/2026',
            'approval_case' => $case['case'],
            'approval_flow' => $case['flow'],
            'item_groups' => [],
            'total_keseluruhan' => $case['bucket'] === 'over' ? 300000000 : 200000000,
            'status' => Hpp::STATUS_IN_REVIEW,
            'submitted_at' => now(),
            'created_by' => $this->admin->id,
        ]);
    }

    private function signatureData(): string
    {
        return 'data:image/png;base64,'.base64_encode(str_repeat('signature-bytes', 20));
    }
}
