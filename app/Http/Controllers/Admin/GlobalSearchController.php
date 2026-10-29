<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\GlobalSearchRequest;
use App\Services\Admin\AdminGlobalSearchService;
use Illuminate\Http\JsonResponse;

class GlobalSearchController extends Controller
{
    public function __invoke(
        GlobalSearchRequest $request,
        AdminGlobalSearchService $searchService,
    ): JsonResponse {
        $validated = $request->validated();

        return response()->json([
            'results' => $searchService->search((string) $validated['q'], $request->user()),
        ]);
    }
}
