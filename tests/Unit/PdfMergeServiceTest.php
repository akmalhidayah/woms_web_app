<?php

namespace Tests\Unit;

use App\Support\PdfMergeService;
use Tests\TestCase;

class PdfMergeServiceTest extends TestCase
{
    public function test_incompatible_pdf_is_skipped_when_valid_pdf_is_available(): void
    {
        $validPdf = $this->pdfOutput('Dokumen valid');

        $merged = (new PdfMergeService)->merge([
            "%PDF-1.7\nfile rusak",
            $validPdf,
        ]);

        $this->assertStringStartsWith('%PDF-', $merged);
        $this->assertNotSame("%PDF-1.7\nfile rusak", $merged);
    }

    public function test_first_document_is_returned_when_no_pdf_can_be_parsed(): void
    {
        $firstDocument = "%PDF-1.7\nfile pertama";

        $merged = (new PdfMergeService)->merge([
            $firstDocument,
            "%PDF-1.7\nfile kedua",
        ]);

        $this->assertSame($firstDocument, $merged);
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
