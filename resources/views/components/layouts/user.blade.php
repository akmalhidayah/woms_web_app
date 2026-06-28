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
        @endphp

        <div x-data="{ mobileMenu: false, profileOpen: false }" class="relative min-h-screen">
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
                            <div class="truncate text-lg font-black tracking-tight text-white sm:text-xl">Dept. Project Management &amp; Main Support</div>
                            <div class="mt-0.5 hidden truncate text-sm text-red-100 sm:block">Section of Machine Workshop</div>
                        </div>
                    </div>

                    <div class="hidden items-center gap-3 md:flex">
                        <a
                            href="{{ route('user.dashboard') }}"
                            class="rounded-xl border px-4 py-1.5 text-sm font-semibold transition {{ $currentRoute === 'user.dashboard' ? 'border-white/25 bg-white text-red-800 shadow-sm' : 'border-white/20 bg-white/10 text-white hover:bg-white/15' }}"
                        >
                            Dashboard
                        </a>
                        <div class="relative" @click.outside="profileOpen = false">
                            <button
                                type="button"
                                @click="profileOpen = !profileOpen"
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
                        class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-white/20 bg-white text-red-800 shadow-sm md:hidden"
                        @click="mobileMenu = !mobileMenu"
                        aria-label="Buka menu"
                    >
                        <i data-lucide="menu" class="h-5 w-5"></i>
                    </button>
                </div>

                <div x-show="mobileMenu" x-transition x-cloak class="border-t border-red-700 bg-white px-4 py-4 md:hidden">
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

            <main class="relative mx-auto max-w-none px-3 py-4 sm:px-4 lg:px-6 lg:py-5">
                {{ $slot }}
            </main>
        </div>
        @fluxScripts
    </body>
</html>
