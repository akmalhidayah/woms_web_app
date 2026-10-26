<?php

namespace App\Http\Middleware;

use App\Support\AdminPresentationData;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RefreshAdminPresentationCache
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Refresh the acting admin's badges after a successful write, including
        // mark-as-read. Other users see external changes within the short TTL.
        if (! $request->isMethodSafe()
            && $response->getStatusCode() < 400
            && $request->user()?->isAdmin()) {
            app(AdminPresentationData::class)->forget($request->user());
        }

        return $response;
    }
}
