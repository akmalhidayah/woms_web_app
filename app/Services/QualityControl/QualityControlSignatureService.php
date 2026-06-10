<?php

namespace App\Services\QualityControl;

use App\Models\Order;
use App\Models\QualityControlReport;
use App\Models\QualityControlSignature;
use App\Models\UnitWork;
use App\Models\UnitWorkSection;
use App\Models\User;
use App\Services\Approvals\ApprovalNotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class QualityControlSignatureService
{
    private const TOKEN_TTL_DAYS = 7;

    public function __construct(
        private readonly ApprovalNotificationService $approvalNotificationService,
    ) {
    }

    /**
     * @return array{workshop_url: ?string, workshop_signature: ?QualityControlSignature, user_signature: ?QualityControlSignature}
     */
    public function createSignatureChain(QualityControlReport $report): array
    {
        return DB::transaction(function () use ($report): array {
            $report->loadMissing('order');

            $source = $this->resolveSignatureSource($report);

            $workshopSignature = $this->createSignatureRecord(
                $report,
                QualityControlSignature::ROLE_WORKSHOP_MANAGER,
                1,
                $source['workshop_manager'],
                $source['workshop_manager_label'],
                $source['workshop_department_name'],
                $source['workshop_unit_name'],
                $source['workshop_section_name'],
                QualityControlSignature::STATUS_PENDING,
            );

            $userSignature = $this->createSignatureRecord(
                $report,
                QualityControlSignature::ROLE_USER_MANAGER,
                2,
                $source['user_manager'],
                $source['user_manager_label'],
                $source['user_department_name'],
                $source['user_unit_name'],
                $source['user_section_name'],
                QualityControlSignature::STATUS_LOCKED,
            );

            if ($workshopSignature->signer_user_id) {
                $this->issueToken($workshopSignature);
            }

            return [
                'workshop_url' => $workshopSignature->fresh()?->approvalUrl(),
                'workshop_signature' => $workshopSignature->fresh('signer'),
                'user_signature' => $userSignature->fresh('signer'),
            ];
        });
    }

    /**
     * @return array{workshop_url: ?string, workshop_signature: ?QualityControlSignature, user_signature: ?QualityControlSignature}
     */
    public function rebuildIfUnsigned(QualityControlReport $report): array
    {
        return DB::transaction(function () use ($report): array {
            $report->loadMissing('signatures');

            if ($report->signatures->contains(fn (QualityControlSignature $signature): bool => $signature->isSigned())) {
                return [
                    'workshop_url' => null,
                    'workshop_signature' => $report->signatures->firstWhere('role_key', QualityControlSignature::ROLE_WORKSHOP_MANAGER),
                    'user_signature' => $report->signatures->firstWhere('role_key', QualityControlSignature::ROLE_USER_MANAGER),
                ];
            }

            $report->signatures()->delete();
            $report->unsetRelation('signatures');

            return $this->createSignatureChain($report);
        });
    }

    /**
     * Repair signer snapshots for existing QC reports without touching completed approvals.
     *
     * @return array{workshop_url: ?string, user_url: ?string, workshop_signature: ?QualityControlSignature, user_signature: ?QualityControlSignature}
     */
    public function ensureSignatureChain(QualityControlReport $report): array
    {
        return DB::transaction(function () use ($report): array {
            $report->loadMissing(['order', 'signatures']);

            if ($report->signatures->isEmpty()) {
                $created = $this->createSignatureChain($report);

                return [
                    'workshop_url' => $created['workshop_url'],
                    'user_url' => null,
                    'workshop_signature' => $created['workshop_signature'],
                    'user_signature' => $created['user_signature'],
                ];
            }

            $source = $this->resolveSignatureSource($report);
            $workshopSignature = $this->upsertRepairableSignature(
                $report,
                QualityControlSignature::ROLE_WORKSHOP_MANAGER,
                1,
                $source['workshop_manager'],
                $source['workshop_manager_label'],
                $source['workshop_department_name'],
                $source['workshop_unit_name'],
                $source['workshop_section_name'],
                QualityControlSignature::STATUS_PENDING,
            );
            $userSignature = $this->upsertRepairableSignature(
                $report,
                QualityControlSignature::ROLE_USER_MANAGER,
                2,
                $source['user_manager'],
                $source['user_manager_label'],
                $source['user_department_name'],
                $source['user_unit_name'],
                $source['user_section_name'],
                QualityControlSignature::STATUS_LOCKED,
            );

            $workshopUrl = null;
            $userUrl = null;

            if (! $workshopSignature->isSigned() && $workshopSignature->signer_user_id) {
                if (! $workshopSignature->isPending() || ! $workshopSignature->approvalUrl()) {
                    $this->issueToken($workshopSignature);
                    $workshopSignature->update(['status' => QualityControlSignature::STATUS_PENDING]);
                    $workshopUrl = $workshopSignature->fresh()->approvalUrl();
                } else {
                    $workshopUrl = $workshopSignature->approvalUrl();
                }
            }

            if ($workshopSignature->fresh()->isSigned() && ! $userSignature->isSigned() && $userSignature->signer_user_id) {
                if (! $userSignature->isPending() || ! $userSignature->approvalUrl()) {
                    $this->issueToken($userSignature);
                    $userSignature->update(['status' => QualityControlSignature::STATUS_PENDING]);
                    $userUrl = $userSignature->fresh()->approvalUrl();
                } else {
                    $userUrl = $userSignature->approvalUrl();
                }
            }

            return [
                'workshop_url' => $workshopUrl,
                'user_url' => $userUrl,
                'workshop_signature' => $workshopSignature->fresh('signer'),
                'user_signature' => $userSignature->fresh('signer'),
            ];
        });
    }

    public function activateNextSignature(QualityControlSignature $signedSignature): ?string
    {
        return DB::transaction(function () use ($signedSignature): ?string {
            if ($signedSignature->role_key !== QualityControlSignature::ROLE_WORKSHOP_MANAGER) {
                return null;
            }

            $nextSignature = QualityControlSignature::query()
                ->where('quality_control_report_id', $signedSignature->quality_control_report_id)
                ->where('role_key', QualityControlSignature::ROLE_USER_MANAGER)
                ->lockForUpdate()
                ->first();

            if (! $nextSignature || ! $nextSignature->signer_user_id || $nextSignature->isSigned()) {
                return null;
            }

            if ($nextSignature->isPending() && $nextSignature->approvalUrl()) {
                return $nextSignature->approvalUrl();
            }

            $this->issueToken($nextSignature);

            $nextSignature->update([
                'status' => QualityControlSignature::STATUS_PENDING,
            ]);

            return $nextSignature->fresh()->approvalUrl();
        });
    }

    public function regenerateExpiredToken(QualityControlSignature $signature): string
    {
        return DB::transaction(function () use ($signature): string {
            $lockedSignature = QualityControlSignature::query()
                ->whereKey($signature->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedSignature->isPending() || ! $lockedSignature->signer_user_id) {
                throw ValidationException::withMessages([
                    'approval' => 'Signature Quality Control ini tidak sedang menunggu approval.',
                ]);
            }

            if (! $lockedSignature->tokenExpired()) {
                throw ValidationException::withMessages([
                    'approval' => 'Token Quality Control masih aktif dan tidak perlu dibuat ulang.',
                ]);
            }

            return $this->issueToken($lockedSignature);
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveSignatureSource(QualityControlReport $report): array
    {
        $order = $report->order;
        $workshopSection = $this->resolveWorkshopSection($report);
        $userSection = $this->resolveUserSection($order);

        $workshopUnit = $workshopSection?->unitWork;
        $userUnit = $userSection?->unitWork;

        return [
            'workshop_manager' => $workshopSection?->manager,
            'workshop_manager_label' => $workshopSection?->name
                ? 'Manager '.$workshopSection->name
                : 'Manager Workshop',
            'workshop_department_name' => $workshopUnit?->department?->name,
            'workshop_unit_name' => $workshopUnit?->name,
            'workshop_section_name' => $workshopSection?->name,
            'user_manager' => $userSection?->manager,
            'user_manager_label' => $userSection?->name
                ? 'Manager '.$userSection->name
                : 'Manager Unit Terkait',
            'user_department_name' => $userUnit?->department?->name,
            'user_unit_name' => $userUnit?->name,
            'user_section_name' => $userSection?->name,
        ];
    }

    private function resolveWorkshopSection(QualityControlReport $report): ?UnitWorkSection
    {
        $workshopUnit = UnitWork::query()
            ->with(['department', 'sections.manager'])
            ->whereRaw('LOWER(name) = ?', ['workshop'])
            ->first();

        if (! $workshopUnit) {
            return null;
        }

        $target = $report->type === QualityControlReport::TYPE_REFURBISH
            ? 'Machine Workshop'
            : 'Machine Workshop';

        return $this->resolveSectionFromUnit($workshopUnit, $target)
            ?: $workshopUnit->sections->first(fn (UnitWorkSection $section): bool => $section->manager_id !== null)
            ?: $workshopUnit->sections->first();
    }

    private function resolveUserSection(?Order $order): ?UnitWorkSection
    {
        if (! $order) {
            return null;
        }

        $fallbackUnit = $this->resolveUnitByName((string) $order->unit_kerja);

        if ($fallbackUnit) {
            $section = $this->resolveSectionFromUnit($fallbackUnit, (string) $order->seksi);

            if ($section?->manager_id) {
                return $section;
            }
        }

        if ($fallbackUnit) {
            return $this->resolveSectionFromUnit($fallbackUnit, (string) $order->seksi)
                ?: $fallbackUnit->sections->first(fn (UnitWorkSection $section): bool => $section->manager_id !== null)
                ?: $fallbackUnit->sections->first();
        }

        return $this->resolveSectionByName((string) $order->seksi);
    }

    private function resolveUnitByName(string $unitName): ?UnitWork
    {
        $unitName = trim($unitName);
        $exact = UnitWork::query()
            ->with(['department', 'sections.manager'])
            ->whereRaw('LOWER(name) = ?', [strtolower($unitName)])
            ->first();

        if ($exact) {
            return $exact;
        }

        $targetKey = $this->normalizeStructureName($unitName);

        if ($targetKey === '') {
            return null;
        }

        return UnitWork::query()
            ->with(['department', 'sections.manager'])
            ->get()
            ->first(function (UnitWork $unit) use ($targetKey): bool {
                $unitKey = $this->normalizeStructureName($unit->name);

                return $unitKey !== ''
                    && ($unitKey === $targetKey
                        || str_contains($targetKey, $unitKey)
                        || str_contains($unitKey, $targetKey));
            });
    }

    private function resolveSectionByName(string $sectionName): ?UnitWorkSection
    {
        $sectionName = trim($sectionName);
        $exact = UnitWorkSection::query()
            ->with(['manager', 'unitWork.department'])
            ->whereRaw('LOWER(name) = ?', [strtolower($sectionName)])
            ->first();

        if ($exact) {
            return $exact;
        }

        $targetKey = $this->normalizeStructureName($sectionName);

        if ($targetKey === '') {
            return null;
        }

        return UnitWorkSection::query()
            ->with(['manager', 'unitWork.department'])
            ->get()
            ->first(function (UnitWorkSection $section) use ($targetKey): bool {
                $sectionKey = $this->normalizeStructureName($section->name);

                return $sectionKey !== ''
                    && ($sectionKey === $targetKey
                        || str_contains($targetKey, $sectionKey)
                        || str_contains($sectionKey, $targetKey));
            });
    }

    private function resolveSectionFromUnit(UnitWork $unit, string $sectionName): ?UnitWorkSection
    {
        $sections = $unit->relationLoaded('sections')
            ? $unit->sections
            : $unit->sections()->with('manager')->get();

        $sectionName = trim($sectionName);

        if ($sectionName !== '') {
            $exact = $sections->first(
                fn (UnitWorkSection $section): bool => strcasecmp($section->name, $sectionName) === 0
            );

            if ($exact) {
                return $exact;
            }

            $targetKey = $this->normalizeStructureName($sectionName);
            $normalized = $sections->first(function (UnitWorkSection $section) use ($targetKey): bool {
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

        return null;
    }

    private function normalizeStructureName(?string $value): string
    {
        $normalized = strtolower(trim((string) $value));
        $normalized = str_replace(['&', 'sction'], [' and ', 'section'], $normalized);
        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized) ?? '';

        $tokens = array_values(array_filter(
            explode(' ', $normalized),
            fn (string $token): bool => ! in_array($token, ['unit', 'section', 'of', 'and', 'design'], true)
        ));

        return implode(' ', $tokens);
    }

    private function createSignatureRecord(
        QualityControlReport $report,
        string $roleKey,
        int $stepOrder,
        ?User $signer,
        string $roleLabel,
        ?string $departmentName,
        ?string $unitName,
        ?string $sectionName,
        string $defaultStatus,
    ): QualityControlSignature {
        return $report->signatures()->create([
            'step_order' => $stepOrder,
            'role_key' => $roleKey,
            'role_label' => $roleLabel,
            'signer_user_id' => $signer?->id,
            'signer_name' => $signer?->name,
            'signer_position' => $roleLabel,
            'source_department' => $departmentName,
            'source_unit' => $unitName,
            'source_section' => $sectionName,
            'status' => $signer ? $defaultStatus : QualityControlSignature::STATUS_MISSING,
        ]);
    }

    private function upsertRepairableSignature(
        QualityControlReport $report,
        string $roleKey,
        int $stepOrder,
        ?User $signer,
        string $roleLabel,
        ?string $departmentName,
        ?string $unitName,
        ?string $sectionName,
        string $defaultStatus,
    ): QualityControlSignature {
        $signature = QualityControlSignature::query()
            ->where('quality_control_report_id', $report->id)
            ->where('role_key', $roleKey)
            ->lockForUpdate()
            ->first();

        if (! $signature) {
            return $this->createSignatureRecord(
                $report,
                $roleKey,
                $stepOrder,
                $signer,
                $roleLabel,
                $departmentName,
                $unitName,
                $sectionName,
                $defaultStatus,
            );
        }

        if ($signature->isSigned()) {
            return $signature;
        }

        $signerChanged = $signature->signer_user_id !== $signer?->id;
        $updates = [
            'step_order' => $stepOrder,
            'role_label' => $roleLabel,
            'signer_user_id' => $signer?->id,
            'signer_name' => $signer?->name,
            'signer_position' => $roleLabel,
            'source_department' => $departmentName,
            'source_unit' => $unitName,
            'source_section' => $sectionName,
            'status' => $signer ? (
                $signature->status === QualityControlSignature::STATUS_MISSING ? $defaultStatus : $signature->status
            ) : QualityControlSignature::STATUS_MISSING,
        ];

        if ($signerChanged) {
            $updates = [
                ...$updates,
                'token_hash' => null,
                'token_encrypted' => null,
                'token_expires_at' => null,
            ];
        }

        $signature->update($updates);

        return $signature->fresh();
    }

    private function issueToken(QualityControlSignature $signature): string
    {
        $token = Str::random(64);

        $signature->update([
            'token_hash' => hash('sha256', $token),
            'token_encrypted' => $token,
            'token_expires_at' => now()->addDays(self::TOKEN_TTL_DAYS),
        ]);

        $this->approvalNotificationService->sendQualityControl($signature->fresh());

        return $token;
    }
}
