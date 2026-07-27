<?php

namespace App\Support;

use App\Models\HppSignature;

class HppApprovalMarkResolver
{
    public const TYPE_INITIAL = 'initial';

    public const TYPE_SIGNATURE = 'signature';

    public const INITIAL_ROLE_KEYS = [
        'manager_peminta',
        'manager_pengendali',
        'manager_counter_part',
    ];

    public function typeFor(HppSignature $signature): string
    {
        return in_array((string) $signature->role_key, self::INITIAL_ROLE_KEYS, true)
            ? self::TYPE_INITIAL
            : self::TYPE_SIGNATURE;
    }

    public function requiresInitial(HppSignature $signature): bool
    {
        return $this->typeFor($signature) === self::TYPE_INITIAL;
    }

    public function requiresFullSignature(HppSignature $signature): bool
    {
        return ! $this->requiresInitial($signature);
    }
}
