<?php

namespace App\Livewire\Actions;

use App\Models\User;
use App\Services\Auth\UserImpersonationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class Logout
{
    /**
     * Log the current user out of the application.
     */
    public function __invoke(?User $user = null)
    {
        $request = request();
        $impersonation = app(UserImpersonationService::class);

        if ($request->hasSession() && $impersonation->hasContext($request)) {
            return $impersonation->stop($request);
        }

        Auth::guard('web')->logout();

        Session::invalidate();
        Session::regenerateToken();

        return redirect()->route('login');
    }
}
