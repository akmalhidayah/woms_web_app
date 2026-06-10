<?php

namespace Tests\Unit;

use App\Support\HppApprovalFlow;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HppApprovalFlowTest extends TestCase
{
    /**
     * @return list<array{int|float|string, string}>
     */
    public static function totalBuckets(): array
    {
        return [
            [0, 'under'],
            [249999999.99, 'under'],
            ['250000000.00', 'under'],
            ['250000000.01', 'over'],
            [300000000, 'over'],
        ];
    }

    #[DataProvider('totalBuckets')]
    public function test_bucket_is_derived_from_total(int|float|string $total, string $expected): void
    {
        $this->assertSame($expected, HppApprovalFlow::resolveBucketFromTotal($total));
    }
}
