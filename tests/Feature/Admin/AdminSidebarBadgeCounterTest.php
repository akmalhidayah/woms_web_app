<?php

namespace Tests\Feature\Admin;

use App\Domain\Orders\Enums\OrderDocumentType;
use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\BudgetVerification;
use App\Models\Garansi;
use App\Models\Hpp;
use App\Models\LhppBast;
use App\Models\LpjPpl;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\OrderScopeOfWork;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Support\AdminSidebarBadgeCounter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSidebarBadgeCounterTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_hpp_count_tracks_eligible_orders_without_hpp(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $order = $this->makeOrder($admin, 'BADGE-HPP-001', [
            'catatan_status' => OrderUserNoteStatus::ApprovedJasa->value,
        ]);

        OrderDocument::query()->create([
            'order_id' => $order->id,
            'jenis_dokumen' => OrderDocumentType::Abnormalitas->value,
            'nama_file_asli' => 'abnormalitas.pdf',
            'path_file' => 'orders/abnormalitas.pdf',
            'uploaded_by' => $admin->id,
            'uploaded_at' => now(),
        ]);
        OrderScopeOfWork::query()->create([
            'order_id' => $order->id,
            'tanggal_dokumen' => '2026-07-01',
            'scope_items' => [['pekerjaan' => 'Scope test']],
            'created_by' => $admin->id,
        ]);

        $this->assertSame(1, $this->counts()['create_hpp']);

        $this->makeHpp($admin, $order);

        $this->assertSame(0, $this->counts()['create_hpp']);
    }

    public function test_verifikasi_anggaran_and_purchase_order_counts_follow_execution_status(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $order = $this->makeOrder($admin, 'BADGE-BV-001');
        $hpp = $this->makeHpp($admin, $order);

        $this->assertSame(1, $this->counts()['verifikasi_anggaran']);

        $verification = BudgetVerification::query()->create([
            'order_id' => $order->id,
            'hpp_id' => $hpp->id,
            'status_anggaran' => 'Menunggu',
            'created_by' => $admin->id,
        ]);

        $this->assertSame(1, $this->counts()['verifikasi_anggaran']);

        $verification->update(['status_anggaran' => 'Tersedia']);

        $counts = $this->counts();
        $this->assertSame(0, $counts['verifikasi_anggaran']);
        $this->assertSame(1, $counts['purchase_order']);

        $purchaseOrder = PurchaseOrder::query()->create([
            'order_id' => $order->id,
            'hpp_id' => $hpp->id,
            'purchase_order_number' => '   ',
            'created_by' => $admin->id,
        ]);

        $this->assertSame(1, $this->counts()['purchase_order']);

        $purchaseOrder->update(['purchase_order_number' => 'PO-BADGE-001']);

        $this->assertSame(0, $this->counts()['purchase_order']);
    }

    public function test_set_garansi_cek_bast_and_parent_bast_counts(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $order = $this->makeOrder($admin, 'BADGE-BAST-001');
        $hpp = $this->makeHpp($admin, $order);
        $purchaseOrder = PurchaseOrder::query()->create([
            'order_id' => $order->id,
            'hpp_id' => $hpp->id,
            'purchase_order_number' => 'PO-BAST-001',
            'progress_pekerjaan' => 100,
            'created_by' => $admin->id,
        ]);
        $lhpp = $this->makeLhppBast($admin, $order, [
            'purchase_order_id' => $purchaseOrder->id,
            'purchase_order_number' => $purchaseOrder->purchase_order_number,
            'quality_control_status' => 'pending',
        ]);

        $counts = $this->counts();
        $this->assertSame(1, $counts['set_garansi']);
        $this->assertSame(1, $counts['cek_bast']);
        $this->assertSame(2, $counts['bast_total']);

        Garansi::query()->create([
            'order_id' => $order->id,
            'lhpp_bast_id' => $lhpp->id,
            'garansi_months' => 0,
            'start_date' => '2026-07-01',
            'created_by' => $admin->id,
        ]);
        $lhpp->update(['quality_control_status' => 'approved']);

        $counts = $this->counts();
        $this->assertSame(0, $counts['set_garansi']);
        $this->assertSame(0, $counts['cek_bast']);
        $this->assertSame(0, $counts['bast_total']);
    }

    public function test_lpj_ppl_count_tracks_required_termin_documents_once_per_bast(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $order = $this->makeOrder($admin, 'BADGE-LPJ-001');
        $lhpp = $this->makeLhppBast($admin, $order, [
            'quality_control_status' => 'approved',
        ]);

        $this->assertSame(1, $this->counts()['lpj_ppl']);

        $lpjPpl = LpjPpl::query()->create([
            'lhpp_bast_id' => $lhpp->id,
            'lpj_number_termin1' => 'LPJ-T1',
            'ppl_number_termin1' => 'PPL-T1',
            'lpj_document_path_termin1' => 'lpj/t1.pdf',
            'ppl_document_path_termin1' => 'ppl/t1.pdf',
        ]);

        $this->assertSame(0, $this->counts()['lpj_ppl']);

        Garansi::query()->create([
            'order_id' => $order->id,
            'lhpp_bast_id' => $lhpp->id,
            'garansi_months' => 3,
            'start_date' => '2026-07-01',
            'created_by' => $admin->id,
        ]);

        $this->assertSame(1, $this->counts()['lpj_ppl']);

        $lpjPpl->update([
            'lpj_number_termin2' => 'LPJ-T2',
            'ppl_number_termin2' => 'PPL-T2',
            'lpj_document_path_termin2' => 'lpj/t2.pdf',
            'ppl_document_path_termin2' => 'ppl/t2.pdf',
        ]);

        $this->assertSame(0, $this->counts()['lpj_ppl']);
    }

    /**
     * @return array<string, int>
     */
    private function counts(): array
    {
        return app(AdminSidebarBadgeCounter::class)->counts();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeOrder(User $admin, string $nomorOrder, array $attributes = []): Order
    {
        return Order::query()->create([
            'nomor_order' => $nomorOrder,
            'notifikasi' => 'NOTIF-'.$nomorOrder,
            'nama_pekerjaan' => 'Pekerjaan '.$nomorOrder,
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Deskripsi test',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-07-01',
            'target_selesai' => '2026-07-10',
            'created_by' => $admin->id,
            ...$attributes,
        ]);
    }

    private function makeHpp(User $admin, Order $order): Hpp
    {
        return Hpp::query()->create([
            'order_id' => $order->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Dalam',
            'nilai_hpp_bucket' => 'under',
            'item_groups' => [],
            'total_keseluruhan' => 1000000,
            'status' => Hpp::STATUS_DRAFT,
            'created_by' => $admin->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makeLhppBast(User $admin, Order $order, array $attributes = []): LhppBast
    {
        return LhppBast::query()->create([
            'order_id' => $order->id,
            'termin_type' => 'termin_1',
            'nomor_order' => $order->nomor_order,
            'notifikasi' => $order->notifikasi,
            'deskripsi_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'tanggal_bast' => '2026-07-02',
            'tanggal_mulai_pekerjaan' => '2026-07-01',
            'tanggal_selesai_pekerjaan' => '2026-07-02',
            'quality_control_status' => 'approved',
            'created_by' => $admin->id,
            ...$attributes,
        ]);
    }
}
