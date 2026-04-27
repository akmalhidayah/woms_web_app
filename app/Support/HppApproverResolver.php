<?php

namespace App\Support;

use App\Models\Hpp;
use App\Models\HppApprovalSetting;
use App\Models\Order;
use App\Models\OutlineAgreement;
use App\Models\UnitWork;
use App\Models\UnitWorkSection;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class HppApproverResolver
{
    /**
     * @return array{
     *     role_key: string,
     *     role_label: string,
     *     user: User,
     *     position: string,
     *     department: ?string,
     *     unit: ?string,
     *     section: ?string
     * }
     */
    public function resolveApprover(Hpp $hpp, string $flowRoleLabel): array
    {
        $hpp->loadMissing([
            'order',
            'outlineAgreement.unitWork.department.generalManager',
            'outlineAgreement.unitWork.seniorManager',
            'outlineAgreement.unitWork.sections.manager',
        ]);

        $roleKey = $this->roleKeyFor($hpp, $flowRoleLabel);

        return match ($roleKey) {
            'planner_control' => $this->resolvePlannerControl($roleKey, $flowRoleLabel),
            'manager_counter_part' => $this->resolveCounterPartManager($roleKey, $flowRoleLabel),
            'sm_counter_part' => $this->resolveCounterPartSeniorManager($roleKey, $flowRoleLabel),
            'manager_pengendali',
            'workshop_manager_pengendali' => $this->resolveControllerManager($hpp, $roleKey, $flowRoleLabel),
            'sm_pengendali',
            'workshop_sm_pengendali' => $this->resolveControllerSeniorManager($hpp, $roleKey, $flowRoleLabel),
            'gm_pengendali',
            'workshop_gm_pengendali' => $this->resolveControllerGeneralManager($hpp, $roleKey, $flowRoleLabel),
            'manager_peminta' => $this->resolveRequesterManager($hpp, $roleKey, $flowRoleLabel),
            'sm_peminta' => $this->resolveRequesterSeniorManager($hpp, $roleKey, $flowRoleLabel),
            'gm_peminta' => $this->resolveRequesterGeneralManager($hpp, $roleKey, $flowRoleLabel),
            'dirops' => $this->resolveDirops($roleKey, $flowRoleLabel),
            default => throw ValidationException::withMessages([
                'approval' => "Role approval HPP {$flowRoleLabel} belum didukung oleh resolver.",
            ]),
        };
    }

    public function roleKeyFor(Hpp $hpp, string $flowRoleLabel): string
    {
        $isWorkshop = str_starts_with((string) $hpp->approval_case, 'FAB-WORKSHOP');

        if ($isWorkshop) {
            return match ($flowRoleLabel) {
                'Manager' => 'workshop_manager_pengendali',
                'SM' => 'workshop_sm_pengendali',
                'GM' => 'workshop_gm_pengendali',
                'DIROPS' => 'dirops',
                default => $this->standardRoleKey($flowRoleLabel),
            };
        }

        return $this->standardRoleKey($flowRoleLabel);
    }

    public function resolveUnitForOutlineAgreement(OutlineAgreement $outlineAgreement): ?UnitWork
    {
        $outlineAgreement->loadMissing([
            'unitWork.department.generalManager',
            'unitWork.seniorManager',
            'unitWork.sections.manager',
        ]);

        $unit = $outlineAgreement->unitWork;
        $section = $unit ? $this->resolveSectionFromUnit($unit, (string) $outlineAgreement->jenis_kontrak) : null;

        if ($unit && $unit->senior_manager_id && $unit->department?->general_manager_id && $section?->manager_id) {
            return $unit;
        }

        $targetKey = $this->normalizeStructureName($unit?->name);

        if ($targetKey === '') {
            return $unit;
        }

        $candidateUnits = UnitWork::query()
            ->with(['department.generalManager', 'seniorManager', 'sections.manager'])
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

            return $candidate->senior_manager_id
                && $candidate->department?->general_manager_id
                && $section?->manager_id;
        }) ?: $matchingUnits->first(fn (UnitWork $candidate) => $candidate->senior_manager_id) ?: $unit;
    }

    public function resolveSectionForOutlineAgreement(OutlineAgreement $outlineAgreement): ?UnitWorkSection
    {
        $unit = $this->resolveUnitForOutlineAgreement($outlineAgreement);

        return $unit ? $this->resolveSectionFromUnit($unit, (string) $outlineAgreement->jenis_kontrak) : null;
    }

    public static function plannerControlUserId(): ?int
    {
        return HppApprovalSetting::current()->planner_control_user_id;
    }

    public static function diropsUserId(): ?int
    {
        return HppApprovalSetting::current()->dirops_user_id;
    }

    public static function counterPartManagerUserId(): ?int
    {
        return HppApprovalSetting::current()
            ->loadMissing('counterPartSection.manager')
            ->counterPartSection?->manager?->id;
    }

    public static function counterPartSeniorManagerUserId(): ?int
    {
        return HppApprovalSetting::current()
            ->loadMissing('counterPartUnit.seniorManager')
            ->counterPartUnit?->seniorManager?->id;
    }

    public static function requesterManagerUserId(Order $order): ?int
    {
        return (new self())->requesterChain($order)['section']?->manager?->id;
    }

    public static function requesterSeniorManagerUserId(Order $order): ?int
    {
        return (new self())->requesterChain($order)['unit']?->seniorManager?->id;
    }

    public static function requesterGeneralManagerUserId(Order $order): ?int
    {
        return (new self())->requesterChain($order)['unit']?->department?->generalManager?->id;
    }

    public static function controllerManagerUserId(OutlineAgreement $oa): ?int
    {
        return (new self())->resolveSectionForOutlineAgreement($oa)?->manager?->id;
    }

    public static function controllerSeniorManagerUserId(OutlineAgreement $oa): ?int
    {
        return (new self())->resolveUnitForOutlineAgreement($oa)?->seniorManager?->id;
    }

    public static function controllerGeneralManagerUserId(OutlineAgreement $oa): ?int
    {
        return (new self())->resolveUnitForOutlineAgreement($oa)?->department?->generalManager?->id;
    }

    public static function ensureConfigured(string $role, ?int $userId): int
    {
        if (! $userId) {
            throw new RuntimeException("Approver untuk role {$role} belum dikonfigurasi.");
        }

        return $userId;
    }

    private function standardRoleKey(string $flowRoleLabel): string
    {
        return match ($flowRoleLabel) {
            'Planner Control' => 'planner_control',
            'Manager Counter Part' => 'manager_counter_part',
            'SM Counter Part' => 'sm_counter_part',
            'Manager Pengendali' => 'manager_pengendali',
            'SM Pengendali' => 'sm_pengendali',
            'GM Pengendali' => 'gm_pengendali',
            'Manager Peminta' => 'manager_peminta',
            'SM Peminta' => 'sm_peminta',
            'GM Peminta' => 'gm_peminta',
            'DIROPS' => 'dirops',
            default => throw ValidationException::withMessages([
                'approval' => "Role approval HPP {$flowRoleLabel} tidak dikenali.",
            ]),
        };
    }

    /**
     * @return array{
     *     role_key: string,
     *     role_label: string,
     *     user: User,
     *     position: string,
     *     department: ?string,
     *     unit: ?string,
     *     section: ?string
     * }
     */
    private function approverPayload(
        string $roleKey,
        string $flowRoleLabel,
        User $user,
        string $position,
        ?string $department = null,
        ?string $unit = null,
        ?string $section = null,
    ): array {
        return [
            'role_key' => $roleKey,
            'role_label' => $this->displayRoleLabel($roleKey, $flowRoleLabel),
            'user' => $user,
            'position' => $position,
            'department' => $department,
            'unit' => $unit,
            'section' => $section,
        ];
    }

    private function displayRoleLabel(string $roleKey, string $flowRoleLabel): string
    {
        return match ($roleKey) {
            'workshop_manager_pengendali' => 'Manager Pengendali',
            'workshop_sm_pengendali' => 'SM Pengendali',
            'workshop_gm_pengendali' => 'GM Pengendali',
            default => $flowRoleLabel,
        };
    }

    private function resolvePlannerControl(string $roleKey, string $flowRoleLabel): array
    {
        $setting = $this->settings();
        $user = $this->requireUser($setting->plannerControl, 'Planner Control belum dikonfigurasi.');

        return $this->approverPayload($roleKey, $flowRoleLabel, $user, 'Planner Control');
    }

    private function resolveDirops(string $roleKey, string $flowRoleLabel): array
    {
        $setting = $this->settings();
        $user = $this->requireUser($setting->dirops, 'DIROPS belum dikonfigurasi.');

        return $this->approverPayload($roleKey, $flowRoleLabel, $user, 'Director of Operation');
    }

    private function resolveCounterPartManager(string $roleKey, string $flowRoleLabel): array
    {
        $setting = $this->settings();
        $section = $setting->counterPartSection;
        $unit = $section?->unitWork ?: $setting->counterPartUnit;
        $user = $this->requireUser($section?->manager, 'Manager Counter Part belum tersedia.');

        return $this->approverPayload(
            $roleKey,
            $flowRoleLabel,
            $user,
            $this->managerPosition($section?->name),
            $unit?->department?->name,
            $unit?->name,
            $section?->name,
        );
    }

    private function resolveCounterPartSeniorManager(string $roleKey, string $flowRoleLabel): array
    {
        $setting = $this->settings();
        $unit = $setting->counterPartUnit ?: $setting->counterPartSection?->unitWork;
        $user = $this->requireUser($unit?->seniorManager, 'SM Counter Part belum tersedia.');

        return $this->approverPayload(
            $roleKey,
            $flowRoleLabel,
            $user,
            $this->seniorManagerPosition($unit?->name),
            $unit?->department?->name,
            $unit?->name,
            $setting->counterPartSection?->name,
        );
    }

    private function resolveControllerManager(Hpp $hpp, string $roleKey, string $flowRoleLabel): array
    {
        $source = $this->controllerChain($hpp);
        $user = $this->requireUser($source['section']?->manager, 'Manager Pengendali tidak ditemukan.');

        return $this->approverPayload(
            $roleKey,
            $flowRoleLabel,
            $user,
            $this->managerPosition($source['section']?->name),
            $source['unit']?->department?->name,
            $source['unit']?->name,
            $source['section']?->name,
        );
    }

    private function resolveControllerSeniorManager(Hpp $hpp, string $roleKey, string $flowRoleLabel): array
    {
        $source = $this->controllerChain($hpp);
        $user = $this->requireUser($source['unit']?->seniorManager, 'SM Pengendali tidak ditemukan.');

        return $this->approverPayload(
            $roleKey,
            $flowRoleLabel,
            $user,
            $this->seniorManagerPosition($source['unit']?->name),
            $source['unit']?->department?->name,
            $source['unit']?->name,
            $source['section']?->name,
        );
    }

    private function resolveControllerGeneralManager(Hpp $hpp, string $roleKey, string $flowRoleLabel): array
    {
        $source = $this->controllerChain($hpp);
        $user = $this->requireUser($source['unit']?->department?->generalManager, 'GM Pengendali tidak ditemukan.');

        return $this->approverPayload(
            $roleKey,
            $flowRoleLabel,
            $user,
            $this->generalManagerPosition($source['unit']?->department?->name),
            $source['unit']?->department?->name,
            $source['unit']?->name,
            $source['section']?->name,
        );
    }

    private function resolveRequesterManager(Hpp $hpp, string $roleKey, string $flowRoleLabel): array
    {
        $source = $this->requesterChain($this->requireOrder($hpp));
        $user = $this->requireUser($source['section']?->manager, 'Manager Peminta tidak ditemukan.');

        return $this->approverPayload(
            $roleKey,
            $flowRoleLabel,
            $user,
            $this->managerPosition($source['section']?->name),
            $source['unit']?->department?->name,
            $source['unit']?->name,
            $source['section']?->name,
        );
    }

    private function resolveRequesterSeniorManager(Hpp $hpp, string $roleKey, string $flowRoleLabel): array
    {
        $source = $this->requesterChain($this->requireOrder($hpp));
        $user = $this->requireUser($source['unit']?->seniorManager, 'SM Peminta tidak ditemukan.');

        return $this->approverPayload(
            $roleKey,
            $flowRoleLabel,
            $user,
            $this->seniorManagerPosition($source['unit']?->name),
            $source['unit']?->department?->name,
            $source['unit']?->name,
            $source['section']?->name,
        );
    }

    private function resolveRequesterGeneralManager(Hpp $hpp, string $roleKey, string $flowRoleLabel): array
    {
        $source = $this->requesterChain($this->requireOrder($hpp));
        $user = $this->requireUser($source['unit']?->department?->generalManager, 'GM Peminta tidak ditemukan.');

        return $this->approverPayload(
            $roleKey,
            $flowRoleLabel,
            $user,
            $this->generalManagerPosition($source['unit']?->department?->name),
            $source['unit']?->department?->name,
            $source['unit']?->name,
            $source['section']?->name,
        );
    }

    /**
     * @return array{unit: ?UnitWork, section: ?UnitWorkSection}
     */
    private function controllerChain(Hpp $hpp): array
    {
        $outlineAgreement = $this->requireOutlineAgreement($hpp);
        $unit = $this->resolveUnitForOutlineAgreement($outlineAgreement);
        $section = $this->resolveSectionForOutlineAgreement($outlineAgreement);

        return ['unit' => $unit, 'section' => $section];
    }

    /**
     * @return array{unit: ?UnitWork, section: ?UnitWorkSection}
     */
    private function requesterChain(Order $order): array
    {
        $unit = $this->findUnitByName($order->unit_kerja);
        $section = $this->findSection($order->seksi, $unit, false);

        if ($section) {
            $section->loadMissing(['manager', 'unitWork.department.generalManager', 'unitWork.seniorManager']);
            $unit = $section->unitWork ?: $unit;
        }

        $unit?->loadMissing(['department.generalManager', 'seniorManager', 'sections.manager']);

        return ['unit' => $unit, 'section' => $section];
    }

    private function findUnitByName(?string $name): ?UnitWork
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        $exact = UnitWork::query()
            ->with(['department.generalManager', 'seniorManager', 'sections.manager'])
            ->where('name', $name)
            ->first();

        if ($exact) {
            return $exact;
        }

        $targetKey = $this->normalizeStructureName($name);

        return UnitWork::query()
            ->with(['department.generalManager', 'seniorManager', 'sections.manager'])
            ->orderBy('name')
            ->get()
            ->first(function (UnitWork $unit) use ($targetKey) {
                $unitKey = $this->normalizeStructureName($unit->name);

                return $unitKey !== ''
                    && ($unitKey === $targetKey
                        || str_contains($targetKey, $unitKey)
                        || str_contains($unitKey, $targetKey));
            });
    }

    private function findSection(?string $sectionName, ?UnitWork $unit = null, bool $allowFallback = true): ?UnitWorkSection
    {
        $sectionName = trim((string) $sectionName);

        if ($unit) {
            return $this->resolveSectionFromUnit($unit, $sectionName, $allowFallback);
        }

        if ($sectionName === '') {
            return null;
        }

        $exact = UnitWorkSection::query()
            ->with(['manager', 'unitWork.department.generalManager', 'unitWork.seniorManager'])
            ->where('name', $sectionName)
            ->first();

        if ($exact) {
            return $exact;
        }

        $targetKey = $this->normalizeStructureName($sectionName);

        return UnitWorkSection::query()
            ->with(['manager', 'unitWork.department.generalManager', 'unitWork.seniorManager'])
            ->orderBy('name')
            ->get()
            ->first(function (UnitWorkSection $section) use ($targetKey) {
                $sectionKey = $this->normalizeStructureName($section->name);

                return $sectionKey !== ''
                    && ($sectionKey === $targetKey
                        || str_contains($targetKey, $sectionKey)
                        || str_contains($sectionKey, $targetKey));
            });
    }

    private function resolveSectionFromUnit(UnitWork $unit, string $sectionName, bool $allowFallback = true): ?UnitWorkSection
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
                return $exact->loadMissing('manager', 'unitWork');
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
                return $normalized->loadMissing('manager', 'unitWork');
            }
        }

        if (! $allowFallback) {
            return null;
        }

        return $sections->first(fn (UnitWorkSection $section) => $section->manager_id !== null)
            ?->loadMissing('manager', 'unitWork')
            ?: $sections->first()?->loadMissing('manager', 'unitWork');
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

    private function settings(): HppApprovalSetting
    {
        return HppApprovalSetting::current()->loadMissing([
            'plannerControl',
            'dirops',
            'counterPartUnit.department.generalManager',
            'counterPartUnit.seniorManager',
            'counterPartSection.manager',
            'counterPartSection.unitWork.department.generalManager',
            'counterPartSection.unitWork.seniorManager',
        ]);
    }

    private function requireUser(?User $user, string $message): User
    {
        if (! $user) {
            throw ValidationException::withMessages(['approval' => $message]);
        }

        return $user;
    }

    private function requireOrder(Hpp $hpp): Order
    {
        if (! $hpp->order) {
            throw ValidationException::withMessages(['approval' => 'Order sumber HPP tidak ditemukan.']);
        }

        return $hpp->order;
    }

    private function requireOutlineAgreement(Hpp $hpp): OutlineAgreement
    {
        if (! $hpp->outlineAgreement) {
            throw ValidationException::withMessages(['approval' => 'Outline Agreement sumber HPP tidak ditemukan.']);
        }

        return $hpp->outlineAgreement;
    }

    private function managerPosition(?string $sectionName): string
    {
        return $sectionName ? "Manager of {$sectionName}" : 'Manager';
    }

    private function seniorManagerPosition(?string $unitName): string
    {
        return $unitName ? "SM of {$unitName}" : 'SM';
    }

    private function generalManagerPosition(?string $departmentName): string
    {
        return $departmentName ? "GM of {$departmentName}" : 'GM';
    }
}
