<?php

namespace Tests\Feature;

use App\Domain\Orders\Enums\OrderDocumentType;
use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Hpp;
use App\Models\InitialWork;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PkmJobWaitingMergedDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_pkm_can_open_hpp_document_without_abnormalitas_or_purchase_order(): void
    {
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $hpp = $this->createHpp($pkm, 'ORD-PKM-MERGE-001');

        $this->actingAs($pkm)
            ->get(route('pkm.jobwaiting.hpp.merged-document', ['hpp' => $hpp->nomor_order]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename="dokumen-pekerjaan-'.$hpp->nomor_order.'.pdf"');
    }

    public function test_incompatible_abnormalitas_is_skipped_instead_of_returning_422(): void
    {
        Storage::fake('local');

        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $hpp = $this->createHpp($pkm, 'ORD-PKM-MERGE-002');
        $path = 'orders/'.$hpp->order_id.'/documents/abnormalitas/rusak.pdf';

        Storage::disk('local')->put($path, "%PDF-1.7\nfile tidak kompatibel dengan parser");

        OrderDocument::query()->create([
            'order_id' => $hpp->order_id,
            'jenis_dokumen' => OrderDocumentType::Abnormalitas->value,
            'nama_file_asli' => 'rusak.pdf',
            'path_file' => $path,
            'uploaded_by' => $pkm->id,
            'uploaded_at' => now(),
        ]);

        $this->actingAs($pkm)
            ->get(route('pkm.jobwaiting.hpp.merged-document', ['hpp' => $hpp->nomor_order]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_jobwaiting_exposes_available_hpp_even_without_abnormalitas_or_po(): void
    {
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $hpp = $this->createHpp($pkm, 'ORD-PKM-MERGE-003');

        $this->actingAs($pkm)
            ->get(route('pkm.jobwaiting'))
            ->assertOk()
            ->assertSee('HPP + Dokumen Tersedia')
            ->assertSee(route('pkm.jobwaiting.hpp.merged-document', ['hpp' => $hpp->nomor_order]), false);
    }

    public function test_pkm_document_report_can_open_available_hpp_without_other_documents(): void
    {
        $pkm = User::factory()->create(['role' => User::ROLE_PKM]);
        $hpp = $this->createHpp($pkm, 'ORD-PKM-MERGE-004');

        $this->actingAs($pkm)
            ->get(route('pkm.laporan.merged-documents', ['order' => $hpp->nomor_order]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    private function createHpp(User $creator, string $orderNumber): Hpp
    {
        $order = Order::query()->create([
            'nomor_order' => $orderNumber,
            'nama_pekerjaan' => 'Pekerjaan '.$orderNumber,
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Pekerjaan dokumen PKM',
            'prioritas' => Order::PRIORITY_URGENT,
            'tanggal_order' => now()->toDateString(),
            'target_selesai' => now()->addWeek()->toDateString(),
            'catatan_status' => OrderUserNoteStatus::ApprovedJasa->value,
            'created_by' => $creator->id,
        ]);

        $hpp = Hpp::query()->create([
            'order_id' => $order->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Dalam',
            'nilai_hpp_bucket' => 'under',
            'cost_centre' => 'CC-PKM-001',
            'unit_kerja_pengendali' => 'Unit Pengendali',
            'periode_outline_agreement' => '01/01/2026 - 31/12/2026',
            'approval_case' => 'FAB-DALAM-UNDER250',
            'approval_flow' => ['Manager Pengendali'],
            'item_groups' => [],
            'total_keseluruhan' => 1000000,
            'status' => Hpp::STATUS_APPROVED,
            'created_by' => $creator->id,
        ]);

        InitialWork::query()->create([
            'order_id' => $order->id,
            'nomor_initial_work' => 'IW-'.$orderNumber,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'perihal' => 'Initial Work '.$orderNumber,
            'tanggal_initial_work' => now()->toDateString(),
            'functional_location' => ['FL-001'],
            'scope_pekerjaan' => ['Pekerjaan awal'],
            'qty' => [1],
            'stn' => ['Lot'],
            'created_by' => $creator->id,
        ]);

        return $hpp;
    }
}
