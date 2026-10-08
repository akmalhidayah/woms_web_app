<?php

namespace Tests\Feature\Pkm;

use App\Models\Garansi;
use App\Models\LhppBast;
use App\Models\LpjPpl;
use App\Models\Order;
use App\Models\User;
use App\Services\Pkm\BastDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BastDeletionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_termin_one_cleans_children_files_and_preserves_order_warranty(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $order = Order::query()->create([
            'nomor_order' => 'BAST-DELETE-001', 'nama_pekerjaan' => 'Test', 'unit_kerja' => 'Unit', 'seksi' => 'Seksi',
            'deskripsi' => 'Test', 'prioritas' => Order::PRIORITY_MEDIUM, 'tanggal_order' => now(),
            'target_selesai' => now()->addDays(7), 'created_by' => $user->id,
        ]);
        $parent = $this->bast($order, $user, 'termin_1', null, 'bast/lampiran.pdf');
        $child = $this->bast($order, $user, 'termin_2', $parent->id);
        $parent->images()->create(['file_path' => 'bast/parent.jpg', 'created_by' => $user->id]);
        $child->signatures()->create([
            'step_order' => 1, 'role_key' => 'manager_pkm', 'role_label' => 'Manager PKM',
            'signer_user_id' => $user->id, 'signer_name_snapshot' => $user->name,
            'status' => 'signed', 'signature_data' => 'bast/signature.png', 'signed_document_path' => 'bast/final.pdf',
        ]);
        LpjPpl::query()->create(['lhpp_bast_id' => $child->id, 'lpj_document_path_termin2' => 'bast/lpj.pdf']);
        $garansi = Garansi::query()->create([
            'order_id' => $order->id, 'lhpp_bast_id' => $parent->id, 'garansi_months' => 3,
            'start_date' => now(), 'end_date' => now()->addMonths(3),
        ]);
        foreach (['bast/parent.jpg', 'bast/lampiran.pdf', 'bast/signature.png', 'bast/final.pdf', 'bast/lpj.pdf'] as $path) {
            Storage::disk('public')->put($path, 'file');
        }

        app(BastDeletionService::class)->delete($parent);

        $this->assertDatabaseMissing('lhpp_basts', ['id' => $parent->id]);
        $this->assertDatabaseMissing('lhpp_basts', ['id' => $child->id]);
        $this->assertDatabaseHas('garansis', ['id' => $garansi->id, 'order_id' => $order->id, 'lhpp_bast_id' => null]);
        foreach (['bast/parent.jpg', 'bast/lampiran.pdf', 'bast/signature.png', 'bast/final.pdf', 'bast/lpj.pdf'] as $path) {
            Storage::disk('public')->assertMissing($path);
        }
    }

    private function bast(
        Order $order,
        User $user,
        string $termin,
        ?int $parentId = null,
        ?string $attachmentPdfPath = null,
    ): LhppBast {
        return LhppBast::query()->create([
            'order_id' => $order->id, 'parent_lhpp_bast_id' => $parentId, 'termin_type' => $termin,
            'nomor_order' => $order->nomor_order, 'deskripsi_pekerjaan' => 'Test', 'unit_kerja' => 'Unit',
            'seksi' => 'Seksi', 'tanggal_bast' => now(), 'approval_status' => LhppBast::APPROVAL_IN_REVIEW,
            'attachment_pdf_path' => $attachmentPdfPath,
            'created_by' => $user->id,
        ]);
    }
}
