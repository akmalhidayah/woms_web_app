<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        @include('partials.head')
    </head>
    <body class="auth-shell min-h-screen antialiased">
        @php
            $logoSt = asset('assets/branding/logos/logo-st2.png');
            $logoBms = asset('assets/branding/logos/logo-bms2.png');
            $authVideo = asset('assets/branding/illustrations/bg.mp4');
            $authPoster = asset('assets/branding/illustrations/bg-login.jpg');
            $showLoginInfoMenu = request()->routeIs('login');

            $caraKerjaPns = collect();
            $caraKerjaPkm = collect();
            $caraKerjaApproval = collect();
            $flowchartFiles = collect();
            $kontrakFiles = collect();

            if ($showLoginInfoMenu) {
                $loginInfoFiles = \App\Models\AdminInformationFile::query()
                    ->whereIn('type', \App\Models\AdminInformationFile::allowedTypes())
                    ->orderByDesc('id')
                    ->get();

                $caraKerjaPns = $loginInfoFiles
                    ->where('type', \App\Models\AdminInformationFile::TYPE_CARA_KERJA)
                    ->where('role', \App\Models\User::ROLE_USER)
                    ->values();

                $caraKerjaPkm = $loginInfoFiles
                    ->where('type', \App\Models\AdminInformationFile::TYPE_CARA_KERJA)
                    ->where('role', \App\Models\User::ROLE_PKM)
                    ->values();

                $caraKerjaApproval = $loginInfoFiles
                    ->where('type', \App\Models\AdminInformationFile::TYPE_CARA_KERJA)
                    ->where('role', \App\Models\User::ROLE_APPROVER)
                    ->values();

                $flowchartFiles = $loginInfoFiles
                    ->where('type', \App\Models\AdminInformationFile::TYPE_FLOWCHART_APLIKASI)
                    ->values();

                $kontrakFiles = $loginInfoFiles
                    ->where('type', \App\Models\AdminInformationFile::TYPE_KONTRAK_PKM)
                    ->values();
            }
        @endphp

        <div class="relative isolate min-h-screen overflow-hidden" style="background: url('{{ $authPoster }}') center center / cover no-repeat;">
            <div class="auth-orb left-[-5rem] top-[-3rem] -z-10 h-48 w-48 bg-pink-300/40 sm:h-64 sm:w-64"></div>
            <div class="auth-orb auth-delay-2 right-[-4rem] top-[12%] -z-10 h-56 w-56 bg-sky-300/35 sm:h-72 sm:w-72"></div>
            <div class="auth-orb auth-orb-soft bottom-[-4rem] left-[14%] -z-10 h-52 w-52 bg-violet-300/30 sm:h-72 sm:w-72"></div>
            <div class="auth-orb auth-delay-4 auth-orb-soft bottom-[8%] right-[10%] -z-10 h-40 w-40 bg-amber-200/40 sm:h-56 sm:w-56"></div>

            <div class="relative z-10 mx-auto flex min-h-screen max-w-6xl items-center px-4 py-8 sm:px-6 lg:px-8">
                <div class="grid w-full overflow-hidden rounded-[2rem] border border-white/60 bg-white/70 shadow-2xl shadow-slate-300/20 backdrop-blur-sm lg:grid-cols-[1.08fr_0.92fr]">
                    <section class="auth-panel relative hidden overflow-hidden px-10 py-12 text-white lg:flex lg:flex-col lg:justify-between">
                        <video
                            class="absolute inset-0 h-full w-full object-cover opacity-35"
                            autoplay
                            muted
                            loop
                            playsinline
                            preload="metadata"
                            poster="{{ $authPoster }}"
                        >
                            <source src="{{ $authVideo }}" type="video/mp4">
                        </video>
                        <div class="absolute inset-0 bg-slate-950/55"></div>
                        <div class="auth-orb left-[8%] top-[12%] h-28 w-28 bg-fuchsia-300/20"></div>
                        <div class="auth-orb auth-delay-3 right-[10%] top-[20%] h-36 w-36 bg-sky-300/15"></div>

                        <div class="relative z-10 flex h-full flex-col justify-between">
                            <div class="auth-reveal">
                                <a href="{{ route('home') }}" class="inline-flex items-center gap-4 text-sm text-slate-100" wire:navigate>
                                    <span class="flex items-center gap-3 rounded-2xl bg-white/10 px-4 py-3 ring-1 ring-white/15 shadow-lg shadow-sky-950/20 backdrop-blur-sm">
                                        <img src="{{ $logoSt }}" alt="Logo ST2" class="h-12 w-auto object-contain">
                                        <span class="h-8 w-px bg-white/15"></span>
                                        <img src="{{ $logoBms }}" alt="Logo BMS2" class="h-12 w-auto object-contain">
                                    </span>
                                    <span class="max-w-[15rem] text-sm font-semibold leading-5 text-slate-100">
                                        Workshop Order Management System
                                    </span>
                                </a>

                                @if ($showLoginInfoMenu)
                                    <div class="mt-3 hidden flex-wrap items-center gap-2 text-xs md:flex">
                                        @if ($caraKerjaPns->isNotEmpty() || $caraKerjaPkm->isNotEmpty() || $caraKerjaApproval->isNotEmpty())
                                            <details class="group relative">
                                                <summary class="inline-flex cursor-pointer list-none items-center gap-2 rounded-xl bg-white/8 px-3 py-2 font-semibold text-slate-100 ring-1 ring-white/10 transition hover:bg-white/12">
                                                    <span>User Book App</span>
                                                    <svg class="h-4 w-4 transition group-open:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                                                    </svg>
                                                </summary>

                                                <div class="absolute left-0 top-full z-20 mt-3 w-64 rounded-2xl border border-slate-200 bg-white p-2 text-left shadow-xl shadow-slate-900/20">
                                                    @if ($caraKerjaPns->isNotEmpty())
                                                        <a href="{{ route('public.information-upload.preview', $caraKerjaPns->first()) }}" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-xl px-3 py-3 text-slate-700 transition hover:bg-emerald-50">
                                                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">📘</div>
                                                            <div>
                                                                <div class="text-sm font-semibold">Role PNS</div>
                                                                <div class="text-xs text-slate-500">Panduan pengguna</div>
                                                            </div>
                                                        </a>
                                                    @endif

                                                    @if ($caraKerjaPkm->isNotEmpty())
                                                        <a href="{{ route('public.information-upload.preview', $caraKerjaPkm->first()) }}" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-xl px-3 py-3 text-slate-700 transition hover:bg-indigo-50">
                                                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700">📗</div>
                                                            <div>
                                                                <div class="text-sm font-semibold">Role PKM</div>
                                                                <div class="text-xs text-slate-500">Panduan PKM</div>
                                                            </div>
                                                        </a>
                                                    @endif

                                                    @if ($caraKerjaApproval->isNotEmpty())
                                                        <a href="{{ route('public.information-upload.preview', $caraKerjaApproval->first()) }}" target="_blank" rel="noopener" class="flex items-center gap-3 rounded-xl px-3 py-3 text-slate-700 transition hover:bg-amber-50">
                                                            <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100 text-amber-700">📙</div>
                                                            <div>
                                                                <div class="text-sm font-semibold">Role Approval</div>
                                                                <div class="text-xs text-slate-500">Panduan approval</div>
                                                            </div>
                                                        </a>
                                                    @endif
                                                </div>
                                            </details>
                                        @endif

                                        @if ($flowchartFiles->isNotEmpty())
                                            <a href="{{ route('public.information-upload.preview', $flowchartFiles->first()) }}" target="_blank" rel="noopener" class="rounded-xl px-3 py-2 font-semibold text-slate-200 transition hover:bg-white/10 hover:text-white">
                                                Flowchart
                                            </a>
                                        @endif

                                        @if ($kontrakFiles->isNotEmpty())
                                            <a href="{{ route('public.information-upload.preview', $kontrakFiles->first()) }}" target="_blank" rel="noopener" class="rounded-xl px-3 py-2 font-semibold text-slate-200 transition hover:bg-white/10 hover:text-white">
                                                Kontrak PKM
                                            </a>
                                        @endif

                                        <a href="{{ route('display.bengkel') }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-xl bg-emerald-400/12 px-3 py-2 font-semibold text-emerald-100 ring-1 ring-emerald-300/25 transition hover:bg-emerald-400/20 hover:text-white">
                                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="3" y="4" width="18" height="12" rx="2"></rect>
                                                <path d="M8 20h8"></path>
                                                <path d="M12 16v4"></path>
                                                <path d="m10 10 2 2 3-4"></path>
                                            </svg>
                                            <span>Buka Display</span>
                                        </a>
                                    </div>
                                @endif
                            </div>

                            <div class="auth-reveal auth-delay-1 space-y-3">
                            </div>

                            <div class="auth-accent-card auth-reveal auth-delay-2 w-fit rounded-2xl border border-white/10 px-5 py-3 text-sm text-slate-200">
                                Workshop Order Management System
                            </div>
                        </div>
                    </section>

                    <section class="flex items-center justify-center px-5 py-8 sm:px-8 lg:px-12 lg:py-12">
                        <div class="w-full max-w-md space-y-6">
                            <a href="{{ route('home') }}" class="auth-reveal inline-flex flex-col items-start gap-3 text-sm text-slate-700 lg:hidden" wire:navigate>
                                <span class="inline-flex items-center gap-3 rounded-2xl bg-white/80 px-4 py-3 shadow-lg shadow-slate-200/70 ring-1 ring-slate-200/70 backdrop-blur-sm">
                                    <img src="{{ $logoSt }}" alt="Logo ST2" class="h-10 w-auto object-contain">
                                    <span class="h-7 w-px bg-slate-200"></span>
                                    <img src="{{ $logoBms }}" alt="Logo BMS2" class="h-10 w-auto object-contain">
                                </span>
                                <span>
                                    <span class="block font-semibold leading-5 text-slate-900">Workshop Order Management System</span>
                                </span>
                            </a>

                            @if ($showLoginInfoMenu)
                                <div class="auth-reveal auth-delay-1 flex flex-wrap items-center gap-2 lg:hidden">
                                    @if ($caraKerjaPns->isNotEmpty())
                                        <a href="{{ route('public.information-upload.preview', $caraKerjaPns->first()) }}" target="_blank" rel="noopener" class="rounded-full border border-slate-200 bg-white/80 px-3 py-1.5 text-xs font-semibold text-slate-600">
                                            User PNS
                                        </a>
                                    @endif
                                    @if ($caraKerjaPkm->isNotEmpty())
                                        <a href="{{ route('public.information-upload.preview', $caraKerjaPkm->first()) }}" target="_blank" rel="noopener" class="rounded-full border border-slate-200 bg-white/80 px-3 py-1.5 text-xs font-semibold text-slate-600">
                                            User PKM
                                        </a>
                                    @endif
                                    @if ($caraKerjaApproval->isNotEmpty())
                                        <a href="{{ route('public.information-upload.preview', $caraKerjaApproval->first()) }}" target="_blank" rel="noopener" class="rounded-full border border-slate-200 bg-white/80 px-3 py-1.5 text-xs font-semibold text-slate-600">
                                            Approval
                                        </a>
                                    @endif
                                    @if ($flowchartFiles->isNotEmpty())
                                        <a href="{{ route('public.information-upload.preview', $flowchartFiles->first()) }}" target="_blank" rel="noopener" class="rounded-full border border-slate-200 bg-white/80 px-3 py-1.5 text-xs font-semibold text-slate-600">
                                            Flowchart
                                        </a>
                                    @endif
                                    @if ($kontrakFiles->isNotEmpty())
                                        <a href="{{ route('public.information-upload.preview', $kontrakFiles->first()) }}" target="_blank" rel="noopener" class="rounded-full border border-slate-200 bg-white/80 px-3 py-1.5 text-xs font-semibold text-slate-600">
                                            Kontrak PKM
                                        </a>
                                    @endif

                                    <a href="{{ route('display.bengkel') }}" target="_blank" rel="noopener" class="inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50/90 px-3 py-1.5 text-xs font-semibold text-emerald-700 shadow-sm shadow-emerald-100/60 transition hover:bg-emerald-100">
                                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="4" width="18" height="12" rx="2"></rect>
                                            <path d="M8 20h8"></path>
                                            <path d="M12 16v4"></path>
                                            <path d="m10 10 2 2 3-4"></path>
                                        </svg>
                                        <span>Buka Display</span>
                                    </a>
                                </div>
                            @endif

                            <div class="auth-card auth-reveal auth-delay-1 rounded-[1.75rem] border border-white/80 p-6 sm:p-8">
                                {{ $slot }}
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
        @fluxScripts
    </body>
</html>
