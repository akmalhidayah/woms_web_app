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
            class="w-full max-w-2xl overflow-hidden rounded-2xl border border-white/15 bg-white shadow-2xl"
        >
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-6">
                <div class="min-w-0">
                    <p class="inline-flex items-center gap-1.5 text-[10px] font-bold uppercase tracking-[0.14em] text-blue-600">
                        <i data-lucide="chart-no-axes-column-increasing" class="h-3.5 w-3.5" aria-hidden="true"></i>
                        KPI Laporan Harian
                    </p>
                    <h2 id="daily-report-kpi-title" class="mt-1 text-lg font-bold text-slate-900">Top 3 Pembuat Laporan</h2>
                    <p class="mt-1 text-xs leading-relaxed text-slate-500">
                        Berdasarkan {{ number_format($reporterKpi['report_count']) }} laporan dengan penginput tercatat dari seluruh hasil filter aktif.
                    </p>
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

            <div class="p-5 sm:p-6">
                @if ($reporterKpi['items'] !== [])
                    @php
                        $maximumReportCount = max(array_column($reporterKpi['items'], 'count'));
                    @endphp
                    <div class="space-y-4">
                        @foreach ($reporterKpi['items'] as $index => $reporter)
                            @php
                                $filledSegments = max(1, (int) ceil(($reporter['count'] / $maximumReportCount) * 12));
                            @endphp
                            <article class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-xs font-bold text-white shadow-sm">
                                        {{ $index + 1 }}
                                    </span>
                                    <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-100 text-xs font-black text-blue-700 ring-1 ring-blue-200">
                                        {{ $reporter['initials'] ?: '?' }}
                                    </span>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center justify-between gap-3">
                                            <h3 class="truncate text-sm font-bold text-slate-900">{{ $reporter['name'] }}</h3>
                                            <p class="shrink-0 text-sm font-bold tabular-nums text-blue-700">
                                                {{ number_format($reporter['count']) }} <span class="text-[10px] font-semibold text-slate-500">laporan</span>
                                            </p>
                                        </div>
                                        <div
                                            class="mt-2 grid grid-cols-12 gap-1"
                                            role="img"
                                            aria-label="{{ $reporter['name'] }} membuat {{ $reporter['count'] }} laporan"
                                        >
                                            @for ($segment = 1; $segment <= 12; $segment++)
                                                <span class="h-2 rounded-full {{ $segment <= $filledSegments ? 'bg-blue-600' : 'bg-slate-200' }}"></span>
                                            @endfor
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <div class="mt-5 flex flex-wrap items-center justify-between gap-2 border-t border-slate-100 pt-4 text-[10px] text-slate-500">
                        <span>{{ number_format($reporterKpi['contributor_count']) }} pembuat laporan tercatat</span>
                        <span>Data dihitung sebelum pagination</span>
                    </div>
                @else
                    <div class="py-8 text-center">
                        <span class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                            <i data-lucide="chart-no-axes-column-increasing" class="h-6 w-6" aria-hidden="true"></i>
                        </span>
                        <p class="mt-4 text-sm font-semibold text-slate-700">Data KPI belum tersedia</p>
                        <p class="mx-auto mt-1 max-w-sm text-xs leading-relaxed text-slate-500">
                            Belum ada laporan dengan informasi penginput pada hasil filter saat ini.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>
</template>
