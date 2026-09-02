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
use App\Support\HppSignatureIdentityResolver;
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

            foreach ($signatures as $signature) {
                $this->assertSame('', $signature->signer_name_snapshot, $case['case'].' step '.$signature->step_order);
                $this->assertSame('', $signature->signer_position_snapshot, $case['case'].' step '.$signature->step_order);
                $this->assertNull($signature->signer_department_snapshot, $case['case'].' step '.$signature->step_order);
                $this->assertNull($signature->signer_unit_snapshot, $case['case'].' step '.$signature->step_order);
                $this->assertNull($signature->signer_section_snapshot, $case['case'].' step '.$signature->step_order);
            }

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
            $this->assertTrue($hpp->signatures->every(
                fn (HppSignature $signature): bool => filled($signature->signer_name_snapshot)
                    && filled($signature->signer_position_snapshot)
            ), $case['case'].' signed identities must be snapshotted');
        }
    }

    public function test_pending_identity_is_live_and_becomes_immutable_when_signed(): void
    {
        Storage::fake('public');
        $this->setUpApprovalStructure();

        $hpp = $this->createHppForCase($this->hppCases()[0]);
        app(HppApprovalSignatureBuilder::class)->ensureSignatures($hpp);

        $pending = $hpp->refresh()->signatures->firstOrFail();
        $pending->update([
            'signer_name_snapshot' => 'Nama Snapshot Lama',
            'signer_position_snapshot' => 'Jabatan Snapshot Lama',
            'signer_department_snapshot' => 'Departemen Snapshot Lama',
            'signer_unit_snapshot' => 'Unit Snapshot Lama',
            'signer_section_snapshot' => 'Section Snapshot Lama',
        ]);
        $pending->signer->update(['name' => 'Nama Approver Terbaru']);
        $requesterSection = UnitWorkSection::query()->where('name', 'Requester Section')->firstOrFail();
        $requesterUnit = $requesterSection->unitWork;
        $requesterDepartment = $requesterUnit->department;
        $requesterDepartment->update(['name' => 'Requester Department Terbaru']);
        $requesterUnit->update(['name' => 'Requester Unit Terbaru']);
        $requesterSection->update(['name' => 'Requester Section Terbaru']);
        $hpp->order->update([
            'unit_kerja' => 'Requester Unit Terbaru',
            'seksi' => 'Requester Section Terbaru',
        ]);
        $pending = $pending->fresh(['signer', 'hpp']);
        $identityBeforeSigning = app(HppSignatureIdentityResolver::class)->resolve($pending);

        $this->assertSame('Nama Approver Terbaru', $identityBeforeSigning['name']);
        $this->assertSame('Manager of Requester Section Terbaru', $identityBeforeSigning['position']);
        $this->assertSame('Requester Department Terbaru', $identityBeforeSigning['department']);
        $this->assertSame('Requester Unit Terbaru', $identityBeforeSigning['unit']);
        $this->assertSame('Requester Section Terbaru', $identityBeforeSigning['section']);
        $this->assertSame('Nama Snapshot Lama', $pending->signer_name_snapshot);

        $this->actingAs($pending->signer)
            ->post(route('approval.hpp.sign', $pending->token), [
                'approval_action' => 'sign',
                'signature_data' => $this->signatureData(),
            ])
            ->assertRedirect(route('approval.hpp.show', $pending->token));

        $signed = $pending->fresh(['signer', 'hpp']);
        $this->assertTrue($signed->isSigned());
        $this->assertSame($identityBeforeSigning['name'], $signed->signer_name_snapshot);
        $this->assertSame($identityBeforeSigning['position'], $signed->signer_position_snapshot);
        $this->assertSame($identityBeforeSigning['department'], $signed->signer_department_snapshot);
        $this->assertSame($identityBeforeSigning['unit'], $signed->signer_unit_snapshot);
        $this->assertSame($identityBeforeSigning['section'], $signed->signer_section_snapshot);

        $signed->signer->update(['name' => 'Nama Berubah Setelah TTD']);
        $requesterDepartment->update(['name' => 'Department Setelah TTD']);
        $requesterUnit->update(['name' => 'Unit Setelah TTD']);
        $requesterSection->update(['name' => 'Section Setelah TTD']);
        $signed = $signed->fresh(['signer', 'hpp']);

        $identityAfterSigning = app(HppSignatureIdentityResolver::class)->resolve($signed);
        $this->assertSame('Nama Approver Terbaru', $identityAfterSigning['name']);
        $this->assertSame('Manager of Requester Section Terbaru', $identityAfterSigning['position']);
        $this->assertSame('Requester Department Terbaru', $identityAfterSigning['department']);
        $this->assertSame('Requester Unit Terbaru', $identityAfterSigning['unit']);
        $this->assertSame('Requester Section Terbaru', $identityAfterSigning['section']);

        $smSignature = $hpp->refresh()->signatures->first(
            fn (HppSignature $signature): bool => $signature->isPending()
        );
        $this->assertSame('sm_peminta', $smSignature?->role_key);
        $smSignature->signer->update(['name' => 'Nama SM Terbaru']);
        $smSignature = $smSignature->fresh(['signer', 'hpp']);
        $this->assertStringContainsString(
            'Nama SM Terbaru',
            view('admin.hpp.hpppdf', ['hpp' => $hpp->fresh(['signatures.signer'])])->render(),
        );

        $this->actingAs($smSignature->signer)
            ->post(route('approval.hpp.sign', $smSignature->token), [
                'approval_action' => 'sign',
                'signature_data' => $this->signatureData(),
            ])
            ->assertRedirect(route('approval.hpp.show', $smSignature->token));

        $smSignature = $smSignature->fresh(['signer', 'hpp']);
        $this->assertSame('Nama SM Terbaru', $smSignature->signer_name_snapshot);
        $smSignature->signer->update(['name' => 'Nama SM Setelah TTD']);
        $signedPdfHtml = view('admin.hpp.hpppdf', ['hpp' => $hpp->fresh(['signatures.signer'])])->render();
        $this->assertStringContainsString('Nama SM Terbaru', $signedPdfHtml);
        $this->assertStringNotContainsString('Nama SM Setelah TTD', $signedPdfHtml);
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
     * @param  array{kategori: string, area: string, bucket: string, case: string, flow: list<string>}  $case
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
     * @param  array{kategori: string, area: string, bucket: string, case: string, flow: list<string>}  $case
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
        return 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAHgAAAA8CAYAAACtrX6oAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAB1ElEQVR4nO3bwW3DMAyF4brIADlmh+w/infw0Rs4JwKB0SYSRYrk8/vPSSHpg2QHdpfjOH4Ybr/RA2C+ERg8AoNHYPAIDB6BwSMweAQGj8DgERg8AoNH4ALdH0/1A4Ob5UCYbSOwEoETZgErpTqiLSdWsfvjeVivQRpgmdgVkT1gpSXDA///Jrdv6zJ7LDNrRR1Zh3Dgb5NERJ4BK4UB9xxJKMgzYaUQYM31pjpyy5w95jgd+NNE921d0I7sKFhpKvA33JbPnT+btWhYaRpwK+7od6LLAitNAR6BqoKcDVZyB7b4jZv5yI64M+7JDdh652VDzg4ruQB7HqvRR3YVWMkceAZAFHLW6+ynTIFnLvzMI7sirGQGHLGrvJErw0omwJmvix43dNq/G9EwcJZHfRbISLCSGjh61/6V9siudmfckwo4I67Ug4wMK3UDZ8Z9z+IVmEzz0dYFXAVX0iJnnIu2ZuBquNIV3xx5b/ityuyL0jK+fVuX7PPQpn7xvdKCyFjPu7nSHLSpdnDVhZFxI+/Yc93A1Rem+vh76wK+2uIgFP7iO/Mtzf8mMZ8IDB6BwSMweAQGj8DgERg8AoNHYPAIDB6BwSMweAQGj8DgERg8AoP3Ak1UWTxLpAzmAAAAAElFTkSuQmCC';
    }
}
