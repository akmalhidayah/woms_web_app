<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\UserImpersonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UserImpersonationController extends Controller
{
    public function start(
        Request $request,
        User $user,
        UserImpersonationService $impersonation
    ): RedirectResponse {
        $impersonation->start($request, $request->user(), $user);

        return redirect()
            ->route($user->dashboardRouteName())
            ->with('success', 'Anda sekarang masuk sebagai '.$user->name.'.');
    }

    public function stop(Request $request, UserImpersonationService $impersonation): RedirectResponse
    {
        return $impersonation->stop($request);
    }
}
