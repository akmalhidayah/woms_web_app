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
    ) {}

    /**
     * @return array{workshop_url: ?string, workshop_signature: ?QualityControlSignature, user_signature: ?QualityControlSignature}
     */
    public function createSignatureChain(QualityControlReport $report): array
    {
        return DB::transaction(function () use ($report): array {
            $lockedReport = QualityControlReport::query()
                ->whereKey($report->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $lockedReport->loadMissing(['order', 'signatures']);

            if ($lockedReport->signatures->isNotEmpty()) {
                $workshopSignature = $lockedReport->signatures
                    ->firstWhere('role_key', QualityControlSignature::ROLE_WORKSHOP_MANAGER);
                $userSignature = $lockedReport->signatures
                    ->firstWhere('role_key', QualityControlSignature::ROLE_USER_MANAGER);

                return [
                    'workshop_url' => $workshopSignature?->approvalUrl(),
                    'workshop_signature' => $workshopSignature?->fresh('signer'),
                    'user_signature' => $userSignature?->fresh('signer'),
                ];
            }

            $this->assertApprovalReady($lockedReport);
            $source = $this->resolveSignatureSource($lockedReport);

            $workshopSignature = $this->createSignatureRecord(
                $lockedReport,
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
                $lockedReport,
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

    public function assertApprovalReady(QualityControlReport $report): void
    {
        $report->loadMissing('order');
        $source = $this->resolveSignatureSource($report);

        if (! $source['workshop_manager']) {
            throw ValidationException::withMessages([
                'approval' => 'Manager Workshop belum ditemukan di struktur organisasi.',
            ]);
        }

        if (blank($source['workshop_manager']->email)) {
            throw ValidationException::withMessages([
                'approval' => 'Email Manager Workshop belum dikonfigurasi.',
            ]);
        }

        if (! $source['user_manager']) {
            throw ValidationException::withMessages([
                'approval' => 'Manager User belum ditemukan dari unit/seksi order.',
            ]);
        }

        if (blank($source['user_manager']->email)) {
            throw ValidationException::withMessages([
                'approval' => 'Email Manager User pada unit/seksi order belum dikonfigurasi.',
            ]);
        }
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
            if ($report->status !== QualityControlReport::STATUS_SUBMITTED) {
                throw ValidationException::withMessages([
                    'approval' => 'Approval Quality Control hanya dapat dibuat setelah laporan disubmit.',
                ]);
            }

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

            $this->assertApprovalReady($report);
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

        $section = $this->resolveSectionFromUnit($workshopUnit, 'Machine Workshop');

        return $section?->manager && filled($section->manager->email) ? $section : null;
    }

    private function resolveUserSection(?Order $order): ?UnitWorkSection
    {
        if (! $order) {
            return null;
        }

        $fallbackUnit = $this->resolveUnitByName((string) $order->unit_kerja);

        if (! $fallbackUnit) {
            return null;
        }

        $section = $this->resolveSectionFromUnit($fallbackUnit, (string) $order->seksi);

        return $section?->manager && filled($section->manager->email) ? $section : null;
    }

    private function resolveUnitByName(string $unitName): ?UnitWork
    {
        $unitName = trim($unitName);
        $exact = UnitWork::query()
            ->with(['department', 'sections.manager'])
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($unitName)])
            ->get();

        return $exact->count() === 1 ? $exact->first() : null;
    }

    private function resolveSectionByName(string $sectionName): ?UnitWorkSection
    {
        $sectionName = trim($sectionName);

        return UnitWorkSection::query()
            ->with(['manager', 'unitWork.department'])
            ->whereRaw('LOWER(TRIM(name)) = ?', [strtolower($sectionName)])
            ->first();
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

            return null;
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

        DB::afterCommit(function () use ($signature): void {
            $this->approvalNotificationService->sendQualityControl($signature->fresh());
        });

        return $token;
    }
}
