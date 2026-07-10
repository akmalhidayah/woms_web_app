<?php

namespace Tests\Feature\Admin\Hpp;

use App\Domain\Orders\Enums\OrderDocumentType;
use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Department;
use App\Models\Hpp;
use App\Models\HppApprovalSetting;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\OrderScopeOfWork;
use App\Models\OutlineAgreement;
use App\Models\UnitWork;
use App\Models\UnitWorkSection;
use App\Models\User;
use App\Support\HppApprovalFlow;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateHppTest extends TestCase
{
    use RefreshDatabase;

    public function test_hpp_index_uses_compact_order_and_detail_layout(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);

        $order = Order::query()->create([
            'nomor_order' => 'ORD-HPP-INDEX-001',
            'notifikasi' => 'NOTIF-HPP-001',
            'nama_pekerjaan' => 'Pekerjaan HPP Ringkas',
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Detail pekerjaan test',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-06-01',
            'target_selesai' => '2026-06-10',
            'created_by' => $admin->id,
        ]);

        Hpp::query()->create([
            'order_id' => $order->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Dalam',
            'nilai_hpp_bucket' => 'under',
            'item_groups' => [],
            'total_keseluruhan' => 25000000,
            'status' => Hpp::STATUS_DRAFT,
            'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.hpp.index'))
            ->assertOk()
            ->assertSee('ORD-HPP-INDEX-001')
            ->assertSee('Notif: NOTIF-HPP-001')
            ->assertSee('Nilai HPP / Status')
            ->assertDontSee('>Case<', false)
            ->assertDontSee('Dibuat:');
    }

    public function test_create_hpp_page_includes_order_seksi_in_livewire_payload(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);

        $this->createApprovalStructureAndOutlineAgreement($admin);

        $this->makeEligibleOrder($admin, [
            'nomor_order' => 'ORD-2026-0099',
            'nama_pekerjaan' => 'Potong plat',
            'unit_kerja' => 'Unit of Elins Maintenance 2',
            'seksi' => 'Section Line RKC Electrical Maintenance',
            'deskripsi' => 'Cutting plate for maintenance work.',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-04-04',
            'target_selesai' => '2026-04-10',
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('admin.hpp.create'));

        $response
            ->assertOk()
            ->assertSee('Unit of Elins Maintenance 2')
            ->assertSee('Section Line RKC Electrical Maintenance');
    }

    public function test_it_stores_hpp_from_selected_order_and_snapshots_order_fields(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);

        $outlineAgreement = $this->createApprovalStructureAndOutlineAgreement($admin);

        $order = $this->makeEligibleOrder($admin, [
            'nomor_order' => 'ORD-2026-0001',
            'nama_pekerjaan' => 'Perbaikan conveyor raw mill',
            'unit_kerja' => 'Unit Produksi Raw Mill',
            'seksi' => 'Maintenance',
            'deskripsi' => 'Perbaikan roller dan housing conveyor.',
            'prioritas' => Order::PRIORITY_HIGH,
            'tanggal_order' => '2026-04-04',
            'target_selesai' => '2026-04-10',
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('admin.hpp.store'), [
                'action' => 'submit',
                'order_id' => $order->id,
                'outline_agreement_id' => $outlineAgreement->id,
                'kategori_pekerjaan' => 'Fabrikasi',
                'area_pekerjaan' => HppApprovalFlow::displayArea('Dalam'),
                'nilai_hpp_bucket' => 'under',
                'cost_centre' => 'CC-RM-001',
                'unit_kerja_pengendali' => 'Unit Workshop',
                'outline_agreement' => 'OA/2026/001',
                'periode_outline_agreement' => '01/01/2026 - 31/12/2026',
                'jenis_label_visible' => [
                    0 => 'Material Utama',
                ],
                'nama_item' => [
                    0 => ['Plat baja'],
                ],
                'jumlah_item' => [
                    0 => ['2 lembar'],
                ],
                'qty' => [
                    0 => [2],
                ],
                'satuan' => [
                    0 => ['Lembar'],
                ],
                'harga_satuan' => [
                    0 => [1500000],
                ],
                'keterangan' => [
                    0 => ['Untuk repair conveyor'],
                ],
            ]);

        $response
            ->assertRedirect(route('admin.hpp.index'))
            ->assertSessionHas('status');

        $this->assertDatabaseCount('hpps', 1);

        $hpp = Hpp::query()->firstOrFail();

        $this->assertSame($order->id, $hpp->order_id);
        $this->assertSame('ORD-2026-0001', $hpp->nomor_order);
        $this->assertSame('Perbaikan conveyor raw mill', $hpp->nama_pekerjaan);
        $this->assertSame('Unit Produksi Raw Mill', $hpp->unit_kerja);
        $this->assertSame('Requester Department', $hpp->departemen_peminta);
        $this->assertSame('Unit Workshop', $hpp->unit_kerja_pengendali);
        $this->assertSame('Controller Section', $hpp->seksi_pengendali);
        $this->assertSame('Controller Department', $hpp->departemen_pengendali);
        $this->assertSame('OA/2026/001', $hpp->outline_agreement);
        $this->assertSame('01/01/2026 - 31/12/2026', $hpp->periode_outline_agreement);
        $this->assertSame('Dalam (T.23,4,5, Pelabuhan BKS & Packing Plant)', $hpp->area_pekerjaan);
        $this->assertSame(Hpp::STATUS_IN_REVIEW, $hpp->status);
        $this->assertSame(3000000.0, (float) $hpp->total_keseluruhan);
        $this->assertSame('FAB-DALAM-UNDER250', $hpp->approval_case);
        $this->assertSame('Material Utama', $hpp->item_groups[0]['jenis_item']);
        $this->assertSame('Plat baja', $hpp->item_groups[0]['items'][0]['nama_item']);
    }

    public function test_it_stores_hpp_with_scope_of_work_without_order_documents(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);

        $outlineAgreement = $this->createApprovalStructureAndOutlineAgreement($admin);
        $order = $this->makeEligibleOrder($admin, [
            'nomor_order' => 'ORD-2026-SCOPE-ONLY',
            'nama_pekerjaan' => 'Pekerjaan HPP hanya dengan scope of work',
            'unit_kerja' => 'Unit Produksi Raw Mill',
            'seksi' => 'Maintenance',
            'deskripsi' => 'Dokumen abnormalitas dan gambar teknik tidak wajib untuk create HPP.',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-05-01',
            'target_selesai' => '2026-05-10',
        ]);

        $order->documents()->delete();

        $this
            ->actingAs($admin)
            ->post(route('admin.hpp.store'), [
                'action' => 'submit',
                'order_id' => $order->id,
                'outline_agreement_id' => $outlineAgreement->id,
                'kategori_pekerjaan' => 'Fabrikasi',
                'area_pekerjaan' => HppApprovalFlow::displayArea('Dalam'),
                'jenis_label_visible' => [0 => 'Jasa'],
                'nama_item' => [0 => ['Pekerjaan hanya dengan scope of work']],
                'jumlah_item' => [0 => ['1 lot']],
                'qty' => [0 => [1]],
                'satuan' => [0 => ['Lot']],
                'harga_satuan' => [0 => [1000000]],
                'keterangan' => [0 => ['Tetap valid tanpa dokumen order']],
            ])
            ->assertRedirect(route('admin.hpp.index'));

        $this->assertDatabaseHas('hpps', [
            'order_id' => $order->id,
            'nomor_order' => 'ORD-2026-SCOPE-ONLY',
        ]);
    }

    public function test_hpp_bucket_and_approval_flow_are_derived_from_server_total(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);

        $outlineAgreement = $this->createApprovalStructureAndOutlineAgreement($admin);
        $order = $this->makeEligibleOrder($admin, [
            'nomor_order' => 'ORD-2026-OVER-250',
            'nama_pekerjaan' => 'Pekerjaan HPP di atas batas DIROPS',
            'unit_kerja' => 'Unit Produksi Raw Mill',
            'seksi' => 'Maintenance',
            'deskripsi' => 'Memastikan bucket tidak dapat dimanipulasi dari browser.',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-06-11',
            'target_selesai' => '2026-06-20',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.hpp.store'), [
                'action' => 'submit',
                'order_id' => $order->id,
                'outline_agreement_id' => $outlineAgreement->id,
                'kategori_pekerjaan' => 'Fabrikasi',
                'area_pekerjaan' => HppApprovalFlow::displayArea('Dalam'),
                'nilai_hpp_bucket' => 'under',
                'jenis_label_visible' => [0 => 'Jasa'],
                'nama_item' => [0 => ['Pekerjaan besar']],
                'jumlah_item' => [0 => ['1 lot']],
                'qty' => [0 => [1]],
                'satuan' => [0 => ['Lot']],
                'harga_satuan' => [0 => [300000000]],
                'keterangan' => [0 => ['Harus melalui DIROPS']],
            ])
            ->assertRedirect(route('admin.hpp.index'));

        $hpp = Hpp::query()->where('order_id', $order->id)->firstOrFail();

        $this->assertSame(300000000.0, (float) $hpp->total_keseluruhan);
        $this->assertSame('over', $hpp->nilai_hpp_bucket);
        $this->assertSame('FAB-DALAM-OVER250', $hpp->approval_case);
        $this->assertSame('DIROPS', collect($hpp->approval_flow)->last());
        $this->assertDatabaseHas('hpp_signatures', [
            'hpp_id' => $hpp->id,
            'role_key' => 'dirops',
        ]);
    }

    public function test_save_draft_does_not_assign_hpp_document_number(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);

        $outlineAgreement = $this->createApprovalStructureAndOutlineAgreement($admin);
        $order = $this->makeEligibleOrder($admin, [
            'nomor_order' => 'ORD-HPP-DRAFT-NO-DOC',
            'nama_pekerjaan' => 'Draft HPP tanpa nomor dokumen',
            'unit_kerja' => 'Unit Produksi Raw Mill',
            'seksi' => 'Maintenance',
            'deskripsi' => 'Draft HPP belum boleh punya nomor dokumen.',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-07-01',
            'target_selesai' => '2026-07-10',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.hpp.store'), $this->hppPayload($order, $outlineAgreement, [
                'action' => 'draft',
            ]))
            ->assertRedirect(route('admin.hpp.index'));

        $hpp = Hpp::query()->where('order_id', $order->id)->firstOrFail();

        $this->assertSame(Hpp::STATUS_DRAFT, $hpp->status);
        $this->assertNull($hpp->document_no);
        $this->assertNull($hpp->document_sequence);
        $this->assertNull($hpp->document_year);
    }

    public function test_submitted_hpp_receives_yearly_sequence_document_number(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);

        $outlineAgreement = $this->createApprovalStructureAndOutlineAgreement($admin);

        try {
            Carbon::setTestNow('2026-07-09 10:00:00');
            $firstOrder = $this->makeEligibleOrder($admin, [
                'nomor_order' => 'ORD-HPP-DOC-2026-001',
                'nama_pekerjaan' => 'HPP pertama 2026',
                'unit_kerja' => 'Unit Produksi Raw Mill',
                'seksi' => 'Maintenance',
                'deskripsi' => 'Nomor dokumen pertama tahun 2026.',
                'prioritas' => Order::PRIORITY_MEDIUM,
                'tanggal_order' => '2026-07-01',
                'target_selesai' => '2026-07-10',
            ]);

            $this->actingAs($admin)
                ->post(route('admin.hpp.store'), $this->hppPayload($firstOrder, $outlineAgreement))
                ->assertRedirect(route('admin.hpp.index'));

            Carbon::setTestNow('2026-08-02 10:00:00');
            $secondOrder = $this->makeEligibleOrder($admin, [
                'nomor_order' => 'ORD-HPP-DOC-2026-002',
                'nama_pekerjaan' => 'HPP kedua 2026',
                'unit_kerja' => 'Unit Produksi Raw Mill',
                'seksi' => 'Maintenance',
                'deskripsi' => 'Nomor dokumen kedua tahun 2026.',
                'prioritas' => Order::PRIORITY_MEDIUM,
                'tanggal_order' => '2026-08-01',
                'target_selesai' => '2026-08-10',
            ]);

            $this->actingAs($admin)
                ->post(route('admin.hpp.store'), $this->hppPayload($secondOrder, $outlineAgreement))
                ->assertRedirect(route('admin.hpp.index'));

            Carbon::setTestNow('2027-01-02 10:00:00');
            $thirdOrder = $this->makeEligibleOrder($admin, [
                'nomor_order' => 'ORD-HPP-DOC-2027-001',
                'nama_pekerjaan' => 'HPP pertama 2027',
                'unit_kerja' => 'Unit Produksi Raw Mill',
                'seksi' => 'Maintenance',
                'deskripsi' => 'Nomor dokumen reset tahun 2027.',
                'prioritas' => Order::PRIORITY_MEDIUM,
                'tanggal_order' => '2027-01-01',
                'target_selesai' => '2027-01-10',
            ]);

            $this->actingAs($admin)
                ->post(route('admin.hpp.store'), $this->hppPayload($thirdOrder, $outlineAgreement))
                ->assertRedirect(route('admin.hpp.index'));
        } finally {
            Carbon::setTestNow();
        }

        $firstHpp = Hpp::query()->where('order_id', $firstOrder->id)->firstOrFail();
        $secondHpp = Hpp::query()->where('order_id', $secondOrder->id)->firstOrFail();
        $thirdHpp = Hpp::query()->where('order_id', $thirdOrder->id)->firstOrFail();

        $this->assertSame('001/HPP/25.10/07-2026', $firstHpp->document_no);
        $this->assertSame(1, $firstHpp->document_sequence);
        $this->assertSame(2026, $firstHpp->document_year);

        $this->assertSame('002/HPP/25.10/08-2026', $secondHpp->document_no);
        $this->assertSame(2, $secondHpp->document_sequence);
        $this->assertSame(2026, $secondHpp->document_year);

        $this->assertSame('001/HPP/25.10/01-2027', $thirdHpp->document_no);
        $this->assertSame(1, $thirdHpp->document_sequence);
        $this->assertSame(2027, $thirdHpp->document_year);
    }

    public function test_backfill_document_numbers_assigns_submitted_hpps_only(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);

        $outlineAgreement = $this->createApprovalStructureAndOutlineAgreement($admin);
        $firstOrder = $this->makeEligibleOrder($admin, [
            'nomor_order' => 'ORD-HPP-BACKFILL-001',
            'nama_pekerjaan' => 'Backfill HPP pertama',
            'unit_kerja' => 'Unit Produksi Raw Mill',
            'seksi' => 'Maintenance',
            'deskripsi' => 'Backfill pertama.',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-07-01',
            'target_selesai' => '2026-07-10',
        ]);
        $secondOrder = $this->makeEligibleOrder($admin, [
            'nomor_order' => 'ORD-HPP-BACKFILL-002',
            'nama_pekerjaan' => 'Backfill HPP kedua',
            'unit_kerja' => 'Unit Produksi Raw Mill',
            'seksi' => 'Maintenance',
            'deskripsi' => 'Backfill kedua.',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-08-01',
            'target_selesai' => '2026-08-10',
        ]);
        $draftOrder = $this->makeEligibleOrder($admin, [
            'nomor_order' => 'ORD-HPP-BACKFILL-DRAFT',
            'nama_pekerjaan' => 'Backfill HPP draft',
            'unit_kerja' => 'Unit Produksi Raw Mill',
            'seksi' => 'Maintenance',
            'deskripsi' => 'Draft tidak dibackfill.',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-09-01',
            'target_selesai' => '2026-09-10',
        ]);

        $firstHpp = $this->createHppSnapshot($firstOrder, $outlineAgreement, [
            'status' => Hpp::STATUS_IN_REVIEW,
            'submitted_at' => '2026-07-11 08:00:00',
            'created_at' => '2026-07-10 08:00:00',
        ]);
        $secondHpp = $this->createHppSnapshot($secondOrder, $outlineAgreement, [
            'status' => Hpp::STATUS_APPROVED,
            'submitted_at' => '2026-08-11 08:00:00',
            'created_at' => '2026-08-10 08:00:00',
        ]);
        $draftHpp = $this->createHppSnapshot($draftOrder, $outlineAgreement, [
            'status' => Hpp::STATUS_DRAFT,
            'created_at' => '2026-09-10 08:00:00',
        ]);

        $this->artisan('hpp:backfill-document-numbers', ['--dry-run' => true])
            ->assertExitCode(0);

        $this->assertNull($firstHpp->refresh()->document_no);
        $this->assertNull($secondHpp->refresh()->document_no);
        $this->assertNull($draftHpp->refresh()->document_no);

        $this->artisan('hpp:backfill-document-numbers')
            ->assertExitCode(0);

        $this->assertSame('001/HPP/25.10/07-2026', $firstHpp->refresh()->document_no);
        $this->assertSame('002/HPP/25.10/08-2026', $secondHpp->refresh()->document_no);
        $this->assertNull($draftHpp->refresh()->document_no);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function makeEligibleOrder(User $admin, array $attributes): Order
    {
        $order = Order::query()->create($attributes + [
            'catatan_status' => OrderUserNoteStatus::ApprovedJasa->value,
            'created_by' => $admin->id,
        ]);

        foreach ([OrderDocumentType::Abnormalitas, OrderDocumentType::GambarTeknik] as $type) {
            OrderDocument::query()->create([
                'order_id' => $order->id,
                'jenis_dokumen' => $type->value,
                'nama_file_asli' => $type->value.'.pdf',
                'path_file' => 'testing/'.$type->value.'.pdf',
                'uploaded_by' => $admin->id,
                'uploaded_at' => now(),
            ]);
        }

        OrderScopeOfWork::query()->create([
            'order_id' => $order->id,
            'nama_penginput' => $admin->name,
            'tanggal_dokumen' => '2026-04-04',
            'scope_items' => [[
                'item' => 'Scope test',
            ]],
            'created_by' => $admin->id,
        ]);

        return $order;
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function hppPayload(Order $order, OutlineAgreement $outlineAgreement, array $overrides = []): array
    {
        return array_replace_recursive([
            'action' => 'submit',
            'order_id' => $order->id,
            'outline_agreement_id' => $outlineAgreement->id,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => HppApprovalFlow::displayArea('Dalam'),
            'jenis_label_visible' => [0 => 'Jasa'],
            'nama_item' => [0 => ['Pekerjaan test']],
            'jumlah_item' => [0 => ['1 lot']],
            'qty' => [0 => [1]],
            'satuan' => [0 => ['Lot']],
            'harga_satuan' => [0 => [1000000]],
            'keterangan' => [0 => ['Item test HPP']],
        ], $overrides);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createHppSnapshot(Order $order, OutlineAgreement $outlineAgreement, array $attributes = []): Hpp
    {
        return Hpp::query()->create(array_merge([
            'order_id' => $order->id,
            'outline_agreement_id' => $outlineAgreement->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => HppApprovalFlow::displayArea('Dalam'),
            'nilai_hpp_bucket' => 'under',
            'item_groups' => [],
            'total_keseluruhan' => 1000000,
            'status' => Hpp::STATUS_DRAFT,
            'created_by' => $order->created_by,
        ], $attributes));
    }

    private function createApprovalStructureAndOutlineAgreement(User $admin): OutlineAgreement
    {
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
            'nomor_oa' => 'OA/2026/001',
            'unit_work_id' => $controllerUnit->id,
            'jenis_kontrak' => 'Controller Section',
            'nama_kontrak' => 'Kontrak Test HPP',
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
