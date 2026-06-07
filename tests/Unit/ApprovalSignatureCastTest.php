<?php

namespace Tests\Unit;

use App\Models\HppSignature;
use App\Models\InitialWorkSignature;
use App\Models\LhppBastSignature;
use App\Models\QualityControlSignature;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ApprovalSignatureCastTest extends TestCase
{
    /**
     * @return list<array{class-string}>
     */
    public static function signatureModels(): array
    {
        return [
            [HppSignature::class],
            [InitialWorkSignature::class],
            [LhppBastSignature::class],
            [QualityControlSignature::class],
        ];
    }

    /**
     * @param class-string $modelClass
     */
    #[DataProvider('signatureModels')]
    public function test_signer_user_id_is_always_cast_to_integer(string $modelClass): void
    {
        $signature = new $modelClass();
        $signature->setRawAttributes(['signer_user_id' => '67']);

        $this->assertSame(67, $signature->signer_user_id);
    }
}
