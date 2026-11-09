<?php

namespace Tests\Feature\Admin\Orders;

use App\Domain\Orders\Enums\OrderDocumentType;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;
use Tests\TestCase;

class OrderGambarTeknikUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_multiple_gambar_teknik_files_are_merged_into_one_pdf(): void
    {
        Storage::fake('local');
        [$admin, $order] = $this->adminAndOrder('ORD-GT-MULTI');

        $this->actingAs($admin)
            ->post(route('admin.orders.documents.store', $order), [
                'gambar_teknik_file' => [
                    $this->pdfFile('a.pdf', 2),
                    UploadedFile::fake()->image('b.jpg', 600, 400),
                    $this->pdfFile('c.pdf', 1),
                ],
            ])
            ->assertSessionHasNoErrors();

        $documents = $order->documents()->where('jenis_dokumen', OrderDocumentType::GambarTeknik->value)->get();
        $this->assertCount(1, $documents);

        $document = $documents->first();
        $this->assertStringEndsWith('.pdf', $document->nama_file_asli);
        Storage::disk('local')->assertExists($document->path_file);

        $merged = Storage::disk('local')->get($document->path_file);
        $this->assertStringStartsWith('%PDF', $merged);
        $this->assertSame(4, (new Fpdi)->setSourceFile(Storage::disk('local')->path($document->path_file)));
    }

    public function test_single_gambar_teknik_file_is_stored_as_is(): void
    {
        Storage::fake('local');
        [$admin, $order] = $this->adminAndOrder('ORD-GT-SINGLE');

        $this->actingAs($admin)
            ->post(route('admin.orders.documents.store', $order), [
                'gambar_teknik_file' => [UploadedFile::fake()->image('only.jpg', 300, 300)],
            ])
            ->assertSessionHasNoErrors();

        $document = $order->documents()->where('jenis_dokumen', OrderDocumentType::GambarTeknik->value)->firstOrFail();
        $this->assertSame('only.jpg', $document->nama_file_asli);
    }

    public function test_doc_and_docx_are_rejected(): void
    {
        Storage::fake('local');
        [$admin, $order] = $this->adminAndOrder('ORD-GT-DOCX');

        $this->actingAs($admin)
            ->post(route('admin.orders.documents.store', $order), [
                'gambar_teknik_file' => [
                    UploadedFile::fake()->create('gambar.docx', 24, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'),
                ],
            ])
            ->assertSessionHasErrors('gambar_teknik_file.0');

        $this->assertFalse($order->documents()->where('jenis_dokumen', OrderDocumentType::GambarTeknik->value)->exists());
    }

    public function test_unmergeable_file_keeps_existing_document_and_reports_error(): void
    {
        Storage::fake('local');
        [$admin, $order] = $this->adminAndOrder('ORD-GT-BROKEN');

        $this->actingAs($admin)
            ->post(route('admin.orders.documents.store', $order), [
                'gambar_teknik_file' => [UploadedFile::fake()->image('old.jpg', 300, 300)],
            ]);

        $this->actingAs($admin)
            ->post(route('admin.orders.documents.store', $order), [
                'gambar_teknik_file' => [
                    $this->pdfFile('ok.pdf', 1),
                    UploadedFile::fake()->createWithContent('rusak.pdf', "%PDF-1.4\nrusak tanpa struktur"),
                ],
            ])
            ->assertSessionHasErrors('gambar_teknik_file');

        $document = $order->documents()->where('jenis_dokumen', OrderDocumentType::GambarTeknik->value)->firstOrFail();
        $this->assertSame('old.jpg', $document->nama_file_asli);
    }

    /**
     * @return array{0: User, 1: Order}
     */
    private function adminAndOrder(string $nomorOrder): array
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'admin_role' => User::ADMIN_ROLE_SUPER_ADMIN,
        ]);
        $order = Order::query()->create([
            'nomor_order' => $nomorOrder,
            'nama_pekerjaan' => 'Pekerjaan Gambar Teknik',
            'unit_kerja' => 'Unit Test',
            'seksi' => 'Seksi Test',
            'deskripsi' => 'Detail pekerjaan test',
            'prioritas' => Order::PRIORITY_MEDIUM,
            'tanggal_order' => '2026-06-01',
            'target_selesai' => '2026-06-10',
            'created_by' => $admin->id,
        ]);

        return [$admin, $order];
    }

    private function pdfFile(string $name, int $pages): UploadedFile
    {
        $pdf = new Fpdi;

        for ($i = 0; $i < $pages; $i++) {
            $pdf->AddPage();
        }

        return UploadedFile::fake()->createWithContent($name, $pdf->Output('S'));
    }
}
