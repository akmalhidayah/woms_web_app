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
            class="flex max-h-[90vh] w-full max-w-xl flex-col overflow-hidden rounded-2xl border border-white/15 bg-white shadow-2xl"
        >
            <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200 px-5 py-4">
                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-[0.14em] text-blue-600">KPI Laporan Harian</p>
                    <h2 id="daily-report-kpi-title" class="mt-1 text-lg font-bold text-slate-900">Peringkat Kontributor</h2>
                </div>
                <button
                    type="button"
                    class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    aria-label="Tutup peringkat kontributor"
                    x-on:click="kpiOpen = false"
                >
                    <i data-lucide="x" class="h-5 w-5" aria-hidden="true"></i>
                </button>
            </div>

            <div class="shrink-0 border-b border-slate-100 px-5 py-3">
                <div class="grid grid-cols-2 gap-2 rounded-xl bg-slate-100 p-1" role="tablist" aria-label="Jenis peringkat laporan harian">
                    <button
                        type="button"
                        role="tab"
                        x-bind:aria-selected="kpiTab === 'reporter'"
                        x-on:click="kpiTab = 'reporter'"
                        x-bind:class="kpiTab === 'reporter' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                        class="rounded-lg px-3 py-2 text-xs font-semibold transition"
                    >
                        Pembuat Laporan
                    </button>
                    <button
                        type="button"
                        role="tab"
                        x-bind:aria-selected="kpiTab === 'pic'"
                        x-on:click="kpiTab = 'pic'"
                        x-bind:class="kpiTab === 'pic' ? 'bg-white text-blue-700 shadow-sm' : 'text-slate-500 hover:text-slate-700'"
                        class="rounded-lg px-3 py-2 text-xs font-semibold transition"
                    >
                        PIC
                    </button>
                </div>
            </div>

            @php
                $kpiRankings = [
                    [
                        'key' => 'reporter',
                        'title' => 'Top 3 Pembuat Laporan',
                        'description' => 'Berdasarkan INPUT BY dari hasil filter aktif',
                        'items' => $reporterKpi['items'],
                        'total' => $reporterKpi['report_count'],
                        'total_label' => 'laporan',
                    ],
                    [
                        'key' => 'pic',
                        'title' => 'Top 3 PIC',
                        'description' => 'Berdasarkan PIC dari hasil filter aktif',
                        'items' => $picKpi['items'],
                        'total' => $picKpi['assignment_count'],
                        'total_label' => 'penugasan',
                    ],
                ];
                $rankStyles = [
                    0 => [
                        'row' => 'border-amber-200 bg-amber-50/70',
                        'rank' => 'bg-amber-400 text-amber-950',
                        'avatar' => 'ring-amber-300',
                    ],
                    1 => [
                        'row' => 'border-slate-200 bg-slate-50',
                        'rank' => 'bg-slate-300 text-slate-700',
                        'avatar' => 'ring-slate-300',
                    ],
                    2 => [
                        'row' => 'border-orange-200 bg-orange-50/60',
                        'rank' => 'bg-orange-300 text-orange-950',
                        'avatar' => 'ring-orange-300',
                    ],
                ];
            @endphp

            <div class="min-h-0 overflow-y-auto p-5">
                @foreach ($kpiRankings as $ranking)
                    <section x-cloak x-show="kpiTab === @js($ranking['key'])" role="tabpanel">
                        <div class="mb-4 flex items-end justify-between gap-3">
                            <div>
                                <h3 class="text-sm font-bold text-slate-900">{{ $ranking['title'] }}</h3>
                                <p class="mt-1 text-[10px] text-slate-500">{{ $ranking['description'] }}</p>
                            </div>
                            <p class="shrink-0 text-[10px] font-semibold text-slate-500">
                                {{ number_format($ranking['total']) }} {{ $ranking['total_label'] }}
                            </p>
                        </div>

                        @if ($ranking['items'] !== [])
                            <ol class="space-y-3">
                                @foreach ($ranking['items'] as $index => $person)
                                    @php
                                        $style = $rankStyles[$index];
                                    @endphp
                                    <li class="flex items-center gap-3 rounded-2xl border p-3 {{ $style['row'] }}">
                                        <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-sm font-black {{ $style['rank'] }}" aria-label="Peringkat {{ $index + 1 }}">
                                            {{ $index + 1 }}
                                        </span>
                                        <span class="relative inline-flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-full bg-white text-xs font-black text-blue-700 ring-2 {{ $style['avatar'] }}">
                                            {{ $person['display_initials'] ?: '?' }}
                                            @if ($person['avatar_url'])
                                                <img
                                                    src="{{ $person['avatar_url'] }}"
                                                    alt="Avatar {{ $person['display_name'] }}"
                                                    loading="lazy"
                                                    class="absolute inset-0 h-full w-full bg-white object-contain object-center p-0.5"
                                                    onerror="this.remove()"
                                                >
                                            @endif
                                        </span>
                                        <div class="min-w-0 flex-1">
                                            <div class="flex items-center gap-2">
                                                <span class="text-[10px] font-bold text-slate-400">#{{ $index + 1 }}</span>
                                                <p class="truncate text-sm font-bold text-slate-900">{{ $person['display_name'] }}</p>
                                            </div>
                                            <p class="mt-0.5 text-[10px] text-slate-500">
                                                {{ $ranking['key'] === 'reporter' ? 'Pembuat laporan' : 'PIC pekerjaan' }}
                                            </p>
                                        </div>
                                        <div class="shrink-0 text-right">
                                            <p class="text-lg font-black tabular-nums text-blue-700">{{ number_format($person['count']) }}</p>
                                            <p class="text-[9px] font-semibold uppercase tracking-wide text-slate-400">laporan</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        @else
                            <div class="rounded-2xl border border-dashed border-slate-200 py-10 text-center">
                                <span class="mx-auto inline-flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                    <i data-lucide="trophy" class="h-5 w-5" aria-hidden="true"></i>
                                </span>
                                <p class="mt-3 text-xs font-semibold text-slate-700">Belum ada peringkat</p>
                                <p class="mt-1 text-[10px] text-slate-500">Data belum tersedia pada hasil filter saat ini.</p>
                            </div>
                        @endif
                    </section>
                @endforeach
            </div>
        </div>
    </div>
</template>
