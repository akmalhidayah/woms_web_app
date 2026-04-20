@php
    $allTasks = collect($tasks ?? []);

    $fabrikasiTasks = $allTasks
        ->filter(fn ($row) => (($row['catatan'] ?? null) === 'Regu Fabrikasi') || empty($row['catatan']))
        ->values();

    $refurbishTasks = $allTasks
        ->filter(fn ($row) => ($row['catatan'] ?? null) === 'Regu Bengkel (Refurbish)')
        ->values();

    $fabrikasiPage = $fabrikasiTasks->slice($pageSlide * $perPageFabrikasi, $perPageFabrikasi)->values();
    $refurbishPage = $refurbishTasks->slice($pageSlide * $perPageRefurbish, $perPageRefurbish)->values();

    $initials = function (?string $name): string {
        $parts = preg_split('/\s+/', trim((string) $name)) ?: [];
        $parts = array_slice(array_values(array_filter($parts)), 0, 2);
        $result = '';
        foreach ($parts as $part) {
            $result .= mb_strtoupper(mb_substr($part, 0, 1));
        }
        return $result !== '' ? $result : '?';
    };

@endphp

@if (($mode ?? 'admin') === 'display')
    <div wire:poll.10s="refreshBoard" class="h-screen overflow-hidden">
        <div class="mx-auto flex h-full max-w-[1920px] flex-col px-4 py-4">
            <div class="mb-3 grid grid-cols-[auto_1fr_auto] items-center gap-4 rounded-[1.5rem] border border-slate-300 bg-white px-6 py-4 shadow-sm">
                <div class="flex items-center gap-4">
                    <img src="{{ asset('assets/branding/logos/logo-sig.png') }}" alt="SIG" class="h-16 w-auto object-contain">
                    <img src="{{ asset('assets/branding/logos/logo-st2.png') }}" alt="ST" class="h-16 w-auto object-contain">
                </div>

                <div class="text-center">
                    <h1 class="mt-2 text-[2.2rem] font-black tracking-tight text-slate-900">Dashboard Pekerjaan Bengkel</h1>
                    <div id="dateDisplay" class="mt-2 text-[1rem] font-semibold text-slate-600"></div>
                </div>

                <div class="text-right">
                    <div class="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-500">Jam</div>
                    <div id="timeDisplay" class="mt-2 text-[2rem] font-black tracking-tight text-slate-900"></div>
                    <div class="mt-2 text-[11px] font-semibold text-slate-500">Slide {{ $pageSlide + 1 }} / {{ $maxPages }}</div>
                </div>
            </div>

            <div class="ticker mb-3 rounded-2xl shadow-sm">
                <span>
                    Monitoring pekerjaan bengkel aktif • Regu Fabrikasi: {{ $fabrikasiTasks->count() }} item • Regu Bengkel (Refurbish): {{ $refurbishTasks->count() }} item • Monitoring pekerjaan bengkel aktif • Regu Fabrikasi: {{ $fabrikasiTasks->count() }} item • Regu Bengkel (Refurbish): {{ $refurbishTasks->count() }} item •
                </span>
            </div>

            <div class="grid min-h-0 flex-1 gap-4 xl:grid-cols-[1.45fr_1fr]">
                <section class="flex min-h-0 flex-col rounded-[1.4rem] border border-blue-200 bg-[linear-gradient(135deg,_#eff6ff_0%,_#ffffff_60%,_#dbeafe_100%)] p-4 shadow-sm">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div class="text-[1.05rem] font-black text-blue-900">Regu Fabrikasi</div>
                        <span class="rounded-full bg-blue-100 px-3 py-1 text-[11px] font-semibold text-blue-700">{{ $fabrikasiTasks->count() }} item</span>
                    </div>

                    <div class="grid min-h-0 flex-1 content-start gap-3 xl:grid-cols-2">
                        @forelse ($fabrikasiPage as $task)
                            <article class="flex h-fit min-h-[138px] flex-col rounded-[1.1rem] border border-blue-100 bg-white p-3 shadow-sm">
                                <div class="text-[0.88rem] font-black leading-snug text-slate-900">{{ $task['job_name'] }}</div>
                                <div class="mt-1 text-[11px] font-semibold text-slate-500">{{ $task['notification_number'] ?: '-' }}</div>
                                <div class="mt-2 space-y-0.5 text-[11px] leading-5 text-slate-600">
                                    <div>{{ $task['unit_work'] ?: '-' }}</div>
                                    <div>{{ $task['seksi'] ?: '-' }}</div>
                                    <div>Target: {{ $task['usage_plan_date'] ?: '-' }}</div>
                                </div>

                                <div class="mt-3 border-t border-slate-100 pt-2">
                                    <div class="mb-2 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">PIC Bengkel</div>
                                    @if (count($task['person_in_charge_profiles'] ?? []) > 0)
                                        <div class="flex flex-wrap gap-2">
                                            @foreach (($task['person_in_charge_profiles'] ?? []) as $profile)
                                                @php
                                                    $name = is_array($profile) ? ($profile['name'] ?? '') : '';
                                                    $avatar = is_array($profile) ? ($profile['avatar_url'] ?? null) : null;
                                                @endphp
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-50 px-2 py-1 ring-1 ring-slate-200">
                                                    @if ($avatar)
                                                        <img src="{{ $avatar }}" alt="" class="h-6 w-6 rounded-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                                                        <span style="display:none" class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-200 text-[10px] font-bold text-slate-700">{{ $initials($name) }}</span>
                                                    @else
                                                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-200 text-[10px] font-bold text-slate-700">{{ $initials($name) }}</span>
                                                    @endif
                                                    <span class="text-[10px] font-semibold text-slate-700">{{ $name }}</span>
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="rounded-xl bg-slate-50 px-3 py-2 text-[10px] font-medium text-slate-500">
                                            PIC belum dipilih di data pekerjaan ini.
                                        </div>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="flex min-h-[220px] items-center justify-center rounded-[1.35rem] border border-dashed border-blue-200 bg-white/80 px-4 text-center text-base text-slate-500 md:col-span-2">
                                Belum ada data regu fabrikasi.
                            </div>
                        @endforelse
                    </div>
                </section>

                <section class="flex min-h-0 flex-col rounded-[1.4rem] border border-amber-200 bg-[linear-gradient(135deg,_#fff7ed_0%,_#ffffff_60%,_#fde68a_100%)] p-4 shadow-sm">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <div class="text-[1.05rem] font-black text-amber-900">Regu Bengkel (Refurbish)</div>
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-[11px] font-semibold text-amber-700">{{ $refurbishTasks->count() }} item</span>
                    </div>

                    <div class="grid min-h-0 flex-1 content-start gap-3 xl:grid-cols-2">
                        @forelse ($refurbishPage as $task)
                            <article class="flex h-fit min-h-[138px] flex-col rounded-[1.1rem] border border-amber-100 bg-white p-3 shadow-sm">
                                <div class="text-[0.88rem] font-black leading-snug text-slate-900">{{ $task['job_name'] }}</div>
                                <div class="mt-1 text-[11px] font-semibold text-slate-500">{{ $task['notification_number'] ?: '-' }}</div>
                                <div class="mt-2 space-y-0.5 text-[11px] leading-5 text-slate-600">
                                    <div>{{ $task['unit_work'] ?: '-' }}</div>
                                    <div>{{ $task['seksi'] ?: '-' }}</div>
                                    <div>Target: {{ $task['usage_plan_date'] ?: '-' }}</div>
                                </div>

                                <div class="mt-3 border-t border-slate-100 pt-2">
                                    <div class="mb-2 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-400">PIC Bengkel</div>
                                    @if (count($task['person_in_charge_profiles'] ?? []) > 0)
                                        <div class="flex flex-wrap gap-2">
                                            @foreach (($task['person_in_charge_profiles'] ?? []) as $profile)
                                                @php
                                                    $name = is_array($profile) ? ($profile['name'] ?? '') : '';
                                                    $avatar = is_array($profile) ? ($profile['avatar_url'] ?? null) : null;
                                                @endphp
                                                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-50 px-2 py-1 ring-1 ring-slate-200">
                                                    @if ($avatar)
                                                        <img src="{{ $avatar }}" alt="" class="h-6 w-6 rounded-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                                                        <span style="display:none" class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-200 text-[10px] font-bold text-slate-700">{{ $initials($name) }}</span>
                                                    @else
                                                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-200 text-[10px] font-bold text-slate-700">{{ $initials($name) }}</span>
                                                    @endif
                                                    <span class="text-[10px] font-semibold text-slate-700">{{ $name }}</span>
                                                </span>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="rounded-xl bg-slate-50 px-3 py-2 text-[10px] font-medium text-slate-500">
                                            PIC belum dipilih di data pekerjaan ini.
                                        </div>
                                    @endif
                                </div>
                            </article>
                        @empty
                            <div class="flex min-h-[220px] items-center justify-center rounded-[1.35rem] border border-dashed border-amber-200 bg-white/80 px-4 text-center text-base text-slate-500">
                                Belum ada data regu refurbish.
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </div>
@else
    <div wire:poll.10s="refreshBoard" class="rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-sm">
        <div class="mb-4 flex items-center justify-between gap-3">
            <div>
                <h2 class="text-[1.1rem] font-bold text-slate-900">Preview Display Bengkel</h2>
                <p class="text-[11px] text-slate-500">Slide {{ $pageSlide + 1 }} dari {{ $maxPages }}</p>
            </div>

            <button type="button" wire:click="nextSlide" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                <i data-lucide="chevrons-right" class="h-4 w-4"></i>
                Geser
            </button>
        </div>

        <div class="grid gap-4 xl:grid-cols-[1.4fr_1fr]">
            <section class="rounded-[1.25rem] border border-blue-200 bg-[linear-gradient(135deg,_#eff6ff_0%,_#ffffff_60%,_#dbeafe_100%)] p-4">
                <div class="mb-3 flex items-center justify-between">
                    <div class="text-sm font-bold text-blue-900">Regu Fabrikasi</div>
                    <span class="rounded-full bg-blue-100 px-2.5 py-1 text-[10px] font-semibold text-blue-700">{{ $fabrikasiTasks->count() }} item</span>
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    @forelse ($fabrikasiPage as $task)
                        <article class="rounded-[1.1rem] border border-blue-100 bg-white p-3 shadow-sm">
                            <div class="text-[13px] font-bold text-slate-900">{{ $task['job_name'] }}</div>
                            <div class="mt-1 text-[11px] text-slate-500">{{ $task['notification_number'] ?: '-' }}</div>
                            <div class="mt-3 space-y-1 text-[11px] text-slate-600">
                                <div>{{ $task['unit_work'] ?: '-' }}</div>
                                <div>{{ $task['seksi'] ?: '-' }}</div>
                                <div>Target: {{ $task['usage_plan_date'] ?: '-' }}</div>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach (($task['person_in_charge_profiles'] ?? []) as $profile)
                                    @php
                                        $name = is_array($profile) ? ($profile['name'] ?? '') : '';
                                        $avatar = is_array($profile) ? ($profile['avatar_url'] ?? null) : null;
                                    @endphp
                                    <span class="inline-flex items-center gap-2 rounded-full bg-slate-50 px-2 py-1 ring-1 ring-slate-200">
                                        @if ($avatar)
                                            <img src="{{ $avatar }}" alt="" class="h-6 w-6 rounded-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                                            <span style="display:none" class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-200 text-[10px] font-bold text-slate-700">{{ $initials($name) }}</span>
                                        @else
                                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-200 text-[10px] font-bold text-slate-700">{{ $initials($name) }}</span>
                                        @endif
                                        <span class="text-[11px] font-medium text-slate-700">{{ $name }}</span>
                                    </span>
                                @endforeach
                            </div>
                        </article>
                    @empty
                        <div class="rounded-[1.1rem] border border-dashed border-blue-200 bg-white/80 px-4 py-10 text-center text-sm text-slate-500 md:col-span-2">
                            Belum ada data regu fabrikasi.
                        </div>
                    @endforelse
                </div>
            </section>

            <section class="rounded-[1.25rem] border border-amber-200 bg-[linear-gradient(135deg,_#fff7ed_0%,_#ffffff_60%,_#fde68a_100%)] p-4">
                <div class="mb-3 flex items-center justify-between">
                    <div class="text-sm font-bold text-amber-900">Regu Bengkel (Refurbish)</div>
                    <span class="rounded-full bg-amber-100 px-2.5 py-1 text-[10px] font-semibold text-amber-700">{{ $refurbishTasks->count() }} item</span>
                </div>

                <div class="space-y-3">
                    @forelse ($refurbishPage as $task)
                        <article class="rounded-[1.1rem] border border-amber-100 bg-white p-3 shadow-sm">
                            <div class="text-[13px] font-bold text-slate-900">{{ $task['job_name'] }}</div>
                            <div class="mt-1 text-[11px] text-slate-500">{{ $task['notification_number'] ?: '-' }}</div>
                            <div class="mt-3 space-y-1 text-[11px] text-slate-600">
                                <div>{{ $task['unit_work'] ?: '-' }}</div>
                                <div>{{ $task['seksi'] ?: '-' }}</div>
                                <div>Target: {{ $task['usage_plan_date'] ?: '-' }}</div>
                            </div>

                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach (($task['person_in_charge_profiles'] ?? []) as $profile)
                                    @php
                                        $name = is_array($profile) ? ($profile['name'] ?? '') : '';
                                        $avatar = is_array($profile) ? ($profile['avatar_url'] ?? null) : null;
                                    @endphp
                                    <span class="inline-flex items-center gap-2 rounded-full bg-slate-50 px-2 py-1 ring-1 ring-slate-200">
                                        @if ($avatar)
                                            <img src="{{ $avatar }}" alt="" class="h-6 w-6 rounded-full object-cover" onerror="this.style.display='none'; this.nextElementSibling.style.display='inline-flex';">
                                            <span style="display:none" class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-200 text-[10px] font-bold text-slate-700">{{ $initials($name) }}</span>
                                        @else
                                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-200 text-[10px] font-bold text-slate-700">{{ $initials($name) }}</span>
                                        @endif
                                        <span class="text-[11px] font-medium text-slate-700">{{ $name }}</span>
                                    </span>
                                @endforeach
                            </div>
                        </article>
                    @empty
                        <div class="rounded-[1.1rem] border border-dashed border-amber-200 bg-white/80 px-4 py-10 text-center text-sm text-slate-500">
                            Belum ada data regu refurbish.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
@endif
