<?php

namespace App\Services\Pkm;

use App\Models\LhppBast;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use setasign\Fpdi\Fpdi;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class BastPdfAttachmentService
{
    public function assertReadable(UploadedFile $file): void
    {
        $path = $file->getRealPath();

        if (! is_string($path) || $path === '' || ! is_file($path)) {
            throw new RuntimeException('File lampiran BAST tidak dapat dibaca.');
        }

        try {
            $pdf = new Fpdi;
            $pageCount = $pdf->setSourceFile($path);

            if ($pageCount < 1) {
                throw new RuntimeException('Lampiran BAST tidak memiliki halaman PDF.');
            }

            for ($page = 1; $page <= $pageCount; $page++) {
                $pdf->importPage($page);
            }
        } catch (Throwable $exception) {
            throw new RuntimeException('Lampiran BAST harus berupa PDF yang dapat dibuka dan digabungkan.', previous: $exception);
        }
    }

    public function pdfOutput(?LhppBast $lhpp): ?string
    {
        if (! $lhpp || blank($lhpp->attachment_pdf_path)) {
            return null;
        }

        $disk = Storage::disk('public');
        $path = ltrim((string) $lhpp->attachment_pdf_path, '/');

        if (! $disk->exists($path)) {
            throw new HttpException(404, 'Lampiran PDF BAST tidak ditemukan.');
        }

        $mimeType = $lhpp->attachment_pdf_mime_type ?: $disk->mimeType($path);
        if (! str_contains(strtolower((string) $mimeType), 'pdf')) {
            throw new HttpException(422, 'Lampiran BAST harus berupa PDF.');
        }

        $contents = $disk->get($path);
        if (! is_string($contents) || $contents === '') {
            throw new HttpException(404, 'Lampiran PDF BAST tidak dapat dibaca.');
        }

        return $contents;
    }
}
