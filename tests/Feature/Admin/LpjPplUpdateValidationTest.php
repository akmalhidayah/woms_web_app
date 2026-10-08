<?php

namespace Tests\Feature\Admin;

use App\Models\Garansi;
use App\Models\LhppBast;
use App\Models\LpjPpl;
use App\Models\Order;
use App\Models\User;
use App\Services\Admin\LpjPplUpdateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LpjPplUpdateValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_bast_must_be_approved_with_warranty_aware_message(): void
    {
        foreach ([LhppBast::APPROVAL_IN_REVIEW, LhppBast::APPROVAL_REJECTED] as $status) {
            $withoutWarranty = $this->parent($status, 0);
            $withWarranty = $this->parent($status, 3);

            $this->assertValidationMessage($withoutWarranty, 1, 'LPJ/PPL hanya dapat diproses setelah BAST/LHPP disetujui.');
            $this->assertValidationMessage($withWarranty, 1, 'LPJ/PPL Termin 1 hanya dapat diproses setelah BAST Termin 1 disetujui.');
        }
    }

    public function test_termin_two_requires_warranty_paid_termin_one_and_approved_bast_two(): void
    {
        $this->assertValidationMessage($this->parent(LhppBast::APPROVAL_APPROVED, 0), 2, 'LPJ/PPL Termin 2 tidak tersedia untuk order tanpa garansi.');
        $this->assertValidationMessage($this->parent(LhppBast::APPROVAL_APPROVED, null), 2, 'Data garansi belum tersedia sehingga LPJ/PPL Termin 2 belum dapat diproses.');
        $this->assertValidationMessage($this->parent(LhppBast::APPROVAL_APPROVED, 3), 2, 'LPJ/PPL Termin 2 hanya dapat diproses setelah pembayaran Termin 1 selesai.');

        $paidParent = $this->parent(LhppBast::APPROVAL_APPROVED, 3, 'sudah');
        $this->assertValidationMessage($paidParent, 2, 'LPJ/PPL Termin 2 belum dapat diproses karena BAST Termin 2 belum dibuat.');

        $inReview = $this->parent(LhppBast::APPROVAL_APPROVED, 3, 'sudah');
        $this->terminTwo($inReview, LhppBast::APPROVAL_IN_REVIEW);
        $this->assertValidationMessage($inReview, 2, 'LPJ/PPL Termin 2 hanya dapat diproses setelah BAST Termin 2 disetujui.');

        $rejected = $this->parent(LhppBast::APPROVAL_APPROVED, 3, 'sudah');
        $this->terminTwo($rejected, LhppBast::APPROVAL_REJECTED);
        $this->assertValidationMessage($rejected, 2, 'BAST Termin 2 ditolak dan harus dibuat ulang sebelum LPJ/PPL Termin 2 diproses.');
    }

    public function test_completed_payment_requires_complete_numbers_and_physical_files(): void
    {
        Storage::fake('public');
        $parent = $this->parent(LhppBast::APPROVAL_APPROVED, 0);

        try {
            app(LpjPplUpdateService::class)->update($parent->id, [
                'selected_termin' => 1,
                'lpj_number' => 'LPJ-1',
                'ppl_number' => '',
                'termin1_status' => 'sudah',
            ]);
            $this->fail('ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Pembayaran hanya dapat ditandai selesai setelah nomor dan dokumen LPJ/PPL lengkap.',
                $exception->errors()['termin1_status'][0],
            );
        }

        $this->assertDatabaseMissing('lpj_ppls', ['lhpp_bast_id' => $parent->id]);
        $this->assertSame('belum', $parent->fresh()->termin1_status);
    }

    public function test_complete_package_can_be_saved_and_only_active_termin_changes(): void
    {
        Storage::fake('public');
        $actor = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $parent = $this->parent(LhppBast::APPROVAL_APPROVED, 0);
        $parent->update(['termin2_status' => 'sudah']);

        $result = app(LpjPplUpdateService::class)->update($parent->id, [
            'selected_termin' => 1,
            'lpj_number' => ' LPJ-1 ',
            'ppl_number' => ' PPL-1 ',
            'lpj_document' => UploadedFile::fake()->create('lpj.pdf', 10, 'application/pdf'),
            'ppl_document' => UploadedFile::fake()->create('ppl.pdf', 10, 'application/pdf'),
            'termin1_status' => 'sudah',
            'termin2_status' => 'belum',
        ], $actor->id);

        $this->assertSame('LPJ-1', $result->lpj_number_termin1);
        $this->assertSame('PPL-1', $result->ppl_number_termin1);
        $this->assertSame('sudah', $parent->fresh()->termin1_status);
        $this->assertSame('sudah', $parent->fresh()->termin2_status);
        Storage::disk('public')->assertExists($result->lpj_document_path_termin1);
        Storage::disk('public')->assertExists($result->ppl_document_path_termin1);
    }

    public function test_termin_two_update_does_not_change_termin_one_data_or_status(): void
    {
        Storage::fake('public');
        $parent = $this->parent(LhppBast::APPROVAL_APPROVED, 3, 'sudah');
        $this->terminTwo($parent, LhppBast::APPROVAL_APPROVED);
        $existing = LpjPpl::query()->create([
            'lhpp_bast_id' => $parent->id,
            'lpj_number_termin1' => 'LPJ-T1-LAMA',
            'ppl_number_termin1' => 'PPL-T1-LAMA',
        ]);

        $result = app(LpjPplUpdateService::class)->update($parent->id, [
            'selected_termin' => 2,
            'lpj_number' => 'LPJ-T2',
            'ppl_number' => 'PPL-T2',
            'termin1_status' => 'belum',
            'termin2_status' => 'belum',
        ]);

        $this->assertSame($existing->id, $result->id);
        $this->assertSame('LPJ-T1-LAMA', $result->lpj_number_termin1);
        $this->assertSame('PPL-T1-LAMA', $result->ppl_number_termin1);
        $this->assertSame('sudah', $parent->fresh()->termin1_status);
        $this->assertSame('belum', $parent->fresh()->termin2_status);
        $this->assertSame(1, LpjPpl::query()->where('lhpp_bast_id', $parent->id)->count());
    }

    private function assertValidationMessage(LhppBast $parent, int $termin, string $message): void
    {
        try {
            app(LpjPplUpdateService::class)->update($parent->id, [
                'selected_termin' => $termin,
                "termin{$termin}_status" => 'belum',
            ]);
            $this->fail('ValidationException was not thrown.');
        } catch (ValidationException $exception) {
            $this->assertContains($message, collect($exception->errors())->flatten()->all());
        }
    }

    private function parent(string $status, ?int $warrantyMonths, string $terminOneStatus = 'belum'): LhppBast
    {
        $creator = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $number = 'LPJ-'.uniqid();
        $order = Order::query()->create([
            'nomor_order' => $number,
            'nama_pekerjaan' => 'Pekerjaan LPJ',
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Deskripsi',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-08-01',
            'target_selesai' => '2026-08-10',
            'created_by' => $creator->id,
        ]);
        $parent = LhppBast::query()->create([
            'order_id' => $order->id,
            'termin_type' => 'termin_1',
            'nomor_order' => $number,
            'tanggal_bast' => '2026-08-01',
            'approval_status' => $status,
            'termin1_status' => $terminOneStatus,
            'termin2_status' => 'belum',
            'created_by' => $creator->id,
        ]);

        if ($warrantyMonths !== null) {
            Garansi::query()->create([
                'order_id' => $order->id,
                'lhpp_bast_id' => $parent->id,
                'garansi_months' => $warrantyMonths,
                'start_date' => '2026-08-01',
                'created_by' => $creator->id,
            ]);
        }

        return $parent;
    }

    private function terminTwo(LhppBast $parent, string $status): LhppBast
    {
        return LhppBast::query()->create([
            'order_id' => $parent->order_id,
            'parent_lhpp_bast_id' => $parent->id,
            'termin_type' => 'termin_2',
            'nomor_order' => $parent->nomor_order,
            'tanggal_bast' => '2026-08-02',
            'approval_status' => $status,
        ]);
    }
}
