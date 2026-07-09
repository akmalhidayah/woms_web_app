<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#7f1017">

        <title>{{ $title ?? config('app.name', 'WOMS') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('assets/branding/logos/logo-st2.png') }}?v=tonasa">
        <link rel="shortcut icon" type="image/png" href="{{ asset('assets/branding/logos/logo-st2.png') }}?v=tonasa">
        <link rel="apple-touch-icon" href="{{ asset('assets/branding/logos/logo-st2.png') }}?v=tonasa">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
        <style>[x-cloak]{ display:none !important; }</style>
    </head>
    <body class="min-h-screen bg-slate-50 font-sans text-slate-800 antialiased">
        @php
            $logoSigAvif = asset('images/auth/sig-logo.avif');
            $logoSigWebp = asset('images/auth/sig-logo.webp');
            $logoSigFallback = asset('images/auth/sig-logo.png');
            $logoStAvif = asset('images/auth/st-logo.avif');
            $logoStWebp = asset('images/auth/st-logo.webp');
            $logoStFallback = asset('images/auth/st-logo.png');
            $user = auth()->user();
            $currentRoute = request()->route()?->getName();
            $userNotifications = \App\Support\UserNotificationCenter::notifications(5, $user);
            $userNotificationCount = \App\Support\UserNotificationCenter::notificationCount($user);
            $userNotificationBadge = $userNotificationCount > 9 ? '9+' : (string) $userNotificationCount;
            $isApprover = $user?->role === \App\Models\User::ROLE_APPROVER;
            $approvalInboxDocuments = $isApprover ? \App\Support\ApprovalDocumentInbox::pendingPreviewFor($user, 6) : collect();
            $approvalInboxCount = $isApprover ? \App\Support\ApprovalDocumentInbox::pendingCountFor($user) : 0;
            $approvalInboxBadge = $approvalInboxCount > 9 ? '9+' : (string) $approvalInboxCount;
            $notificationToneClasses = [
                'blue' => 'bg-blue-50 text-blue-700 ring-blue-100',
                'amber' => 'bg-amber-50 text-amber-700 ring-amber-100',
                'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            ];
            $approvalTypeToneClasses = [
                'hpp' => 'bg-blue-50 text-blue-700 ring-blue-100',
                'bast' => 'bg-orange-50 text-orange-700 ring-orange-100',
                'initial_work' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
                'quality_control' => 'bg-violet-50 text-violet-700 ring-violet-100',
            ];
            $approvalTypeIcons = [
                'hpp' => 'file-text',
                'bast' => 'clipboard-check',
                'initial_work' => 'bolt',
                'quality_control' => 'badge-check',
            ];
        @endphp

        <div x-data="{ mobileMenu: false, profileOpen: false, notificationsOpen: false, approvalInboxOpen: false }" class="relative min-h-screen">
            <header class="sticky top-0 z-30 border-b border-red-950/20 bg-[#7f1017] shadow-lg shadow-red-950/10">
                <div class="mx-auto flex max-w-none items-center justify-between gap-4 px-3 py-2.5 sm:px-4 lg:px-6 lg:py-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <div class="flex shrink-0 items-center gap-2 rounded-xl border border-white/20 bg-white px-2.5 py-1.5 shadow-sm">
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-white">
                                <picture>
                                    <source srcset="{{ $logoSigAvif }}" type="image/avif">
                                    <source srcset="{{ $logoSigWebp }}" type="image/webp">
                                    <img src="{{ $logoSigFallback }}" alt="SIG" width="220" height="220" class="max-h-full w-auto object-contain">
                                </picture>
                            </div>
                            <div class="h-7 w-px bg-slate-200"></div>
                            <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-red-50">
                                <picture>
                                    <source srcset="{{ $logoStAvif }}" type="image/avif">
                                    <source srcset="{{ $logoStWebp }}" type="image/webp">
                                    <img src="{{ $logoStFallback }}" alt="Semen Tonasa" width="220" height="220" class="max-h-full w-auto object-contain">
                                </picture>
                            </div>
                        </div>

                        <div class="hidden min-w-0 sm:block">
                            <div class="truncate text-base font-black tracking-tight text-white sm:text-lg">Dept. Project Management &amp; Main Support</div>
                            <div class="mt-0.5 hidden truncate text-xs text-red-100 sm:block">Section of Machine Workshop</div>
                        </div>
                    </div>

                    <div class="hidden items-center gap-3 md:flex">
                        <a
                            href="{{ route('user.dashboard') }}"
                            class="rounded-xl border px-4 py-1.5 text-sm font-semibold transition {{ $currentRoute === 'user.dashboard' ? 'border-white/25 bg-white text-red-800 shadow-sm' : 'border-white/20 bg-white/10 text-white hover:bg-white/15' }}"
                        >
                            Dashboard
                        </a>
                        @if ($isApprover)
                            <div class="relative" @click.outside="approvalInboxOpen = false">
                                <button
                                    type="button"
                                    @click="approvalInboxOpen = !approvalInboxOpen; notificationsOpen = false; profileOpen = false"
                                    class="relative inline-flex h-10 w-10 items-center justify-center rounded-xl border transition {{ request()->routeIs('approval-documents.*') ? 'border-white/25 bg-white text-red-800 shadow-sm' : 'border-white/20 bg-white/10 text-white hover:bg-white/15' }}"
                                    aria-label="Approval Inbox"
                                >
                                    <i data-lucide="inbox" class="h-5 w-5"></i>
                                    @if ($approvalInboxCount > 0)
                                        <span class="absolute -right-1 -top-1 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white">{{ $approvalInboxBadge }}</span>
                                    @endif
                                </button>

                                <div
                                    x-show="approvalInboxOpen"
                                    x-transition.origin.top.right
                                    x-cloak
                                    class="absolute right-0 z-50 mt-2 w-[min(92vw,24rem)] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
                                >
                                    <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-4 py-3">
                                        <div>
                                            <div class="text-sm font-bold text-slate-900">Approval Inbox</div>
                                            <a href="{{ route('approval-documents.index') }}" class="mt-1 inline-flex text-[11px] font-bold text-blue-700 transition hover:text-blue-900">
                                                Lihat semua
                                            </a>
                                        </div>
                                        @if ($approvalInboxCount > 0)
                                            <span class="rounded-full bg-red-50 px-2.5 py-1 text-[10px] font-bold text-red-700 ring-1 ring-red-100">{{ $approvalInboxBadge }}</span>
                                        @endif
                                    </div>

                                    <div class="max-h-[min(70vh,24rem)] overflow-y-auto">
                                        @forelse ($approvalInboxDocuments as $item)
                                            @php
                                                $toneClass = $approvalTypeToneClasses[$item['type'] ?? 'hpp'] ?? $approvalTypeToneClasses['hpp'];
                                                $iconName = $approvalTypeIcons[$item['type'] ?? 'hpp'] ?? 'file-text';
                                            @endphp
                                            <a href="{{ $item['open_url'] ?? route('approval-documents.open', ['type' => $item['type'], 'id' => $item['id']]) }}" class="group flex w-full gap-3 border-b border-slate-100 px-4 py-3 text-left transition last:border-b-0 hover:bg-slate-50">
                                                <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ring-1 {{ $toneClass }}">
                                                    <i data-lucide="{{ $iconName }}" class="h-4 w-4"></i>
                                                </span>
                                                <span class="min-w-0 flex-1">
                                                    <span class="block text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ $item['type_label'] }}</span>
                                                    <span class="mt-1 block truncate text-sm font-semibold leading-5 text-slate-900">{{ $item['number'] }} - {{ $item['title'] }}</span>
                                                    <span class="mt-1 flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
                                                        <span class="truncate">Step: {{ $item['step'] ?: '-' }}</span>
                                                        <span class="text-slate-300">/</span>
                                                        <span>{{ optional($item['submitted_at'])->format('d/m/Y') ?: '-' }}</span>
                                                    </span>
                                                </span>
                                                <span class="mt-1 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-slate-400 transition group-hover:text-slate-600">
                                                    <i data-lucide="arrow-right" class="h-4 w-4"></i>
                                                </span>
                                            </a>
                                        @empty
                                            <div class="px-4 py-8 text-center">
                                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-slate-400 ring-1 ring-slate-100">
                                                    <i data-lucide="inbox" class="h-5 w-5"></i>
                                                </div>
                                                <div class="mt-3 text-sm font-semibold text-slate-700">Tidak ada dokumen approval</div>
                                                <div class="mt-1 text-xs leading-5 text-slate-500">Dokumen akan muncul saat step approval aktif menunjuk ke akun Anda.</div>
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        @endif
                        <div class="relative" @click.outside="notificationsOpen = false">
                            <button
                                type="button"
                                @click="notificationsOpen = !notificationsOpen; approvalInboxOpen = false; profileOpen = false"
                                class="relative inline-flex h-10 w-10 items-center justify-center rounded-xl border border-white/20 bg-white/10 text-white transition hover:bg-white/15"
                                aria-label="Pemberitahuan"
                            >
                                <i data-lucide="bell" class="h-5 w-5"></i>
                                @if ($userNotificationCount > 0)
                                    <span class="absolute -right-1 -top-1 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white">{{ $userNotificationBadge }}</span>
                                @endif
                            </button>

                            <div
                                x-show="notificationsOpen"
                                x-transition.origin.top.right
                                x-cloak
                                class="absolute right-0 z-50 mt-2 w-[min(92vw,24rem)] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
                            >
                                <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-4 py-3">
                                    <div>
                                        <div class="text-sm font-bold text-slate-900">Pemberitahuan</div>
                                        @if ($userNotificationCount > 0)
                                            <form method="POST" action="{{ route('user.notifications.read-all') }}" class="mt-1">
                                                @csrf
                                                <button type="submit" class="text-[11px] font-bold text-blue-700 transition hover:text-blue-900">
                                                    Tandai semua dibaca
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                    @if ($userNotificationCount > 0)
                                        <span class="rounded-full bg-red-50 px-2.5 py-1 text-[10px] font-bold text-red-700 ring-1 ring-red-100">{{ $userNotificationBadge }}</span>
                                    @endif
                                </div>

                                <div class="max-h-[min(70vh,24rem)] overflow-y-auto">
                                    @forelse ($userNotifications as $notification)
                                        @php
                                            $toneClass = $notificationToneClasses[$notification['tone'] ?? 'blue'] ?? $notificationToneClasses['blue'];
                                        @endphp
                                        <form method="POST" action="{{ route('user.notifications.read') }}" class="border-b border-slate-100 last:border-b-0">
                                            @csrf
                                            <input type="hidden" name="notification_key" value="{{ $notification['key'] }}">
                                            <input type="hidden" name="redirect_url" value="{{ $notification['url'] }}">
                                            <button type="submit" class="group flex w-full gap-3 px-4 py-3 text-left transition hover:bg-slate-50">
                                                <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ring-1 {{ $toneClass }}">
                                                    <i data-lucide="{{ $notification['icon'] }}" class="h-4 w-4"></i>
                                                </span>
                                                <span class="min-w-0 flex-1">
                                                    <span class="block text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ $notification['type'] }}</span>
                                                    <span class="mt-1 block text-sm font-semibold leading-5 text-slate-900">{{ $notification['message'] }}</span>
                                                    <span class="mt-1 flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
                                                        <span class="truncate">{{ $notification['meta'] ?: '-' }}</span>
                                                        <span class="text-slate-300">/</span>
                                                        <span>{{ optional($notification['occurred_at'])->diffForHumans() }}</span>
                                                    </span>
                                                </span>
                                                <span class="mt-1 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-slate-400 transition group-hover:text-slate-600">
                                                    <i data-lucide="check-check" class="h-4 w-4"></i>
                                                </span>
                                            </button>
                                        </form>
                                    @empty
                                        <div class="px-4 py-8 text-center">
                                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-slate-400 ring-1 ring-slate-100">
                                                <i data-lucide="bell-off" class="h-5 w-5"></i>
                                            </div>
                                            <div class="mt-3 text-sm font-semibold text-slate-700">Belum ada pemberitahuan</div>
                                            <div class="mt-1 text-xs leading-5 text-slate-500">Order approved yang sudah dibaca tidak ditampilkan lagi.</div>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        <div class="relative" @click.outside="profileOpen = false">
                            <button
                                type="button"
                                @click="profileOpen = !profileOpen; notificationsOpen = false; approvalInboxOpen = false"
                                class="inline-flex items-center gap-2.5 rounded-xl border border-white/20 bg-white px-3 py-1.5 shadow-sm transition hover:bg-red-50"
                            >
                                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-50 text-xs font-bold text-red-700">
                                    {{ $user?->initials() ?: 'US' }}
                                </div>
                                <div class="max-w-[180px] text-left">
                                    <div class="truncate text-sm font-semibold text-slate-900">{{ $user?->name }}</div>
                                    <div class="truncate text-xs text-slate-500">{{ $user?->email }}</div>
                                </div>
                                <i data-lucide="chevron-down" class="h-4 w-4 text-slate-400"></i>
                            </button>

                            <div
                                x-show="profileOpen"
                                x-transition.origin.top.right
                                x-cloak
                                class="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl"
                            >
                                <div class="border-b border-slate-100 px-4 py-3">
                                    <div class="text-sm font-semibold text-slate-900">{{ $user?->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $user?->email }}</div>
                                </div>

                                <a href="{{ route('settings.profile') }}" class="block px-4 py-3 text-sm text-slate-700 transition hover:bg-slate-50">
                                    Profile
                                </a>

                                @if ($user?->role === 'pkm')
                                    <a href="{{ route('pkm.dashboard') }}" class="block px-4 py-3 text-sm text-slate-700 transition hover:bg-slate-50">
                                        Kembali ke Dashboard PKM
                                    </a>
                                @endif

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full px-4 py-3 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                        Log Out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 md:hidden">
                        @if ($isApprover)
                            <div class="relative" @click.outside="approvalInboxOpen = false">
                                <button
                                    type="button"
                                    @click="approvalInboxOpen = !approvalInboxOpen; notificationsOpen = false; mobileMenu = false"
                                    class="relative inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-white/20 bg-white text-red-800 shadow-sm"
                                    aria-label="Approval Inbox"
                                >
                                    <i data-lucide="inbox" class="h-5 w-5"></i>
                                    @if ($approvalInboxCount > 0)
                                        <span class="absolute -right-1 -top-1 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white">{{ $approvalInboxBadge }}</span>
                                    @endif
                                </button>

                             <div
    x-show="approvalInboxOpen"
    x-transition.origin.top
    x-cloak
    class="fixed left-3 right-3 top-[4.75rem] z-[60] max-h-[calc(100dvh-6rem)] overflow-hidden rounded-2xl border border-slate-200 bg-white text-left shadow-xl"
>
                                    <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-4 py-3">
                                        <div>
                                            <div class="text-sm font-bold text-slate-900">Approval Inbox</div>
                                            <a href="{{ route('approval-documents.index') }}" class="mt-1 inline-flex text-[11px] font-bold text-blue-700 transition hover:text-blue-900">
                                                Lihat semua
                                            </a>
                                        </div>
                                        @if ($approvalInboxCount > 0)
                                            <span class="rounded-full bg-red-50 px-2.5 py-1 text-[10px] font-bold text-red-700 ring-1 ring-red-100">{{ $approvalInboxBadge }}</span>
                                        @endif
                                    </div>

                                    <div class="max-h-[min(62vh,22rem)] overflow-y-auto">
                                        @forelse ($approvalInboxDocuments as $item)
                                            @php
                                                $toneClass = $approvalTypeToneClasses[$item['type'] ?? 'hpp'] ?? $approvalTypeToneClasses['hpp'];
                                                $iconName = $approvalTypeIcons[$item['type'] ?? 'hpp'] ?? 'file-text';
                                            @endphp
                                            <a href="{{ $item['open_url'] ?? route('approval-documents.open', ['type' => $item['type'], 'id' => $item['id']]) }}" class="group flex w-full gap-3 border-b border-slate-100 px-4 py-3 text-left transition last:border-b-0 hover:bg-slate-50">
                                                <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ring-1 {{ $toneClass }}">
                                                    <i data-lucide="{{ $iconName }}" class="h-4 w-4"></i>
                                                </span>
                                                <span class="min-w-0 flex-1">
                                                    <span class="block text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">{{ $item['type_label'] }}</span>
                                                    <span class="mt-1 block truncate text-xs font-semibold leading-4 text-slate-900">{{ $item['number'] }} - {{ $item['title'] }}</span>
                                                    <span class="mt-1 block truncate text-[11px] text-slate-500">Step: {{ $item['step'] ?: '-' }}</span>
                                                    <span class="mt-0.5 block text-[11px] text-slate-400">{{ optional($item['submitted_at'])->format('d/m/Y') ?: '-' }}</span>
                                                </span>
                                            </a>
                                        @empty
                                            <div class="px-4 py-8 text-center">
                                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-slate-400 ring-1 ring-slate-100">
                                                    <i data-lucide="inbox" class="h-5 w-5"></i>
                                                </div>
                                                <div class="mt-3 text-sm font-semibold text-slate-700">Tidak ada dokumen approval</div>
                                                <div class="mt-1 text-xs leading-5 text-slate-500">Dokumen akan muncul saat step approval aktif menunjuk ke akun Anda.</div>
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        @endif
                        <div class="relative" @click.outside="notificationsOpen = false">
                            <button
                                type="button"
                                @click="notificationsOpen = !notificationsOpen; approvalInboxOpen = false; mobileMenu = false"
                                class="relative inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-white/20 bg-white text-red-800 shadow-sm"
                                aria-label="Pemberitahuan"
                            >
                                <i data-lucide="bell" class="h-5 w-5"></i>
                                @if ($userNotificationCount > 0)
                                    <span class="absolute -right-1 -top-1 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white">{{ $userNotificationBadge }}</span>
                                @endif
                            </button>

                          <div
    x-show="notificationsOpen"
    x-transition.origin.top
    x-cloak
    class="fixed left-3 right-3 top-[4.75rem] z-[60] max-h-[calc(100dvh-6rem)] overflow-hidden rounded-2xl border border-slate-200 bg-white text-left shadow-xl"
>
                                <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-4 py-3">
                                    <div>
                                        <div class="text-sm font-bold text-slate-900">Pemberitahuan</div>
                                        @if ($userNotificationCount > 0)
                                            <form method="POST" action="{{ route('user.notifications.read-all') }}" class="mt-1">
                                                @csrf
                                                <button type="submit" class="text-[11px] font-bold text-blue-700 transition hover:text-blue-900">
                                                    Tandai semua dibaca
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                    @if ($userNotificationCount > 0)
                                        <span class="rounded-full bg-red-50 px-2.5 py-1 text-[10px] font-bold text-red-700 ring-1 ring-red-100">{{ $userNotificationBadge }}</span>
                                    @endif
                                </div>

                                <div class="max-h-[min(62vh,22rem)] overflow-y-auto">
                                    @forelse ($userNotifications as $notification)
                                        @php
                                            $toneClass = $notificationToneClasses[$notification['tone'] ?? 'blue'] ?? $notificationToneClasses['blue'];
                                        @endphp
                                        <form method="POST" action="{{ route('user.notifications.read') }}" class="border-b border-slate-100 last:border-b-0">
                                            @csrf
                                            <input type="hidden" name="notification_key" value="{{ $notification['key'] }}">
                                            <input type="hidden" name="redirect_url" value="{{ $notification['url'] }}">
                                            <button type="submit" class="group flex w-full gap-3 px-4 py-3 text-left transition hover:bg-slate-50">
                                                <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ring-1 {{ $toneClass }}">
                                                    <i data-lucide="{{ $notification['icon'] }}" class="h-4 w-4"></i>
                                                </span>
                                                <span class="min-w-0 flex-1">
                                                    <span class="block text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400">{{ $notification['type'] }}</span>
                                                    <span class="mt-1 block text-xs font-semibold leading-4 text-slate-900">{{ $notification['message'] }}</span>
                                                    <span class="mt-1 block truncate text-[11px] text-slate-500">{{ $notification['meta'] ?: '-' }}</span>
                                                </span>
                                            </button>
                                        </form>
                                    @empty
                                        <div class="px-4 py-8 text-center">
                                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-slate-400 ring-1 ring-slate-100">
                                                <i data-lucide="bell-off" class="h-5 w-5"></i>
                                            </div>
                                            <div class="mt-3 text-sm font-semibold text-slate-700">Belum ada pemberitahuan</div>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>

                        <button
                            type="button"
                            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-white/20 bg-white text-red-800 shadow-sm"
                            @click="mobileMenu = !mobileMenu; notificationsOpen = false; approvalInboxOpen = false"
                            aria-label="Buka menu"
                        >
                            <i data-lucide="menu" class="h-5 w-5"></i>
                        </button>
                    </div>
                </div>

                <div x-show="mobileMenu" x-transition x-cloak class="border-t border-red-700 bg-white px-4 py-4 md:hidden">
                    <div class="space-y-3">
                        <a href="{{ route('user.dashboard') }}" class="block rounded-xl border border-red-200 bg-white px-4 py-3 text-sm font-semibold text-red-800">Dashboard</a>
                        <div class="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3">
                            <div class="text-sm font-semibold text-slate-900">{{ $user?->name }}</div>
                            <div class="text-xs text-slate-500">{{ $user?->email }}</div>
                        </div>
                        <a href="{{ route('settings.profile') }}" class="block rounded-xl border border-stone-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700">Profile</a>
                        @if ($user?->role === 'pkm')
                            <a href="{{ route('pkm.dashboard') }}" class="block rounded-xl border border-stone-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700">Kembali ke Dashboard PKM</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full rounded-xl border border-stone-200 bg-white px-4 py-3 text-left text-sm font-semibold text-slate-700">
                                Log Out
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="relative mx-auto max-w-none px-3 py-4 sm:px-4 lg:px-6 lg:py-5">
                {{ $slot }}
            </main>
        </div>
        @fluxScripts
    </body>
</html>
