<?php

namespace Tests\Unit;

use App\Models\HppSignature;
use App\Support\HppApprovalMarkResolver;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class HppApprovalMarkResolverTest extends TestCase
{
    #[DataProvider('roleCases')]
    public function test_role_key_determines_approval_mark(string $roleKey, string $expectedType): void
    {
        $signature = new HppSignature([
            'role_key' => $roleKey,
            'acting_as_label' => 'Workshop Manager Acting Label',
            'signer_user_id' => 999,
        ]);
        $resolver = new HppApprovalMarkResolver;

        $this->assertSame($expectedType, $resolver->typeFor($signature));
        $this->assertSame(
            $expectedType === HppApprovalMarkResolver::TYPE_INITIAL,
            $resolver->requiresInitial($signature)
        );
    }

    public static function roleCases(): array
    {
        return [
            'manager peminta' => ['manager_peminta', HppApprovalMarkResolver::TYPE_INITIAL],
            'manager pengendali' => ['manager_pengendali', HppApprovalMarkResolver::TYPE_INITIAL],
            'manager counter part' => ['manager_counter_part', HppApprovalMarkResolver::TYPE_INITIAL],
            'workshop manager' => ['workshop_manager_pengendali', HppApprovalMarkResolver::TYPE_SIGNATURE],
            'workshop sm' => ['workshop_sm_pengendali', HppApprovalMarkResolver::TYPE_SIGNATURE],
            'workshop gm' => ['workshop_gm_pengendali', HppApprovalMarkResolver::TYPE_SIGNATURE],
            'sm peminta' => ['sm_peminta', HppApprovalMarkResolver::TYPE_SIGNATURE],
            'gm peminta' => ['gm_peminta', HppApprovalMarkResolver::TYPE_SIGNATURE],
            'sm pengendali' => ['sm_pengendali', HppApprovalMarkResolver::TYPE_SIGNATURE],
            'gm pengendali' => ['gm_pengendali', HppApprovalMarkResolver::TYPE_SIGNATURE],
            'planner control' => ['planner_control', HppApprovalMarkResolver::TYPE_SIGNATURE],
            'dirops' => ['dirops', HppApprovalMarkResolver::TYPE_SIGNATURE],
            'unknown role defaults full' => ['other_hpp_role', HppApprovalMarkResolver::TYPE_SIGNATURE],
        ];
    }
}
