@php
    $impersonation = app(\App\Services\Auth\UserImpersonationService::class);
    $impersonationActive = $impersonation->isActive(request());
    $originalUser = $impersonationActive ? $impersonation->originalUser(request()) : null;
    $targetUser = $impersonationActive ? auth()->user() : null;
@endphp

@if ($impersonationActive && $originalUser && $targetUser)
    <div data-impersonation-banner class="border-b border-amber-300 bg-amber-100 px-3 py-2 text-amber-950">
        <div class="mx-auto flex max-w-[1600px] flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-0 items-start gap-2.5">
                <span class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-amber-200 text-amber-800">
                    <i data-lucide="user-round-cog" class="h-4 w-4"></i>
                </span>
                <div class="min-w-0 text-xs leading-5 sm:text-sm">
                    <span class="font-bold uppercase tracking-[0.12em]">Mode Maintenance</span>
                    <span class="mx-1 hidden text-amber-500 sm:inline">•</span>
                    <span class="block sm:inline">
                        Masuk sebagai <strong>{{ $targetUser->name }}</strong> — {{ \App\Models\User::roleLabels()[$targetUser->role] ?? strtoupper($targetUser->role) }}.
                        Akun asli: <strong>{{ $originalUser->name }}</strong> — Super Admin.
                    </span>
                </div>
            </div>

            <form method="POST" action="{{ route('impersonation.stop') }}" class="shrink-0">
                @csrf
                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-amber-700 px-3 py-2 text-xs font-semibold text-white transition hover:bg-amber-800 sm:w-auto">
                    <i data-lucide="log-out" class="h-4 w-4"></i>
                    Kembali ke Super Admin
                </button>
            </form>
        </div>
    </div>
@endif
