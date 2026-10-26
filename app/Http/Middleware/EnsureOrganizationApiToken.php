<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class EnsureOrganizationApiToken
{
    public const ABILITY = 'organization:read';

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $accessToken = $user?->currentAccessToken();

        if (
            blank($request->bearerToken())
            || ! $user
            || ! $accessToken instanceof PersonalAccessToken
            || ($accessToken->expires_at !== null && $accessToken->expires_at->isPast())
        ) {
            return $this->error('Unauthenticated', Response::HTTP_UNAUTHORIZED);
        }

        $configuredUserId = filter_var(
            config('services.organization_api.client_user_id'),
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );

        if ($configuredUserId === false || (int) $user->id !== $configuredUserId) {
            return $this->error('Token is not authorized for this integration', Response::HTTP_FORBIDDEN);
        }

        if (array_values($accessToken->abilities ?? []) !== [self::ABILITY]) {
            return $this->error('Token does not have the required organization:read ability', Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }

    private function error(string $message, int $status): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => null,
        ], $status);
    }
}
