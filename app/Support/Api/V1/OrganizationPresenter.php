<?php

namespace App\Support\Api\V1;

use App\Models\Department;
use App\Models\UnitWork;
use App\Models\UnitWorkSection;
use App\Models\User;

final class OrganizationPresenter
{
    /**
     * @return array{id: int, name: string, email: string}|null
     */
    public function person(?User $user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'id' => (int) $user->id,
            'name' => (string) $user->name,
            'email' => (string) $user->email,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function department(Department $department, bool $includeUnits = false): array
    {
        $data = [
            'id' => (int) $department->id,
            'name' => (string) $department->name,
            'general_manager' => $this->person($department->generalManager),
        ];

        if ($includeUnits) {
            $data['units'] = $department->units
                ->map(fn (UnitWork $unit): array => $this->unit($unit, includeSections: true))
                ->values()
                ->all();
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function unit(
        UnitWork $unit,
        bool $includeDepartment = false,
        bool $includeSections = false,
    ): array {
        $data = [
            'id' => (int) $unit->id,
            'department_id' => (int) $unit->department_id,
            'name' => (string) $unit->name,
        ];

        if ($includeDepartment) {
            $data['department'] = $unit->department ? [
                'id' => (int) $unit->department->id,
                'name' => (string) $unit->department->name,
            ] : null;
        }

        $data['senior_manager'] = $this->person($unit->seniorManager);

        if ($includeSections) {
            $data['sections'] = $unit->sections
                ->map(fn (UnitWorkSection $section): array => $this->section($section))
                ->values()
                ->all();
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function section(UnitWorkSection $section, bool $includeUnit = false): array
    {
        $data = [
            'id' => (int) $section->id,
            'unit_work_id' => (int) $section->unit_work_id,
            'name' => (string) $section->name,
        ];

        if ($includeUnit) {
            $data['unit'] = $section->unitWork ? [
                'id' => (int) $section->unitWork->id,
                'department_id' => (int) $section->unitWork->department_id,
                'name' => (string) $section->unitWork->name,
                'department' => $section->unitWork->department ? [
                    'id' => (int) $section->unitWork->department->id,
                    'name' => (string) $section->unitWork->department->name,
                ] : null,
            ] : null;
        }

        $data['manager'] = $this->person($section->manager);

        return $data;
    }
}
