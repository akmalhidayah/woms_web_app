<?php

namespace Tests\Feature;

use App\Domain\Orders\Enums\OrderDocumentType;
use App\Models\BudgetVerification;
use App\Models\User;
use App\Models\Order;
use App\Models\Hpp;
use App\Models\HppSignature;
use App\Models\LhppBast;
use App\Models\Department;
use App\Models\OutlineAgreement;
use App\Models\OutlineAgreementTarget;
use App\Models\OrderDocument;
use App\Models\OrderScopeOfWork;
use App\Models\PurchaseOrder;
use App\Models\UnitWork;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleDashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_admin_dashboard(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($user)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSee('Admin Dashboard');
    }

    public function test_admin_dashboard_order_process_cards_use_real_data(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $outstandingOrder = $this->createOrder('ORD-DASH-OUTSTANDING', $admin);
        $this->completeHppSourceDocuments($outstandingOrder, $admin);

        $pendingOrder = $this->createOrder('ORD-DASH-PENDING', $admin);
        Hpp::query()->create([
            'order_id' => $pendingOrder->id,
            'nomor_order' => $pendingOrder->nomor_order,
            'nama_pekerjaan' => $pendingOrder->nama_pekerjaan,
            'unit_kerja' => $pendingOrder->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Workshop',
            'nilai_hpp_bucket' => 'under',
            'total_keseluruhan' => '1000000.00',
            'status' => Hpp::STATUS_IN_REVIEW,
            'submitted_at' => now(),
            'created_by' => $admin->id,
        ]);

        $approvedOrder = $this->createOrder('ORD-DASH-APPROVED', $admin);
        Hpp::query()->create([
            'order_id' => $approvedOrder->id,
            'nomor_order' => $approvedOrder->nomor_order,
            'nama_pekerjaan' => $approvedOrder->nama_pekerjaan,
            'unit_kerja' => $approvedOrder->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Workshop',
            'nilai_hpp_bucket' => 'under',
            'total_keseluruhan' => '2000000.00',
            'status' => Hpp::STATUS_APPROVED,
            'submitted_at' => now(),
            'created_by' => $admin->id,
        ]);

        $poOrder = $this->createOrder('ORD-DASH-PO', $admin);
        $poHpp = Hpp::query()->create([
            'order_id' => $poOrder->id,
            'nomor_order' => $poOrder->nomor_order,
            'nama_pekerjaan' => $poOrder->nama_pekerjaan,
            'unit_kerja' => $poOrder->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Workshop',
            'nilai_hpp_bucket' => 'under',
            'total_keseluruhan' => '3000000.00',
            'status' => Hpp::STATUS_APPROVED,
            'submitted_at' => now(),
            'created_by' => $admin->id,
        ]);
        PurchaseOrder::query()->create([
            'order_id' => $poOrder->id,
            'hpp_id' => $poHpp->id,
            'purchase_order_number' => 'PO-DASH-001',
            'po_document_path' => 'purchase-orders/ORD-DASH-PO/PO-DASH-001.pdf',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $normalLhppOrder = $this->createOrder('ORD-DASH-LHPP-NORMAL', $admin, Order::PRIORITY_MEDIUM);
        LhppBast::query()->create([
            'order_id' => $normalLhppOrder->id,
            'termin_type' => 'termin_1',
            'nomor_order' => $normalLhppOrder->nomor_order,
            'purchase_order_number' => 'PO-DASH-LHPP-001',
            'deskripsi_pekerjaan' => $normalLhppOrder->nama_pekerjaan,
            'unit_kerja' => $normalLhppOrder->unit_kerja,
            'seksi' => $normalLhppOrder->seksi,
            'tanggal_bast' => '2026-05-15',
            'total_aktual_biaya' => '4000000.00',
            'termin_1_nilai' => '3800000.00',
            'termin_2_nilai' => '200000.00',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $urgentLhppOrder = $this->createOrder('ORD-DASH-LHPP-URGENT', $admin, Order::PRIORITY_HIGH);
        LhppBast::query()->create([
            'order_id' => $urgentLhppOrder->id,
            'termin_type' => 'termin_1',
            'nomor_order' => $urgentLhppOrder->nomor_order,
            'deskripsi_pekerjaan' => $urgentLhppOrder->nama_pekerjaan,
            'unit_kerja' => $urgentLhppOrder->unit_kerja,
            'seksi' => $urgentLhppOrder->seksi,
            'tanggal_bast' => '2026-05-15',
            'total_aktual_biaya' => '5000000.00',
            'termin_1_nilai' => '4750000.00',
            'termin_2_nilai' => '250000.00',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $department = Department::query()->create(['name' => 'Dept Dashboard']);
        $unitWork = UnitWork::query()->create([
            'department_id' => $department->id,
            'name' => 'Unit Dashboard',
        ]);
        $outlineAgreement = OutlineAgreement::query()->create([
            'nomor_oa' => 'OA-DASH-001',
            'unit_work_id' => $unitWork->id,
            'jenis_kontrak' => 'Bengkel Mesin',
            'nama_kontrak' => 'Kontrak Dashboard',
            'nilai_kontrak_awal' => '20000000.00',
            'periode_awal_start' => '2026-01-01',
            'periode_awal_end' => '2026-12-31',
            'current_total_nilai' => '20000000.00',
            'current_period_start' => '2026-01-01',
            'current_period_end' => '2026-12-31',
            'status' => OutlineAgreement::STATUS_ACTIVE,
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);
        OutlineAgreementTarget::query()->create([
            'outline_agreement_id' => $outlineAgreement->id,
            'tahun' => 2026,
            'nilai_target' => '12000000.00',
        ]);
        $maintenanceOrder = $this->createOrder('ORD-DASH-MAINTENANCE', $admin);
        $maintenanceHpp = Hpp::query()->create([
            'order_id' => $maintenanceOrder->id,
            'nomor_order' => $maintenanceOrder->nomor_order,
            'nama_pekerjaan' => $maintenanceOrder->nama_pekerjaan,
            'unit_kerja' => $maintenanceOrder->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Workshop',
            'nilai_hpp_bucket' => 'under',
            'total_keseluruhan' => '7000000.00',
            'status' => Hpp::STATUS_DRAFT,
            'created_by' => $admin->id,
        ]);
        BudgetVerification::query()->create([
            'order_id' => $maintenanceOrder->id,
            'hpp_id' => $maintenanceHpp->id,
            'status_anggaran' => 'Tersedia',
            'kategori_item' => 'jasa',
            'kategori_biaya' => 'pemeliharaan',
            'created_by' => $admin->id,
            'updated_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk();
        $response->assertSeeTextInOrder([
            'Outstanding Order',
            '1',
            'Document On Process (HPP)',
            '1',
            'Approval Process (HPP)',
            '1',
            'PR/PO Process (HPP Approved)',
            '1',
        ]);
        $response->assertSeeTextInOrder([
            'Potensi Biaya (Cost)',
            'Document On Process (HPP)',
            'Rp. 1.000.000',
            'Approval Process (HPP)',
            'Rp. 2.000.000',
            'PR/PO On Process',
            'Rp. 3.000.000',
            'Subtotal potensi',
            'Rp. 6.000.000',
        ]);
        $response->assertSeeTextInOrder([
            'Realisasi Biaya (LPJ)',
            'Document PR/PO (LHPP)',
            'Rp. 4.000.000',
            'Pekerjaan Urgent',
            'Rp. 5.000.000',
            'Subtotal realisasi',
            'Rp. 9.000.000',
            'Total Realisasi Biaya: Rp 9.000.000',
        ]);
        $response->assertSeeTextInOrder([
            'Ringkasan Kuota Anggaran',
            'Kuota Anggaran',
            'Rp. 20.000.000',
            'Periode:',
            '01 Jan 2026',
            's/d',
            '31 Dec 2026',
            'Potensi Biaya + Realisasi Biaya:',
            'Rp. 15.000.000',
            'Kuota Anggaran Actual',
            'Rp. 5.000.000',
            'Total Biaya Pemeliharaan',
            'Rp. 12.000.000',
            'Total Jasa Pemeliharaan',
            'Rp. 7.000.000',
            'Sisa Biaya Pemeliharaan',
            'Rp. 5.000.000',
            'Sisa Kuota Kontrak',
            'Rp. 5.000.000',
        ]);

        $this->actingAs($admin)
            ->getJson(route('admin.dashboard.realization-chart', [
                'startYear' => 2026,
                'endYear' => 2026,
                'startMonth' => 5,
                'endMonth' => 5,
            ]))
            ->assertOk()
            ->assertJsonFragment([
                'year' => 2026,
                'month' => 5,
                'total' => 9000000,
                'normal_total' => 4000000,
                'urgent_total' => 5000000,
            ]);
    }

    public function test_pkm_can_access_pkm_dashboard(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_PKM]);

        $response = $this->actingAs($user)->get('/pkm/dashboard');

        $response->assertOk();
        $response->assertSee('PKM Dashboard');
    }

    public function test_approver_dashboard_redirects_to_user_dashboard(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_APPROVER]);

        $response = $this->actingAs($user)->get('/approver/dashboard');

        $response->assertRedirect('/user/dashboard');
    }

    public function test_approver_can_open_user_order_detail_from_dashboard(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_APPROVER]);
        $order = Order::query()->create([
            'nomor_order' => 'ORD-APPROVER-DETAIL',
            'nama_pekerjaan' => 'Pekerjaan untuk approver',
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Detail pekerjaan test',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-05-01',
            'target_selesai' => '2026-05-10',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('user.orders.show', $order));

        $response->assertOk();
        $response->assertSee($order->nomor_order);
    }

    public function test_user_order_detail_shows_active_hpp_approval_token(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);
        $signer = User::factory()->create([
            'role' => User::ROLE_APPROVER,
            'name' => 'Manager Approval Test',
        ]);
        $order = Order::query()->create([
            'nomor_order' => 'ORD-HPP-TOKEN',
            'nama_pekerjaan' => 'Pekerjaan HPP Token',
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Detail pekerjaan test',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-05-01',
            'target_selesai' => '2026-05-10',
            'created_by' => $user->id,
        ]);
        $hpp = Hpp::query()->create([
            'order_id' => $order->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Dalam',
            'nilai_hpp_bucket' => 'under',
            'total_keseluruhan' => '1500000.00',
            'status' => Hpp::STATUS_IN_REVIEW,
            'created_by' => $user->id,
        ]);
        $token = 'hpp-token-for-user-share';
        $signedToken = 'hpp-token-signed-for-user-share';

        HppSignature::query()->create([
            'hpp_id' => $hpp->id,
            'step_order' => 1,
            'role_key' => 'manager_peminta',
            'role_label' => 'Manager Peminta',
            'signer_user_id' => $signer->id,
            'signer_name_snapshot' => $signer->name,
            'signer_position_snapshot' => 'Manager Peminta',
            'token' => $token,
            'token_hash' => hash('sha256', $token),
            'token_expires_at' => now()->addDay(),
            'status' => HppSignature::STATUS_PENDING,
        ]);
        HppSignature::query()->create([
            'hpp_id' => $hpp->id,
            'step_order' => 2,
            'role_key' => 'sm_peminta',
            'role_label' => 'SM Peminta',
            'signer_user_id' => $signer->id,
            'signer_name_snapshot' => $signer->name,
            'signer_position_snapshot' => 'SM Peminta',
            'token' => $signedToken,
            'token_hash' => hash('sha256', $signedToken),
            'token_expires_at' => now()->addDay(),
            'status' => HppSignature::STATUS_SIGNED,
        ]);

        $response = $this->actingAs($user)->get(route('user.orders.show', $order));

        $response->assertOk();
        $response->assertSee('Token TTD HPP');
        $response->assertSee('Manager Approval Test');
        $response->assertSee(route('approval.hpp.show', $token));
        $response->assertDontSee(route('approval.hpp.show', $signedToken));
    }

    private function createOrder(string $nomorOrder, User $creator, string $priority = Order::PRIORITY_MEDIUM): Order
    {
        return Order::query()->create([
            'nomor_order' => $nomorOrder,
            'nama_pekerjaan' => 'Pekerjaan '.$nomorOrder,
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Detail pekerjaan test',
            'prioritas' => $priority,
            'tanggal_order' => '2026-05-01',
            'target_selesai' => '2026-05-10',
            'created_by' => $creator->id,
        ]);
    }

    private function completeHppSourceDocuments(Order $order, User $uploader): void
    {
        foreach ([OrderDocumentType::Abnormalitas, OrderDocumentType::GambarTeknik] as $type) {
            OrderDocument::query()->create([
                'order_id' => $order->id,
                'jenis_dokumen' => $type->value,
                'nama_file_asli' => $type->value.'.pdf',
                'path_file' => 'orders/'.$order->nomor_order.'/'.$type->value.'.pdf',
                'uploaded_by' => $uploader->id,
                'uploaded_at' => now(),
            ]);
        }

        OrderScopeOfWork::query()->create([
            'order_id' => $order->id,
            'nama_penginput' => $uploader->name,
            'tanggal_dokumen' => '2026-05-01',
            'scope_items' => [
                ['description' => 'Scope pekerjaan test'],
            ],
            'created_by' => $uploader->id,
        ]);
    }
}
