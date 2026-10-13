<?php

namespace App\Services\Api;

use App\Models\Department;
use App\Models\UnitWork;
use App\Models\UnitWorkSection;
use App\Support\Api\V1\OrganizationPresenter;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use JsonException;

final class OrganizationSnapshotService
{
    public const SOURCE = 'WOMS';

    public const SCHEMA_VERSION = 1;

    public function __construct(
        private readonly OrganizationPresenter $presenter,
    ) {}

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    public function snapshot(): array
    {
        return DB::transaction(function (): array {
            $departments = Department::query()
                ->select(['id', 'name', 'general_manager_id'])
                ->with([
                    'generalManager:id,name,email',
                    'units' => fn ($query) => $query
                        ->select(['id', 'department_id', 'name', 'senior_manager_id'])
                        ->with([
                            'seniorManager:id,name,email',
                            'sections' => fn ($sectionQuery) => $sectionQuery
                                ->select(['id', 'unit_work_id', 'name', 'manager_id'])
                                ->with('manager:id,name,email')
                                ->reorder('id'),
                        ])
                        ->orderBy('id'),
                ])
                ->orderBy('id')
                ->get();

            $data = [
                'departments' => $departments
                    ->map(fn (Department $department): array => $this->presenter->department($department, includeUnits: true))
                    ->values()
                    ->all(),
            ];

            return $this->envelope($data, [
                'departments' => $departments->count(),
                'units' => $departments->sum(fn (Department $department): int => $department->units->count()),
                'sections' => $departments->sum(
                    fn (Department $department): int => $department->units->sum(
                        fn (UnitWork $unit): int => $unit->sections->count()
                    )
                ),
            ]);
        });
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    public function departments(): array
    {
        return DB::transaction(function (): array {
            $departments = Department::query()
                ->select(['id', 'name', 'general_manager_id'])
                ->with('generalManager:id,name,email')
                ->orderBy('id')
                ->get();

            $data = [
                'departments' => $departments
                    ->map(fn (Department $department): array => $this->presenter->department($department))
                    ->values()
                    ->all(),
            ];

            return $this->envelope($data, [
                'departments' => $departments->count(),
                'units' => 0,
                'sections' => 0,
            ]);
        });
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    public function units(?int $departmentId = null): array
    {
        return DB::transaction(function () use ($departmentId): array {
            $units = UnitWork::query()
                ->select(['id', 'department_id', 'name', 'senior_manager_id'])
                ->with([
                    'department:id,name',
                    'seniorManager:id,name,email',
                ])
                ->when($departmentId !== null, fn ($query) => $query->where('department_id', $departmentId))
                ->orderBy('id')
                ->get();

            $data = [
                'units' => $units
                    ->map(fn (UnitWork $unit): array => $this->presenter->unit($unit, includeDepartment: true))
                    ->values()
                    ->all(),
            ];

            return $this->envelope(
                $data,
                [
                    'departments' => $units->pluck('department_id')->unique()->count(),
                    'units' => $units->count(),
                    'sections' => 0,
                ],
                ['department_id' => $departmentId],
            );
        });
    }

    /**
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    public function sections(?int $unitWorkId = null): array
    {
        return DB::transaction(function () use ($unitWorkId): array {
            $sections = UnitWorkSection::query()
                ->select(['id', 'unit_work_id', 'name', 'manager_id'])
                ->with([
                    'unitWork' => fn ($query) => $query
                        ->select(['id', 'department_id', 'name'])
                        ->with('department:id,name'),
                    'manager:id,name,email',
                ])
                ->when($unitWorkId !== null, fn ($query) => $query->where('unit_work_id', $unitWorkId))
                ->orderBy('id')
                ->get();

            $data = [
                'sections' => $sections
                    ->map(fn (UnitWorkSection $section): array => $this->presenter->section($section, includeUnit: true))
                    ->values()
                    ->all(),
            ];

            return $this->envelope(
                $data,
                [
                    'departments' => $sections
                        ->map(fn (UnitWorkSection $section): ?int => $section->unitWork?->department_id)
                        ->filter()
                        ->unique()
                        ->count(),
                    'units' => $sections->pluck('unit_work_id')->unique()->count(),
                    'sections' => $sections->count(),
                ],
                ['unit_work_id' => $unitWorkId],
            );
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array{departments: int, units: int, sections: int}  $counts
     * @param  array<string, int|null>  $filters
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    private function envelope(array $data, array $counts, array $filters = []): array
    {
        $canonicalJson = json_encode(
            $data,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR,
        );

        $meta = [
            'source' => self::SOURCE,
            'schema_version' => self::SCHEMA_VERSION,
            'generated_at' => CarbonImmutable::now('UTC')->format('Y-m-d\TH:i:s\Z'),
            'complete' => true,
            'counts' => $counts,
            'checksum' => hash('sha256', $canonicalJson),
        ];

        $activeFilters = array_filter($filters, fn (?int $value): bool => $value !== null);

        if ($activeFilters !== []) {
            $meta['filters'] = $activeFilters;
        }

        return [
            'success' => true,
            'data' => $data,
            'meta' => $meta,
        ];
    }
}
