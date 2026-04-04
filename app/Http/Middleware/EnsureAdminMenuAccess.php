<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\AdminMenuRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminMenuAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $menuKey): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole(User::ROLE_ADMIN)) {
            return redirect()->route('login');
        }

        if (AdminMenuRegistry::canAccess($user, $menuKey)) {
            return $next($request);
        }

        return redirect()
            ->route('admin.dashboard')
            ->with('status', 'Anda tidak memiliki akses ke menu tersebut.');
    }
}
