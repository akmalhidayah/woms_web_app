<?php

namespace App\Http\Middleware;

use App\Domain\Orders\Enums\OrderUserNoteStatus;
use App\Models\Order;
use App\Models\User;
use App\Support\AdminMenuRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminOrderMenuAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        abort_unless(
            $user->hasRole(User::ROLE_ADMIN),
            Response::HTTP_FORBIDDEN,
            'Anda tidak memiliki hak akses untuk membuka halaman ini.',
        );

        $order = $request->route('order');

        abort_unless(
            $order instanceof Order,
            Response::HTTP_FORBIDDEN,
            'Jenis akses order tidak dapat ditentukan.',
        );

        $status = $order->catatan_status instanceof OrderUserNoteStatus
            ? $order->catatan_status
            : OrderUserNoteStatus::tryFrom((string) $order->catatan_status);

        $allowed = match ($status) {
            OrderUserNoteStatus::ApprovedWorkshop => AdminMenuRegistry::canAccess($user, AdminMenuRegistry::MENU_ORDER_BENGKEL),
            OrderUserNoteStatus::ApprovedWorkshopJasa => AdminMenuRegistry::canAccess($user, AdminMenuRegistry::MENU_ORDER_JASA)
                || AdminMenuRegistry::canAccess($user, AdminMenuRegistry::MENU_ORDER_BENGKEL),
            default => AdminMenuRegistry::canAccess($user, AdminMenuRegistry::MENU_ORDER_JASA),
        };

        abort_unless(
            $allowed,
            Response::HTTP_FORBIDDEN,
            'Anda tidak memiliki hak akses untuk membuka halaman ini.',
        );

        return $next($request);
    }
}
