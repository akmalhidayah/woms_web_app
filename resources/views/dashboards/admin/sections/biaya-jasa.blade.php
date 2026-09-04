    @php
        $cleanNumber = function ($x) {
            if ($x === null || $x === '') {
                return 0;
            }

            if (is_int($x) || (is_string($x) && ctype_digit($x))) {
                return (int) $x;
            }

            if (is_numeric($x)) {
                return (int) round((float) $x);
            }

            $trim = trim((string) $x);
            if (str_starts_with($trim, '[') && str_ends_with($trim, ']')) {
                return 0;
            }

            $onlyDigits = preg_replace('/[^\d\-]/', '', (string) $x);
            return ($onlyDigits === '') ? 0 : (int) $onlyDigits;
        };

        $fmt = function ($v) use ($cleanNumber) {
            if (is_array($v)) {
                $sum = 0;
                foreach ($v as $item) {
                    $sum += $cleanNumber($item);
                }
                $v = $sum;
            } else {
                $v = $cleanNumber($v);
            }

            return number_format((int) $v, 0, ',', '.');
        };

        $rp = fn ($v) => 'Rp. ' . $fmt($v);

        $maintenanceTargetYear = $maintenanceTargetYear ?? now()->year;
        $maintenanceAnnualTarget = $maintenanceAnnualTarget ?? 0;
        $maintenanceRealization = $maintenanceRealization ?? 0;
        $maintenanceOutstanding = $maintenanceOutstanding ?? 0;
        $maintenancePrognosis = $maintenancePrognosis ?? 0;
        $maintenanceRemainingTarget = $maintenanceRemainingTarget ?? 0;
        $maintenanceTargetUsagePercentageHundredths = $maintenanceTargetUsagePercentageHundredths ?? 0;
        $maintenanceTargetUsagePercentageLabel = $maintenanceTargetUsagePercentageLabel ?? '0';
        $maintenanceTargetUsageProgressWidth = $maintenanceTargetUsageProgressWidth ?? '0';
        $maintenanceLpjStatusAmount = $maintenanceLpjStatusAmount ?? 0;
        $maintenanceInvoiceStatusAmount = $maintenanceInvoiceStatusAmount ?? 0;
        $emptyCategorySummary = [
            'realization' => 0,
            'outstanding' => 0,
            'prognosis' => 0,
            'outstanding_stages' => [
                'hpp' => 0,
                'purchase_order' => 0,
                'lpj_process' => 0,
            ],
        ];
        $nonMaintenanceSummary = array_replace_recursive($emptyCategorySummary, $nonMaintenanceSummary ?? []);
        $capexSummary = array_replace_recursive($emptyCategorySummary, $capexSummary ?? []);
        $totalRealisasiBiaya = $totalRealisasiBiaya ?? 0;
        $totalPaguKontrak = $totalPaguKontrak ?? 0;
        $totalOutstandingBiaya = $totalOutstandingBiaya ?? 0;
        $totalPrognosaBiaya = $totalPrognosaBiaya ?? 0;
        $totalAnggaranTersedia = $totalAnggaranTersedia ?? 0;
        $latestKuotaAnggaran = $latestKuotaAnggaran ?? null;
        $periodeKontrak = $periodeKontrak ?? ['start' => null, 'end' => null, 'adendum' => null];

        $contractPeriodLabel = collect([
            $periodeKontrak['start']
                ? strtoupper(\Carbon\Carbon::parse($periodeKontrak['start'])->locale('id')->translatedFormat('M Y'))
                : null,
            $periodeKontrak['end']
                ? strtoupper(\Carbon\Carbon::parse($periodeKontrak['end'])->locale('id')->translatedFormat('M Y'))
                : null,
        ])->filter()->join(' - ');

        $remainingBudgetIsNegative = $totalAnggaranTersedia < 0;
        $remainingBudgetClasses = $remainingBudgetIsNegative
            ? 'border-rose-200 bg-rose-50 text-rose-700'
            : 'border-amber-200 bg-amber-50 text-amber-900';
    @endphp

        <div id="dashboardJasaContent" class="space-y-3">
        <section class="general-cost-section rounded-xl bg-blue-100 px-3 py-2.5">
            <div class="mb-3 flex items-center gap-2 border-b border-slate-300 pb-2.5">
                <i data-lucide="wallet-cards" class="h-4 w-4 text-blue-600"></i>
                <h2 class="text-[12px] font-bold tracking-[0.08em] text-slate-800">GENERAL BIAYA JASA</h2>
            </div>

            <div class="grid overflow-hidden sm:grid-cols-2 xl:grid-cols-4">
                <article class="min-w-0 border-b border-white/30 bg-blue-700 px-3 py-2.5 text-white sm:border-r xl:border-b-0">
                    <div class="border-l-4 border-blue-300 pl-2 text-[9px] font-bold uppercase tracking-[0.12em] text-blue-100">Total Prognosa Biaya</div>
                    <div class="mt-2 break-words text-base font-bold text-white">{{ $rp($totalPrognosaBiaya) }}</div>
                    <div class="mt-1 text-[9px] font-semibold text-blue-100">{{ $prognosaPercentageLabel ?? '0' }}% dari pagu</div>
                </article>
                <article class="min-w-0 border-b border-white/30 bg-emerald-700 px-3 py-2.5 text-white xl:border-b-0 xl:border-r">
                    <div class="border-l-4 border-emerald-300 pl-2 text-[9px] font-bold uppercase tracking-[0.12em] text-emerald-100">Realisasi Biaya</div>
                    <div class="mt-2 break-words text-base font-bold text-white">{{ $rp($totalRealisasiBiaya) }}</div>
                    <div class="mt-1 text-[9px] font-semibold text-emerald-100">{{ $realisasiPercentageLabel ?? '0' }}% dari pagu</div>
                </article>
                <article class="min-w-0 border-b border-white/30 bg-indigo-700 px-3 py-2.5 text-white sm:border-b-0 sm:border-r">
                    <div class="border-l-4 border-indigo-300 pl-2 text-[9px] font-bold uppercase tracking-[0.12em] text-indigo-100">Outstanding Biaya</div>
                    <div class="mt-2 break-words text-base font-bold text-white">{{ $rp($totalOutstandingBiaya) }}</div>
                    <div class="mt-1 text-[9px] font-semibold text-indigo-100">{{ $outstandingPercentageLabel ?? '0' }}% dari pagu</div>
                </article>
                <article class="min-w-0 bg-amber-500 px-3 py-2.5 text-slate-950">
                    <div class="border-l-4 border-amber-900 pl-2 text-[9px] font-bold uppercase tracking-[0.12em] text-amber-950">Anggaran Tersedia</div>
                    <div class="mt-2 break-words text-base font-bold text-slate-950">{{ $rp($totalAnggaranTersedia) }}</div>
                    <div class="mt-1 text-[9px] font-semibold text-amber-950">{{ $anggaranTersediaPercentageLabel ?? '0' }}% dari pagu</div>
                </article>
            </div>
        </section>

        <section class="main-cost-grid grid grid-cols-1 items-stretch gap-3 xl:grid-cols-12">
            <div class="grid min-w-0 gap-3 xl:col-span-8">
                <article class="maintenance-panel flex min-w-0 flex-col rounded-xl bg-emerald-100 p-3">
                <div class="flex items-start gap-2 border-b border-slate-100 pb-2.5">
                    <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                        <i data-lucide="wrench" class="h-3.5 w-3.5"></i>
                    </span>
                    <h2 class="pt-1 text-xs font-bold leading-4 tracking-[0.06em] text-slate-800 sm:text-sm">
                        PROGNOSA DAN REALISASI BIAYA JASA OPEX PEMELIHARAAN
                    </h2>
                </div>

                <div class="mt-3 grid gap-3 md:grid-cols-12">
                    <section class="flex flex-col px-3 py-2 md:col-span-4">
                        <div class="text-center">
                            <div class="text-[9px] font-bold uppercase tracking-[0.16em] text-slate-500 sm:text-[10px]">Target Tahunan {{ $maintenanceTargetYear }}</div>
                            <div class="mt-1.5 break-words text-sm font-bold text-slate-900 sm:text-base">{{ $rp($maintenanceAnnualTarget) }}</div>
                        </div>

                        <div class="dashboard-chart-placeholder mx-auto my-3 aspect-square w-full max-w-[128px] rounded-full p-[9px] shadow-inner" style="background: conic-gradient(#10b981 0 {{ $maintenanceTargetUsageProgressWidth }}%, #e2e8f0 {{ $maintenanceTargetUsageProgressWidth }}% 100%);">
                            <div class="flex h-full w-full items-center justify-center rounded-full bg-white shadow-sm">
                                <div class="px-2 text-center">
                                    <div class="text-[8px] font-semibold uppercase tracking-[0.12em] text-slate-400">Pemakaian</div>
                                    <div class="mt-0.5 text-lg font-extrabold sm:text-xl {{ $maintenanceTargetUsagePercentageHundredths > 10000 ? 'text-rose-600' : 'text-emerald-700' }}">
                                        {{ $maintenanceTargetUsagePercentageLabel }}%
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-auto border-t border-slate-200 pt-2.5 text-center">
                            <div class="text-[8px] font-semibold uppercase tracking-[0.14em] text-slate-400 sm:text-[9px]">Sisa Target</div>
                            <div class="mt-1 break-words text-xs font-bold sm:text-sm {{ $maintenanceRemainingTarget < 0 ? 'text-rose-700' : 'text-slate-800' }}">
                                {{ $rp($maintenanceRemainingTarget) }}
                            </div>
                        </div>
                    </section>

                    <section class="px-3 py-2 md:col-span-8 md:border-l md:border-slate-200">
                        <h3 class="text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500 sm:text-[11px]">Ringkasan Biaya</h3>
                        <dl class="mt-2 divide-y divide-slate-100">
                            <div class="flex items-center justify-between gap-3 py-4">
                                <dt class="text-[11px] font-medium text-slate-600 sm:text-xs">Outstanding Biaya</dt>
                                <dd class="shrink-0 text-xs font-bold text-slate-900 sm:text-sm">{{ $rp($maintenanceOutstanding) }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 py-4">
                                <dt class="text-[11px] font-medium text-slate-600 sm:text-xs">Realisasi Biaya</dt>
                                <dd class="shrink-0 text-xs font-bold text-slate-900 sm:text-sm">{{ $rp($maintenanceRealization) }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 py-4">
                                <dt class="text-[11px] font-medium text-slate-600 sm:text-xs">Total Prognosa Biaya</dt>
                                <dd class="shrink-0 text-xs font-bold text-slate-900 sm:text-sm">{{ $rp($maintenancePrognosis) }}</dd>
                            </div>
                        </dl>
                    </section>
                </div>

                <section class="mt-3 border-t border-slate-200 px-3 pt-3">
                    <h3 class="text-[10px] font-bold uppercase tracking-[0.12em] text-slate-600 sm:text-[11px]">Monitoring Realisasi Anggaran Pemeliharaan</h3>
                    <div class="mt-3 grid overflow-hidden rounded-lg sm:grid-cols-2">
                        <div class="bg-emerald-700 px-4 py-3.5 text-white">
                            <div class="text-[10px] font-semibold uppercase tracking-[0.1em] text-emerald-100 sm:text-[11px]">Biaya Status LPJ</div>
                            <div class="mt-2 text-base font-bold text-white sm:text-lg">{{ $rp($maintenanceLpjStatusAmount) }}</div>
                        </div>
                        <div class="border-t border-white/30 bg-blue-700 px-4 py-3.5 text-white sm:border-l sm:border-t-0">
                            <div class="text-[10px] font-semibold uppercase tracking-[0.1em] text-blue-100 sm:text-[11px]">Biaya Status Invoice</div>
                            <div class="mt-2 text-base font-bold text-white sm:text-lg">{{ $rp($maintenanceInvoiceStatusAmount) }}</div>
                        </div>
                    </div>
                </section>
                </article>

                <article class="monthly-realization-panel flex min-w-0 min-h-[330px] flex-col overflow-hidden rounded-xl bg-blue-100 p-3">
                    <div class="flex flex-col gap-3 border-b border-slate-100 pb-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <h3 class="text-[9px] font-bold uppercase tracking-[0.12em] text-slate-600">Realisasi Per Bulan</h3>
                            <div id="monthlyRealizationTotal" class="text-[10px] font-bold text-slate-700">{{ $rp(collect($realizationChartData ?? [])->sum('general')) }}</div>
                        </div>

                        <div class="grid gap-2 sm:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_auto]">
                            <label class="min-w-0">
                                <span class="mb-1 block text-[8px] font-semibold uppercase tracking-[0.08em] text-slate-500">Dari Bulan</span>
                                <select id="monthlyStartMonth" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-[10px] text-slate-700"></select>
                            </label>
                            <label class="min-w-0">
                                <span class="mb-1 block text-[8px] font-semibold uppercase tracking-[0.08em] text-slate-500">Sampai Bulan</span>
                                <select id="monthlyEndMonth" class="w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-[10px] text-slate-700"></select>
                            </label>
                            <button id="applyMonthlyRealizationFilter" type="button" class="self-end rounded-lg bg-blue-600 px-3 py-2 text-[9px] font-bold text-white transition hover:bg-blue-700">
                                Terapkan
                            </button>
                        </div>

                    </div>

                    <div id="monthlyRealizationChartContainer" class="relative mt-3 h-[230px] min-w-0 w-full flex-none overflow-hidden">
                        <canvas id="monthlyRealizationChart" class="h-full w-full max-w-full" role="img" aria-label="Grafik garis Realisasi Per Bulan"></canvas>
                    </div>
                    <div id="monthlyRealizationEmptyState" class="mt-3 hidden min-h-[230px] items-center justify-center px-4 text-center text-[10px] text-slate-500">
                        Tidak ada data realisasi pada periode ini.
                    </div>
                </article>
            </div>

            <div class="right-cost-column grid min-w-0 gap-3 xl:col-span-4">
                @foreach ([
                    ['title' => 'PROGNOSA DAN REALISASI BIAYA JASA OPEX NON PEMELIHARAAN', 'icon' => 'building-2', 'accent' => 'bg-violet-600 text-white', 'background' => 'bg-violet-100', 'summary' => $nonMaintenanceSummary, 'canvas' => 'nonMaintenanceOutstandingChart', 'color' => '#7c3aed'],
                    ['title' => 'PROGNOSA DAN REALISASI BIAYA CAPEX', 'icon' => 'landmark', 'accent' => 'bg-cyan-600 text-white', 'background' => 'bg-cyan-100', 'summary' => $capexSummary, 'canvas' => 'capexOutstandingChart', 'color' => '#0891b2'],
                ] as $costPanel)
                    <article class="flex min-w-0 flex-col rounded-xl p-3 {{ $costPanel['background'] }}">
                        <div class="flex items-start gap-2 border-b border-slate-300 pb-2.5">
                            <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $costPanel['accent'] }}">
                                <i data-lucide="{{ $costPanel['icon'] }}" class="h-3.5 w-3.5"></i>
                            </span>
                            <h2 class="pt-1 text-xs font-bold leading-4 tracking-[0.06em] text-slate-800">
                                {{ $costPanel['title'] }}
                            </h2>
                        </div>

                        <div class="mt-3 grid sm:grid-cols-2">
                            <div class="pb-3 sm:pr-3">
                                <div class="text-[10px] font-semibold uppercase tracking-[0.1em] text-slate-500">Total Prognosa Biaya</div>
                                <div class="mt-2 break-words text-base font-bold text-slate-900">{{ $rp($costPanel['summary']['prognosis']) }}</div>
                            </div>
                            <div class="border-t border-slate-300 pt-3 sm:border-l sm:border-t-0 sm:pl-3 sm:pt-0">
                                <div class="text-[10px] font-semibold uppercase tracking-[0.1em] text-slate-500">Total Realisasi Biaya</div>
                                <div class="mt-2 break-words text-base font-bold text-slate-900">{{ $rp($costPanel['summary']['realization']) }}</div>
                            </div>
                        </div>

                        <section class="mt-3 flex-1 border-t border-slate-300 px-1 pt-3">
                            <h3 class="text-[10px] font-bold uppercase tracking-[0.12em] text-slate-600">Outstanding Biaya</h3>
                            <div class="relative mt-3 h-[190px] min-w-0 w-full flex-none overflow-hidden">
                                <canvas
                                    id="{{ $costPanel['canvas'] }}"
                                    class="h-full w-full"
                                    role="img"
                                    aria-label="Grafik outstanding {{ $costPanel['title'] }}"
                                    data-stages='@json($costPanel['summary']['outstanding_stages'])'
                                    data-color="{{ $costPanel['color'] }}"
                                ></canvas>
                            </div>
                        </section>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="bottom-cost-grid grid grid-cols-1 items-stretch gap-3 xl:grid-cols-12">
            <article class="top-ten-panel flex min-w-0 flex-col rounded-xl bg-blue-100 p-3 xl:col-span-8">
                <div class="mb-2 flex items-center gap-2 border-b border-slate-300 pb-2.5">
                    <i data-lucide="bar-chart-3" class="h-4 w-4 text-blue-500"></i>
                    <h2 class="text-[11px] font-bold tracking-[0.08em] text-slate-800">TOP TEN PEMICU BIAYA</h2>
                </div>

                <div class="grid min-w-0 flex-1 grid-cols-1 xl:grid-cols-2">
                    <section class="flex min-w-0 flex-col p-3 xl:pr-4">
                        <div class="flex items-center gap-1.5 text-[9px] font-bold tracking-[0.08em] text-slate-700">
                            <span class="h-2.5 w-2.5 rounded-sm bg-blue-600"></span>
                            GENERAL
                        </div>
                        <div id="topTenGeneralCostChartContainer" class="relative mt-2 h-[320px] min-w-0 w-full flex-none overflow-hidden">
                            <canvas
                                id="topTenGeneralCostChart"
                                class="h-full w-full"
                                role="img"
                                aria-label="Grafik Top Ten Pemicu Biaya General"
                            ></canvas>
                        </div>
                        <div id="topTenGeneralCostEmptyState" class="hidden flex-1 items-center justify-center px-3 py-8 text-center text-xs text-slate-500">
                            Belum ada data HPP pada periode ini.
                        </div>
                    </section>

                    <section class="flex min-w-0 flex-col border-t border-slate-300 p-3 xl:border-l xl:border-t-0 xl:pl-4">
                        <div class="flex items-center gap-1.5 text-[9px] font-bold tracking-[0.08em] text-slate-700">
                            <span class="h-2.5 w-2.5 rounded-sm bg-emerald-500"></span>
                            PEMELIHARAAN
                        </div>
                        <div id="topTenMaintenanceCostChartContainer" class="relative mt-2 h-[320px] min-w-0 w-full flex-none overflow-hidden">
                            <canvas
                                id="topTenMaintenanceCostChart"
                                class="h-full w-full"
                                role="img"
                                aria-label="Grafik Top Ten Pemicu Biaya Pemeliharaan"
                            ></canvas>
                        </div>
                        <div id="topTenMaintenanceCostEmptyState" class="hidden flex-1 items-center justify-center px-3 py-8 text-center text-xs text-slate-500">
                            Belum ada data HPP kategori Pemeliharaan pada periode ini.
                        </div>
                    </section>
                </div>
            </article>

            <article class="overhaul-panel flex min-w-0 flex-col rounded-xl bg-amber-100 p-3 xl:col-span-4">
                <div class="mb-2 flex items-center gap-2 border-b border-slate-300 pb-2.5">
                    <i data-lucide="trending-up" class="h-4 w-4 text-amber-500"></i>
                    <h2 class="text-[11px] font-bold tracking-[0.08em] text-slate-800">PROGNOSA OVERHAUL</h2>
                </div>

                <div class="relative h-[220px] min-w-0 w-full flex-none overflow-hidden">
                    <canvas
                        id="overhaulPrognosisChart"
                        class="h-full w-full"
                        role="img"
                        aria-label="Grafik Prognosa Biaya Overhaul"
                    ></canvas>
                </div>
            </article>
        </section>
        </div>
