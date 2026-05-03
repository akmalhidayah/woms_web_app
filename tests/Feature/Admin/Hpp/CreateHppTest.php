<?php

namespace Tests\Feature\Admin\Hpp;

use App\Domain\Orders\Enums\OrderDocumentType;
use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Department;
use App\Models\Hpp;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\OrderScopeOfWork;
use App\Models\OutlineAgreement;
use App\Models\UnitWork;
use App\Models\UnitWorkSection;
use App\Models\User;
use App\Support\HppApprovalFlow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateHppTest extends TestCase
{
    use RefreshDatabase;

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
        $this->assertSame('Dalam (T.23,4,5, Pelabuhan BKS & Packing Plant)', $hpp->area_pekerjaan);
        $this->assertSame(Hpp::STATUS_IN_REVIEW, $hpp->status);
        $this->assertSame(3000000.0, (float) $hpp->total_keseluruhan);
        $this->assertSame('FAB-DALAM-UNDER250', $hpp->approval_case);
        $this->assertSame('Material Utama', $hpp->item_groups[0]['jenis_item']);
        $this->assertSame('Plat baja', $hpp->item_groups[0]['items'][0]['nama_item']);
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

    private function createApprovalStructureAndOutlineAgreement(User $admin): OutlineAgreement
    {
        $requesterManager = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $requesterSm = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $requesterGm = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $controllerManager = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $controllerSm = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $controllerGm = User::factory()->create(['role' => User::ROLE_APPROVER]);

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
