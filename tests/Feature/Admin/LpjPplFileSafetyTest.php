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

class LpjPplFileSafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_replacement_commits_new_path_before_removing_old_file(): void
    {
        Storage::fake('public');
        [$parent, $lpjPpl] = $this->record();
        Storage::disk('public')->put('lpj-ppl/old-lpj.pdf', 'old');

        $updated = app(LpjPplUpdateService::class)->update($parent->id, [
            'selected_termin' => 1,
            'lpj_number' => 'LPJ-1',
            'ppl_number' => 'PPL-1',
            'lpj_document' => UploadedFile::fake()->create('replacement.pdf', 10, 'application/pdf'),
            'termin1_status' => 'belum',
        ]);

        $this->assertNotSame('lpj-ppl/old-lpj.pdf', $updated->lpj_document_path_termin1);
        Storage::disk('public')->assertExists($updated->lpj_document_path_termin1);
        Storage::disk('public')->assertMissing('lpj-ppl/old-lpj.pdf');
        Storage::disk('public')->assertExists($lpjPpl->ppl_document_path_termin1);
    }

    public function test_failed_final_package_validation_preserves_old_file_and_cleans_new_file(): void
    {
        Storage::fake('public');
        [$parent, $lpjPpl] = $this->record();

        try {
            app(LpjPplUpdateService::class)->update($parent->id, [
                'selected_termin' => 1,
                'lpj_number' => 'LPJ-1',
                'ppl_number' => '',
                'lpj_document' => UploadedFile::fake()->create('replacement.pdf', 10, 'application/pdf'),
                'termin1_status' => 'sudah',
            ]);
            $this->fail('ValidationException was not thrown.');
        } catch (ValidationException) {
            Storage::disk('public')->assertExists('lpj-ppl/old-lpj.pdf');
            $this->assertSame('lpj-ppl/old-lpj.pdf', $lpjPpl->fresh()->lpj_document_path_termin1);
            $this->assertCount(2, Storage::disk('public')->allFiles('lpj-ppl'));
        }
    }

    public function test_document_removal_happens_after_valid_database_update(): void
    {
        Storage::fake('public');
        [$parent, $lpjPpl] = $this->record();

        $updated = app(LpjPplUpdateService::class)->update($parent->id, [
            'selected_termin' => 1,
            'lpj_number' => 'LPJ-1',
            'ppl_number' => 'PPL-1',
            'remove_lpj_document' => true,
            'termin1_status' => 'belum',
        ]);

        $this->assertNull($updated->lpj_document_path_termin1);
        Storage::disk('public')->assertMissing($lpjPpl->lpj_document_path_termin1);
        Storage::disk('public')->assertExists($lpjPpl->ppl_document_path_termin1);
    }

    public function test_database_failure_cleans_new_file_and_preserves_old_path(): void
    {
        Storage::fake('public');
        [$parent, $lpjPpl] = $this->record();
        $thrown = null;

        try {
            app(LpjPplUpdateService::class)->update($parent->id, [
                'selected_termin' => 1,
                'lpj_number' => 'LPJ-1',
                'ppl_number' => 'PPL-1',
                'lpj_document' => UploadedFile::fake()->create('replacement.pdf', 10, 'application/pdf'),
                'termin1_status' => 'belum',
            ], PHP_INT_MAX);
        } catch (\Throwable $exception) {
            $thrown = $exception;
        }

        $this->assertNotNull($thrown, 'Database exception was not thrown.');
        $this->assertSame('lpj-ppl/old-lpj.pdf', $lpjPpl->fresh()->lpj_document_path_termin1);
        Storage::disk('public')->assertExists('lpj-ppl/old-lpj.pdf');
        Storage::disk('public')->assertExists('lpj-ppl/old-ppl.pdf');
        $this->assertCount(2, Storage::disk('public')->allFiles('lpj-ppl'));
    }

    public function test_removal_is_rejected_when_paid_package_would_be_incomplete(): void
    {
        Storage::fake('public');
        [$parent, $lpjPpl] = $this->record();
        $parent->update(['termin1_status' => 'sudah']);

        $this->expectException(ValidationException::class);

        try {
            app(LpjPplUpdateService::class)->update($parent->id, [
                'selected_termin' => 1,
                'lpj_number' => 'LPJ-1',
                'ppl_number' => 'PPL-1',
                'remove_lpj_document' => true,
                'termin1_status' => 'sudah',
            ]);
        } finally {
            Storage::disk('public')->assertExists($lpjPpl->lpj_document_path_termin1);
            $this->assertSame($lpjPpl->lpj_document_path_termin1, $lpjPpl->fresh()->lpj_document_path_termin1);
        }
    }

    /** @return array{LhppBast, LpjPpl} */
    private function record(): array
    {
        $creator = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $number = 'FILE-'.uniqid();
        $order = Order::query()->create([
            'nomor_order' => $number,
            'nama_pekerjaan' => 'Pekerjaan File',
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
            'approval_status' => LhppBast::APPROVAL_APPROVED,
            'termin1_status' => 'belum',
            'termin2_status' => 'belum',
            'created_by' => $creator->id,
        ]);
        Garansi::query()->create([
            'order_id' => $order->id,
            'lhpp_bast_id' => $parent->id,
            'garansi_months' => 0,
            'start_date' => '2026-08-01',
            'created_by' => $creator->id,
        ]);
        $lpjPpl = LpjPpl::query()->create([
            'lhpp_bast_id' => $parent->id,
            'lpj_number_termin1' => 'LPJ-1',
            'ppl_number_termin1' => 'PPL-1',
            'lpj_document_path_termin1' => 'lpj-ppl/old-lpj.pdf',
            'ppl_document_path_termin1' => 'lpj-ppl/old-ppl.pdf',
        ]);
        Storage::disk('public')->put('lpj-ppl/old-lpj.pdf', 'old');
        Storage::disk('public')->put('lpj-ppl/old-ppl.pdf', 'old');

        return [$parent, $lpjPpl];
    }
}
