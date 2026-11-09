<?php

namespace App\Http\Controllers\Pkm;

use App\Http\Controllers\Controller;
use App\Http\Requests\Pkm\GlobalSearchRequest;
use App\Services\Pkm\PkmGlobalSearchService;
use Illuminate\Http\JsonResponse;

class GlobalSearchController extends Controller
{
    public function __invoke(GlobalSearchRequest $request, PkmGlobalSearchService $searchService): JsonResponse
    {
        $results = $searchService->search((string) $request->validated()['q']);

        return response()->json([
            'success' => true,
            'results' => $results,
            'message' => $results === [] ? 'Tidak ada hasil ditemukan.' : null,
        ]);
    }
}
