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

    public function test_special_area_uses_existing_inside_case_family_without_extending_static_matrix(): void
    {
        $this->assertSame(
            'KONS-DALAM-OVER250',
            HppApprovalFlow::resolvePreviewCase('Konstruksi', HppApprovalFlow::SPECIAL_AREA_LABEL, 'over'),
        );
        $this->assertArrayHasKey(HppApprovalFlow::SPECIAL_AREA_LABEL, HppApprovalFlow::areaOptions());
        $this->assertArrayNotHasKey(HppApprovalFlow::SPECIAL_AREA_KEY, HppApprovalFlow::flowMatrix()['Fabrikasi']);
        $this->assertArrayNotHasKey(HppApprovalFlow::SPECIAL_AREA_KEY, HppApprovalFlow::flowMatrix()['Konstruksi']);
    }
}
