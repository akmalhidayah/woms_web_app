<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminHasSubrole
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string ...$subroles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole(User::ROLE_ADMIN)) {
            return redirect()->route('login');
        }

        if (in_array($user->resolvedAdminRole(), $subroles, true)) {
            return $next($request);
        }

        return redirect()->route('admin.dashboard');
    }
}
