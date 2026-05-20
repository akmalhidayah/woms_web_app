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

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->hasRole(User::ROLE_ADMIN)) {
            abort(403, 'Anda tidak memiliki hak akses untuk membuka halaman ini.');
        }

        if (AdminMenuRegistry::canAccess($user, $menuKey)) {
            return $next($request);
        }

        abort(403, 'Anda tidak memiliki hak akses untuk membuka halaman ini.');
    }
}
