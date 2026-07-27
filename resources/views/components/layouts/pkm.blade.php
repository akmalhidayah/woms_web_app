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

        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js"></script>
        <script defer src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js"></script>

        <style>[x-cloak]{ display:none !important; }</style>
    </head>
    <body class="bg-slate-50 font-sans text-slate-800 antialiased">
        @php
            $logoBms = asset('assets/branding/logos/logo-bms2.png');
            $logoSig = asset('assets/branding/logos/logo-sig.png');
            $logoSt = asset('assets/branding/logos/logo-st2.png');
            $user = auth()->user();
            $isSuperAdminViewingPkm = $user?->isSuperAdmin() ?? false;
            $pkmRoleBadge = $isSuperAdminViewingPkm
                ? 'SUPER ADMIN'
                : strtoupper($user?->role ?? 'pkm');
            $userInitials = $user?->initials() ?: 'PK';
            $pkmNotificationBadge = $pkmNotificationCount > 9 ? '9+' : (string) $pkmNotificationCount;
            $notificationToneClasses = [
                'blue' => 'bg-blue-50 text-blue-700 ring-blue-100',
                'amber' => 'bg-amber-50 text-amber-700 ring-amber-100',
                'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
                'rose' => 'bg-rose-50 text-rose-700 ring-rose-100',
            ];
            $pkmMenus = [
                ['route' => 'pkm.dashboard', 'icon' => 'layout-dashboard', 'label' => 'Dashboard'],
                ['route' => 'pkm.hpp.index', 'active' => 'pkm.hpp.*', 'icon' => 'file-pen-line', 'label' => 'Create HPP'],
                ['route' => 'pkm.jobwaiting', 'icon' => 'bell', 'label' => 'List Pekerjaan', 'badge_count' => $pkmBadgeCounts['jobwaiting'] ?? 0],
                ['route' => 'pkm.lhpp.index', 'icon' => 'file-text', 'label' => 'Buat BAST/LHPP', 'badge_count' => $pkmBadgeCounts['lhpp'] ?? 0],
                ['route' => 'pkm.laporan', 'icon' => 'folder-open', 'label' => 'Dokumen', 'badge_count' => $pkmBadgeCounts['documents'] ?? 0],
            ];
        @endphp

        <div
            x-data="{
                sidebarOpen: true,
                mobileOpen: false,
                profileOpen: false,
                notificationsOpen: false,
                vendorSectionModalOpen: @js(session()->has('pkm_vendor_structure_modal') || $errors->pkmVendorStructure->any()),
                toggle() {
                    if (window.innerWidth >= 1024) this.sidebarOpen = !this.sidebarOpen;
                    else this.mobileOpen = !this.mobileOpen;
                },
                closeMobile() {
                    this.mobileOpen = false;
                },
                closeVendorSectionModal() {
                    this.vendorSectionModalOpen = false;
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
                                class="group flex items-center gap-2.5 rounded-lg px-2.5 py-2 transition {{ request()->routeIs($menu['active'] ?? $menu['route']) ? 'bg-white text-[#c7612c] ring-1 ring-white/45' : 'text-white/95 hover:bg-white/12' }}"
                            >
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg transition {{ request()->routeIs($menu['active'] ?? $menu['route']) ? 'bg-[#fde9db] text-[#c7612c]' : 'bg-white/12 text-white/90 group-hover:bg-white/16' }}">
                                    <i data-lucide="{{ $menu['icon'] }}" class="h-4 w-4"></i>
                                </span>
                                <span x-show="sidebarOpen" x-transition.opacity.duration.200ms class="flex-1 font-medium">{{ $menu['label'] }}</span>
                                @if (($menu['badge_count'] ?? 0) > 0)
                                    <span
                                        x-show="sidebarOpen"
                                        x-transition.opacity.duration.200ms
                                        class="inline-flex min-w-5 items-center justify-center rounded-full bg-white px-1.5 py-0.5 text-[10px] font-extrabold leading-none text-[#c7612c]"
                                    >
                                        {{ $menu['badge_count'] > 99 ? '99+' : $menu['badge_count'] }}
                                    </span>
                                @endif
                            </a>
                        @endforeach
                    </nav>
                </div>

                <div class="border-t border-white/10 px-2 py-2">
                    <a href="{{ route('pkm.user-panel.index') }}" class="group flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-[13px] transition {{ request()->routeIs('pkm.user-panel.*') ? 'bg-white text-[#c7612c] ring-1 ring-white/45' : 'text-white/95 hover:bg-white/12' }}">
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg transition {{ request()->routeIs('pkm.user-panel.*') ? 'bg-[#fde9db] text-[#c7612c]' : 'bg-white/12 text-white/90 group-hover:bg-white/16' }}"><i data-lucide="users" class="h-4 w-4"></i></span>
                        <span x-show="sidebarOpen" x-transition.opacity.duration.200ms class="flex-1 font-medium">User Panel</span>
                    </a>
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
                            @if ($pkmVendorWorkType)
                                <button
                                    type="button"
                                    @click="vendorSectionModalOpen = true"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-white transition hover:bg-white/10"
                                    title="Kelola Seksi Vendor"
                                    aria-label="Kelola Seksi Vendor"
                                >
                                    <i data-lucide="list-plus" class="h-5 w-5"></i>
                                </button>
                            @endif

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
                                            @if ($pkmNotificationCount > 0)
                                                <form method="POST" action="{{ route('pkm.notifications.read-all') }}" class="mt-1">
                                                    @csrf
                                                    <button type="submit" class="text-[11px] font-bold text-blue-700 transition hover:text-blue-900">
                                                        Tandai semua dibaca
                                                    </button>
                                                </form>
                                            @endif
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
                                        <span class="block text-[10px] text-[#d88858]">{{ $pkmRoleBadge }}</span>
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

                                    @if ($isSuperAdminViewingPkm)
                                        <a
                                            href="{{ route('admin.profile.edit') }}"
                                            class="block px-4 py-3 text-sm text-slate-700 transition hover:bg-slate-50"
                                        >
                                            Edit Profile
                                        </a>

                                        <a
                                            href="{{ route('admin.dashboard') }}"
                                            class="block px-4 py-3 text-sm text-slate-700 transition hover:bg-slate-50"
                                        >
                                            Kembali ke Dashboard Admin
                                        </a>
                                    @else
                                        <a
                                            href="{{ route('pkm.profile.edit') }}"
                                            class="block px-4 py-3 text-sm text-slate-700 transition hover:bg-slate-50"
                                        >
                                            Profile
                                        </a>

                                        <a
                                            href="{{ route('user.dashboard') }}"
                                            class="block px-4 py-3 text-sm text-slate-700 transition hover:bg-slate-50"
                                        >
                                            Lihat Halaman User
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
                    </div>
                </header>

                <main class="p-3 lg:p-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm lg:p-4">
                        {{ $slot }}
                    </div>
                </main>
            </div>

            @if ($pkmVendorWorkType)
                <div
                    x-show="vendorSectionModalOpen"
                    x-transition.opacity
                    x-cloak
                    class="fixed inset-0 z-50 overflow-y-auto bg-slate-950/55 p-4"
                    @click.self="closeVendorSectionModal()"
                >
                    <div
                        class="mx-auto my-8 w-full max-w-3xl rounded-[1.35rem] border border-slate-200 bg-white p-5 shadow-2xl"
                        x-data="pkmVendorStructureForm(@js($pkmVendorSections), @js($pkmVendorManagers->values()), @js(route('pkm.vendor-managers.store')))"
                        @click.stop
                    >
                        <div class="mb-4 flex items-start justify-between gap-4 border-b border-slate-200 pb-4">
                            <div>
                                <h2 class="text-xl font-bold text-slate-900">Kelola Seksi Vendor</h2>
                                <p class="mt-1 text-sm text-slate-500">{{ $pkmVendorWorkType->name }} bersifat tetap. Tambahkan atau ubah seksi di bawahnya.</p>
                            </div>
                            <button type="button" @click="closeVendorSectionModal()" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 text-slate-500 transition hover:bg-slate-100" aria-label="Tutup modal">
                                <i data-lucide="x" class="h-4 w-4"></i>
                            </button>
                        </div>

                        @if ($errors->pkmVendorStructure->any())
                            <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                                <div class="font-semibold">Data seksi vendor belum bisa disimpan.</div>
                                <ul class="mt-2 list-disc space-y-1 pl-5">
                                    @foreach ($errors->pkmVendorStructure->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('pkm.vendor-structure.update', $pkmVendorWorkType) }}" class="space-y-4">
                            @csrf
                            @method('PUT')

                            <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-3">
                                <div class="mb-3 flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-[11px] font-semibold text-slate-800">Seksi Vendor</div>
                                        <div class="text-[10px] text-slate-500">Kelola nama seksi manual dan manager vendor.</div>
                                    </div>
                                    <button type="button" @click="addSection()" class="inline-flex items-center gap-1.5 rounded-lg bg-orange-50 px-3 py-2 text-[11px] font-semibold text-[#ca642f] ring-1 ring-orange-200 hover:bg-orange-100">
                                        <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                                        Tambah Seksi
                                    </button>
                                </div>

                                <div class="space-y-2">
                                    <template x-for="(section, index) in sections" :key="section.uid">
                                        <div class="grid gap-2 rounded-lg border border-slate-200 bg-white p-2 lg:grid-cols-[1fr_1fr_auto] lg:items-end">
                                            <input type="hidden" :name="`sections[${index}][id]`" :value="section.id || ''">
                                            <div>
                                                <label class="mb-1 block text-[10px] font-semibold text-slate-600">Nama Seksi</label>
                                                <input type="text" :name="`sections[${index}][name]`" x-model="section.name" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-700 focus:border-[#ca642f] focus:outline-none" required>
                                            </div>
                                            <div>
                                                <div class="mb-1 flex items-center justify-between gap-2">
                                                    <label class="block text-[10px] font-semibold text-slate-600">Manager Seksi</label>
                                                    <button type="button" @click="openManagerModal(index)" class="inline-flex items-center gap-1 text-[10px] font-semibold text-[#ca642f] hover:text-[#a94f24]"><i data-lucide="user-plus" class="h-3 w-3"></i>Tambah Manager</button>
                                                </div>
                                                <select :name="`sections[${index}][manager_id]`" x-model="section.manager_id" x-init="$nextTick(() => { $el.value = String(section.manager_id || '') })" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-700 focus:border-[#ca642f] focus:outline-none" required>
                                                    <option value="">Pilih Manager</option>
                                                    <template x-for="manager in managerOptions" :key="manager.id"><option :value="String(manager.id)" x-text="manager.name"></option></template>
                                                </select>
                                            </div>
                                            <button type="button" @click="removeSection(index)" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-rose-50 text-rose-600 transition hover:bg-rose-100" title="Hapus Seksi">
                                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div class="flex justify-end gap-2 border-t border-slate-200 pt-4">
                                <button type="button" @click="closeVendorSectionModal()" class="rounded-lg bg-slate-100 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-200">
                                    Batal
                                </button>
                                <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-[#ca642f] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#b85b2b]">
                                    <i data-lucide="save" class="h-4 w-4"></i>
                                    Simpan Seksi
                                </button>
                            </div>
                        </form>

                        <div x-show="managerModalOpen" x-cloak class="fixed inset-0 z-[60] overflow-y-auto bg-slate-950/55 p-4" @click.self="closeManagerModal()">
                            <div class="mx-auto my-12 w-full max-w-lg rounded-2xl bg-white p-5 shadow-2xl" @click.stop>
                                <div class="flex justify-between border-b border-slate-200 pb-4"><div><h3 class="text-lg font-bold text-slate-900">Tambah Manager Seksi</h3><p class="mt-1 text-xs text-slate-500">Buat akun manager baru untuk approval BAST/LHPP.</p></div><button type="button" @click="closeManagerModal()"><i data-lucide="x" class="h-4 w-4"></i></button></div>
                                <form class="mt-4 space-y-3" @submit.prevent="storeManager()">
                                    <template x-for="field in ['name', 'email', 'nomor_hp', 'inisial']" :key="field"><div><label class="mb-1 block text-[11px] font-semibold" x-text="({name:'Nama',email:'Email',nomor_hp:'Nomor HP',inisial:'Inisial'})[field]"></label><input :type="field === 'email' ? 'email' : 'text'" x-model="managerForm[field]" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" :class="managerErrors[field] ? 'border-rose-400 bg-rose-50' : ''"><p x-show="managerErrors[field]" class="mt-1 text-[10px] text-rose-600" x-text="managerErrors[field]?.[0]"></p></div></template>
                                    <div class="rounded-xl border border-orange-100 bg-orange-50 p-3 text-xs text-orange-800">Akun otomatis dibuat sebagai Approval dengan password awal <span class="font-mono font-bold">bengkelmesin123</span>.</div>
                                    <div class="flex justify-end gap-2 border-t pt-4"><button type="button" @click="closeManagerModal()" class="rounded-lg bg-slate-100 px-4 py-2 text-xs font-semibold">Batal</button><button type="submit" :disabled="managerSaving" class="rounded-lg bg-[#ca642f] px-4 py-2 text-xs font-semibold text-white disabled:opacity-50" x-text="managerSaving ? 'Menyimpan...' : 'Simpan Manager'"></button></div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <script>
            function pkmVendorStructureForm(initialSections, initialManagers, managerStoreUrl) {
                return {
                    sections: Array.isArray(initialSections) && initialSections.length
                        ? initialSections
                        : [{ id: '', uid: `${Date.now()}-${Math.random()}`, name: '', manager_id: '' }],
                    managerOptions: Array.isArray(initialManagers) ? initialManagers : [],
                    managerModalOpen: false,
                    managerTargetIndex: null,
                    managerSaving: false,
                    managerErrors: {},
                    managerForm: { name: '', email: '', nomor_hp: '', inisial: '' },
                    addSection() {
                        this.sections.push({
                            id: '',
                            uid: `${Date.now()}-${Math.random()}`,
                            name: '',
                            manager_id: '',
                        });
                    },
                    removeSection(index) {
                        if (this.sections.length === 1) {
                            this.sections = [{ id: '', uid: `${Date.now()}-${Math.random()}`, name: '', manager_id: '' }];
                            return;
                        }

                        this.sections.splice(index, 1);
                    },
                    openManagerModal(index) { this.managerTargetIndex = index; this.managerForm = { name: '', email: '', nomor_hp: '', inisial: '' }; this.managerErrors = {}; this.managerModalOpen = true; },
                    closeManagerModal() { if (this.managerSaving) return; this.managerModalOpen = false; this.managerTargetIndex = null; this.managerErrors = {}; },
                    async storeManager() {
                        this.managerErrors = {};
                        if (!this.managerForm.name.trim()) this.managerErrors.name = ['Nama wajib diisi.'];
                        if (!this.managerForm.email.trim()) this.managerErrors.email = ['Email wajib diisi.'];
                        if (Object.keys(this.managerErrors).length) return;
                        this.managerSaving = true;
                        try {
                            const response = await fetch(managerStoreUrl, { method: 'POST', credentials: 'same-origin', headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' }, body: JSON.stringify(this.managerForm) });
                            const data = await response.json().catch(() => ({}));
                            if (response.status === 422) { this.managerErrors = data.errors ?? {}; return; }
                            if (!response.ok) throw new Error(response.status === 403 ? 'Anda tidak memiliki akses untuk menambah manager.' : (response.status === 419 ? 'Sesi Anda telah berakhir. Silakan muat ulang halaman.' : 'Manager belum berhasil dibuat. Silakan coba kembali.'));
                            const manager = data.manager;
                            if (!this.managerOptions.some(item => String(item.id) === String(manager.id))) this.managerOptions.push(manager);
                            this.managerOptions.sort((a, b) => a.name.localeCompare(b.name));
                            if (this.sections[this.managerTargetIndex]) this.sections[this.managerTargetIndex].manager_id = String(manager.id);
                            this.managerModalOpen = false; this.managerTargetIndex = null; this.managerForm = { name: '', email: '', nomor_hp: '', inisial: '' };
                            window.Swal?.fire({ icon: 'success', title: 'Manager berhasil dibuat dan dipilih.', text: 'Klik Simpan Seksi untuk menerapkan perubahan.', timer: 2200, showConfirmButton: false });
                        } catch (error) { const message = error?.message || 'Manager belum berhasil dibuat. Silakan coba kembali.'; if (window.Swal) window.Swal.fire({ icon: 'error', title: 'Gagal', text: message }); else window.alert(message); }
                        finally { this.managerSaving = false; }
                    },
                };
            }

            document.addEventListener('DOMContentLoaded', function () {
                if (window.lucide) {
                    window.lucide.createIcons();
                }
            });
        </script>
    </body>
</html>
