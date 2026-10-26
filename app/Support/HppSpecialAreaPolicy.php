<?php

namespace App\Support;

use App\Models\Order;
use App\Models\UnitWork;
use App\Models\UnitWorkSection;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class HppSpecialAreaPolicy
{
    /** @var Collection<int, UnitWork>|null */
    private ?Collection $organizationUnits = null;

    /**
     * @return array{
     *     available: bool,
     *     can_submit: bool,
     *     reason: string,
     *     missing_roles: list<string>,
     *     requester_names: array<string, ?string>
     * }
     */
    public function availability(Order $order): array
    {
        $inspection = $this->inspect($order);

        if ($inspection['error'] !== null) {
            return [
                'available' => false,
                'can_submit' => false,
                'reason' => $inspection['error'],
                'missing_roles' => [],
                'requester_names' => [],
            ];
        }

        $missingRoles = [];

        if (! $inspection['senior_manager']) {
            $missingRoles[] = 'SM Peminta';
        }

        if (! $inspection['general_manager']) {
            $missingRoles[] = 'GM Peminta';
        }

        if ($missingRoles === []) {
            return [
                'available' => false,
                'can_submit' => false,
                'reason' => 'Khusus tidak diperlukan karena Manager, SM, dan GM Peminta sudah lengkap.',
                'missing_roles' => [],
                'requester_names' => $this->requesterNames($inspection),
            ];
        }

        $canSubmit = count($missingRoles) === 1;

        return [
            'available' => true,
            'can_submit' => $canSubmit,
            'reason' => $canSubmit
                ? 'Khusus tersedia karena '.implode(' dan ', $missingRoles).' belum ditetapkan pada struktur Unit Peminta.'
                : 'Khusus dapat disimpan sebagai draft, tetapi belum dapat disubmit karena SM dan GM Peminta belum ditetapkan.',
            'missing_roles' => $missingRoles,
            'requester_names' => $this->requesterNames($inspection),
        ];
    }

    /**
     * @return list<string>
     */
    public function resolveApprovalFlow(
        Order $order,
        string $category,
        string $bucket,
        bool $forSubmission,
    ): array {
        $availability = $this->availability($order);

        if (! $availability['available']) {
            throw ValidationException::withMessages([
                'area_pekerjaan' => $availability['reason'],
            ]);
        }

        if ($forSubmission && ! $availability['can_submit']) {
            throw ValidationException::withMessages([
                'area_pekerjaan' => 'HPP Khusus belum dapat disubmit karena SM dan GM Peminta sama-sama belum ditetapkan. Tetapkan kewenangan Peminta terlebih dahulu.',
            ]);
        }

        $baseFlow = HppApprovalFlow::resolveApprovalFlow($category, 'Dalam', $bucket);

        if ($baseFlow === []) {
            throw ValidationException::withMessages([
                'approval_flow' => 'Flow approval Dalam tidak ditemukan untuk kategori dan nilai HPP yang dipilih.',
            ]);
        }

        return array_values(array_filter(
            $baseFlow,
            fn (string $role): bool => ! in_array($role, $availability['missing_roles'], true),
        ));
    }

    /**
     * Resolve the exact requester structure used by Special HPP signatures.
     *
     * @return array{unit: UnitWork, section: UnitWorkSection}
     */
    public function requesterChain(Order $order): array
    {
        $inspection = $this->inspect($order);

        if ($inspection['error'] !== null || ! $inspection['unit'] || ! $inspection['section']) {
            throw ValidationException::withMessages([
                'approval' => $inspection['error'] ?: 'Struktur organisasi Peminta tidak dapat ditentukan.',
            ]);
        }

        return [
            'unit' => $inspection['unit'],
            'section' => $inspection['section'],
        ];
    }

    /**
     * @return array{
     *     unit: ?UnitWork,
     *     section: ?UnitWorkSection,
     *     manager: mixed,
     *     senior_manager: mixed,
     *     general_manager: mixed,
     *     error: ?string
     * }
     */
    private function inspect(Order $order): array
    {
        $unitResult = $this->matchUnit((string) $order->unit_kerja);

        if ($unitResult['error'] !== null) {
            return $this->failedInspection($unitResult['error']);
        }

        $unit = $unitResult['unit'];
        $sectionResult = $this->matchSection($unit, (string) $order->seksi);

        if ($sectionResult['error'] !== null) {
            return $this->failedInspection($sectionResult['error'], $unit);
        }

        $section = $sectionResult['section'];

        if (! $unit->department_id || ! $unit->department) {
            return $this->failedInspection(
                'Departemen Unit Peminta tidak ditemukan atau relasinya tidak valid.',
                $unit,
                $section,
            );
        }

        if (! $section->manager_id) {
            return $this->failedInspection(
                'Manager Peminta belum ditetapkan pada Seksi Order. HPP Khusus tidak dapat digunakan.',
                $unit,
                $section,
            );
        }

        if (! $section->manager) {
            return $this->failedInspection(
                'Akun Manager Peminta yang ditetapkan pada Seksi Order tidak ditemukan.',
                $unit,
                $section,
            );
        }

        if ($unit->senior_manager_id && ! $unit->seniorManager) {
            return $this->failedInspection(
                'Akun SM Peminta yang ditetapkan pada Unit Kerja Order tidak ditemukan.',
                $unit,
                $section,
            );
        }

        if ($unit->department->general_manager_id && ! $unit->department->generalManager) {
            return $this->failedInspection(
                'Akun GM Peminta yang ditetapkan pada Departemen Order tidak ditemukan.',
                $unit,
                $section,
            );
        }

        return [
            'unit' => $unit,
            'section' => $section,
            'manager' => $section->manager,
            'senior_manager' => $unit->seniorManager,
            'general_manager' => $unit->department->generalManager,
            'error' => null,
        ];
    }

    /** @return array{unit: ?UnitWork, error: ?string} */
    private function matchUnit(string $name): array
    {
        $name = trim($name);

        if ($name === '') {
            return ['unit' => null, 'error' => 'Unit Kerja pada Order belum tersedia.'];
        }

        $units = $this->organizationUnits();
        $exact = $units->filter(fn (UnitWork $unit): bool => strcasecmp(trim($unit->name), $name) === 0)->values();

        if ($exact->count() > 1) {
            return ['unit' => null, 'error' => 'Unit Kerja Order ditemukan lebih dari satu pada Struktur Organisasi.'];
        }

        if ($exact->count() === 1) {
            return ['unit' => $exact->first(), 'error' => null];
        }

        $target = $this->normalizeStructureName($name);
        $normalized = $units
            ->filter(fn (UnitWork $unit): bool => $this->normalizeStructureName($unit->name) === $target)
            ->values();

        if ($normalized->count() !== 1) {
            return [
                'unit' => null,
                'error' => $normalized->isEmpty()
                    ? 'Unit Kerja Order tidak ditemukan pada Struktur Organisasi.'
                    : 'Unit Kerja Order ambigu pada Struktur Organisasi.',
            ];
        }

        return ['unit' => $normalized->first(), 'error' => null];
    }

    /** @return array{section: ?UnitWorkSection, error: ?string} */
    private function matchSection(UnitWork $unit, string $name): array
    {
        $name = trim($name);

        if ($name === '') {
            return ['section' => null, 'error' => 'Seksi pada Order belum tersedia.'];
        }

        $sections = $unit->sections;
        $exact = $sections
            ->filter(fn (UnitWorkSection $section): bool => strcasecmp(trim($section->name), $name) === 0)
            ->values();

        if ($exact->count() > 1) {
            return ['section' => null, 'error' => 'Seksi Order ditemukan lebih dari satu pada Unit Kerja Peminta.'];
        }

        if ($exact->count() === 1) {
            return ['section' => $exact->first(), 'error' => null];
        }

        $target = $this->normalizeStructureName($name);
        $normalized = $sections
            ->filter(fn (UnitWorkSection $section): bool => $this->normalizeStructureName($section->name) === $target)
            ->values();

        if ($normalized->count() !== 1) {
            return [
                'section' => null,
                'error' => $normalized->isEmpty()
                    ? 'Seksi Order tidak ditemukan pada Unit Kerja Peminta.'
                    : 'Seksi Order ambigu pada Unit Kerja Peminta.',
            ];
        }

        return ['section' => $normalized->first(), 'error' => null];
    }

    /** @return Collection<int, UnitWork> */
    private function organizationUnits(): Collection
    {
        return $this->organizationUnits ??= UnitWork::query()
            ->with([
                'department.generalManager',
                'seniorManager',
                'sections.manager',
            ])
            ->orderBy('id')
            ->get();
    }

    private function normalizeStructureName(?string $value): string
    {
        $normalized = strtolower(trim((string) $value));
        $normalized = str_replace(['&', 'sction'], [' and ', 'section'], $normalized);
        $normalized = preg_replace('/[^a-z0-9]+/', ' ', $normalized) ?? '';
        $tokens = array_values(array_filter(
            explode(' ', $normalized),
            fn (string $token): bool => ! in_array($token, ['unit', 'section', 'of', 'and', 'design'], true),
        ));

        return implode(' ', $tokens);
    }

    /**
     * @return array{unit: ?UnitWork, section: ?UnitWorkSection, manager: null, senior_manager: null, general_manager: null, error: string}
     */
    private function failedInspection(string $message, ?UnitWork $unit = null, ?UnitWorkSection $section = null): array
    {
        return [
            'unit' => $unit,
            'section' => $section,
            'manager' => null,
            'senior_manager' => null,
            'general_manager' => null,
            'error' => $message,
        ];
    }

    /** @param array<string, mixed> $inspection */
    private function requesterNames(array $inspection): array
    {
        return [
            'Manager Peminta' => $inspection['manager']?->name,
            'SM Peminta' => $inspection['senior_manager']?->name,
            'GM Peminta' => $inspection['general_manager']?->name,
        ];
    }
}
