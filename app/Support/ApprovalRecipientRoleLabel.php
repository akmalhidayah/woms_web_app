<?php

namespace App\Support;

use App\Models\HppSignature;
use App\Models\InitialWorkSignature;
use App\Models\LhppBastSignature;
use App\Models\QualityControlSignature;

class ApprovalRecipientRoleLabel
{
    public static function for(
        HppSignature|InitialWorkSignature|LhppBastSignature|QualityControlSignature $signature
    ): string {
        $roleKey = trim((string) $signature->role_key);
        $fallback = trim((string) $signature->displayRoleLabel());

        if (self::isSeniorManager($roleKey)) {
            return self::withContext(
                'SM',
                self::firstFilled($signature, ['signer_unit_snapshot', 'source_unit']),
                'SM',
            );
        }

        if (self::isGeneralManager($roleKey)) {
            return self::withContext(
                'GM',
                self::firstFilled($signature, ['signer_department_snapshot', 'source_department']),
                'GM',
            );
        }

        if (self::isManager($roleKey)) {
            return self::withContext(
                'Manager',
                self::firstFilled($signature, ['signer_section_snapshot', 'source_section']),
                'Manager',
            );
        }

        return $fallback;
    }

    private static function isManager(string $roleKey): bool
    {
        return in_array($roleKey, [
            'manager',
            'manager_pkm',
            'manager_peminta',
            'manager_pengendali',
            'manager_counter_part',
            'workshop_manager',
            'user_manager',
            'workshop_manager_pengendali',
        ], true);
    }

    private static function isSeniorManager(string $roleKey): bool
    {
        return $roleKey === 'senior_manager'
            || str_starts_with($roleKey, 'sm_')
            || str_contains($roleKey, '_sm_');
    }

    private static function isGeneralManager(string $roleKey): bool
    {
        return str_starts_with($roleKey, 'gm_')
            || str_contains($roleKey, '_gm_');
    }

    /**
     * @param  list<string>  $attributes
     */
    private static function firstFilled(
        HppSignature|InitialWorkSignature|LhppBastSignature|QualityControlSignature $signature,
        array $attributes,
    ): string {
        foreach ($attributes as $attribute) {
            $value = trim((string) $signature->getAttribute($attribute));

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private static function withContext(string $prefix, string $context, string $fallback): string
    {
        return $context !== '' ? $prefix.' '.$context : $fallback;
    }
}
