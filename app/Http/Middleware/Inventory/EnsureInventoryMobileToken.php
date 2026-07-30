<?php

namespace App\Http\Middleware\Inventory;

use App\Models\Inventory\InventoryUser;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInventoryMobileToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $token = $user?->currentAccessToken();

        if (
            ! $user instanceof InventoryUser
            || ! $user->exists
            || $user->trashed()
            || ! $user->is_active
            || ! $token
            || ! $token->can('inventory-mobile')
        ) {
            return $this->unauthenticated();
        }

        return $next($request);
    }

    private function unauthenticated(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'Token tidak tersedia atau tidak valid.',
        ], 401);
    }
}
