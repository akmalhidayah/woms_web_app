<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-slate-100 text-slate-900">
        @php
            $dashboardUrl = route('dashboard');
            $dashboardCurrent = request()->routeIs('dashboard') || request()->routeIs('*.dashboard');
            $roleLabel = strtoupper(auth()->user()->role);
        @endphp

        <flux:sidebar sticky stashable class="border-r border-slate-200 bg-white">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ $dashboardUrl }}" class="mr-5 flex items-center gap-3" wire:navigate>
                <x-app-logo class="size-8" href="#"></x-app-logo>
                <div class="min-w-0">
                    <div class="text-sm font-semibold tracking-[0.18em] text-slate-900 uppercase">{{ config('app.name', 'WOMS') }}</div>
                    <div class="text-xs text-slate-500">Work Order Management</div>
                </div>
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group heading="Platform" class="grid">
                    <flux:navlist.item icon="home" :href="$dashboardUrl" :current="$dashboardCurrent" wire:navigate>Dashboard</flux:navlist.item>
                    <flux:navlist.item icon="cog-6-tooth" :href="route('settings.profile')" :current="request()->routeIs('settings.*')" wire:navigate>Settings</flux:navlist.item>
                </flux:navlist.group>
            </flux:navlist>

            <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 text-sm text-slate-600">
                <div class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Role</div>
                <div class="mt-2 inline-flex rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em] text-white">
                    {{ $roleLabel }}
                </div>
                <p class="mt-3 leading-6">Setelah login, setiap akun langsung diarahkan ke dashboard sesuai role.</p>
            </div>

            <flux:spacer />

            <flux:dropdown position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevrons-up-down"
                />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.item href="{{ route('settings.profile') }}" icon="cog-6-tooth" wire:navigate>Settings</flux:menu.item>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <flux:header class="border-b border-slate-200 bg-white lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <a href="{{ $dashboardUrl }}" class="ml-3 flex items-center gap-3" wire:navigate>
                <x-app-logo class="size-8" href="#"></x-app-logo>
                <div class="text-sm font-semibold tracking-[0.18em] text-slate-900 uppercase">{{ config('app.name', 'WOMS') }}</div>
            </a>

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-left text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.item href="{{ route('settings.profile') }}" icon="cog-6-tooth" wire:navigate>Settings</flux:menu.item>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
