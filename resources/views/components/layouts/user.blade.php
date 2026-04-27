<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#dc2626">

        <title>{{ $title ?? config('app.name', 'WOMS') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @fluxAppearance
        <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
        <script defer src="https://unpkg.com/lucide@latest"></script>
        <style>[x-cloak]{ display:none !important; }</style>
    </head>
    <body class="min-h-screen bg-stone-50 font-sans text-slate-800 antialiased">
        @php
            $logoSig = asset('assets/branding/logos/logo-sig.png');
            $logoSt = asset('assets/branding/logos/logo-st2.png');
            $user = auth()->user();
            $currentRoute = request()->route()?->getName();
        @endphp

        <div x-data="{ mobileMenu: false, profileOpen: false }" class="relative min-h-screen">
            <header class="sticky top-0 z-30 border-b border-red-900 bg-red-800/95 backdrop-blur">
                <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2 rounded-2xl border border-stone-200 bg-white px-3 py-2 shadow-sm">
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-white">
                                <img src="{{ $logoSig }}" alt="SIG" class="max-h-full w-auto object-contain">
                            </div>
                            <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-red-50">
                                <img src="{{ $logoSt }}" alt="Semen Tonasa" class="max-h-full w-auto object-contain">
                            </div>
                        </div>

                        <div class="hidden min-w-0 sm:block">
                            <div class="truncate text-lg font-black tracking-tight text-white">User Order Tracking</div>
                            <div class="truncate text-sm text-red-100">Pantau progress order dan seluruh dokumen pekerjaan</div>
                        </div>
                    </div>

                    <div class="hidden items-center gap-3 md:flex">
                        <a
                            href="{{ route('user.dashboard') }}"
                            class="rounded-xl border px-4 py-2 text-sm font-semibold transition {{ $currentRoute === 'user.dashboard' ? 'border-red-800 bg-red-800 text-white' : 'border-stone-200 bg-white text-slate-600 hover:border-red-200 hover:text-red-800' }}"
                        >
                            Dashboard
                        </a>
                        <div class="relative" @click.outside="profileOpen = false">
                            <button
                                type="button"
                                @click="profileOpen = !profileOpen"
                                class="inline-flex items-center gap-3 rounded-2xl border border-stone-200 bg-white px-3 py-2 shadow-sm transition hover:border-red-200 hover:bg-red-50/40"
                            >
                                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-red-50 text-sm font-bold text-red-700">
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

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="block w-full px-4 py-3 text-left text-sm text-slate-700 transition hover:bg-slate-50">
                                        Log Out
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <button
                        type="button"
                        class="inline-flex h-11 w-11 items-center justify-center rounded-xl border border-stone-200 bg-white text-slate-700 shadow-sm md:hidden"
                        @click="mobileMenu = !mobileMenu"
                    >
                        <i data-lucide="menu" class="h-5 w-5"></i>
                    </button>
                </div>

                <div x-show="mobileMenu" x-transition x-cloak class="border-t border-stone-200 bg-white px-4 py-4 md:hidden">
                    <div class="space-y-3">
                        <a href="{{ route('user.dashboard') }}" class="block rounded-xl border border-red-200 bg-white px-4 py-3 text-sm font-semibold text-red-800">Dashboard</a>
                        <div class="rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3">
                            <div class="text-sm font-semibold text-slate-900">{{ $user?->name }}</div>
                            <div class="text-xs text-slate-500">{{ $user?->email }}</div>
                        </div>
                        <a href="{{ route('settings.profile') }}" class="block rounded-xl border border-stone-200 bg-white px-4 py-3 text-sm font-semibold text-slate-700">Profile</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full rounded-xl border border-stone-200 bg-white px-4 py-3 text-left text-sm font-semibold text-slate-700">
                                Log Out
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <main class="relative mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8 lg:py-8">
                {{ $slot }}
            </main>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if (window.lucide) {
                    window.lucide.createIcons();
                }
            });
        </script>
        @fluxScripts
    </body>
</html>
