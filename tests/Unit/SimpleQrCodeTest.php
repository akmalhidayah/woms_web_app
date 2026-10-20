<?php

namespace Tests\Unit;

use App\Support\Inspector\SimpleQrCode;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class SimpleQrCodeTest extends TestCase
{
    public function test_it_builds_a_square_qr_matrix_and_svg_with_quiet_zone(): void
    {
        $qr = new SimpleQrCode;
        $url = 'https://woms.test/equipment/q/abcdefghijklmnopqrstuvwx12345678';
        $matrix = $qr->matrix($url);

        $this->assertContains(count($matrix), [21, 25, 29, 33, 37, 41]);
        $this->assertCount(count($matrix), $matrix[0]);
        $this->assertTrue($matrix[0][0]);
        $this->assertFalse($matrix[0][7]);
        $this->assertStringContainsString('<svg', $qr->svg($url));
        $this->assertStringContainsString('shape-rendering="crispEdges"', $qr->svg($url));
    }

    public function test_it_rejects_urls_outside_the_bounded_encoder_capacity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new SimpleQrCode)->matrix(str_repeat('x', 200));
    }
}
