<?php

namespace Tests\Feature\Pkm;

use App\Domain\Orders\Enums\OrderDocumentType;
use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Garansi;
use App\Models\Hpp;
use App\Models\LhppBast;
use App\Models\LhppBastSignature;
use App\Models\LpjPpl;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\OrderScopeOfWork;
use App\Models\PurchaseOrder;
use App\Models\User;
use App\Support\PkmSidebarBadgeCounter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PkmSidebarBadgeCounterTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_hpp_count_matches_eligible_orders_without_hpp(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $eligible = $this->makeOrder($admin, 'PKM-HPP-BADGE-001', [
            'catatan_status' => OrderUserNoteStatus::ApprovedJasa->value,
        ]);
        OrderScopeOfWork::query()->create([
            'order_id' => $eligible->id,
            'tanggal_dokumen' => '2026-07-01',
            'scope_items' => [['pekerjaan' => 'Scope test']],
            'created_by' => $admin->id,
        ]);

        $this->makeOrder($admin, 'PKM-HPP-NO-SCOPE', [
            'catatan_status' => OrderUserNoteStatus::ApprovedJasa->value,
        ]);
        $this->makeOrder($admin, 'PKM-HPP-PENDING', [
            'catatan_status' => OrderUserNoteStatus::Pending->value,
        ]);

        $this->assertSame(1, $this->counts()['create_hpp']);

        $this->makeHpp($admin, $eligible);

        $this->assertSame(0, $this->counts()['create_hpp']);
    }

    public function test_jobwaiting_count_tracks_orders_until_bast_termin_one_approval_starts(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $order = $this->makeOrder($admin, 'PKM-JW-001', [
            'catatan_status' => OrderUserNoteStatus::ApprovedJasa->value,
        ]);
        $hpp = $this->makeHpp($admin, $order);
        $purchaseOrder = $this->makePurchaseOrder($admin, $order, $hpp, [
            'approve_manager' => true,
            'purchase_order_number' => 'PO-PKM-JW-001',
        ]);

        $this->assertSame(1, $this->counts()['jobwaiting']);

        $lhpp = $this->makeLhppBast($admin, $order, [
            'purchase_order_id' => $purchaseOrder->id,
            'purchase_order_number' => $purchaseOrder->purchase_order_number,
            'quality_control_status' => 'pending',
        ]);
        Garansi::query()->create([
            'order_id' => $order->id,
            'lhpp_bast_id' => $lhpp->id,
            'garansi_months' => 0,
            'start_date' => '2026-07-01',
            'created_by' => $admin->id,
        ]);
        LpjPpl::query()->create([
            'lhpp_bast_id' => $lhpp->id,
            'lpj_document_path_termin1' => 'lpj/t1.pdf',
            'ppl_document_path_termin1' => 'ppl/t1.pdf',
        ]);

        $this->assertSame(1, $this->counts()['jobwaiting']);

        $lhpp->update(['quality_control_status' => 'approved']);
        $this->assertSame(0, $this->counts()['jobwaiting']);

        $lhpp->update(['quality_control_status' => 'pending']);
        $this->assertSame(1, $this->counts()['jobwaiting']);

        $lhpp->signatures()->create([
            'step_order' => 1,
            'role_key' => 'manager_pkm',
            'role_label' => 'Manager PKM',
            'signer_user_id' => $admin->id,
            'signer_name_snapshot' => $admin->name,
            'status' => LhppBastSignature::STATUS_PENDING,
        ]);

        $this->assertSame(0, $this->counts()['jobwaiting']);
    }

    public function test_lhpp_count_tracks_eligible_termin_one_and_drops_after_bast_created(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $order = $this->makeOrder($admin, 'PKM-LHPP-T1-001');
        $hpp = $this->makeHpp($admin, $order);
        $this->makePurchaseOrder($admin, $order, $hpp, [
            'purchase_order_number' => 'PO-PKM-T1-001',
            'progress_pekerjaan' => 100,
        ]);
        $garansiSeed = $this->makeLhppBast($admin, $order, [
            'termin_type' => 'garansi_seed',
        ]);
        Garansi::query()->create([
            'order_id' => $order->id,
            'lhpp_bast_id' => $garansiSeed->id,
            'garansi_months' => 0,
            'start_date' => '2026-07-01',
            'created_by' => $admin->id,
        ]);

        $counts = $this->counts();
        $this->assertSame(1, $counts['lhpp_termin_1']);
        $this->assertSame(1, $counts['lhpp']);

        $this->makeLhppBast($admin, $order);

        $counts = $this->counts();
        $this->assertSame(0, $counts['lhpp_termin_1']);
        $this->assertSame(0, $counts['lhpp']);
    }

    public function test_lhpp_count_tracks_eligible_termin_two_and_drops_after_child_bast_created(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $order = $this->makeOrder($admin, 'PKM-LHPP-T2-001');
        $terminOne = $this->makeLhppBast($admin, $order, [
            'termin1_status' => 'sudah',
        ]);
        Garansi::query()->create([
            'order_id' => $order->id,
            'lhpp_bast_id' => $terminOne->id,
            'garansi_months' => 3,
            'start_date' => '2026-07-01',
            'created_by' => $admin->id,
        ]);

        $counts = $this->counts();
        $this->assertSame(1, $counts['lhpp_termin_2']);
        $this->assertSame(1, $counts['lhpp']);

        $this->makeLhppBast($admin, $order, [
            'termin_type' => 'termin_2',
            'parent_lhpp_bast_id' => $terminOne->id,
        ]);

        $counts = $this->counts();
        $this->assertSame(0, $counts['lhpp_termin_2']);
        $this->assertSame(0, $counts['lhpp']);
    }

    public function test_documents_count_tracks_incomplete_and_complete_termin_one_package(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $order = $this->makeOrder($admin, 'PKM-DOC-001');
        $hpp = $this->makeHpp($admin, $order);
        $purchaseOrder = $this->makePurchaseOrder($admin, $order, $hpp, [
            'approve_manager' => true,
            'purchase_order_number' => 'PO-PKM-DOC-001',
        ]);

        $this->assertSame(0, $this->counts()['documents']);

        $lhpp = $this->makeLhppBast($admin, $order, [
            'purchase_order_id' => $purchaseOrder->id,
            'purchase_order_number' => $purchaseOrder->purchase_order_number,
        ]);

        $this->assertSame(1, $this->counts()['documents']);

        OrderDocument::query()->create([
            'order_id' => $order->id,
            'jenis_dokumen' => OrderDocumentType::Abnormalitas->value,
            'nama_file_asli' => 'abnormalitas.pdf',
            'path_file' => 'orders/abnormalitas.pdf',
            'uploaded_by' => $admin->id,
            'uploaded_at' => now(),
        ]);
        $purchaseOrder->update(['po_document_path' => 'po/document.pdf']);
        LpjPpl::query()->create([
            'lhpp_bast_id' => $lhpp->id,
            'lpj_document_path_termin1' => 'lpj/t1.pdf',
            'ppl_document_path_termin1' => 'ppl/t1.pdf',
        ]);

        $this->assertSame(0, $this->counts()['documents']);
    }

    /**
     * @return array<string, int>
     */
    private function counts(): array
    {
        return app(PkmSidebarBadgeCounter::class)->counts();
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
            'status' => Hpp::STATUS_APPROVED,
            'created_by' => $admin->id,
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function makePurchaseOrder(User $admin, Order $order, Hpp $hpp, array $attributes = []): PurchaseOrder
    {
        return PurchaseOrder::query()->create([
            'order_id' => $order->id,
            'hpp_id' => $hpp->id,
            'purchase_order_number' => null,
            'progress_pekerjaan' => 0,
            'created_by' => $admin->id,
            ...$attributes,
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
