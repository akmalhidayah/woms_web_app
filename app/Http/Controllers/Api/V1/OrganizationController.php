<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\OrganizationSectionsRequest;
use App\Http\Requests\Api\V1\OrganizationUnitsRequest;
use App\Services\Api\OrganizationSnapshotService;
use Illuminate\Http\JsonResponse;

class OrganizationController extends Controller
{
    public function __construct(
        private readonly OrganizationSnapshotService $organization,
    ) {}

    public function snapshot(): JsonResponse
    {
        return response()->json($this->organization->snapshot());
    }

    public function departments(): JsonResponse
    {
        return response()->json($this->organization->departments());
    }

    public function units(OrganizationUnitsRequest $request): JsonResponse
    {
        $departmentId = $request->validated('department_id');

        return response()->json(
            $this->organization->units($departmentId === null ? null : (int) $departmentId)
        );
    }

    public function sections(OrganizationSectionsRequest $request): JsonResponse
    {
        $unitWorkId = $request->validated('unit_work_id');

        return response()->json(
            $this->organization->sections($unitWorkId === null ? null : (int) $unitWorkId)
        );
    }
}
