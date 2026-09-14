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

    public function test_stock_status_uses_only_spare_stock_without_treating_invalid_values_as_zero(): void
    {
        self::assertSame('habis', $this->stockStatus(0));
        self::assertSame('habis', $this->stockStatus(-3));
        self::assertSame('tersedia', $this->stockStatus(1));
        self::assertSame('tersedia', $this->stockStatus('13,500'));
        self::assertNull($this->stockStatus(''));
        self::assertNull($this->stockStatus('#VALUE!'));
        self::assertNull($this->stockStatus(null));
    }

    public function test_dates_support_sheet_serials_and_keep_invalid_or_original_text(): void
    {
        self::assertSame('1899-12-30 12:00:00', ConsumableData::date(0.5)?->format('Y-m-d H:i:s'));
        self::assertSame('2026-09-09 14:15:16', ConsumableData::date('09/09/2026 14:15:16')?->format('Y-m-d H:i:s'));
        self::assertSame('2026-09-09 14:15:00', ConsumableData::date('09/09/2026 14:15')?->format('Y-m-d H:i:s'));
        self::assertSame('2026-09-09', ConsumableData::date('2026-09-09')?->format('Y-m-d'));
        self::assertSame('2026-09-09 14:15:16', ConsumableData::date('2026-09-09T14:15:16+00:00')?->format('Y-m-d H:i:s'));
        self::assertSame(
            ConsumableData::date('31/12/2026 15:30:00')?->getTimestamp(),
            ConsumableData::dateTimestamp('31/12/2026 15:30:00'),
        );
        self::assertNull(ConsumableData::date('31/02/2026'));
        self::assertNull(ConsumableData::date("2026-09-09\0"));
        self::assertNull(ConsumableData::date(''));
        self::assertSame(' 09/09/2026 ', ConsumableData::displayDate(' 09/09/2026 '));
        self::assertSame('30/12/1899 12:00:00', ConsumableData::displayDate(0.5));
    }

    private function stockStatus(mixed $spareStock): ?string
    {
        return ConsumableData::stockStatus([
            'SPARE STOCK' => $spareStock,
        ]);
    }
}
