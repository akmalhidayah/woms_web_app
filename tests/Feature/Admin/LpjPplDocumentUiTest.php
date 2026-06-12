<?php

namespace Tests\Feature\Admin;

use App\Models\LhppBast;
use App\Models\LpjPpl;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LpjPplDocumentUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_lpj_ppl_document_column_shows_uploaded_document_or_upload_button_only(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
        $order = Order::query()->create([
            'nomor_order' => 'ORD-LPJ-UI',
            'nama_pekerjaan' => 'Pekerjaan LPJ UI',
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Deskripsi',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-06-01',
            'target_selesai' => '2026-06-10',
            'created_by' => $admin->id,
        ]);
        $lhpp = LhppBast::query()->create([
            'order_id' => $order->id,
            'termin_type' => 'termin_1',
            'nomor_order' => $order->nomor_order,
            'purchase_order_number' => 'PO-LPJ-UI',
            'deskripsi_pekerjaan' => $order->nama_pekerjaan,
            'unit_kerja' => $order->unit_kerja,
            'seksi' => $order->seksi,
            'tanggal_bast' => '2026-06-05',
            'tanggal_mulai_pekerjaan' => '2026-06-01',
            'tanggal_selesai_pekerjaan' => '2026-06-05',
            'total_aktual_biaya' => '1000000.00',
            'created_by' => $admin->id,
        ]);

        LpjPpl::query()->create([
            'lhpp_bast_id' => $lhpp->id,
            'lpj_number_termin1' => 'LPJ-001',
            'ppl_number_termin1' => 'PPL-001',
            'lpj_document_path_termin1' => 'lpj-ppl/ORD-LPJ-UI/LPJ-Termin-1-ORD-LPJ-UI.pdf',
        ]);

        $this->actingAs($admin)
            ->get(route('admin.lpj.index'))
            ->assertOk()
            ->assertSee('LPJ T1')
            ->assertSee('LPJ-Termin-1-ORD-LPJ-UI.pdf')
            ->assertSee('id="lpj-document-'.$lhpp->id.'" class=" h-8', false)
            ->assertSee('id="lpj-upload-'.$lhpp->id.'" title="Upload PDF LPJ" aria-label="Upload PDF LPJ" style="display: none; height: 2rem; width: 7.5rem;', false)
            ->assertSee('id="ppl-document-'.$lhpp->id.'" class="hidden h-8', false)
            ->assertSee('id="ppl-upload-'.$lhpp->id.'" title="Upload PDF PPL" aria-label="Upload PDF PPL" style="display: flex; height: 2rem; width: 7.5rem;', false);
    }
}
