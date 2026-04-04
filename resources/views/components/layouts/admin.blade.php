<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0b4db3">

        <title>{{ $title ?? config('app.name', 'WOMS') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles

        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
        <script defer src="https://unpkg.com/lucide@latest"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <style>[x-cloak]{ display:none !important; }</style>
    </head>
    <body class="bg-slate-50 font-sans text-slate-800 antialiased">
        @php
            $logoBms = asset('assets/branding/logos/logo-bms2.png');
            $logoSig = asset('assets/branding/logos/logo-sig.png');
            $logoSt = asset('assets/branding/logos/logo-st2.png');
            $user = auth()->user();
            $userInitials = $user?->initials() ?: 'AD';
            $sidebarMenus = \App\Support\AdminMenuRegistry::sidebarForUser($user);
            $dashboardMenu = $sidebarMenus['dashboard'];
            $orderMenu = $sidebarMenus['orders'];
            $mainMenus = $sidebarMenus['main'];
            $supportMenus = $sidebarMenus['support'];
            $otherMenus = $sidebarMenus['other'];
            $orderMenus = $orderMenu['children'] ?? [];
            $isOrdersSection = $orderMenu && ($orderMenu['active'] ?? false);
            $isOtherSection = collect($otherMenus)->contains(fn (array $menu) => $menu['active'] ?? false);
            $roleBadge = $user?->isSuperAdmin() ? 'SUPER ADMIN' : strtoupper($user?->role ?? 'admin');
        @endphp

        <div
            x-data="{
                sidebarOpen: true,
                mobileOpen: false,
                profileOpen: false,
                orderOpen: {{ $isOrdersSection ? 'true' : 'false' }},
                otherOpen: {{ $isOtherSection ? 'true' : 'false' }},
                toggleSidebar() {
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
                class="fixed inset-0 z-30 bg-slate-950/45 lg:hidden"
                @click="closeMobile()"
            ></div>

            <aside
                class="fixed inset-y-0 left-0 z-40 flex w-72 flex-col border-r border-blue-950/30 bg-blue-900 shadow-sm transition-all duration-300"
                :class="[
                    mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
                    sidebarOpen ? 'lg:w-72' : 'lg:w-20'
                ]"
            >
                <div class="sticky top-0 z-10 border-b border-blue-950/30 bg-blue-900">
                    <div class="flex items-center justify-between gap-3 px-4 py-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/10 p-2 ring-1 ring-white/10">
                                <img src="{{ $logoBms }}" alt="Logo BMS2" class="max-h-full w-auto object-contain">
                            </div>

                            <div class="min-w-0 space-y-1" x-show="sidebarOpen" x-transition.opacity.duration.200ms>
                                <div class="truncate text-xs font-medium text-white/70">Admin Dashboard</div>
                            </div>
                        </div>

                        <button
                            @click="toggleSidebar()"
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
                                class="w-full rounded-xl border border-white/15 bg-white/10 py-2 pl-9 pr-3 text-sm text-white placeholder:text-white/50 focus:border-white/25 focus:outline-none focus:ring-2 focus:ring-white/20"
                            >
                        </div>
                    </div>
                </div>

                <div class="no-scrollbar flex-1 overflow-y-auto px-3 py-4">
                    <nav class="space-y-1 text-sm">
                        @if ($dashboardMenu)
                            @php($isActive = $dashboardMenu['active'] ?? false)
                            <a
                                href="{{ $dashboardMenu['href'] }}"
                                class="group flex items-center gap-3 rounded-xl px-3 py-2.5 transition {{ $isActive ? 'bg-white text-blue-900 ring-1 ring-white/30' : 'text-white/90 hover:bg-white/10' }}"
                            >
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl transition {{ $isActive ? 'bg-blue-100 text-blue-900' : 'bg-white/10 text-white/90 group-hover:bg-white/15' }}">
                                    <i data-lucide="{{ $dashboardMenu['icon'] }}" class="h-5 w-5"></i>
                                </span>
                                <span x-show="sidebarOpen" x-transition.opacity.duration.200ms class="font-medium">{{ $dashboardMenu['label'] }}</span>
                            </a>
                        @endif

                        @if ($orderMenu || $mainMenus !== [])
                            <div class="pb-1 pt-2" x-show="sidebarOpen" x-transition.opacity.duration.200ms>
                                <div class="px-3 text-[11px] uppercase tracking-wider text-white/60">Menu Utama</div>
                            </div>
                        @endif

                        @if ($orderMenu)
                            <div class="rounded-xl">
                                <button
                                    @click="orderOpen = !orderOpen"
                                    class="group flex w-full items-center gap-3 rounded-xl px-3 py-2.5 transition {{ $isOrdersSection ? 'bg-white text-blue-900 ring-1 ring-white/30' : 'text-white/90 hover:bg-white/10' }}"
                                >
                                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl transition {{ $isOrdersSection ? 'bg-blue-100 text-blue-900' : 'bg-white/10 text-white/90 group-hover:bg-white/15' }}">
                                        <i data-lucide="{{ $orderMenu['icon'] }}" class="h-5 w-5"></i>
                                    </span>
                                    <span x-show="sidebarOpen" x-transition.opacity.duration.200ms class="flex-1 text-left font-medium">{{ $orderMenu['label'] }}</span>
                                    <i
                                        data-lucide="chevron-down"
                                        class="h-4 w-4 transition"
                                        :class="orderOpen ? 'rotate-180 {{ $isOrdersSection ? 'text-blue-900' : 'text-white/70' }}' : '{{ $isOrdersSection ? 'text-blue-900' : 'text-white/70' }}'"
                                        x-show="sidebarOpen"
                                        x-transition.opacity.duration.200ms
                                    ></i>
                                </button>

                                <div x-show="orderOpen && sidebarOpen" x-transition.opacity.duration.200ms x-cloak class="mt-1 space-y-1 pl-12">
                                    @foreach ($orderMenus as $menu)
                                        <a href="{{ $menu['href'] }}" class="flex items-center justify-between rounded-lg px-3 py-2 transition {{ $menu['active'] ? 'bg-white text-blue-900' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                                            <span>{{ $menu['label'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @foreach ($mainMenus as $menu)
                            @php($isActive = $menu['active'] ?? false)
                            <a
                                href="{{ $menu['href'] }}"
                                class="group flex items-center gap-3 rounded-xl px-3 py-2.5 transition {{ $isActive ? 'bg-white text-blue-900 ring-1 ring-white/30' : 'text-white/90 hover:bg-white/10' }}"
                            >
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl transition {{ $isActive ? 'bg-blue-100 text-blue-900' : 'bg-white/10 text-white/90 group-hover:bg-white/15' }}">
                                    <i data-lucide="{{ $menu['icon'] }}" class="h-5 w-5"></i>
                                </span>
                                <span x-show="sidebarOpen" x-transition.opacity.duration.200ms class="font-medium">{{ $menu['label'] }}</span>
                            </a>
                        @endforeach

                        @if ($supportMenus !== [] || $otherMenus !== [])
                            <div class="pb-1 pt-2" x-show="sidebarOpen" x-transition.opacity.duration.200ms>
                                <div class="px-3 text-[11px] uppercase tracking-wider text-white/60">Menu Pendukung</div>
                            </div>
                        @endif

                        @foreach ($supportMenus as $supportMenu)
                            @php($supportActive = $supportMenu['active'] ?? false)
                            <a
                                href="{{ $supportMenu['href'] }}"
                                class="group flex items-center gap-3 rounded-xl px-3 py-2.5 transition {{ $supportActive ? 'bg-white text-blue-900 ring-1 ring-white/30' : 'text-white/90 hover:bg-white/10' }}"
                            >
                                <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl transition {{ $supportActive ? 'bg-blue-100 text-blue-900' : 'bg-white/10 text-white/90 group-hover:bg-white/15' }}">
                                    <i data-lucide="{{ $supportMenu['icon'] }}" class="h-5 w-5"></i>
                                </span>
                                <span x-show="sidebarOpen" x-transition.opacity.duration.200ms class="font-medium">{{ $supportMenu['label'] }}</span>
                            </a>
                        @endforeach

                        @if ($otherMenus !== [])
                            <div class="rounded-xl">
                                <button
                                    @click="otherOpen = !otherOpen"
                                    class="group flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-white/90 transition hover:bg-white/10"
                                >
                                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-white/10 text-white/90 transition group-hover:bg-white/15">
                                        <i data-lucide="layers" class="h-5 w-5"></i>
                                    </span>
                                    <span x-show="sidebarOpen" x-transition.opacity.duration.200ms class="flex-1 text-left font-medium">Lainnya</span>
                                    <i
                                        data-lucide="chevron-down"
                                        class="h-4 w-4 text-white/70 transition"
                                        :class="otherOpen ? 'rotate-180' : ''"
                                        x-show="sidebarOpen"
                                        x-transition.opacity.duration.200ms
                                    ></i>
                                </button>

                                <div x-show="otherOpen && sidebarOpen" x-transition.opacity.duration.200ms x-cloak class="mt-1 space-y-1 pl-12">
                                    @foreach ($otherMenus as $menu)
                                        <a
                                            href="{{ $menu['href'] }}"
                                            class="block rounded-lg px-3 py-2 transition {{ $menu['active'] ? 'bg-white text-blue-900' : 'text-white/80 hover:bg-white/10 hover:text-white' }}"
                                        >
                                            {{ $menu['label'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </nav>
                </div>

                <div class="border-t border-white/10 p-3">
                    <div class="flex items-center gap-2 rounded-xl px-3 py-2 text-xs text-white/70">
                        <i data-lucide="sparkles" class="h-4 w-4"></i>
                        <span x-show="sidebarOpen" x-transition.opacity.duration.200ms>Workshop • Admin</span>
                    </div>
                </div>
            </aside>

            <div class="min-h-screen transition-all duration-300" :class="sidebarOpen ? 'lg:pl-72' : 'lg:pl-20'">
                <header class="sticky top-0 z-20 border-b border-blue-950/30 bg-blue-900">
                    <div class="flex items-center justify-between px-4 py-3 lg:px-6">
                        <div class="flex items-center gap-3">
                            <button
                                @click="toggleSidebar()"
                                class="inline-flex h-10 w-10 items-center justify-center rounded-xl text-white transition hover:bg-white/10 lg:hidden"
                                aria-label="Open Menu"
                            >
                                <i data-lucide="menu" class="h-5 w-5"></i>
                            </button>

                            <div class="flex items-center gap-2.5">
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white p-1.5 shadow-sm">
                                    <img src="{{ $logoSig }}" alt="Logo SIG" class="max-h-full w-auto object-contain">
                                </div>
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-800 p-1.5 ring-1 ring-white/10">
                                    <img src="{{ $logoSt }}" alt="Logo ST2" class="max-h-full w-auto object-contain">
                                </div>
                            </div>

                            <div class="hidden leading-tight text-white md:flex md:flex-col">
                                <span class="font-extrabold tracking-tight">SECTION OF WORKSHOP</span>
                                <span class="mt-0.5 text-xs text-white/80">Dept. Of Project Management &amp; Main Support</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-3">
                            <button
                                class="relative inline-flex h-10 w-10 items-center justify-center rounded-xl text-white transition hover:bg-white/10"
                                aria-label="Notifications"
                            >
                                <i data-lucide="bell" class="h-5 w-5"></i>
                                <span class="absolute -right-1 -top-1 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] text-white">3</span>
                            </button>

                            <div class="relative" @click.outside="profileOpen = false">
                                <button
                                    @click="profileOpen = !profileOpen"
                                    class="inline-flex items-center gap-3 rounded-xl bg-white px-3 py-2 text-blue-700 shadow-sm transition hover:bg-blue-50"
                                >
                                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-sm font-bold tracking-wide text-blue-700">
                                        {{ $userInitials }}
                                    </span>
                                    <span class="hidden min-w-0 text-left sm:block">
                                        <span class="block truncate text-sm font-semibold">{{ $user?->name ?? 'Admin' }}</span>
                                        <span class="block text-xs text-blue-400">{{ $roleBadge }}</span>
                                    </span>
                                    <i data-lucide="chevron-down" class="h-4 w-4 text-blue-400"></i>
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
                                        Edit Profile
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
        @livewireScripts
    </body>
</html>
