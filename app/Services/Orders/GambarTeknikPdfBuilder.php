<?php

namespace App\Services\Orders;

use Illuminate\Http\UploadedFile;
use RuntimeException;
use setasign\Fpdi\Fpdi;
use Throwable;

/**
 * Menggabungkan beberapa file Gambar Teknik (PDF/JPG) menjadi satu PDF.
 *
 * Berbeda dengan PdfMergeService, builder ini TIDAK diam-diam melewati file yang gagal:
 * satu file bermasalah menghentikan proses agar tidak ada gambar yang hilang tanpa disadari.
 */
class GambarTeknikPdfBuilder
{
    private const PAGE_MARGIN_MM = 10.0;

    private const A4_WIDTH_MM = 210.0;

    private const A4_HEIGHT_MM = 297.0;

    /**
     * @param  list<UploadedFile>  $files  Urutan file menentukan urutan halaman.
     * @return string Isi biner PDF.
     *
     * @throws RuntimeException bila ada file yang tidak dapat digabung.
     */
    public function build(array $files, string $title = ''): string
    {
        if ($files === []) {
            throw new RuntimeException('Tidak ada file Gambar Teknik untuk digabung.');
        }

        $pdf = new Fpdi;
        $pdf->SetAutoPageBreak(false);

        if ($title !== '') {
            $pdf->SetTitle($title, true);
        }

        foreach ($files as $file) {
            $path = $file->getRealPath();

            try {
                if ($path === false || ! is_file($path)) {
                    throw new RuntimeException('File upload tidak ditemukan.');
                }

                if ($this->isPdf($file)) {
                    $this->appendPdf($pdf, $path);
                } else {
                    $this->appendJpeg($pdf, $path);
                }
            } catch (Throwable $exception) {
                throw new RuntimeException(
                    sprintf(
                        'File "%s" tidak dapat digabung. Pastikan file tidak rusak/terproteksi, lalu unggah ulang atau gabungkan secara manual.',
                        $file->getClientOriginalName(),
                    ),
                    previous: $exception,
                );
            }
        }

        return $pdf->Output('S');
    }

    private function isPdf(UploadedFile $file): bool
    {
        return $file->getMimeType() === 'application/pdf'
            || strtolower($file->getClientOriginalExtension()) === 'pdf';
    }

    private function appendPdf(Fpdi $pdf, string $path): void
    {
        $pageCount = $pdf->setSourceFile($path);

        for ($pageNumber = 1; $pageNumber <= $pageCount; $pageNumber++) {
            $templateId = $pdf->importPage($pageNumber);
            $size = $pdf->getTemplateSize($templateId);
            $orientation = ($size['width'] ?? 0) > ($size['height'] ?? 0) ? 'L' : 'P';

            $pdf->AddPage($orientation, [$size['width'], $size['height']]);
            $pdf->useTemplate($templateId);
        }
    }

    private function appendJpeg(Fpdi $pdf, string $path): void
    {
        $dimensions = @getimagesize($path);

        if ($dimensions === false || ($dimensions[0] ?? 0) < 1 || ($dimensions[1] ?? 0) < 1) {
            throw new RuntimeException('Gambar tidak valid.');
        }

        [$imageWidth, $imageHeight] = $dimensions;
        $isLandscape = $imageWidth > $imageHeight;
        $pageWidth = $isLandscape ? self::A4_HEIGHT_MM : self::A4_WIDTH_MM;
        $pageHeight = $isLandscape ? self::A4_WIDTH_MM : self::A4_HEIGHT_MM;
        $maxWidth = $pageWidth - (self::PAGE_MARGIN_MM * 2);
        $maxHeight = $pageHeight - (self::PAGE_MARGIN_MM * 2);

        $scale = min($maxWidth / $imageWidth, $maxHeight / $imageHeight);
        $drawWidth = $imageWidth * $scale;
        $drawHeight = $imageHeight * $scale;

        $pdf->AddPage($isLandscape ? 'L' : 'P', 'A4');
        $pdf->Image(
            $path,
            ($pageWidth - $drawWidth) / 2,
            ($pageHeight - $drawHeight) / 2,
            $drawWidth,
            $drawHeight,
            'JPG',
        );
    }
}
