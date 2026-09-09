<?php

namespace Tests\Unit\AppSheet;

use App\Support\AppSheet\ConsumableData;
use PHPUnit\Framework\TestCase;

class ConsumableDataTest extends TestCase
{
    public function test_numbers_preserve_zero_and_parse_thousands_without_guessing_invalid_text(): void
    {
        self::assertSame(925.0, ConsumableData::number(925));
        self::assertSame(13500.0, ConsumableData::number('13,500'));
        self::assertSame(0.0, ConsumableData::number('0'));
        self::assertSame(13500.25, ConsumableData::number('13,500.25'));
        self::assertNull(ConsumableData::number(''));
        self::assertNull(ConsumableData::number('13,50'));
        self::assertNull(ConsumableData::number('#VALUE!'));
    }

    public function test_stock_uses_both_quantities_and_respects_minimum_boundary(): void
    {
        self::assertSame('habis', $this->status('', 0, ''));
        self::assertSame('habis', $this->status(-5, 2, 10));
        self::assertSame('rendah', $this->status(3, 2, 5));
        self::assertSame('aman', $this->status(3, 3, 5));
        self::assertSame('aman', $this->status('13,500', 0, 100));
        self::assertSame('aman', $this->status(1, '', ''));
        self::assertNull($this->status('#VALUE!', 10, 5));
        self::assertNull($this->status(10, 0, 'unknown'));
    }

    public function test_dates_support_sheet_serials_and_keep_invalid_or_original_text(): void
    {
        self::assertSame('1899-12-30 12:00:00', ConsumableData::date(0.5)?->format('Y-m-d H:i:s'));
        self::assertSame('2026-09-09 14:15:16', ConsumableData::date('09/09/2026 14:15:16')?->format('Y-m-d H:i:s'));
        self::assertSame('2026-09-09', ConsumableData::date('2026-09-09')?->format('Y-m-d'));
        self::assertNull(ConsumableData::date('31/02/2026'));
        self::assertNull(ConsumableData::date("2026-09-09\0"));
        self::assertNull(ConsumableData::date(''));
        self::assertSame(' 09/09/2026 ', ConsumableData::displayDate(' 09/09/2026 '));
        self::assertSame('30/12/1899 12:00:00', ConsumableData::displayDate(0.5));
    }

    private function status(mixed $consignment, mixed $nonConsignment, mixed $minimum): ?string
    {
        return ConsumableData::stockStatus([
            'QTY KONSINYASI' => $consignment,
            'QTY NON KONSINYASI' => $nonConsignment,
            'MIN' => $minimum,
        ]);
    }
}
