<?php

namespace Tests\Feature\Admin\Hpp;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Department;
use App\Models\Hpp;
use App\Models\HppApprovalSetting;
use App\Models\Order;
use App\Models\OrderScopeOfWork;
use App\Models\OutlineAgreement;
use App\Models\UnitWork;
use App\Models\UnitWorkSection;
use App\Models\User;
use App\Support\HppApprovalFlow;
use App\Support\HppSpecialAreaPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class HppSpecialAreaTest extends TestCase
{
    use RefreshDatabase;

    public function test_special_flow_filters_only_missing_requester_roles_and_preserves_inside_order(): void
    {
        $fixture = $this->fixture();
        $policy = app(HppSpecialAreaPolicy::class);

        $fixture['requester_department']->update(['general_manager_id' => null]);
        $withoutGm = [
            'Manager Peminta',
            'SM Peminta',
            'Manager Pengendali',
            'SM Pengendali',
            'GM Pengendali',
        ];

        $this->assertSame($withoutGm, $policy->resolveApprovalFlow($fixture['order'], 'Fabrikasi', 'under', true));
        $this->assertSame([...$withoutGm, 'DIROPS'], $policy->resolveApprovalFlow($fixture['order'], 'Fabrikasi', 'over', true));
        $this->assertSame([
            'Manager Counter Part',
            'SM Counter Part',
            ...$withoutGm,
        ], $policy->resolveApprovalFlow($fixture['order'], 'Konstruksi', 'under', true));
        $this->assertSame([
            'Manager Counter Part',
            'SM Counter Part',
            ...$withoutGm,
            'DIROPS',
        ], $policy->resolveApprovalFlow($fixture['order'], 'Konstruksi', 'over', true));

        $fixture = $this->fixture('without-sm');
        $fixture['requester_unit']->update(['senior_manager_id' => null]);
        $withoutSm = [
            'Manager Peminta',
            'GM Peminta',
            'Manager Pengendali',
            'SM Pengendali',
            'GM Pengendali',
        ];
        $secondPolicy = app(HppSpecialAreaPolicy::class);

        $this->assertSame($withoutSm, $secondPolicy->resolveApprovalFlow($fixture['order'], 'Fabrikasi', 'under', true));
        $this->assertSame([...$withoutSm, 'DIROPS'], $secondPolicy->resolveApprovalFlow($fixture['order'], 'Fabrikasi', 'over', true));
        $this->assertSame([
            'Manager Counter Part',
            'SM Counter Part',
            ...$withoutSm,
        ], $secondPolicy->resolveApprovalFlow($fixture['order'], 'Konstruksi', 'under', true));
        $this->assertSame([
            'Manager Counter Part',
            'SM Counter Part',
            ...$withoutSm,
            'DIROPS',
        ], $secondPolicy->resolveApprovalFlow($fixture['order'], 'Konstruksi', 'over', true));
    }

    public function test_special_is_unavailable_when_requester_structure_is_complete(): void
    {
        $fixture = $this->fixture();
        $availability = app(HppSpecialAreaPolicy::class)->availability($fixture['order']);

        $this->assertFalse($availability['available']);
        $this->assertFalse($availability['can_submit']);
        $this->assertStringContainsString('tidak diperlukan', $availability['reason']);

        $this->actingAs($fixture['admin'])
            ->from(route('admin.hpp.create'))
            ->post(route('admin.hpp.store'), $this->payload($fixture, ['action' => 'draft']))
            ->assertRedirect(route('admin.hpp.create'))
            ->assertSessionHasErrors('area_pekerjaan');

        $this->assertDatabaseMissing('hpps', ['order_id' => $fixture['order']->id]);
    }

    public function test_special_rejects_missing_manager_and_ambiguous_requester_unit(): void
    {
        $missingManager = $this->fixture('missing-manager');
        $missingManager['requester_section']->update(['manager_id' => null]);
        $managerAvailability = app(HppSpecialAreaPolicy::class)->availability($missingManager['order']);

        $this->assertFalse($managerAvailability['available']);
        $this->assertStringContainsString('Manager Peminta belum ditetapkan', $managerAvailability['reason']);

        $ambiguous = $this->fixture('ambiguous');
        UnitWork::query()->create([
            'department_id' => $ambiguous['requester_department']->id,
            'name' => 'Unit Requester ambiguous',
        ]);
        $ambiguous['order']->update(['unit_kerja' => 'Requester ambiguous']);
        $ambiguousAvailability = app(HppSpecialAreaPolicy::class)->availability($ambiguous['order']->fresh());

        $this->assertFalse($ambiguousAvailability['available']);
        $this->assertStringContainsString('ambigu', strtolower($ambiguousAvailability['reason']));
    }

    public function test_manager_only_special_can_be_drafted_but_cannot_be_submitted(): void
    {
        $draftFixture = $this->fixture('manager-only-draft');
        $draftFixture['requester_unit']->update(['senior_manager_id' => null]);
        $draftFixture['requester_department']->update(['general_manager_id' => null]);

        $this->actingAs($draftFixture['admin'])
            ->post(route('admin.hpp.store'), $this->payload($draftFixture, ['action' => 'draft']))
            ->assertRedirect(route('admin.hpp.index'));

        $draft = Hpp::query()->where('order_id', $draftFixture['order']->id)->firstOrFail();
        $this->assertSame(Hpp::STATUS_DRAFT, $draft->status);
        $this->assertSame([
            'Manager Peminta',
            'Manager Pengendali',
            'SM Pengendali',
            'GM Pengendali',
        ], $draft->approval_flow);
        $managerOnlyPdf = view('admin.hpp.hpppdf', ['hpp' => $draft])->render();
        $this->assertStringContainsString('Manager Peminta (Paraf)', $managerOnlyPdf);
        $this->assertStringNotContainsString('colspan="0"', $managerOnlyPdf);

        $submitFixture = $this->fixture('manager-only-submit');
        $submitFixture['requester_unit']->update(['senior_manager_id' => null]);
        $submitFixture['requester_department']->update(['general_manager_id' => null]);

        $this->actingAs($submitFixture['admin'])
            ->from(route('admin.hpp.create'))
            ->post(route('admin.hpp.store'), $this->payload($submitFixture, ['action' => 'submit']))
            ->assertRedirect(route('admin.hpp.create'))
            ->assertSessionHasErrors('area_pekerjaan');

        $this->assertDatabaseMissing('hpps', ['order_id' => $submitFixture['order']->id]);
    }

    public function test_admin_submit_builds_filtered_signature_chain_without_skipped_gm(): void
    {
        Notification::fake();
        $fixture = $this->fixture('signature-chain');
        $fixture['requester_department']->update(['general_manager_id' => null]);

        $this->actingAs($fixture['admin'])
            ->post(route('admin.hpp.store'), $this->payload($fixture, ['action' => 'submit']))
            ->assertRedirect(route('admin.hpp.index'));

        $hpp = Hpp::query()->where('order_id', $fixture['order']->id)->firstOrFail();

        $this->assertSame(HppApprovalFlow::SPECIAL_AREA_LABEL, $hpp->area_pekerjaan);
        $this->assertSame('FAB-DALAM-UNDER250', $hpp->approval_case);
        $this->assertSame([
            'manager_peminta',
            'sm_peminta',
            'manager_pengendali',
            'sm_pengendali',
            'gm_pengendali',
        ], $hpp->signatures()->orderBy('step_order')->pluck('role_key')->all());
        $this->assertDatabaseMissing('hpp_signatures', [
            'hpp_id' => $hpp->id,
            'role_key' => 'gm_peminta',
        ]);
        $this->assertSame('pending', $hpp->signatures()->orderBy('step_order')->firstOrFail()->status);
        $this->assertSame(4, $hpp->signatures()->where('status', 'locked')->count());
        $submittedPdf = view('admin.hpp.hpppdf', ['hpp' => $hpp->fresh(['signatures.signer'])])->render();
        $this->assertStringNotContainsString('GM of '.$fixture['requester_department']->name, $submittedPdf);
        $this->assertStringContainsString('SM of '.$fixture['requester_unit']->name, $submittedPdf);
    }

    public function test_construction_special_keeps_counter_part_first_when_sm_requester_is_missing(): void
    {
        Notification::fake();
        $fixture = $this->fixture('construction-signatures');
        $fixture['requester_unit']->update(['senior_manager_id' => null]);

        $this->actingAs($fixture['admin'])
            ->post(route('admin.hpp.store'), $this->payload($fixture, [
                'action' => 'submit',
                'kategori_pekerjaan' => 'Konstruksi',
            ]))
            ->assertRedirect(route('admin.hpp.index'));

        $hpp = Hpp::query()->where('order_id', $fixture['order']->id)->firstOrFail();

        $this->assertSame([
            'manager_counter_part',
            'sm_counter_part',
            'manager_peminta',
            'gm_peminta',
            'manager_pengendali',
            'sm_pengendali',
            'gm_pengendali',
        ], $hpp->signatures()->orderBy('step_order')->pluck('role_key')->all());
        $submittedPdf = view('admin.hpp.hpppdf', ['hpp' => $hpp->fresh(['signatures.signer'])])->render();
        $this->assertStringContainsString('SM of Counter Part', $submittedPdf);
        $this->assertStringContainsString('Manager Counter Part', $submittedPdf);
    }

    public function test_special_request_cannot_restore_skipped_role_or_remove_counter_part(): void
    {
        $restoredRole = $this->fixture('tampered-requester');
        $restoredRole['requester_department']->update(['general_manager_id' => null]);

        $this->actingAs($restoredRole['admin'])
            ->from(route('admin.hpp.create'))
            ->post(route('admin.hpp.store'), $this->payload($restoredRole, [
                'approval_flow' => HppApprovalFlow::resolveApprovalFlow('Fabrikasi', 'Dalam', 'under'),
            ]))
            ->assertSessionHasErrors('approval_flow');

        $missingCounterPart = $this->fixture('tampered-counter-part');
        $missingCounterPart['requester_department']->update(['general_manager_id' => null]);

        $this->actingAs($missingCounterPart['admin'])
            ->from(route('admin.hpp.create'))
            ->post(route('admin.hpp.store'), $this->payload($missingCounterPart, [
                'kategori_pekerjaan' => 'Konstruksi',
                'approval_flow' => [
                    'Manager Peminta',
                    'SM Peminta',
                    'Manager Pengendali',
                    'SM Pengendali',
                    'GM Pengendali',
                ],
            ]))
            ->assertSessionHasErrors('approval_flow');

        $this->assertDatabaseMissing('hpps', ['order_id' => $restoredRole['order']->id]);
        $this->assertDatabaseMissing('hpps', ['order_id' => $missingCounterPart['order']->id]);
    }

    public function test_pkm_can_save_special_draft_with_same_filtered_flow(): void
    {
        $fixture = $this->fixture('pkm-draft');
        $fixture['requester_unit']->update(['senior_manager_id' => null]);

        $this->actingAs($fixture['pkm'])
            ->post(route('pkm.hpp.store'), $this->payload($fixture, ['action' => 'submit']))
            ->assertRedirect(route('pkm.hpp.index'));

        $hpp = Hpp::query()->where('order_id', $fixture['order']->id)->firstOrFail();

        $this->assertSame(Hpp::STATUS_DRAFT, $hpp->status);
        $this->assertSame('FAB-DALAM-UNDER250', $hpp->approval_case);
        $this->assertSame([
            'Manager Peminta',
            'GM Peminta',
            'Manager Pengendali',
            'SM Pengendali',
            'GM Pengendali',
        ], $hpp->approval_flow);
        $this->assertSame(0, $hpp->signatures()->count());
    }

    public function test_special_pdf_omits_skipped_requester_columns_for_both_categories(): void
    {
        foreach (['Fabrikasi', 'Konstruksi'] as $category) {
            foreach (['gm', 'sm'] as $missingRole) {
                $suffix = strtolower($category).'-pdf-without-'.$missingRole;
                $fixture = $this->fixture($suffix);

                if ($missingRole === 'gm') {
                    $fixture['requester_department']->update(['general_manager_id' => null]);
                } else {
                    $fixture['requester_unit']->update(['senior_manager_id' => null]);
                }

                $flow = app(HppSpecialAreaPolicy::class)
                    ->resolveApprovalFlow($fixture['order'], $category, 'under', false);
                $hpp = $this->makeDraft($fixture, $flow, $category);
                $html = view('admin.hpp.hpppdf', ['hpp' => $hpp])->render();

                $this->assertSame($missingRole !== 'sm', str_contains($html, 'SM of '.$fixture['requester_unit']->name));
                $this->assertSame($missingRole !== 'gm', str_contains($html, 'GM of '.$fixture['requester_department']->name));
                $this->assertStringNotContainsString('colspan="0"', $html);

                if ($category === 'Konstruksi') {
                    $this->assertStringContainsString('SM of Counter Part', $html);
                    $this->assertStringContainsString('Manager Counter Part', $html);
                }
            }
        }
    }

    /** @return array<string, mixed> */
    private function fixture(string $suffix = 'default'): array
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $requesterManager = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $requesterSm = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $requesterGm = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $controllerManager = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $controllerSm = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $controllerGm = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $dirops = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $counterManager = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $counterSm = User::factory()->create(['role' => User::ROLE_APPROVER]);

        $requesterDepartment = Department::query()->create([
            'name' => 'Requester Department '.$suffix,
            'general_manager_id' => $requesterGm->id,
        ]);
        $requesterUnit = UnitWork::query()->create([
            'department_id' => $requesterDepartment->id,
            'name' => 'Requester Unit '.$suffix,
            'senior_manager_id' => $requesterSm->id,
        ]);
        $requesterSection = UnitWorkSection::query()->create([
            'unit_work_id' => $requesterUnit->id,
            'name' => 'Requester Section '.$suffix,
            'manager_id' => $requesterManager->id,
        ]);

        $controllerDepartment = Department::query()->create([
            'name' => 'Controller Department '.$suffix,
            'general_manager_id' => $controllerGm->id,
        ]);
        $controllerUnit = UnitWork::query()->create([
            'department_id' => $controllerDepartment->id,
            'name' => 'Controller Unit '.$suffix,
            'senior_manager_id' => $controllerSm->id,
        ]);
        UnitWorkSection::query()->create([
            'unit_work_id' => $controllerUnit->id,
            'name' => 'Controller Section '.$suffix,
            'manager_id' => $controllerManager->id,
        ]);

        $counterDepartment = Department::query()->create(['name' => 'Counter Department '.$suffix]);
        $counterUnit = UnitWork::query()->create([
            'department_id' => $counterDepartment->id,
            'name' => 'Counter Unit '.$suffix,
            'senior_manager_id' => $counterSm->id,
        ]);
        $counterSection = UnitWorkSection::query()->create([
            'unit_work_id' => $counterUnit->id,
            'name' => 'Counter Section '.$suffix,
            'manager_id' => $counterManager->id,
        ]);

        HppApprovalSetting::query()->create([
            'counter_part_unit_work_id' => $counterUnit->id,
            'counter_part_section_id' => $counterSection->id,
            'dirops_user_id' => $dirops->id,
        ]);

        $oa = OutlineAgreement::query()->create([
            'nomor_oa' => 'OA-SPECIAL-'.$suffix,
            'unit_work_id' => $controllerUnit->id,
            'jenis_kontrak' => 'Controller Section '.$suffix,
            'nama_kontrak' => 'Kontrak Special '.$suffix,
            'nilai_kontrak_awal' => 1000000000,
            'periode_awal_start' => '2026-01-01',
            'periode_awal_end' => '2026-12-31',
            'current_total_nilai' => 1000000000,
            'current_period_start' => '2026-01-01',
            'current_period_end' => '2026-12-31',
            'status' => OutlineAgreement::STATUS_ACTIVE,
            'created_by' => $admin->id,
        ]);
        $order = Order::query()->create([
            'nomor_order' => 'ORD-SPECIAL-'.$suffix,
            'nama_pekerjaan' => 'Pekerjaan Special '.$suffix,
            'unit_kerja' => $requesterUnit->name,
            'seksi' => $requesterSection->name,
            'deskripsi' => 'Pengujian HPP Khusus',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'catatan_status' => OrderUserNoteStatus::ApprovedJasa->value,
            'tanggal_order' => '2026-10-26',
            'target_selesai' => '2026-11-01',
            'created_by' => $admin->id,
        ]);
        OrderScopeOfWork::query()->create([
            'order_id' => $order->id,
            'nama_penginput' => $admin->name,
            'tanggal_dokumen' => '2026-10-26',
            'scope_items' => [['scope_pekerjaan' => 'Test', 'qty' => '1', 'satuan' => 'lot']],
            'tanda_tangan' => 'data:image/png;base64,test',
            'created_by' => $admin->id,
        ]);

        return compact(
            'admin',
            'pkm',
            'order',
            'oa',
            'requesterDepartment',
            'requesterUnit',
            'requesterSection',
        ) + [
            'requester_department' => $requesterDepartment,
            'requester_unit' => $requesterUnit,
            'requester_section' => $requesterSection,
        ];
    }

    /** @param array<string, mixed> $fixture @return array<string, mixed> */
    private function payload(array $fixture, array $overrides = []): array
    {
        return array_replace([
            'action' => 'draft',
            'order_id' => $fixture['order']->id,
            'outline_agreement_id' => $fixture['oa']->id,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => HppApprovalFlow::SPECIAL_AREA_LABEL,
            'jenis_label_visible' => [0 => 'Jasa'],
            'nama_item' => [0 => ['Pekerjaan khusus']],
            'jumlah_item' => [0 => ['1 lot']],
            'qty' => [0 => [1]],
            'satuan' => [0 => ['Lot']],
            'harga_satuan' => [0 => [1000000]],
            'keterangan' => [0 => ['Test']],
        ], $overrides);
    }

    /** @param array<string, mixed> $fixture @param list<string> $flow */
    private function makeDraft(array $fixture, array $flow, string $category = 'Fabrikasi'): Hpp
    {
        return Hpp::query()->create([
            'order_id' => $fixture['order']->id,
            'outline_agreement_id' => $fixture['oa']->id,
            'nomor_order' => $fixture['order']->nomor_order,
            'nama_pekerjaan' => $fixture['order']->nama_pekerjaan,
            'unit_kerja' => $fixture['order']->unit_kerja,
            'departemen_peminta' => $fixture['requester_department']->name,
            'unit_work_id' => $fixture['oa']->unit_work_id,
            'kategori_pekerjaan' => $category,
            'area_pekerjaan' => HppApprovalFlow::SPECIAL_AREA_LABEL,
            'nilai_hpp_bucket' => 'under',
            'unit_kerja_pengendali' => $fixture['oa']->unitWork->name,
            'seksi_pengendali' => $fixture['oa']->jenis_kontrak,
            'approval_case' => HppApprovalFlow::resolvePreviewCase($category, HppApprovalFlow::SPECIAL_AREA_LABEL, 'under'),
            'approval_flow' => $flow,
            'item_groups' => [],
            'total_keseluruhan' => 1000000,
            'status' => Hpp::STATUS_DRAFT,
            'created_by' => $fixture['admin']->id,
        ]);
    }
}
