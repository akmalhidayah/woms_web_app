<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserImpersonationService
{
    public const SESSION_IMPERSONATOR_ID = 'impersonator_user_id';

    public const SESSION_TARGET_ID = 'impersonated_user_id';

    public const SESSION_STARTED_AT = 'impersonation_started_at';

    public function isActive(Request $request): bool
    {
        $impersonatorId = $request->session()->get(self::SESSION_IMPERSONATOR_ID);
        $targetId = $request->session()->get(self::SESSION_TARGET_ID);

        return filled($impersonatorId)
            && filled($targetId)
            && (int) $request->user()?->getKey() === (int) $targetId;
    }

    public function hasContext(Request $request): bool
    {
        return $request->session()->has(self::SESSION_IMPERSONATOR_ID)
            || $request->session()->has(self::SESSION_TARGET_ID)
            || $request->session()->has(self::SESSION_STARTED_AT);
    }

    public function originalUser(Request $request): ?User
    {
        $id = $request->session()->get(self::SESSION_IMPERSONATOR_ID);

        return filled($id) ? User::query()->find($id) : null;
    }

    public function targetUser(Request $request): ?User
    {
        $id = $request->session()->get(self::SESSION_TARGET_ID);

        return filled($id) ? User::query()->find($id) : null;
    }

    public function start(Request $request, User $impersonator, User $target): void
    {
        abort_unless($impersonator->isSuperAdmin(), 403, 'Hanya Super Admin yang dapat masuk sebagai user lain.');
        abort_if(
            $this->hasContext($request),
            403,
            'Anda sedang masuk sebagai user lain. Kembali ke akun Super Admin terlebih dahulu.'
        );
        abort_if($impersonator->is($target), 403, 'Anda tidak dapat masuk sebagai akun sendiri.');
        abort_if($target->isSuperAdmin(), 403, 'Akun Super Admin tidak dapat dijadikan target.');
        abort_unless(
            in_array($target->role, User::roles(), true),
            403,
            'Role akun target tidak valid.'
        );

        Auth::guard('web')->login($target, false);
        $request->session()->regenerate();
        $request->session()->put([
            self::SESSION_IMPERSONATOR_ID => $impersonator->getKey(),
            self::SESSION_TARGET_ID => $target->getKey(),
            self::SESSION_STARTED_AT => now()->toDateTimeString(),
        ]);

        Log::notice('impersonation.started', $this->logContext($request, $impersonator, $target));
    }

    public function stop(Request $request): RedirectResponse
    {
        if (! $this->hasContext($request)) {
            abort(403, 'Mode maintenance tidak sedang aktif.');
        }

        $impersonatorId = $request->session()->get(self::SESSION_IMPERSONATOR_ID);
        $targetId = $request->session()->get(self::SESSION_TARGET_ID);
        $target = $request->user();

        if (
            blank($impersonatorId)
            || blank($targetId)
            || ! $target
            || (int) $target->getKey() !== (int) $targetId
        ) {
            return $this->failSafely($request, 'Session impersonation tidak konsisten.', $target);
        }

        $impersonator = User::query()->find($impersonatorId);

        if (! $impersonator || ! $impersonator->isSuperAdmin()) {
            return $this->failSafely(
                $request,
                'Akun Super Admin asal tidak ditemukan atau tidak lagi valid.',
                $target
            );
        }

        Auth::guard('web')->login($impersonator, false);
        $request->session()->regenerate();
        $this->clear($request);

        Log::notice('impersonation.stopped', $this->logContext($request, $impersonator, $target));

        return redirect()
            ->route('admin.user-panel.index')
            ->with('success', 'Anda telah kembali ke akun Super Admin.');
    }

    public function clear(Request $request): void
    {
        $request->session()->forget([
            self::SESSION_IMPERSONATOR_ID,
            self::SESSION_TARGET_ID,
            self::SESSION_STARTED_AT,
        ]);
    }

    private function failSafely(Request $request, string $reason, ?User $target): RedirectResponse
    {
        Log::warning('impersonation.failed', [
            'reason' => $reason,
            'current_user_id' => $target?->getKey(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $this->clear($request);
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('error', 'Akun Super Admin asal tidak dapat dipulihkan. Silakan login kembali.');
    }

    /**
     * @return array<string, mixed>
     */
    private function logContext(Request $request, User $impersonator, User $target): array
    {
        return [
            'impersonator_user_id' => $impersonator->getKey(),
            'impersonator_name' => $impersonator->name,
            'target_user_id' => $target->getKey(),
            'target_name' => $target->name,
            'target_role' => $target->role,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ];
    }
}
