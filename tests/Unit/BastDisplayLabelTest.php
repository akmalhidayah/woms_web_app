<?php

namespace Tests\Unit;

use App\Support\BastDisplayLabel;
use PHPUnit\Framework\TestCase;

class BastDisplayLabelTest extends TestCase
{
    public function test_without_warranty_uses_single_payment_labels(): void
    {
        $this->assertTrue(BastDisplayLabel::isWithoutWarranty(0));
        $this->assertSame('BAST/LHPP', BastDisplayLabel::bastLabel('termin_1', 0));
        $this->assertSame('BAST', BastDisplayLabel::bastLabel('termin_1', 0, false));
        $this->assertSame('Pembayaran', BastDisplayLabel::stageLabel('termin_1', 0));
        $this->assertSame('Pembayaran', BastDisplayLabel::shortStageLabel('termin_1', 0));
        $this->assertSame('LPJ', BastDisplayLabel::documentLabel('lpj', 'termin_1', 0));
        $this->assertSame('PPL', BastDisplayLabel::documentLabel('ppl', 'termin_1', 0));
        $this->assertSame('ORDER-001', BastDisplayLabel::approvalDocumentNumber('ORDER-001', 'termin_1', 0));
        $this->assertSame('bast-ORDER-001.pdf', BastDisplayLabel::generatedBastPdfFilename('ORDER-001', 'termin_1', 0));
    }

    public function test_warranty_and_unknown_warranty_keep_termin_one_labels(): void
    {
        $this->assertFalse(BastDisplayLabel::isWithoutWarranty(null));
        $this->assertSame('Termin 1', BastDisplayLabel::stageLabel('termin_1', 3));
        $this->assertSame('Termin 1', BastDisplayLabel::stageLabel('termin_1', null));
        $this->assertSame('BAST/LHPP Termin 1', BastDisplayLabel::bastLabel('termin_1', 3));
        $this->assertSame('bast-termin-1-ORDER-001.pdf', BastDisplayLabel::generatedBastPdfFilename('ORDER-001', 'termin_1', 3));
    }

    public function test_termin_two_always_keeps_termin_two_labels(): void
    {
        $this->assertSame('Termin 2', BastDisplayLabel::stageLabel('termin_2', 0));
        $this->assertSame('T2', BastDisplayLabel::shortStageLabel('termin_2', 0));
        $this->assertSame('LPJ Termin 2', BastDisplayLabel::documentLabel('lpj', 'termin_2', 0));
        $this->assertSame('bast-termin-2-ORDER-001.pdf', BastDisplayLabel::generatedBastPdfFilename('ORDER-001', 'termin_2', 0));
    }
}
