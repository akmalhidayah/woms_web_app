<?php

namespace App\Support;

use App\Models\HppSignature;
use Throwable;

class HppSignatureIdentityResolver
{
    public function __construct(
        private readonly HppApproverResolver $approverResolver,
    ) {}

    /**
     * @return array{name: string, position: string, department: ?string, unit: ?string, section: ?string}
     */
    public function resolve(HppSignature $signature): array
    {
        if ($signature->isSigned()) {
            return [
                'name' => $this->filledOrFallback($signature->signer_name_snapshot, 'N/A'),
                'position' => $this->filledOrFallback(
                    $signature->signer_position_snapshot,
                    $signature->displayRoleLabel(),
                ),
                'department' => $this->nullableString($signature->signer_department_snapshot),
                'unit' => $this->nullableString($signature->signer_unit_snapshot),
                'section' => $this->nullableString($signature->signer_section_snapshot),
            ];
        }

        $signature->loadMissing(['signer', 'hpp']);
        $currentApprover = $this->currentApprover($signature);

        return [
            'name' => $this->filledOrFallback(
                $signature->signer?->name,
                'N/A',
            ),
            'position' => $this->filledOrFallback(
                $signature->acting_as_label,
                $this->filledOrFallback(
                    $currentApprover['position'] ?? null,
                    $signature->displayRoleLabel(),
                ),
            ),
            'department' => $this->nullableString($currentApprover['department'] ?? null),
            'unit' => $this->nullableString($currentApprover['unit'] ?? null),
            'section' => $this->nullableString($currentApprover['section'] ?? null),
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public function snapshotAttributes(HppSignature $signature): array
    {
        $identity = $this->resolve($signature);

        return [
            'signer_name_snapshot' => $identity['name'],
            'signer_position_snapshot' => $identity['position'],
            'signer_department_snapshot' => $identity['department'],
            'signer_unit_snapshot' => $identity['unit'],
            'signer_section_snapshot' => $identity['section'],
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function currentApprover(HppSignature $signature): ?array
    {
        if (! $signature->hpp) {
            return null;
        }

        try {
            return $this->approverResolver->resolveApproverByRoleKey(
                $signature->hpp,
                (string) $signature->role_key,
                (string) $signature->role_label,
            );
        } catch (Throwable) {
            return null;
        }
    }

    private function filledOrFallback(mixed $value, string $fallback): string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : $fallback;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }
}
