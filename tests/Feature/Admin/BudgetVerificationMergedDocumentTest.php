<?php

namespace Tests\Feature\Admin;

use App\Domain\Orders\Enums\OrderDocumentType;
use App\Models\Hpp;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BudgetVerificationMergedDocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_budget_verification_uses_single_merged_hpp_abnormalitas_button(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
        $hpp = $this->createHppWithAbnormalitas($admin);

        $this->actingAs($admin)
            ->get(route('admin.budget-verification.index'))
            ->assertOk()
            ->assertSee('HPP + Abnormalitas')
            ->assertSee(route('admin.budget-verification.merged-document', ['hpp' => $hpp->nomor_order]), false)
            ->assertDontSee('Gambar Teknik')
            ->assertDontSee('Scope of Work');
    }

    public function test_admin_can_open_merged_hpp_abnormalitas_pdf(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
        $hpp = $this->createHppWithAbnormalitas($admin);

        $this->actingAs($admin)
            ->get(route('admin.budget-verification.merged-document', ['hpp' => $hpp->nomor_order]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertHeader('content-disposition', 'inline; filename="hpp-abnormalitas-'.$hpp->nomor_order.'.pdf"');
    }

    public function test_incompatible_abnormalitas_falls_back_to_hpp_pdf(): void
    {
        Storage::fake('local');

        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
        $hpp = $this->createHppWithAbnormalitas($admin);
        $document = OrderDocument::query()
            ->where('order_id', $hpp->order_id)
            ->where('jenis_dokumen', OrderDocumentType::Abnormalitas->value)
            ->firstOrFail();

        Storage::disk('local')->put($document->path_file, "%PDF-1.7\nfile abnormalitas rusak");

        $this->actingAs($admin)
            ->get(route('admin.budget-verification.merged-document', ['hpp' => $hpp->nomor_order]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    private function createHppWithAbnormalitas(User $admin): Hpp
    {
        $order = Order::query()->create([
            'nomor_order' => 'ORD-BUDGET-MERGE-001',
            'notifikasi' => 'NOTIF-BUDGET-MERGE-001',
            'nama_pekerjaan' => 'Pekerjaan Merge Budget',
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Detail pekerjaan merge budget',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-06-01',
            'target_selesai' => '2026-06-10',
            'created_by' => $admin->id,
        ]);

        $path = 'orders/'.$order->id.'/documents/abnormalitas/abnormalitas.pdf';
        Storage::disk('local')->put($path, $this->pdfOutput('Abnormalitas test'));

        OrderDocument::query()->create([
            'order_id' => $order->id,
            'jenis_dokumen' => OrderDocumentType::Abnormalitas->value,
            'nama_file_asli' => 'abnormalitas.pdf',
            'path_file' => $path,
            'uploaded_by' => $admin->id,
            'uploaded_at' => now(),
        ]);

        return Hpp::query()->create([
            'order_id' => $order->id,
            'nomor_order' => $order->nomor_order,
            'nama_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'kategori_pekerjaan' => 'Fabrikasi',
            'area_pekerjaan' => 'Dalam',
            'nilai_hpp_bucket' => 'under',
            'cost_centre' => 'CC-BUDGET-001',
            'unit_kerja_pengendali' => 'Unit Pengendali',
            'periode_outline_agreement' => '01/01/2026 - 31/12/2026',
            'approval_case' => 'FAB-DALAM-UNDER250',
            'approval_flow' => ['Manager Pengendali'],
            'item_groups' => [],
            'total_keseluruhan' => 25000000,
            'status' => Hpp::STATUS_APPROVED,
            'created_by' => $admin->id,
        ]);
    }

    private function pdfOutput(string $text): string
    {
        $pdf = new \FPDF;
        $pdf->AddPage();
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 10, $text);

        return $pdf->Output('S');
    }
}
