<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name', 'WOMS') }}</title>

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
            $pkmMenus = [
                ['route' => 'pkm.dashboard', 'icon' => 'layout-dashboard', 'label' => 'Dashboard'],
                ['route' => 'pkm.jobwaiting', 'icon' => 'bell', 'label' => 'List Pekerjaan'],
                ['route' => 'pkm.items.index', 'icon' => 'boxes', 'label' => 'Item Kebutuhan'],
                ['route' => 'pkm.lhpp.index', 'icon' => 'file-text', 'label' => 'Buat LHPP'],
                ['route' => 'pkm.laporan', 'icon' => 'folder-open', 'label' => 'Dokumen'],
            ];
        @endphp

        <div
            x-data="{
                sidebarOpen: true,
                mobileOpen: false,
                profileOpen: false,
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
                class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-orange-800/30 bg-orange-600 shadow-sm transition-all duration-300"
                :class="[
                    (mobileOpen ? 'translate-x-0' : '-translate-x-full') + ' lg:translate-x-0',
                    sidebarOpen ? 'lg:w-72' : 'lg:w-20'
                ]"
            >
                <div class="sticky top-0 z-10 border-b border-orange-800/30 bg-orange-600">
                    <div class="flex items-center justify-between gap-3 px-4 py-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white p-2 shadow-sm">
                                <img src="{{ $logoBms }}" alt="Logo BMS2" class="max-h-full w-auto object-contain">
                            </div>

                            <div class="min-w-0 space-y-1" x-show="sidebarOpen" x-transition.opacity.duration.200ms>
                                <div class="truncate font-extrabold leading-none tracking-tight text-white">Vendor BMS</div>
                                <div class="truncate text-xs font-medium text-white/70">PKM Dashboard</div>
                            </div>
                        </div>

                        <button
                            @click="toggle()"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-white transition hover:bg-white/10 active:scale-[0.98]"
                            aria-label="Toggle Sidebar"
                        >
                            <i data-lucide="panel-left" class="h-5 w-5"></i>
                        </button>
                    </div>

                    <div class="px-4 pb-4" x-show="sidebarOpen" x-transition.opacity.duration.200ms>
                        <div class="relative">
                            <i data-lucide="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-white/60"></i>
                            <input
                                type="text"
                                placeholder="Cari menu..."
                                class="w-full rounded-xl border border-white/15 bg-white/10 py-2 pl-9 pr-3 text-sm text-white placeholder:text-white/50 focus:border-white/25 focus:outline-none focus:ring-2 focus:ring-white/25"
                            >
                        </div>
                    </div>
                </div>

                <div class="no-scrollbar flex-1 overflow-y-auto px-3 py-4">
                    <nav class="space-y-1 text-sm">
                        @foreach ($pkmMenus as $menu)
                            <a
                                href="{{ route($menu['route']) }}"
                                class="group flex items-center gap-3 rounded-xl px-3 py-2.5 transition {{ request()->routeIs($menu['route']) ? 'bg-white text-orange-700 ring-1 ring-white/30' : 'text-white/90 hover:bg-white/10' }}"
                            >
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl transition {{ request()->routeIs($menu['route']) ? 'bg-orange-100 text-orange-700' : 'bg-white/10 text-white/90 group-hover:bg-white/15' }}">
                                    <i data-lucide="{{ $menu['icon'] }}" class="h-5 w-5"></i>
                                </span>
                                <span x-show="sidebarOpen" x-transition.opacity.duration.200ms class="font-medium">{{ $menu['label'] }}</span>
                            </a>
                        @endforeach
                    </nav>
                </div>

                <div class="border-t border-white/10 p-3">
                    <div class="flex items-center gap-2 rounded-xl px-3 py-2 text-xs text-white/70">
                        <i data-lucide="sparkles" class="h-4 w-4"></i>
                        <span x-show="sidebarOpen" x-transition.opacity.duration.200ms>Vendor • PKM</span>
                    </div>
                </div>
            </aside>

            <div class="min-h-screen transition-all duration-300" :class="sidebarOpen ? 'lg:pl-72' : 'lg:pl-20'">
                <header class="sticky top-0 z-20 border-b border-orange-800/30 bg-orange-600">
                    <div class="flex items-center justify-between px-4 py-3 lg:px-6">
                        <div class="flex items-center gap-3">
                            <button
                                @click="toggle()"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-white transition hover:bg-white/10 lg:hidden"
                                aria-label="Open Menu"
                            >
                                <i data-lucide="menu" class="h-5 w-5"></i>
                            </button>

                            <div class="flex items-center gap-2.5">
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white p-1.5 shadow-sm">
                                    <img src="{{ $logoSig }}" alt="Logo SIG" class="max-h-full w-auto object-contain">
                                </div>
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-orange-700 p-1.5 ring-1 ring-white/10">
                                    <img src="{{ $logoSt }}" alt="Logo ST2" class="max-h-full w-auto object-contain">
                                </div>
                            </div>

                            <div class="hidden flex-col leading-tight text-white md:flex">
                                <span class="font-extrabold tracking-tight">Vendor Workshop Section</span>
                                <span class="mt-0.5 text-xs text-white/80">Halaman Dashboard Vendor</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="relative" @click.outside="profileOpen = false">
                                <button
                                    @click="profileOpen = !profileOpen"
                                    class="inline-flex items-center gap-3 rounded-xl bg-white px-3 py-2 text-orange-700 shadow-sm transition hover:bg-orange-50"
                                >
                                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-orange-100 text-sm font-bold tracking-wide text-orange-700">
                                        {{ $userInitials }}
                                    </span>
                                    <span class="hidden min-w-0 text-left sm:block">
                                        <span class="block truncate text-sm font-semibold">{{ $user?->name ?? 'Vendor' }}</span>
                                        <span class="block text-xs text-orange-400">{{ strtoupper($user?->role ?? 'pkm') }}</span>
                                    </span>
                                    <i data-lucide="chevron-down" class="h-4 w-4 text-orange-300"></i>
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

                <main class="p-4 lg:p-6">
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm lg:p-6">
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
