<?php

namespace App\Services\InitialWorks;

use App\Models\InitialWork;
use App\Models\InitialWorkSignature;
use App\Models\OutlineAgreement;
use App\Models\UnitWork;
use App\Models\UnitWorkSection;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InitialWorkSignatureService
{
    private const TOKEN_TTL_DAYS = 7;

    /**
     * Create the Manager and Senior Manager signature chain.
     *
     * @return array{manager_url: ?string, manager_signature: ?InitialWorkSignature, senior_signature: ?InitialWorkSignature}
     */
    public function createSignatureChain(InitialWork $initialWork): array
    {
        return DB::transaction(function () use ($initialWork): array {
            $initialWork->loadMissing([
                'outlineAgreement.unitWork.department',
                'outlineAgreement.unitWork.seniorManager',
                'outlineAgreement.unitWork.sections.manager',
            ]);

            $source = $this->resolveSignatureSource($initialWork);

            $managerSignature = $this->createSignatureRecord(
                $initialWork,
                InitialWorkSignature::ROLE_MANAGER,
                1,
                $source['manager'],
                $source['manager_label'],
                $source,
                InitialWorkSignature::STATUS_PENDING,
            );

            $seniorSignature = $this->createSignatureRecord(
                $initialWork,
                InitialWorkSignature::ROLE_SENIOR_MANAGER,
                2,
                $source['senior_manager'],
                $source['senior_manager_label'],
                $source,
                InitialWorkSignature::STATUS_LOCKED,
            );

            $managerToken = $managerSignature->signer_user_id
                ? $this->issueToken($managerSignature)
                : null;

            return [
                'manager_url' => $managerToken ? route('approval.initial-work.show', $managerToken) : null,
                'manager_signature' => $managerSignature->fresh('signer'),
                'senior_signature' => $seniorSignature->fresh('signer'),
            ];
        });
    }

    /**
     * Rebuild unsigned signatures when the OA source is changed before signing starts.
     *
     * @return array{manager_url: ?string, manager_signature: ?InitialWorkSignature, senior_signature: ?InitialWorkSignature}
     */
    public function rebuildIfUnsigned(InitialWork $initialWork): array
    {
        return DB::transaction(function () use ($initialWork): array {
            $initialWork->loadMissing('signatures');

            if ($initialWork->signatures->contains(fn (InitialWorkSignature $signature) => $signature->isSigned())) {
                return [
                    'manager_url' => null,
                    'manager_signature' => $initialWork->signatures->firstWhere('role_key', InitialWorkSignature::ROLE_MANAGER),
                    'senior_signature' => $initialWork->signatures->firstWhere('role_key', InitialWorkSignature::ROLE_SENIOR_MANAGER),
                ];
            }

            $initialWork->signatures()->delete();
            $initialWork->unsetRelation('signatures');

            return $this->createSignatureChain($initialWork);
        });
    }

    /**
     * Activate the next approver after the current signature is completed.
     */
    public function activateNextSignature(InitialWorkSignature $signedSignature): ?string
    {
        if ($signedSignature->role_key !== InitialWorkSignature::ROLE_MANAGER) {
            return null;
        }

        $nextSignature = InitialWorkSignature::query()
            ->where('initial_work_id', $signedSignature->initial_work_id)
            ->where('role_key', InitialWorkSignature::ROLE_SENIOR_MANAGER)
            ->first();

        if (! $nextSignature || ! $nextSignature->signer_user_id || $nextSignature->isSigned()) {
            return null;
        }

        $token = $this->issueToken($nextSignature);

        $nextSignature->update([
            'status' => InitialWorkSignature::STATUS_PENDING,
        ]);

        return route('approval.initial-work.show', $token);
    }

    public function resolveUnitForOutlineAgreement(OutlineAgreement $outlineAgreement): ?UnitWork
    {
        $outlineAgreement->loadMissing([
            'unitWork.department',
            'unitWork.seniorManager',
            'unitWork.sections.manager',
        ]);

        $unit = $outlineAgreement->unitWork;
        $section = $unit ? $this->resolveSectionFromUnit($unit, (string) $outlineAgreement->jenis_kontrak) : null;

        if ($unit && $unit->senior_manager_id && $section?->manager_id) {
            return $unit;
        }

        $targetKey = $this->normalizeStructureName($unit?->name);

        if ($targetKey === '') {
            return $unit;
        }

        $candidateUnits = UnitWork::query()
            ->with(['department', 'seniorManager', 'sections.manager'])
            ->orderBy('name')
            ->get();

        $matchingUnits = $candidateUnits
            ->filter(function (UnitWork $candidate) use ($targetKey) {
                $candidateKey = $this->normalizeStructureName($candidate->name);

                return $candidateKey !== ''
                    && ($candidateKey === $targetKey
                        || str_contains($targetKey, $candidateKey)
                        || str_contains($candidateKey, $targetKey));
            })
            ->values();

        return $matchingUnits->first(function (UnitWork $candidate) use ($outlineAgreement) {
            $section = $this->resolveSectionFromUnit($candidate, (string) $outlineAgreement->jenis_kontrak);

            return $candidate->senior_manager_id && $section?->manager_id;
        }) ?: $matchingUnits->first(fn (UnitWork $candidate) => $candidate->senior_manager_id) ?: $unit;
    }

    public function resolveSectionForOutlineAgreement(OutlineAgreement $outlineAgreement): ?UnitWorkSection
    {
        $unit = $this->resolveUnitForOutlineAgreement($outlineAgreement);

        return $unit ? $this->resolveSectionFromUnit($unit, (string) $outlineAgreement->jenis_kontrak) : null;
    }

    /**
     * @return array{
     *     outline_agreement: ?OutlineAgreement,
     *     unit: ?\App\Models\UnitWork,
     *     section: ?UnitWorkSection,
     *     manager: ?User,
     *     senior_manager: ?User,
     *     manager_label: string,
     *     senior_manager_label: string,
     *     department_name: ?string,
     *     unit_name: ?string,
     *     section_name: ?string
     * }
     */
    private function resolveSignatureSource(InitialWork $initialWork): array
    {
        $outlineAgreement = $initialWork->outlineAgreement;
        $unit = $outlineAgreement ? $this->resolveUnitForOutlineAgreement($outlineAgreement) : null;
        $section = $outlineAgreement ? $this->resolveSectionForOutlineAgreement($outlineAgreement) : null;

        $unitName = $unit?->name;
        $sectionName = $section?->name ?: $outlineAgreement?->jenis_kontrak;

        return [
            'outline_agreement' => $outlineAgreement,
            'unit' => $unit,
            'section' => $section,
            'manager' => $section?->manager,
            'senior_manager' => $unit?->seniorManager,
            'manager_label' => $sectionName ? 'Manager '.$sectionName : 'Manager',
            'senior_manager_label' => $unitName ? 'Senior Manager '.$unitName : 'Senior Manager',
            'department_name' => $unit?->department?->name,
            'unit_name' => $unitName,
            'section_name' => $sectionName,
        ];
    }

    private function resolveSectionFromUnit(UnitWork $unit, string $sectionName): ?UnitWorkSection
    {
        $sections = $unit->relationLoaded('sections')
            ? $unit->sections
            : $unit->sections()->with('manager')->get();

        $sectionName = trim($sectionName);

        if ($sectionName !== '') {
            $exact = $sections->first(
                fn (UnitWorkSection $section) => strcasecmp($section->name, $sectionName) === 0
            );

            if ($exact) {
                return $exact;
            }

            $targetKey = $this->normalizeStructureName($sectionName);
            $normalized = $sections->first(function (UnitWorkSection $section) use ($targetKey) {
                $sectionKey = $this->normalizeStructureName($section->name);

                return $sectionKey !== ''
                    && ($sectionKey === $targetKey
                        || str_contains($targetKey, $sectionKey)
                        || str_contains($sectionKey, $targetKey));
            });

            if ($normalized) {
                return $normalized;
            }
        }

        return $sections->first(fn (UnitWorkSection $section) => $section->manager_id !== null)
            ?: $sections->first();
    }

    private function normalizeStructureName(?string $value): string
    {
        $normalized = strtolower(trim((string) $value));
        $normalized = str_replace(['&', 'sction'], [' and ', 'section'], $normalized);
        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized) ?? '';

        $tokens = array_values(array_filter(
            explode(' ', $normalized),
            fn (string $token) => ! in_array($token, ['unit', 'section', 'of', 'and', 'design'], true)
        ));

        return implode(' ', $tokens);
    }

    /**
     * @param array<string, mixed> $source
     */
    private function createSignatureRecord(
        InitialWork $initialWork,
        string $roleKey,
        int $stepOrder,
        ?User $signer,
        string $roleLabel,
        array $source,
        string $defaultStatus,
    ): InitialWorkSignature {
        return $initialWork->signatures()->create([
            'step_order' => $stepOrder,
            'role_key' => $roleKey,
            'role_label' => $roleLabel,
            'signer_user_id' => $signer?->id,
            'signer_name' => $signer?->name,
            'signer_position' => $roleLabel,
            'source_department' => $source['department_name'],
            'source_unit' => $source['unit_name'],
            'source_section' => $source['section_name'],
            'status' => $signer ? $defaultStatus : InitialWorkSignature::STATUS_MISSING,
        ]);
    }

    private function issueToken(InitialWorkSignature $signature): string
    {
        $token = Str::random(64);

        $signature->update([
            'token_hash' => hash('sha256', $token),
            'token_encrypted' => $token,
            'token_expires_at' => now()->addDays(self::TOKEN_TTL_DAYS),
        ]);

        return $token;
    }
}
