<?php

namespace App\Support;

use App\Models\LhppBast;
use App\Models\LhppBastSignature;

class BastPdfSignatureLayoutResolver
{
    /**
     * @return array{
     *     approval_cells: list<array{role_key: string, fallback_title: string, signature: ?LhppBastSignature}>,
     *     manager_pkm_signature: ?LhppBastSignature,
     *     managers_collapsed: bool
     * }
     */
    public function resolve(LhppBast $bast): array
    {
        $bast->loadMissing('signatures');
        $signatures = $bast->signatures->keyBy('role_key');
        $managerPeminta = $signatures->get('manager_peminta');
        $managerPengendali = $signatures->get('manager_pengendali');
        $managersCollapsed = $this->sameCurrentSigner($managerPeminta, $managerPengendali);

        if ($managersCollapsed) {
            $managerPengendali = $this->preferredManagerSignature($managerPeminta, $managerPengendali);
        }

        $flowRoleKeys = collect((array) $bast->approval_flow)
            ->map(fn (mixed $label): ?string => $this->roleKey((string) $label))
            ->filter()
            ->values();
        $include = fn (string $roleKey): bool => $signatures->has($roleKey) || $flowRoleKeys->contains($roleKey);
        $approvalCells = [];

        foreach ([
            ['dirops', 'Director of Operation'],
            ['gm_pengendali', 'GM Pengendali'],
            ['sm_pengendali', 'SM Pengendali'],
            ['manager_pengendali', 'Manager Pengendali'],
        ] as [$roleKey, $fallbackTitle]) {
            if (($roleKey !== 'dirops' || $bast->approval_threshold === 'over_250') && $include($roleKey)) {
                $approvalCells[] = [
                    'role_key' => $roleKey,
                    'fallback_title' => $fallbackTitle,
                    'signature' => $roleKey === 'manager_pengendali'
                        ? $managerPengendali
                        : $signatures->get($roleKey),
                ];
            }
        }

        if (! $managersCollapsed && $include('manager_peminta')) {
            $approvalCells[] = [
                'role_key' => 'manager_peminta',
                'fallback_title' => 'Manager Peminta',
                'signature' => $managerPeminta,
            ];
        }

        return [
            'approval_cells' => $approvalCells,
            'manager_pkm_signature' => $signatures->get('manager_pkm'),
            'managers_collapsed' => $managersCollapsed,
        ];
    }

    private function sameCurrentSigner(?LhppBastSignature $left, ?LhppBastSignature $right): bool
    {
        if (! $left || ! $right) {
            return false;
        }

        if ($left->signer_user_id !== null || $right->signer_user_id !== null) {
            return $left->signer_user_id !== null
                && $right->signer_user_id !== null
                && (int) $left->signer_user_id === (int) $right->signer_user_id;
        }

        foreach ([
            'signer_name_snapshot',
            'signer_position_snapshot',
            'signer_unit_snapshot',
            'signer_section_snapshot',
        ] as $attribute) {
            $leftValue = $this->normalizeSnapshot($left->{$attribute});
            $rightValue = $this->normalizeSnapshot($right->{$attribute});

            if ($leftValue === '' || $rightValue === '' || $leftValue !== $rightValue) {
                return false;
            }
        }

        return true;
    }

    private function preferredManagerSignature(
        LhppBastSignature $managerPeminta,
        LhppBastSignature $managerPengendali
    ): LhppBastSignature {
        foreach ([
            [$managerPengendali, $managerPengendali->isSigned() && filled($managerPengendali->signature_data)],
            [$managerPeminta, $managerPeminta->isSigned() && filled($managerPeminta->signature_data)],
            [$managerPengendali, $managerPengendali->isSigned()],
            [$managerPeminta, $managerPeminta->isSigned()],
            [$managerPengendali, true],
            [$managerPeminta, true],
        ] as [$signature, $matches]) {
            if ($matches) {
                return $signature;
            }
        }

        return $managerPengendali;
    }

    private function roleKey(string $label): ?string
    {
        return match (trim($label)) {
            'Manager PKM' => 'manager_pkm',
            'Manager Peminta', 'Manager User' => 'manager_peminta',
            'Manager Pengendali', 'Manager Workshop' => 'manager_pengendali',
            'SM Pengendali', 'SM PMMS' => 'sm_pengendali',
            'GM Pengendali', 'GM PMMS' => 'gm_pengendali',
            'DIROPS', 'Dirops' => 'dirops',
            default => null,
        };
    }

    private function normalizeSnapshot(mixed $value): string
    {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim((string) $value)) ?? '');
    }
}
