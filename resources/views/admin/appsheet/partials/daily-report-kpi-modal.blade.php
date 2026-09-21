<template x-teleport="body">
    <div
        x-cloak
        x-show="kpiOpen"
        x-transition.opacity
        class="fixed inset-0 z-[160] flex items-center justify-center bg-slate-950/65 p-4 backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        aria-labelledby="daily-report-kpi-title"
        x-on:click.self="kpiOpen = false"
    >
        <div
            x-show="kpiOpen"
            x-transition:enter="transition duration-200 ease-out"
            x-transition:enter-start="scale-95 opacity-0"
            x-transition:enter-end="scale-100 opacity-100"
            x-transition:leave="transition duration-150 ease-in"
            x-transition:leave-start="scale-100 opacity-100"
            x-transition:leave-end="scale-95 opacity-0"
            class="flex max-h-[90vh] w-full max-w-4xl flex-col overflow-hidden rounded-2xl border border-white/15 bg-white shadow-2xl"
        >
            <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-6">
                <div class="min-w-0">
                    <p class="inline-flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-[0.14em] text-blue-600">
                        <i data-lucide="chart-no-axes-column-increasing" class="h-3.5 w-3.5" aria-hidden="true"></i>
                        KPI Laporan Harian
                    </p>
                    <h2 id="daily-report-kpi-title" class="mt-1 text-lg font-bold text-slate-900">Peringkat Kontributor</h2>
                    <p class="mt-1 text-xs leading-relaxed text-slate-500">Dihitung dari seluruh hasil filter aktif sebelum pagination.</p>
                </div>
                <button
                    type="button"
                    class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    aria-label="Tutup KPI laporan harian"
                    x-on:click="kpiOpen = false"
                >
                    <i data-lucide="x" class="h-5 w-5" aria-hidden="true"></i>
                </button>
            </div>

            @php
                $kpiGroups = [
                    [
                        'title' => 'Top 3 Pembuat Laporan',
                        'description' => 'Berdasarkan kolom INPUT BY',
                        'items' => $reporterKpi['items'],
                        'record_count' => $reporterKpi['report_count'],
                        'record_label' => 'laporan dengan penginput',
                        'contributor_count' => $reporterKpi['contributor_count'],
                        'contributor_label' => 'pembuat laporan',
                        'aria_action' => 'membuat',
                    ],
                    [
                        'title' => 'Top 3 PIC',
                        'description' => 'Berdasarkan kolom PIC',
                        'items' => $picKpi['items'],
                        'record_count' => $picKpi['assignment_count'],
                        'record_label' => 'penugasan PIC',
                        'contributor_count' => $picKpi['contributor_count'],
                        'contributor_label' => 'PIC',
                        'aria_action' => 'menangani',
                    ],
                ];
            @endphp

            <div class="min-h-0 overflow-y-auto p-5 sm:p-6">
                <div class="grid gap-5 lg:grid-cols-2">
                    @foreach ($kpiGroups as $group)
                        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="flex items-start justify-between gap-3 border-b border-slate-100 pb-3">
                                <div>
                                    <h3 class="text-sm font-bold text-slate-900">{{ $group['title'] }}</h3>
                                    <p class="mt-0.5 text-[10px] text-slate-500">{{ $group['description'] }}</p>
                                </div>
                                <span class="shrink-0 rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-bold tabular-nums text-blue-700">
                                    {{ number_format($group['record_count']) }} {{ $group['record_label'] }}
                                </span>
                            </div>

                            @if ($group['items'] !== [])
                                @php
                                    $maximumCount = max(array_column($group['items'], 'count'));
                                @endphp
                                <div class="mt-4 space-y-3">
                                    @foreach ($group['items'] as $index => $person)
                                        @php
                                            $filledSegments = max(1, (int) ceil(($person['count'] / $maximumCount) * 12));
                                        @endphp
                                        <article class="rounded-xl bg-slate-50/80 p-3 ring-1 ring-slate-100">
                                            <div class="flex items-center gap-2.5">
                                                <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-[10px] font-bold text-white">
                                                    {{ $index + 1 }}
                                                </span>
                                                <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-[10px] font-black text-blue-700 ring-1 ring-blue-200">
                                                    {{ $person['initials'] ?: '?' }}
                                                </span>
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-center justify-between gap-2">
                                                        <p class="truncate text-xs font-bold text-slate-900">{{ $person['name'] }}</p>
                                                        <p class="shrink-0 text-xs font-bold tabular-nums text-blue-700">
                                                            {{ number_format($person['count']) }} <span class="text-[9px] font-semibold text-slate-500">laporan</span>
                                                        </p>
                                                    </div>
                                                    <div
                                                        class="mt-2 grid grid-cols-12 gap-1"
                                                        role="img"
                                                        aria-label="{{ $person['name'] }} {{ $group['aria_action'] }} {{ $person['count'] }} laporan"
                                                    >
                                                        @for ($segment = 1; $segment <= 12; $segment++)
                                                            <span class="h-1.5 rounded-full {{ $segment <= $filledSegments ? 'bg-blue-600' : 'bg-slate-200' }}"></span>
                                                        @endfor
                                                    </div>
                                                </div>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                                <p class="mt-3 text-right text-[10px] text-slate-500">
                                    {{ number_format($group['contributor_count']) }} {{ $group['contributor_label'] }} tercatat
                                </p>
                            @else
                                <div class="py-8 text-center">
                                    <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                                        <i data-lucide="chart-no-axes-column-increasing" class="h-5 w-5" aria-hidden="true"></i>
                                    </span>
                                    <p class="mt-3 text-xs font-semibold text-slate-700">Data belum tersedia</p>
                                    <p class="mt-1 text-[10px] text-slate-500">Belum ada data pada hasil filter saat ini.</p>
                                </div>
                            @endif
                        </section>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</template>
