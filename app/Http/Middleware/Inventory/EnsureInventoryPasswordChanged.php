<?php

namespace App\Http\Middleware\Inventory;

use App\Models\Inventory\InventoryUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInventoryPasswordChanged
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof InventoryUser && $user->must_change_password) {
            return response()->json([
                'success' => false,
                'message' => 'Anda harus mengganti password sebelum menggunakan aplikasi.',
            ], 403);
        }

        return $next($request);
    }
}
