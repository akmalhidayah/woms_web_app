<?php

namespace Tests\Feature\Pkm;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Department;
use App\Models\Garansi;
use App\Models\Hpp;
use App\Models\HppApprovalSetting;
use App\Models\LhppBast;
use App\Models\Order;
use App\Models\OutlineAgreement;
use App\Models\PurchaseOrder;
use App\Models\UnitWork;
use App\Models\UnitWorkSection;
use App\Models\User;
use App\Models\VendorWorkType;
use App\Support\HppApprovalFlow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BastDocumentNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_bast_termin_one_receives_yearly_sequence_document_number_on_submit(): void
    {
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $outlineAgreement = $this->createApprovalStructureAndOutlineAgreement();

        $firstOrder = $this->createEligibleOrder('ORD-BAST-DOC-001', $outlineAgreement);
        $secondOrder = $this->createEligibleOrder('ORD-BAST-DOC-002', $outlineAgreement);

        $this->actingAs($pkm)
            ->post(route('pkm.lhpp.store'), $this->bastPayload($firstOrder, [
                'tanggal_bast' => '2026-07-05',
            ]))
            ->assertRedirect(route('pkm.lhpp.index'));

        $this->actingAs($pkm)
            ->post(route('pkm.lhpp.store'), $this->bastPayload($secondOrder, [
                'tanggal_bast' => '2026-08-05',
            ]))
            ->assertRedirect(route('pkm.lhpp.index'));

        $firstBast = LhppBast::query()->where('order_id', $firstOrder->id)->where('termin_type', 'termin_1')->firstOrFail();
        $secondBast = LhppBast::query()->where('order_id', $secondOrder->id)->where('termin_type', 'termin_1')->firstOrFail();

        $this->assertSame('001/BAST/25.10/07-2026', $firstBast->document_no);
        $this->assertSame(1, $firstBast->document_sequence);
        $this->assertSame(2026, $firstBast->document_year);

        $this->assertSame('002/BAST/25.10/08-2026', $secondBast->document_no);
        $this->assertSame(2, $secondBast->document_sequence);
        $this->assertSame(2026, $secondBast->document_year);
    }

    public function test_bast_termin_two_copies_document_number_from_termin_one_without_incrementing_sequence(): void
    {
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $outlineAgreement = $this->createApprovalStructureAndOutlineAgreement();
        $this->createVendorApprovalStructure();

        $firstOrder = $this->createEligibleOrder('ORD-BAST-T2-001', $outlineAgreement);
        $secondOrder = $this->createEligibleOrder('ORD-BAST-T2-002', $outlineAgreement);

        $this->actingAs($pkm)
            ->post(route('pkm.lhpp.store'), $this->bastPayload($firstOrder, [
                'tanggal_bast' => '2026-07-05',
            ]))
            ->assertRedirect(route('pkm.lhpp.index'));

        $terminOne = LhppBast::query()
            ->where('order_id', $firstOrder->id)
            ->where('termin_type', 'termin_1')
            ->firstOrFail();

        $terminOne->forceFill([
            'termin1_status' => 'sudah',
        ])->save();

        Garansi::query()
            ->where('order_id', $firstOrder->id)
            ->update(['lhpp_bast_id' => $terminOne->id]);

        $this->actingAs($pkm)
            ->post(route('pkm.lhpp.store'), $this->bastPayload($firstOrder, [
                'termin_type' => 'termin_2',
                'tanggal_bast' => '2026-07-20',
            ]))
            ->assertRedirect(route('pkm.lhpp.index'));

        $this->actingAs($pkm)
            ->post(route('pkm.lhpp.store'), $this->bastPayload($secondOrder, [
                'tanggal_bast' => '2026-08-05',
            ]))
            ->assertRedirect(route('pkm.lhpp.index'));

        $terminTwo = LhppBast::query()
            ->where('order_id', $firstOrder->id)
            ->where('termin_type', 'termin_2')
            ->firstOrFail();
        $nextTerminOne = LhppBast::query()
            ->where('order_id', $secondOrder->id)
            ->where('termin_type', 'termin_1')
            ->firstOrFail();

        $this->assertSame($terminOne->document_no, $terminTwo->document_no);
        $this->assertSame($terminOne->document_sequence, $terminTwo->document_sequence);
        $this->assertSame($terminOne->document_year, $terminTwo->document_year);
        $this->assertSame('002/BAST/25.10/08-2026', $nextTerminOne->document_no);
    }

    private function createEligibleOrder(string $nomorOrder, OutlineAgreement $outlineAgreement): Order
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $order = Order::query()->create([
            'nomor_order' => $nomorOrder,
            'notifikasi' => 'NOTIF-'.$nomorOrder,
            'nama_pekerjaan' => 'Pekerjaan BAST '.$nomorOrder,
            'unit_kerja' => 'Unit Produksi Raw Mill',
            'seksi' => 'Maintenance',
            'deskripsi' => 'Pekerjaan selesai untuk BAST.',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'catatan_status' => OrderUserNoteStatus::ApprovedJasa->value,
            'tanggal_order' => '2026-07-01',
            'target_selesai' => '2026-07-10',
            'created_by' => $admin->id,
        ]);

        $hpp = Hpp::query()->create([
            'order_id' => $order->id,
            'outline_agreement_id' => $outlineAgreement->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'departemen_peminta' => 'Requester Department',
            'unit_work_id' => $outlineAgreement->unit_work_id,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => HppApprovalFlow::displayArea('Dalam'),
            'nilai_hpp_bucket' => 'under',
            'unit_kerja_pengendali' => 'Unit Workshop',
            'seksi_pengendali' => 'Controller Section',
            'departemen_pengendali' => 'Controller Department',
            'outline_agreement' => $outlineAgreement->nomor_oa,
            'periode_outline_agreement' => '01/01/2026 - 31/12/2026',
            'approval_case' => 'FAB-DALAM-UNDER250',
            'approval_flow' => HppApprovalFlow::resolveApprovalFlow('Fabrikasi', 'dalam', 'under'),
            'item_groups' => [[
                'jenis_item' => 'Jasa',
                'subtotal' => '1000000.00',
                'items' => [[
                    'nama_item' => 'Jasa test',
                    'qty' => '1',
                    'satuan' => 'Lot',
                    'harga_satuan' => '1000000.00',
                    'harga_total' => '1000000.00',
                ]],
            ]],
            'total_keseluruhan' => 1000000,
            'status' => Hpp::STATUS_APPROVED,
            'submitted_at' => '2026-07-02 08:00:00',
            'created_by' => $admin->id,
        ]);

        PurchaseOrder::query()->create([
            'order_id' => $order->id,
            'hpp_id' => $hpp->id,
            'purchase_order_number' => 'PO-'.$nomorOrder,
            'approve_manager' => true,
            'progress_pekerjaan' => 100,
            'tanggal_mulai_pekerjaan' => '2026-07-03',
            'tanggal_selesai_pekerjaan' => '2026-07-04',
            'created_by' => $admin->id,
        ]);

        $garansiSeed = LhppBast::query()->create([
            'order_id' => $order->id,
            'termin_type' => 'garansi_seed',
            'hpp_id' => $hpp->id,
            'nomor_order' => $order->nomor_order,
            'notifikasi' => $order->notifikasi,
            'deskripsi_pekerjaan' => $order->nama_pekerjaan,
            'tipe_pekerjaan' => 'pekerjaan_fabrikasi',
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'tanggal_bast' => '2026-07-04',
            'nilai_hpp' => $hpp->total_keseluruhan,
            'approval_status' => LhppBast::APPROVAL_IN_REVIEW,
            'created_by' => $admin->id,
        ]);

        Garansi::query()->create([
            'order_id' => $order->id,
            'lhpp_bast_id' => $garansiSeed->id,
            'garansi_months' => 3,
            'start_date' => '2026-07-05',
            'created_by' => $admin->id,
        ]);

        return $order;
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function bastPayload(Order $order, array $overrides = []): array
    {
        return array_replace_recursive([
            'termin_type' => 'termin_1',
            'tanggal_bast' => '2026-07-05',
            'nomor_order' => $order->nomor_order,
            'approval_threshold' => 'under_250',
            'tipe_pekerjaan' => 'pekerjaan_fabrikasi',
            'tanggal_mulai_pekerjaan' => '2026-07-03',
            'tanggal_selesai_pekerjaan' => '2026-07-04',
            'material_rows' => [[
                'jenis_item' => 'Jasa',
                'kategori_item' => '',
                'name' => 'Pekerjaan test',
                'volume' => '1',
                'unit' => 'Lot',
                'unit_price' => '1000000',
            ]],
            'service_rows' => [],
        ], $overrides);
    }

    private function createVendorApprovalStructure(): void
    {
        $manager = User::factory()->create(['role' => User::ROLE_APPROVER]);

        VendorWorkType::query()->create([
            'name' => 'pekerjaan_fabrikasi',
            'manager_id' => $manager->id,
        ]);
    }

    private function createApprovalStructureAndOutlineAgreement(): OutlineAgreement
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $requesterManager = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $requesterSm = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $requesterGm = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $controllerManager = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $controllerSm = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $controllerGm = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $dirops = User::factory()->create(['role' => User::ROLE_APPROVER]);

        $requesterDepartment = Department::query()->create([
            'name' => 'Requester Department',
            'general_manager_id' => $requesterGm->id,
        ]);

        $requesterUnit = UnitWork::query()->create([
            'department_id' => $requesterDepartment->id,
            'name' => 'Unit Produksi Raw Mill',
            'senior_manager_id' => $requesterSm->id,
        ]);

        UnitWorkSection::query()->create([
            'unit_work_id' => $requesterUnit->id,
            'name' => 'Maintenance',
            'manager_id' => $requesterManager->id,
        ]);

        $controllerDepartment = Department::query()->create([
            'name' => 'Controller Department',
            'general_manager_id' => $controllerGm->id,
        ]);

        $controllerUnit = UnitWork::query()->create([
            'department_id' => $controllerDepartment->id,
            'name' => 'Unit Workshop',
            'senior_manager_id' => $controllerSm->id,
        ]);

        UnitWorkSection::query()->create([
            'unit_work_id' => $controllerUnit->id,
            'name' => 'Controller Section',
            'manager_id' => $controllerManager->id,
        ]);

        HppApprovalSetting::query()->create([
            'dirops_user_id' => $dirops->id,
        ]);

        return OutlineAgreement::query()->create([
            'nomor_oa' => 'OA/BAST/2026/001',
            'unit_work_id' => $controllerUnit->id,
            'jenis_kontrak' => 'Controller Section',
            'nama_kontrak' => 'Kontrak Test BAST',
            'nilai_kontrak_awal' => 1000000000,
            'periode_awal_start' => '2026-01-01',
            'periode_awal_end' => '2026-12-31',
            'current_total_nilai' => 1000000000,
            'current_period_start' => '2026-01-01',
            'current_period_end' => '2026-12-31',
            'status' => OutlineAgreement::STATUS_ACTIVE,
            'created_by' => $admin->id,
        ]);
    }
}
