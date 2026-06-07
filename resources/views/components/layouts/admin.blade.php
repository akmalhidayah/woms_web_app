<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0b4db3">

        <title>{{ $title ?? config('app.name', 'WOMS') }}</title>
        <link rel="icon" type="image/png" href="{{ asset('assets/branding/logos/logo-st2.png') }}?v=tonasa">
        <link rel="shortcut icon" type="image/png" href="{{ asset('assets/branding/logos/logo-st2.png') }}?v=tonasa">
        <link rel="apple-touch-icon" href="{{ asset('assets/branding/logos/logo-st2.png') }}?v=tonasa">

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles

        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
        <script defer src="https://unpkg.com/lucide@latest"></script>
        <script defer src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <style>
            [x-cloak]{ display:none !important; }

            .admin-compact :is(section, article, div)[class*="rounded-[1."] {
                border-radius: 0.85rem !important;
            }

            .admin-compact :is(section, article, div)[class*="rounded-2xl"],
            .admin-compact :is(section, article, div)[class*="rounded-3xl"] {
                border-radius: 0.85rem !important;
            }

            .admin-compact :is(section, article, div)[class*="p-5"],
            .admin-compact :is(section, article, div)[class*="p-6"] {
                padding: 0.75rem !important;
            }

            .admin-compact :is(section, article, div)[class*="px-5"],
            .admin-compact :is(section, article, div)[class*="px-6"] {
                padding-left: 0.75rem !important;
                padding-right: 0.75rem !important;
            }

            .admin-compact :is(section, article, div)[class*="py-5"],
            .admin-compact :is(section, article, div)[class*="py-6"] {
                padding-top: 0.75rem !important;
                padding-bottom: 0.75rem !important;
            }

            .admin-compact table {
                font-size: 0.72rem;
            }

            .admin-compact th {
                padding: 0.5rem 0.65rem !important;
                font-size: 0.66rem !important;
                line-height: 1.1rem;
            }

            .admin-compact td {
                padding: 0.5rem 0.65rem !important;
                line-height: 1.2rem;
            }

            .admin-compact input:not([type="checkbox"]):not([type="radio"]),
            .admin-compact select,
            .admin-compact textarea {
                border-radius: 0.5rem !important;
                font-size: 0.76rem !important;
                padding: 0.4rem 0.65rem !important;
            }

            .admin-compact button[class*="px-5"],
            .admin-compact a[class*="px-5"],
            .admin-compact button[class*="px-4"],
            .admin-compact a[class*="px-4"] {
                padding-left: 0.75rem !important;
                padding-right: 0.75rem !important;
            }

            .admin-compact button[class*="py-3"],
            .admin-compact a[class*="py-3"],
            .admin-compact button[class*="py-2"],
            .admin-compact a[class*="py-2"] {
                padding-top: 0.45rem !important;
                padding-bottom: 0.45rem !important;
            }

            .admin-compact :is(h1, h2, h3)[class*="text-2xl"],
            .admin-compact :is(h1, h2, h3)[class*="text-3xl"],
            .admin-compact :is(h1, h2, h3)[class*="text-xl"] {
                font-size: 1.05rem !important;
                line-height: 1.35rem !important;
            }

            .order-list-compact {
                font-size: 0.75rem;
            }

            .order-list-compact .order-list-hero {
                border-radius: 0.9rem !important;
                padding: 0.8rem 0.95rem !important;
            }

            .order-list-compact .order-list-hero > div {
                gap: 0.75rem !important;
            }

            .order-list-compact .order-list-hero span[class*="h-12"] {
                width: 2.25rem !important;
                height: 2.25rem !important;
                border-radius: 0.75rem !important;
            }

            .order-list-compact .order-list-hero h1 {
                font-size: 1.05rem !important;
                line-height: 1.2rem !important;
            }

            .order-list-compact .order-list-hero p {
                margin-top: 0.25rem !important;
                font-size: 0.72rem !important;
            }

            .order-list-compact .order-list-hero button {
                border-radius: 0.65rem !important;
                padding: 0.45rem 0.8rem !important;
                font-size: 0.72rem !important;
            }

            .order-list-compact .order-list-panel {
                border-radius: 0.9rem !important;
            }

            .order-list-compact .order-list-panel > .border-b {
                padding: 0.75rem !important;
            }

            .order-list-compact .order-list-panel form,
            .order-list-compact .order-list-panel .gap-2\.5 {
                gap: 0.5rem !important;
            }

            .order-list-compact .order-list-panel input:not([type="checkbox"]):not([type="radio"]),
            .order-list-compact .order-list-panel select,
            .order-list-compact .order-list-panel textarea {
                min-height: 2rem;
                border-radius: 0.5rem !important;
                padding: 0.35rem 0.55rem !important;
                font-size: 0.7rem !important;
            }

            .order-list-compact .order-list-panel select {
                padding-right: 1.7rem !important;
            }

            .order-list-compact .order-list-panel textarea {
                min-height: 2rem;
            }

            .order-list-compact .order-list-panel th {
                padding: 0.45rem 0.65rem !important;
                font-size: 0.58rem !important;
                line-height: 0.9rem !important;
            }

            .order-list-compact .order-list-panel td {
                padding: 0.45rem 0.65rem !important;
                font-size: 0.68rem !important;
                line-height: 1.05rem !important;
            }

            .order-list-compact .order-list-panel td > div[class*="rounded"] {
                border-radius: 0.65rem !important;
            }

            .order-list-compact .order-list-panel :is(td > div[class*="rounded-xl"], td > div[class*="rounded-2xl"]) {
                padding: 0.55rem 0.65rem !important;
            }

            .order-list-compact .order-list-panel .min-w-\[165px\] {
                min-width: 9rem !important;
            }

            .order-list-compact .order-list-panel .max-w-\[150px\] {
                max-width: 7rem !important;
            }

            .order-list-compact .order-list-panel [class*="text-[14px]"] {
                font-size: 0.78rem !important;
            }

            .order-list-compact .order-list-panel [class*="text-[13px]"] {
                font-size: 0.72rem !important;
            }

            .order-list-compact .order-list-panel [class*="text-[12px]"] {
                font-size: 0.68rem !important;
            }

            .order-list-compact .order-list-panel [class*="text-[11px]"] {
                font-size: 0.62rem !important;
            }

            .order-list-compact .order-list-panel [class*="text-[10px]"] {
                font-size: 0.58rem !important;
            }

            .order-list-compact .order-list-panel [class*="text-[9px]"] {
                font-size: 0.54rem !important;
            }

            .order-list-compact .order-list-panel :is(a, button, span)[class*="rounded"] {
                border-radius: 0.55rem !important;
            }

            .order-list-compact .order-list-panel :is(a, button, span)[class*="px-2.5"],
            .order-list-compact .order-list-panel :is(a, button, span)[class*="px-3"] {
                padding-left: 0.45rem !important;
                padding-right: 0.45rem !important;
            }

            .order-list-compact .order-list-panel :is(a, button, span)[class*="py-1.5"],
            .order-list-compact .order-list-panel :is(a, button, span)[class*="py-2"] {
                padding-top: 0.28rem !important;
                padding-bottom: 0.28rem !important;
            }

            .order-list-compact .order-list-panel :is(a, button)[class*="h-9"][class*="w-9"] {
                width: 1.95rem !important;
                height: 1.95rem !important;
            }

            .order-list-compact .order-list-panel :is(a, button)[class*="h-8"][class*="w-8"] {
                width: 1.8rem !important;
                height: 1.8rem !important;
            }

            .order-list-compact .order-list-panel :is(a, button)[class*="h-7"][class*="w-7"] {
                width: 1.65rem !important;
                height: 1.65rem !important;
            }

            .lpj-compact .order-list-panel table {
                font-size: 0.62rem !important;
            }

            .lpj-compact .order-list-panel col:nth-child(3) {
                width: 20% !important;
            }

            .lpj-compact .order-list-panel col:nth-child(4) {
                width: 13% !important;
            }

            .lpj-compact .order-list-panel col:nth-child(5) {
                width: 11% !important;
            }

            .lpj-compact .order-list-panel col:nth-child(6) {
                width: 16% !important;
            }

            .lpj-compact .order-list-panel th {
                padding: 0.45rem 0.55rem !important;
                font-size: 0.56rem !important;
            }

            .lpj-compact .order-list-panel td {
                padding: 0.45rem 0.55rem !important;
            }

            .lpj-compact .order-list-panel td > div[class*="space-y"],
            .lpj-compact .order-list-panel td form[class*="space-y"],
            .lpj-compact .order-list-panel td div[class*="rounded"] {
                gap: 0.32rem !important;
            }

            .lpj-compact .order-list-panel td div[class*="rounded-lg"],
            .lpj-compact .order-list-panel td div[class*="rounded-xl"] {
                padding: 0.45rem 0.55rem !important;
                border-radius: 0.55rem !important;
            }

            .lpj-compact .order-list-panel input:not([type="checkbox"]):not([type="radio"]),
            .lpj-compact .order-list-panel select {
                height: 1.8rem !important;
                min-height: 1.8rem !important;
                padding: 0.25rem 0.5rem !important;
                font-size: 0.62rem !important;
            }

            .lpj-compact .order-list-panel label[class*="h-7"],
            .lpj-compact .order-list-panel button[class*="h-8"] {
                height: 1.75rem !important;
                padding: 0.25rem 0.55rem !important;
                font-size: 0.6rem !important;
            }

            .lpj-compact .order-list-panel [class*="mt-2"],
            .lpj-compact .order-list-panel [class*="mt-1.5"] {
                margin-top: 0.35rem !important;
            }

            .lpj-compact .order-list-panel [class*="pt-1.5"],
            .lpj-compact .order-list-panel [class*="pt-2"] {
                padding-top: 0.35rem !important;
            }

            .lpj-compact .order-list-panel [class*="text-[13px]"] {
                font-size: 0.76rem !important;
            }

            .lpj-compact .order-list-panel [class*="text-[11px]"] {
                font-size: 0.64rem !important;
            }

            .bengkel-compact .order-list-panel,
            .bengkel-compact .bengkel-table-panel {
                border-radius: 0.85rem !important;
            }

            .bengkel-compact .order-list-panel {
                padding: 0.7rem !important;
            }

            .bengkel-compact .order-list-panel form {
                gap: 0.55rem !important;
            }

            .bengkel-compact .order-list-panel label {
                font-size: 0.62rem !important;
                margin-bottom: 0.3rem !important;
            }

            .bengkel-compact .order-list-panel input:not([type="checkbox"]):not([type="radio"]),
            .bengkel-compact .order-list-panel select,
            .bengkel-compact .order-list-panel textarea {
                height: 1.85rem !important;
                min-height: 1.85rem !important;
                padding: 0.3rem 0.55rem !important;
                font-size: 0.68rem !important;
            }

            .bengkel-compact .order-list-panel :is(a, button) {
                min-height: 1.85rem !important;
                border-radius: 0.55rem !important;
                padding: 0.3rem 0.65rem !important;
                font-size: 0.68rem !important;
            }

            .bengkel-compact .bengkel-table-panel > form {
                padding: 0.55rem 0.7rem !important;
            }

            .bengkel-compact .bengkel-table-panel table {
                font-size: 0.68rem !important;
            }

            .bengkel-compact .bengkel-table-panel th {
                padding: 0.45rem 0.6rem !important;
                font-size: 0.6rem !important;
            }

            .bengkel-compact .bengkel-table-panel td {
                padding: 0.45rem 0.6rem !important;
                line-height: 1rem !important;
            }

            .bengkel-compact .bengkel-table-panel [class*="text-[13px]"] {
                font-size: 0.72rem !important;
            }

            .bengkel-compact .bengkel-table-panel [class*="text-[11px]"] {
                font-size: 0.62rem !important;
            }

            .bengkel-compact .bengkel-table-panel [class*="text-[10px]"] {
                font-size: 0.58rem !important;
            }

            .bengkel-compact .bengkel-table-panel :is(a, button, label, span)[class*="rounded"] {
                border-radius: 0.55rem !important;
            }

            .bengkel-compact .bengkel-table-panel :is(a, button, label, span)[class*="px-3"],
            .bengkel-compact .bengkel-table-panel :is(a, button, label, span)[class*="px-4"],
            .bengkel-compact .bengkel-table-panel :is(div)[class*="px-3"] {
                padding-left: 0.55rem !important;
                padding-right: 0.55rem !important;
            }

            .bengkel-compact .bengkel-table-panel :is(a, button)[class*="h-8"][class*="w-8"] {
                width: 1.75rem !important;
                height: 1.75rem !important;
            }

            .bengkel-compact .bengkel-table-panel img[class*="h-6"],
            .bengkel-compact .bengkel-table-panel span[class*="h-6"][class*="w-6"] {
                width: 1.25rem !important;
                height: 1.25rem !important;
            }

            .other-menu-compact {
                font-size: 0.74rem;
            }

            .other-menu-compact > section,
            .other-menu-compact > article,
            .other-menu-compact details,
            .other-menu-compact section[class*="rounded"],
            .other-menu-compact article[class*="rounded"] {
                border-radius: 0.85rem !important;
            }

            .other-menu-compact section[class*="px-6"],
            .other-menu-compact section[class*="px-5"],
            .other-menu-compact section[class*="p-5"],
            .other-menu-compact article[class*="p-5"],
            .other-menu-compact details > summary {
                padding: 0.75rem !important;
            }

            .other-menu-compact section[class*="py-6"],
            .other-menu-compact section[class*="py-5"] {
                padding-top: 0.75rem !important;
                padding-bottom: 0.75rem !important;
            }

            .other-menu-compact [class*="h-14"][class*="w-14"] {
                width: 2.4rem !important;
                height: 2.4rem !important;
                border-radius: 0.75rem !important;
            }

            .other-menu-compact :is(h1, h2, h3)[class*="text-2xl"],
            .other-menu-compact :is(h1, h2, h3)[class*="text-xl"],
            .other-menu-compact h1[class*="text-[1.3rem]"] {
                font-size: 1.05rem !important;
                line-height: 1.25rem !important;
            }

            .other-menu-compact :is(p, div, span)[class*="text-sm"] {
                font-size: 0.72rem !important;
                line-height: 1.05rem !important;
            }

            .other-menu-compact [class*="text-lg"] {
                font-size: 0.9rem !important;
                line-height: 1.2rem !important;
            }

            .other-menu-compact [class*="text-base"] {
                font-size: 0.8rem !important;
                line-height: 1.1rem !important;
            }

            .other-menu-compact [class*="text-xs"] {
                font-size: 0.62rem !important;
            }

            .other-menu-compact [class*="text-[11px]"] {
                font-size: 0.6rem !important;
            }

            .other-menu-compact [class*="text-[13px]"] {
                font-size: 0.68rem !important;
            }

            .other-menu-compact [class*="text-2xl"] {
                font-size: 1.05rem !important;
            }

            .other-menu-compact [class*="text-xl"] {
                font-size: 0.95rem !important;
            }

            .other-menu-compact table {
                font-size: 0.68rem !important;
            }

            .other-menu-compact th {
                padding: 0.45rem 0.6rem !important;
                font-size: 0.6rem !important;
                line-height: 0.95rem !important;
            }

            .other-menu-compact td {
                padding: 0.45rem 0.6rem !important;
                line-height: 1rem !important;
            }

            .other-menu-compact input:not([type="checkbox"]):not([type="radio"]):not([type="range"]),
            .other-menu-compact select,
            .other-menu-compact textarea {
                min-height: 1.9rem !important;
                border-radius: 0.55rem !important;
                padding: 0.35rem 0.6rem !important;
                font-size: 0.68rem !important;
            }

            .other-menu-compact :is(a, button, label)[class*="rounded"] {
                border-radius: 0.55rem !important;
            }

            .other-menu-compact :is(a, button)[class*="px-4"],
            .other-menu-compact :is(a, button)[class*="px-5"],
            .other-menu-compact :is(label)[class*="px-3"],
            .other-menu-compact :is(div, span)[class*="px-4"] {
                padding-left: 0.65rem !important;
                padding-right: 0.65rem !important;
            }

            .other-menu-compact :is(a, button)[class*="py-3"],
            .other-menu-compact :is(a, button)[class*="py-2.5"],
            .other-menu-compact :is(label)[class*="py-3"],
            .other-menu-compact :is(div, span)[class*="py-3"] {
                padding-top: 0.42rem !important;
                padding-bottom: 0.42rem !important;
            }

            .other-menu-compact [class*="p-5"],
            .other-menu-compact [class*="p-6"] {
                padding: 0.75rem !important;
            }

            .other-menu-compact [class*="p-4"] {
                padding: 0.65rem !important;
            }

            .other-menu-compact [class*="gap-5"],
            .other-menu-compact [class*="gap-4"],
            .other-menu-compact [class*="space-y-6"],
            .other-menu-compact [class*="space-y-5"],
            .other-menu-compact [class*="space-y-4"] {
                gap: 0.75rem !important;
            }
        </style>
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
            $adminNotifications = \App\Support\AdminNotificationCenter::signatureNotifications(5);
            $adminNotificationCount = \App\Support\AdminNotificationCenter::signatureNotificationCount();
            $adminNotificationBadge = $adminNotificationCount > 9 ? '9+' : (string) $adminNotificationCount;
            $notificationToneClasses = [
                'blue' => 'bg-blue-50 text-blue-700 ring-blue-100',
                'amber' => 'bg-amber-50 text-amber-700 ring-amber-100',
                'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            ];
        @endphp

        <div
            x-data="{
                sidebarOpen: true,
                mobileOpen: false,
                profileOpen: false,
                notificationsOpen: false,
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
                class="fixed inset-y-0 left-0 z-40 flex w-60 flex-col border-r border-blue-950/30 bg-blue-900 shadow-sm transition-all duration-300"
                :class="[
                    mobileOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
                    sidebarOpen ? 'lg:w-60' : 'lg:w-16'
                ]"
            >
                <div class="sticky top-0 z-10 border-b border-blue-950/30 bg-blue-900">
                    <div class="flex items-center justify-between gap-2 px-3 py-3">
                        <div class="flex min-w-0 items-center gap-2">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/10 p-1.5 ring-1 ring-white/10" x-show="sidebarOpen" x-transition.opacity.duration.200ms>
                                <img src="{{ $logoBms }}" alt="Logo BMS2" class="max-h-full w-auto object-contain">
                            </div>

                            <div class="min-w-0 space-y-1" x-show="sidebarOpen" x-transition.opacity.duration.200ms>
                                <div class="truncate text-[11px] font-medium text-white/70">Admin Dashboard</div>
                            </div>
                        </div>

                        <button
                            @click="toggleSidebar()"
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
                                class="w-full rounded-lg border border-white/15 bg-white/10 py-1.5 pl-8 pr-2.5 text-xs text-white placeholder:text-white/50 focus:border-white/25 focus:outline-none focus:ring-2 focus:ring-white/20"
                            >
                        </div>
                    </div>
                </div>

                <div class="no-scrollbar flex-1 overflow-y-auto px-2 py-3">
                    <nav class="space-y-0.5 text-xs">
                        @if ($dashboardMenu)
                            @php($isActive = $dashboardMenu['active'] ?? false)
                            <a
                                href="{{ $dashboardMenu['href'] }}"
                                class="group flex items-center gap-2.5 rounded-lg px-2.5 py-2 transition {{ $isActive ? 'bg-white text-blue-900 ring-1 ring-white/30' : 'text-white/90 hover:bg-white/10' }}"
                            >
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg transition {{ $isActive ? 'bg-blue-100 text-blue-900' : 'bg-white/10 text-white/90 group-hover:bg-white/15' }}">
                                    <i data-lucide="{{ $dashboardMenu['icon'] }}" class="h-4 w-4"></i>
                                </span>
                                <span x-show="sidebarOpen" x-transition.opacity.duration.200ms class="font-medium">{{ $dashboardMenu['label'] }}</span>
                            </a>
                        @endif

                        @if ($orderMenu || $mainMenus !== [])
                            <div class="pb-0.5 pt-2" x-show="sidebarOpen" x-transition.opacity.duration.200ms>
                                <div class="px-2.5 text-[10px] uppercase tracking-wider text-white/60">Menu Utama</div>
                            </div>
                        @endif

                        @if ($orderMenu)
                            <div class="rounded-xl">
                                <button
                                    @click="orderOpen = !orderOpen"
                                    class="group flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 transition {{ $isOrdersSection ? 'bg-white text-blue-900 ring-1 ring-white/30' : 'text-white/90 hover:bg-white/10' }}"
                                >
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg transition {{ $isOrdersSection ? 'bg-blue-100 text-blue-900' : 'bg-white/10 text-white/90 group-hover:bg-white/15' }}">
                                        <i data-lucide="{{ $orderMenu['icon'] }}" class="h-4 w-4"></i>
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

                                <div x-show="orderOpen && sidebarOpen" x-transition.opacity.duration.200ms x-cloak class="mt-0.5 space-y-0.5 pl-9">
                                    @foreach ($orderMenus as $menu)
                                        <a href="{{ $menu['href'] }}" class="flex items-center justify-between rounded-md px-2.5 py-1.5 transition {{ $menu['active'] ? 'bg-white text-blue-900' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                                            <span>{{ $menu['label'] }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @foreach ($mainMenus as $menu)
                            @php($isActive = $menu['active'] ?? false)
                            @if (! empty($menu['children']))
                                <div class="rounded-xl" x-data="{ open: {{ $isActive ? 'true' : 'false' }} }">
                                    <button
                                        @click="open = !open"
                                        class="group flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 transition {{ $isActive ? 'bg-white text-blue-900 ring-1 ring-white/30' : 'text-white/90 hover:bg-white/10' }}"
                                    >
                                        <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg transition {{ $isActive ? 'bg-blue-100 text-blue-900' : 'bg-white/10 text-white/90 group-hover:bg-white/15' }}">
                                            <i data-lucide="{{ $menu['icon'] }}" class="h-4 w-4"></i>
                                        </span>
                                        <span x-show="sidebarOpen" x-transition.opacity.duration.200ms class="flex-1 text-left font-medium">{{ $menu['label'] }}</span>
                                        <i
                                            data-lucide="chevron-down"
                                            class="h-4 w-4 transition"
                                            :class="open ? 'rotate-180 {{ $isActive ? 'text-blue-900' : 'text-white/70' }}' : '{{ $isActive ? 'text-blue-900' : 'text-white/70' }}'"
                                            x-show="sidebarOpen"
                                            x-transition.opacity.duration.200ms
                                        ></i>
                                    </button>

                                    <div x-show="open && sidebarOpen" x-transition.opacity.duration.200ms x-cloak class="mt-0.5 space-y-0.5 pl-9">
                                        @foreach ($menu['children'] as $childMenu)
                                            <a href="{{ $childMenu['href'] }}" class="flex items-center justify-between rounded-md px-2.5 py-1.5 transition {{ $childMenu['active'] ? 'bg-white text-blue-900' : 'text-white/80 hover:bg-white/10 hover:text-white' }}">
                                                <span>{{ $childMenu['label'] }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <a
                                    href="{{ $menu['href'] }}"
                                    class="group flex items-center gap-2.5 rounded-lg px-2.5 py-2 transition {{ $isActive ? 'bg-white text-blue-900 ring-1 ring-white/30' : 'text-white/90 hover:bg-white/10' }}"
                                >
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg transition {{ $isActive ? 'bg-blue-100 text-blue-900' : 'bg-white/10 text-white/90 group-hover:bg-white/15' }}">
                                        <i data-lucide="{{ $menu['icon'] }}" class="h-4 w-4"></i>
                                    </span>
                                    <span x-show="sidebarOpen" x-transition.opacity.duration.200ms class="font-medium">{{ $menu['label'] }}</span>
                                </a>
                            @endif
                        @endforeach

                        @if ($supportMenus !== [] || $otherMenus !== [])
                            <div class="pb-0.5 pt-2" x-show="sidebarOpen" x-transition.opacity.duration.200ms>
                                <div class="px-2.5 text-[10px] uppercase tracking-wider text-white/60">Menu Pendukung</div>
                            </div>
                        @endif

                        @foreach ($supportMenus as $supportMenu)
                            @php($supportActive = $supportMenu['active'] ?? false)
                            <a
                                href="{{ $supportMenu['href'] }}"
                                class="group flex items-center gap-2.5 rounded-lg px-2.5 py-2 transition {{ $supportActive ? 'bg-white text-blue-900 ring-1 ring-white/30' : 'text-white/90 hover:bg-white/10' }}"
                            >
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg transition {{ $supportActive ? 'bg-blue-100 text-blue-900' : 'bg-white/10 text-white/90 group-hover:bg-white/15' }}">
                                    <i data-lucide="{{ $supportMenu['icon'] }}" class="h-4 w-4"></i>
                                </span>
                                <span x-show="sidebarOpen" x-transition.opacity.duration.200ms class="font-medium">{{ $supportMenu['label'] }}</span>
                            </a>
                        @endforeach

                        @if ($otherMenus !== [])
                            <div class="rounded-xl">
                                <button
                                    @click="otherOpen = !otherOpen"
                                    class="group flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-white/90 transition hover:bg-white/10"
                                >
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-white/10 text-white/90 transition group-hover:bg-white/15">
                                        <i data-lucide="layers" class="h-4 w-4"></i>
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

                                <div x-show="otherOpen && sidebarOpen" x-transition.opacity.duration.200ms x-cloak class="mt-0.5 space-y-0.5 pl-9">
                                    @foreach ($otherMenus as $menu)
                                        <a
                                            href="{{ $menu['href'] }}"
                                            class="block rounded-md px-2.5 py-1.5 transition {{ $menu['active'] ? 'bg-white text-blue-900' : 'text-white/80 hover:bg-white/10 hover:text-white' }}"
                                        >
                                            {{ $menu['label'] }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </nav>
                </div>

                <div class="border-t border-white/10 p-2">
                    <div class="flex items-center gap-2 rounded-lg px-2.5 py-1.5 text-[11px] text-white/70">
                        <i data-lucide="sparkles" class="h-3.5 w-3.5"></i>
                        <span x-show="sidebarOpen" x-transition.opacity.duration.200ms>Workshop - Admin</span>
                    </div>
                </div>
            </aside>

            <div class="min-h-screen transition-all duration-300" :class="sidebarOpen ? 'lg:pl-60' : 'lg:pl-16'">
                <header class="sticky top-0 z-20 border-b border-blue-950/30 bg-blue-900">
                    <div class="flex items-center justify-between px-3 py-2 lg:px-5">
                        <div class="flex items-center gap-2.5">
                            <button
                                @click="toggleSidebar()"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-lg text-white transition hover:bg-white/10 lg:hidden"
                                aria-label="Open Menu"
                            >
                                <i data-lucide="menu" class="h-5 w-5"></i>
                            </button>

                            <div class="flex items-center gap-2">
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-white p-1.5 shadow-sm">
                                    <img src="{{ $logoSig }}" alt="Logo SIG" class="max-h-full w-auto object-contain">
                                </div>
                                <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-800 p-1.5 ring-1 ring-white/10">
                                    <img src="{{ $logoSt }}" alt="Logo ST2" class="max-h-full w-auto object-contain">
                                </div>
                            </div>

                            <div class="hidden leading-tight text-white md:flex md:flex-col">
                                <span class="text-sm font-extrabold tracking-tight">SECTION OF WORKSHOP</span>
                                <span class="mt-0.5 text-[11px] text-white/80">Dept. Of Project Management &amp; Main Support</span>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <div class="relative" @click.outside="notificationsOpen = false">
                                <button
                                    type="button"
                                    @click="notificationsOpen = !notificationsOpen"
                                    class="relative inline-flex h-9 w-9 items-center justify-center rounded-lg text-white transition hover:bg-white/10"
                                    aria-label="Notifications"
                                >
                                    <i data-lucide="bell" class="h-5 w-5"></i>
                                    @if ($adminNotificationCount > 0)
                                        <span class="absolute -right-1 -top-1 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white">{{ $adminNotificationBadge }}</span>
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
                                            <div class="mt-0.5 text-xs text-slate-500">Aktivitas terbaru</div>
                                        </div>
                                        @if ($adminNotificationCount > 0)
                                            <span class="rounded-full bg-red-50 px-2.5 py-1 text-[10px] font-bold text-red-700 ring-1 ring-red-100">{{ $adminNotificationBadge }}</span>
                                        @endif
                                    </div>

                                    <div class="max-h-[24rem] overflow-y-auto">
                                        @forelse ($adminNotifications as $notification)
                                            @php($toneClass = $notificationToneClasses[$notification['tone'] ?? 'blue'] ?? $notificationToneClasses['blue'])
                                            <a href="{{ $notification['url'] }}" class="flex gap-3 border-b border-slate-100 px-4 py-3 text-left transition last:border-b-0 hover:bg-slate-50">
                                                <span class="mt-0.5 inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl ring-1 {{ $toneClass }}">
                                                    <i data-lucide="{{ $notification['icon'] }}" class="h-4 w-4"></i>
                                                </span>
                                                <span class="min-w-0 flex-1">
                                                    <span class="block text-xs font-bold uppercase tracking-[0.14em] text-slate-400">{{ $notification['type'] }}</span>
                                                    <span class="mt-1 block text-sm font-semibold leading-5 text-slate-900">{{ $notification['message'] }}</span>
                                                    <span class="mt-1 flex flex-wrap items-center gap-2 text-[11px] text-slate-500">
                                                        <span>{{ $notification['meta'] ?: '-' }}</span>
                                                        <span class="text-slate-300">/</span>
                                                        <span>{{ optional($notification['signed_at'])->diffForHumans() }}</span>
                                                    </span>
                                                </span>
                                            </a>
                                        @empty
                                            <div class="px-4 py-8 text-center">
                                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-50 text-slate-400 ring-1 ring-slate-100">
                                                    <i data-lucide="bell-off" class="h-5 w-5"></i>
                                                </div>
                                                <div class="mt-3 text-sm font-semibold text-slate-700">Belum ada aktivitas terbaru</div>
                                                <div class="mt-1 text-xs leading-5 text-slate-500">Notifikasi hanya menampilkan beberapa aktivitas terbaru.</div>
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>

                            <div class="relative" @click.outside="profileOpen = false">
                                <button
                                    @click="profileOpen = !profileOpen"
                                    class="inline-flex items-center gap-2 rounded-lg bg-white px-2.5 py-1.5 text-blue-700 shadow-sm transition hover:bg-blue-50"
                                >
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 text-xs font-bold tracking-wide text-blue-700">
                                        {{ $userInitials }}
                                    </span>
                                    <span class="hidden min-w-0 text-left sm:block">
                                        <span class="block truncate text-xs font-semibold">{{ $user?->name ?? 'Admin' }}</span>
                                        <span class="block text-[10px] text-blue-400">{{ $roleBadge }}</span>
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

                <main class="p-3 lg:p-4">
                    <div class="admin-compact rounded-xl border border-slate-200 bg-white p-3 shadow-sm lg:p-4">
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
