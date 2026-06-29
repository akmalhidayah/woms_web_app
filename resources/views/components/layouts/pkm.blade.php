<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'WOMS') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('assets/branding/logos/logo-st2.png') }}?v=tonasa">
        <link rel="shortcut icon" type="image/png" href="{{ asset('assets/branding/logos/logo-st2.png') }}?v=tonasa">
        <link rel="apple-touch-icon" href="{{ asset('assets/branding/logos/logo-st2.png') }}?v=tonasa">

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
        <script defer src="https://unpkg.com/lucide@latest"></script>

        <style>[x-cloak]{ display:none !important; }</style>
    </head>
    <body class="bg-slate-50 font-sans text-slate-800 antialiased">
        @php
            $logoBms = asset('assets/branding/logos/logo-bms2.png');
            $logoSig = asset('assets/branding/logos/logo-sig.png');
            $logoSt = asset('assets/branding/logos/logo-st2.png');
            $user = auth()->user();
            $userInitials = $user?->initials() ?: 'PK';
            $pkmNotifications = \App\Support\PkmNotificationCenter::notifications(5, $user);
            $pkmNotificationCount = \App\Support\PkmNotificationCenter::notificationCount($user);
            $pkmNotificationBadge = $pkmNotificationCount > 9 ? '9+' : (string) $pkmNotificationCount;
            $notificationToneClasses = [
                'blue' => 'bg-blue-50 text-blue-700 ring-blue-100',
                'amber' => 'bg-amber-50 text-amber-700 ring-amber-100',
                'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
                'rose' => 'bg-rose-50 text-rose-700 ring-rose-100',
            ];
            $pkmMenus = [
                ['route' => 'pkm.dashboard', 'icon' => 'layout-dashboard', 'label' => 'Dashboard'],
                ['route' => 'pkm.jobwaiting', 'icon' => 'bell', 'label' => 'List Pekerjaan'],
                ['route' => 'pkm.lhpp.index', 'icon' => 'file-text', 'label' => 'Buat BAST/LHPP'],
                ['route' => 'pkm.laporan', 'icon' => 'folder-open', 'label' => 'Dokumen'],
            ];
        @endphp

        <div
            x-data="{
                sidebarOpen: true,
                mobileOpen: false,
                profileOpen: false,
                notificationsOpen: false,
                toggle() {
                    if (window.innerWidth >= 1024) this.sidebarOpen = !this.sidebarOpen;
                    else this.mobileOpen = !this.mobileOpen;
                },
                closeMobile() {
                    this.mobileOpen = false;
                }
            }"
            x-init="$watch('mobileOpen', value => document.body.classList.toggle('overflow-hidden', value))"
            class="min-h-screen"
        >
            <div
                x-show="mobileOpen"
                x-transition.opacity
                x-cloak
                class="fixed inset-0 z-30 bg-black/40 lg:hidden"
                @click="closeMobile()"
            ></div>

            <aside
                class="fixed inset-y-0 left-0 z-40 flex w-60 flex-col border-r border-[#cb6b33]/35 bg-[#de773b] shadow-sm transition-all duration-300"
                :class="[
                    (mobileOpen ? 'translate-x-0' : '-translate-x-full') + ' lg:translate-x-0',
                    sidebarOpen ? 'lg:w-60' : 'lg:w-16'
                ]"
            >
                <div class="sticky top-0 z-10 border-b border-[#cb6b33]/35 bg-[#de773b]">
                    <div class="flex items-center justify-between gap-2 px-3 py-3">
                        <div class="flex min-w-0 items-center gap-2.5">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white p-1.5 shadow-sm">
                                <img src="{{ $logoBms }}" alt="Logo BMS2" class="max-h-full w-auto object-contain">
                            </div>

                            <div class="min-w-0" x-show="sidebarOpen" x-transition.opacity.duration.200ms>
                                <div class="truncate text-[13px] font-extrabold leading-none tracking-tight text-white">Vendor BMS</div>
                                <div class="mt-0.5 truncate text-[10px] font-medium text-white/70">PKM Dashboard</div>
                            </div>
                        </div>

                        <button
                            @click="toggle()"
                            class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-white transition hover:bg-white/10 active:scale-[0.98]"
                            aria-label="Toggle Sidebar"
                        >
                            <i data-lucide="panel-left" class="h-4 w-4"></i>
                        </button>
                    </div>

                    <div class="px-3 pb-3" x-show="sidebarOpen" x-transition.opacity.duration.200ms>
                        <div class="relative">
                            <i data-lucide="search" class="absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-white/60"></i>
                            <input
                                type="text"
                                placeholder="Cari menu..."
                                class="w-full rounded-lg border border-white/20 bg-white/12 py-1.5 pl-8 pr-3 text-[12px] text-white placeholder:text-white/55 focus:border-white/30 focus:outline-none focus:ring-2 focus:ring-white/20"
                            >
                        </div>
                    </div>
                </div>

                <div class="no-scrollbar flex-1 overflow-y-auto px-2 py-3">
                    <nav class="space-y-1 text-[13px]">
                        @foreach ($pkmMenus as $menu)
                            <a
                                href="{{ route($menu['route']) }}"
                                class="group flex items-center gap-2.5 rounded-lg px-2.5 py-2 transition {{ request()->routeIs($menu['route']) ? 'bg-white text-[#c7612c] ring-1 ring-white/45' : 'text-white/95 hover:bg-white/12' }}"
                            >
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg transition {{ request()->routeIs($menu['route']) ? 'bg-[#fde9db] text-[#c7612c]' : 'bg-white/12 text-white/90 group-hover:bg-white/16' }}">
                                    <i data-lucide="{{ $menu['icon'] }}" class="h-4 w-4"></i>
                                </span>
                                <span x-show="sidebarOpen" x-transition.opacity.duration.200ms class="font-medium">{{ $menu['label'] }}</span>
                            </a>
                        @endforeach
                    </nav>
                </div>

                <div class="border-t border-white/12 p-2">
                    <div class="flex items-center gap-2 rounded-lg px-2.5 py-2 text-[11px] text-white/70">
                        <i data-lucide="sparkles" class="h-3.5 w-3.5"></i>
                        <span x-show="sidebarOpen" x-transition.opacity.duration.200ms>Vendor - PKM</span>
                    </div>
                </div>
            </aside>

            <div class="min-h-screen transition-all duration-300" :class="sidebarOpen ? 'lg:pl-60' : 'lg:pl-16'">
                <header class="sticky top-0 z-20 border-b border-[#cb6b33]/35 bg-[#de773b]">
                    <div class="flex items-center justify-between px-3 py-2.5 lg:px-4">
                        <div class="flex items-center gap-3">
                            <button
                                @click="toggle()"
                                class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-white transition hover:bg-white/10 lg:hidden"
                                aria-label="Open Menu"
                            >
                                <i data-lucide="menu" class="h-4 w-4"></i>
                            </button>

                            <div class="flex items-center gap-2">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-white p-1.5 shadow-sm">
                                    <img src="{{ $logoSig }}" alt="Logo SIG" class="max-h-full w-auto object-contain">
                                </div>
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-[#ca6127] p-1.5 ring-1 ring-white/12">
                                    <img src="{{ $logoSt }}" alt="Logo ST2" class="max-h-full w-auto object-contain">
                                </div>
                            </div>

                            <div class="hidden flex-col leading-tight text-white md:flex">
                                <span class="text-[14px] font-extrabold tracking-tight">Vendor Workshop Section</span>
                                <span class="mt-0.5 text-[10px] text-white/80">Dashboard Vendor</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="relative" @click.outside="notificationsOpen = false">
                                <button
                                    type="button"
                                    @click="notificationsOpen = !notificationsOpen"
                                    class="relative inline-flex h-9 w-9 items-center justify-center rounded-lg text-white transition hover:bg-white/10"
                                    aria-label="Pemberitahuan"
                                >
                                    <i data-lucide="bell" class="h-5 w-5"></i>
                                    @if ($pkmNotificationCount > 0)
                                        <span class="absolute -right-1 -top-1 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white">{{ $pkmNotificationBadge }}</span>
                                    @endif
                                </button>

                                <div
                                    x-show="notificationsOpen"
                                    x-transition.origin.top.right
                                    x-cloak
                                    class="fixed left-3 right-3 top-[4.25rem] z-50 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl sm:absolute sm:left-auto sm:right-0 sm:top-auto sm:mt-2 sm:w-[min(92vw,24rem)]"
                                >
                                    <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-3 py-2.5 sm:px-4 sm:py-3">
                                        <div>
                                            <div class="text-sm font-bold text-slate-900">Pemberitahuan PKM</div>
                                        </div>
                                        @if ($pkmNotificationCount > 0)
                                            <span class="rounded-full bg-red-50 px-2.5 py-1 text-[10px] font-bold text-red-700 ring-1 ring-red-100">{{ $pkmNotificationBadge }}</span>
                                        @endif
                                    </div>

                                    <div class="max-h-[min(70vh,24rem)] overflow-y-auto">
                                        @forelse ($pkmNotifications as $notification)
                                            @php($toneClass = $notificationToneClasses[$notification['tone'] ?? 'blue'] ?? $notificationToneClasses['blue'])
                                            <form method="POST" action="{{ route('pkm.notifications.read') }}" class="border-b border-slate-100 last:border-b-0">
                                                @csrf
                                                <input type="hidden" name="notification_key" value="{{ $notification['key'] }}">
                                                <input type="hidden" name="redirect_url" value="{{ $notification['url'] }}">
                                                <button type="submit" class="group flex w-full gap-2.5 px-3 py-2.5 text-left transition hover:bg-slate-50 sm:gap-3 sm:px-4 sm:py-3">
                                                    <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl ring-1 sm:h-9 sm:w-9 {{ $toneClass }}">
                                                        <i data-lucide="{{ $notification['icon'] }}" class="h-4 w-4"></i>
                                                    </span>
                                                    <span class="min-w-0 flex-1">
                                                        <span class="block text-[10px] font-bold uppercase tracking-[0.14em] text-slate-400 sm:text-xs">{{ $notification['type'] }}</span>
                                                        <span class="mt-1 block text-[12px] font-semibold leading-4 text-slate-900 sm:text-sm sm:leading-5">{{ $notification['message'] }}</span>
                                                        <span class="mt-1 flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
                                                            <span>{{ $notification['meta'] ?: '-' }}</span>
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
                                                <div class="mt-1 text-xs leading-5 text-slate-500">Event PKM yang sudah dibaca tidak ditampilkan lagi.</div>
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>

                            <div class="relative" @click.outside="profileOpen = false">
                                <button
                                    @click="profileOpen = !profileOpen"
                                    class="inline-flex items-center gap-2.5 rounded-lg bg-white/96 px-2.5 py-1.5 text-[#c7612c] shadow-sm transition hover:bg-[#fff7f2]"
                                >
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-[#fde9db] text-[12px] font-bold tracking-wide text-[#c7612c]">
                                        {{ $userInitials }}
                                    </span>
                                    <span class="hidden min-w-0 text-left sm:block">
                                        <span class="block truncate text-[12px] font-semibold">{{ $user?->name ?? 'Vendor' }}</span>
                                        <span class="block text-[10px] text-[#d88858]">{{ strtoupper($user?->role ?? 'pkm') }}</span>
                                    </span>
                                    <i data-lucide="chevron-down" class="h-3.5 w-3.5 text-[#dd9b72]"></i>
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

                                    <a href="{{ route('pkm.profile.edit') }}" class="block px-4 py-3 text-sm text-slate-700 transition hover:bg-slate-50">
                                        Profile
                                    </a>

                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="block w-full px-4 py-3 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                            Log Out
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>

                <main class="p-3 lg:p-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm lg:p-4">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                if (window.lucide) {
                    window.lucide.createIcons();
                }
            });
        </script>
    </body>
</html>
