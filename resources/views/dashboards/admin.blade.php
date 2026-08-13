<x-layouts.admin title="Dashboard Admin">
    <style>
        html:has(.dashboard-header) {
            overflow-y: auto !important;
        }

        body:has(.dashboard-header) {
            overflow-y: visible !important;
        }

        .admin-compact {
            border: 0 !important;
            background: transparent !important;
            padding: 0 !important;
            border-radius: 0 !important;
            box-shadow: none !important;
        }
    </style>

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

    <div class="space-y-3">
        @if ($showActionSummaryBanner ?? false)
            <section
                x-data="{ visible: true }"
                x-show="visible"
                x-transition.opacity
                data-admin-action-summary-banner
                class="flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2.5 shadow-sm"
            >
                <button
                    type="button"
                    class="flex min-w-0 flex-1 items-start gap-3 text-left"
                    @click="$dispatch('admin-action-center:open')"
                    aria-label="Buka daftar pekerjaan yang perlu ditindaklanjuti"
                >
                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-amber-700">
                        <i data-lucide="circle-alert" class="h-4 w-4"></i>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-xs font-bold text-amber-950">
                            Ada {{ $adminActionSummaryCount }} pekerjaan yang perlu ditindaklanjuti.
                        </span>
                        @if (($adminActionSummary ?? []) !== [])
                            <span class="mt-1 block text-[11px] leading-4 text-amber-800">
                                {{ collect($adminActionSummary)->map(fn (array $item): string => $item['label'].': '.$item['count'])->join(' · ') }}
                            </span>
                        @endif
                        <span class="mt-1 block text-[10px] font-semibold text-amber-700">Klik untuk melihat Perlu Tindakan.</span>
                    </span>
                </button>
                <button
                    type="button"
                    class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-amber-700 transition hover:bg-amber-100"
                    @click="visible = false"
                    aria-label="Tutup ringkasan tindakan"
                >
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </section>
        @endif

        <header class="dashboard-header grid gap-3 rounded-xl border border-slate-200 bg-white p-4 lg:sticky lg:top-[52px] lg:z-10 xl:grid-cols-[minmax(240px,1fr)_minmax(0,auto)] xl:items-stretch">
            <div class="flex min-w-0 items-center gap-3">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <i data-lucide="layout-dashboard" class="h-5 w-5"></i>
                </span>
                <div class="min-w-0">
                    <h1 class="text-2xl font-bold tracking-[0.08em] text-slate-900 sm:text-3xl">DASHBOARD BIAYA JASA</h1>
                </div>
            </div>

            <div class="grid min-w-0 gap-x-3 gap-y-2 sm:grid-cols-2 xl:grid-cols-[minmax(220px,320px)_110px_minmax(210px,auto)] xl:items-center">
                <form id="dashboardGlobalFilter" method="GET" action="{{ route('admin.dashboard') }}" class="contents">
                    <label class="min-w-0">
                        <span class="block text-[8px] font-bold uppercase tracking-[0.14em] text-slate-500">Outline Agreement</span>
                        <select id="dashboardOutlineAgreement" name="oa_id" class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-[10px] font-semibold text-slate-700">
                            @forelse ($dashboardOutlineAgreements ?? [] as $agreement)
                                <option value="{{ $agreement->id }}" @selected((int) $selectedOutlineAgreementId === (int) $agreement->id)>
                                    {{ $agreement->nomor_oa }} — {{ $agreement->nama_kontrak }} — {{ \App\Models\OutlineAgreement::statusOptions()[$agreement->status] ?? ucfirst($agreement->status) }}
                                </option>
                            @empty
                                <option value="">Belum ada Outline Agreement</option>
                            @endforelse
                        </select>
                    </label>
                    <label class="min-w-0">
                        <span class="block text-[8px] font-bold uppercase tracking-[0.14em] text-slate-500">Tahun</span>
                        <select id="dashboardYear" name="year" class="mt-1.5 w-full rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-[10px] font-semibold text-slate-700">
                            <option value="all" @selected($selectedDashboardYear === null)>Semua Tahun</option>
                            @foreach ($dashboardAvailableYears ?? [] as $year)
                                <option value="{{ $year }}" @selected((int) $selectedDashboardYear === (int) $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                    </label>
                    <noscript><button type="submit">Terapkan</button></noscript>
                </form>

                <aside class="contract-budget-summary min-w-0 border-t-2 border-blue-600 pt-3 sm:col-span-2 xl:col-span-1 xl:border-l-2 xl:border-t-0 xl:py-1 xl:pl-4 xl:text-right">
                    <div class="text-[9px] font-bold uppercase tracking-[0.16em] text-blue-700">Pagu Kontrak</div>
                    <div class="mt-1 text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-500">
                        {{ $contractPeriodLabel !== '' ? $contractPeriodLabel : '-' }}
                    </div>
                    <div class="mt-1 break-words text-lg font-extrabold leading-6 text-slate-950 sm:text-xl">
                        {{ $rp($totalPaguKontrak) }}
                    </div>
                </aside>
            </div>
        </header>

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
                        PROGNOSA DAN REALISASI BIAYA PEMELIHARAAN
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

                <article class="monthly-realization-panel flex min-h-[330px] flex-col rounded-xl bg-blue-100 p-3">
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

                    <div id="monthlyRealizationChartContainer" class="relative mt-3 min-h-[230px] flex-1">
                        <canvas id="monthlyRealizationChart" class="h-full w-full" role="img" aria-label="Grafik garis Realisasi Per Bulan"></canvas>
                    </div>
                    <div id="monthlyRealizationEmptyState" class="mt-3 hidden min-h-[230px] items-center justify-center px-4 text-center text-[10px] text-slate-500">
                        Tidak ada data realisasi pada periode ini.
                    </div>
                </article>
            </div>

            <div class="right-cost-column grid min-w-0 gap-3 xl:col-span-4">
                @foreach ([
                    ['title' => 'PROGNOSA DAN REALISASI BIAYA NON PEMELIHARAAN', 'icon' => 'building-2', 'accent' => 'bg-violet-600 text-white', 'background' => 'bg-violet-100', 'summary' => $nonMaintenanceSummary, 'canvas' => 'nonMaintenanceOutstandingChart', 'color' => '#7c3aed'],
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
                            <div class="relative mt-3 min-h-[190px] min-w-0 flex-1">
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
                        <div id="topTenGeneralCostChartContainer" class="relative mt-2 min-h-[320px] flex-1">
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
                        <div id="topTenMaintenanceCostChartContainer" class="relative mt-2 min-h-[320px] flex-1">
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

                <div class="relative min-h-[220px] flex-1">
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

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const globalFilterForm = document.getElementById('dashboardGlobalFilter');
            const globalAgreementSelect = document.getElementById('dashboardOutlineAgreement');
            const globalYearSelect = document.getElementById('dashboardYear');
            const startMonthSelect = document.getElementById('monthlyStartMonth');
            const endMonthSelect = document.getElementById('monthlyEndMonth');
            const applyFiltersButton = document.getElementById('applyMonthlyRealizationFilter');
            const chartTotal = document.getElementById('monthlyRealizationTotal');
            const chartEmptyState = document.getElementById('monthlyRealizationEmptyState');
            const chartContainer = document.getElementById('monthlyRealizationChartContainer');
            const chartCanvas = document.getElementById('monthlyRealizationChart');
            const nonMaintenanceOutstandingCanvas = document.getElementById('nonMaintenanceOutstandingChart');
            const capexOutstandingCanvas = document.getElementById('capexOutstandingChart');
            const topTenGeneralCostChartContainer = document.getElementById('topTenGeneralCostChartContainer');
            const topTenGeneralCostCanvas = document.getElementById('topTenGeneralCostChart');
            const topTenGeneralCostEmptyState = document.getElementById('topTenGeneralCostEmptyState');
            const topTenMaintenanceCostChartContainer = document.getElementById('topTenMaintenanceCostChartContainer');
            const topTenMaintenanceCostCanvas = document.getElementById('topTenMaintenanceCostChart');
            const topTenMaintenanceCostEmptyState = document.getElementById('topTenMaintenanceCostEmptyState');
            const overhaulPrognosisCanvas = document.getElementById('overhaulPrognosisChart');
            const initialChartData = @json($realizationChartData ?? []);
            const initialTopTenCostSections = @json($topTenCostSections ?? []);
            const initialTopTenMaintenanceCostSections = @json($topTenMaintenanceCostSections ?? []);
            const initialOverhaulPrognosis = @json($overhaulPrognosis ?? []);
            const chartEndpoint = @json(url('/admin/realisasi-biaya'));
            const selectedAgreementId = @json($selectedOutlineAgreementId ?? null);
            const selectedDashboardYear = @json($selectedDashboardYear);
            const chartColors = {
                general: '#2563eb',
                maintenance: '#10b981',
                non_maintenance: '#7c3aed',
                capex: '#0891b2',
            };
            const hasRealizationChart = [
                startMonthSelect,
                endMonthSelect,
                applyFiltersButton,
                chartTotal,
                chartEmptyState,
                chartContainer,
                chartCanvas,
            ].every(Boolean);
            const monthNames = {
                1: 'Jan', 2: 'Feb', 3: 'Mar', 4: 'Apr', 5: 'Mei', 6: 'Jun',
                7: 'Jul', 8: 'Agu', 9: 'Sep', 10: 'Okt', 11: 'Nov', 12: 'Des',
            };

            function loadMonths() {
                const months = [
                    { number: 1, name: 'Januari' }, { number: 2, name: 'Februari' }, { number: 3, name: 'Maret' },
                    { number: 4, name: 'April' }, { number: 5, name: 'Mei' }, { number: 6, name: 'Juni' },
                    { number: 7, name: 'Juli' }, { number: 8, name: 'Agustus' }, { number: 9, name: 'September' },
                    { number: 10, name: 'Oktober' }, { number: 11, name: 'November' }, { number: 12, name: 'Desember' }
                ];

                [startMonthSelect, endMonthSelect].forEach(select => {
                    select.innerHTML = '';
                    months.forEach(month => {
                        select.innerHTML += `<option value="${month.number}">${month.name}</option>`;
                    });
                });
            }

            function loadSavedFilters() {
                const firstRow = initialChartData[0] || {};
                const lastRow = initialChartData[initialChartData.length - 1] || firstRow;
                const savedStartMonth = Number(localStorage.getItem('monthlyRealizationStartMonth'));
                const savedEndMonth = Number(localStorage.getItem('monthlyRealizationEndMonth'));
                const hasValidSavedFilter = savedStartMonth >= 1
                    && savedStartMonth <= 12
                    && savedEndMonth >= 1
                    && savedEndMonth <= 12
                    && savedStartMonth <= savedEndMonth;
                const startMonth = hasValidSavedFilter ? savedStartMonth : Number(firstRow.month || 1);
                const endMonth = hasValidSavedFilter ? savedEndMonth : Number(lastRow.month || 12);

                if (startMonth) startMonthSelect.value = startMonth;
                if (endMonth) endMonthSelect.value = endMonth;

                if (hasValidSavedFilter) {
                    fetchRealizationData(startMonth, endMonth);
                } else {
                    renderChart(initialChartData);
                }
            }

            function fetchRealizationData(startMonth = null, endMonth = null) {
                const queryParams = new URLSearchParams({
                    oa_id: selectedAgreementId,
                    ...(selectedDashboardYear && { year: selectedDashboardYear }),
                    ...(startMonth && { startMonth }),
                    ...(endMonth && { endMonth })
                }).toString();

                fetch(`${chartEndpoint}?${queryParams}`)
                    .then(response => response.json())
                    .then(data => {
                        if (!Array.isArray(data)) {
                            throw new Error('Format data tidak valid.');
                        }

                        renderChart(data);
                    })
                    .catch(error => {
                        console.error('Error saat memproses data:', error);
                        alert('Terjadi kesalahan saat mengambil data.');
                    });
            }

            function renderChart(rows) {
                const labels = rows.map(item => item.label || `${monthNames[item.month] || item.month} ${item.year}`);
                const datasetDefinitions = [
                    { key: 'general', label: 'General', color: chartColors.general },
                    { key: 'maintenance', label: 'Pemeliharaan', color: chartColors.maintenance },
                    { key: 'non_maintenance', label: 'Non Pemeliharaan', color: chartColors.non_maintenance },
                    { key: 'capex', label: 'CAPEX', color: chartColors.capex },
                ];
                const total = rows.reduce((sum, item) => sum + Number(item.general || 0), 0);
                const hasData = rows.some(item => datasetDefinitions.some(dataset => Number(item[dataset.key] || 0) > 0));

                chartTotal.textContent = formatRupiah(total);
                chartEmptyState.classList.toggle('hidden', hasData);
                chartEmptyState.classList.toggle('flex', !hasData);
                chartContainer.classList.toggle('hidden', !hasData);

                if (window.realisasiBiayaChart) window.realisasiBiayaChart.destroy();

                if (hasData) {
                    window.realisasiBiayaChart = new Chart(chartCanvas, {
                        type: 'line',
                        data: {
                            labels,
                            datasets: datasetDefinitions.map(dataset => ({
                                key: dataset.key,
                                label: dataset.label,
                                data: rows.map(item => Number(item[dataset.key] || 0)),
                                borderColor: dataset.color,
                                backgroundColor: dataset.color,
                                borderWidth: dataset.key === 'general' ? 3 : 2,
                                pointRadius: 3,
                                pointHoverRadius: 5,
                                tension: 0.3,
                                fill: false,
                            })),
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                x: {
                                    grid: { display: false },
                                    ticks: {
                                        display: selectedDashboardYear !== null,
                                    },
                                },
                                y: {
                                    beginAtZero: true,
                                    ticks: {
                                        callback: value => compactRupiah(value),
                                    },
                                },
                            },
                            plugins: {
                                legend: {
                                    display: true,
                                    labels: {
                                        boxWidth: 10,
                                        boxHeight: 10,
                                        font: { size: 9 },
                                    },
                                },
                                tooltip: {
                                    callbacks: {
                                        label: context => `${context.dataset.label}: ${formatRupiah(context.raw)}`,
                                    },
                                },
                            },
                        },
                    });
                }
            }

            function renderTopTenCostCharts(generalRows, maintenanceRows) {
                renderTopTenCostChart({
                    rows: generalRows,
                    canvas: topTenGeneralCostCanvas,
                    container: topTenGeneralCostChartContainer,
                    emptyState: topTenGeneralCostEmptyState,
                    instanceKey: 'topTenGeneralCostChartInstance',
                    pluginId: 'topTenGeneralCostSectionLabels',
                    datasetLabel: 'General',
                    color: '#2563eb',
                });
                renderTopTenCostChart({
                    rows: maintenanceRows,
                    canvas: topTenMaintenanceCostCanvas,
                    container: topTenMaintenanceCostChartContainer,
                    emptyState: topTenMaintenanceCostEmptyState,
                    instanceKey: 'topTenMaintenanceCostChartInstance',
                    pluginId: 'topTenMaintenanceCostSectionLabels',
                    datasetLabel: 'Pemeliharaan',
                    color: '#10b981',
                });
            }

            function renderOutstandingStageChart(canvas, instanceKey) {
                if (!canvas) return;

                let stages = {};
                try {
                    stages = JSON.parse(canvas.dataset.stages || '{}');
                } catch (error) {
                    console.error('Gagal membaca data outstanding kategori.', error);
                }

                const labels = ['HPP', 'Purchase Order', 'LPJ Process'];
                const amounts = [
                    Number(stages.hpp || 0),
                    Number(stages.purchase_order || 0),
                    Number(stages.lpj_process || 0),
                ];
                const color = canvas.dataset.color || '#2563eb';

                if (window[instanceKey]) {
                    window[instanceKey].destroy();
                    window[instanceKey] = null;
                }

                const valueLabels = {
                    id: `${instanceKey}ValueLabels`,
                    afterDatasetsDraw(chart) {
                        const { ctx, chartArea } = chart;
                        const metadata = chart.getDatasetMeta(0);

                        ctx.save();
                        ctx.fillStyle = '#334155';
                        ctx.font = '600 9px sans-serif';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'bottom';

                        metadata.data.forEach((bar, index) => {
                            const y = Math.max(chartArea.top + 10, bar.y - 5);
                            ctx.fillText(compactRupiah(amounts[index]), bar.x, y);
                        });

                        ctx.restore();
                    },
                };

                window[instanceKey] = new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            data: amounts,
                            backgroundColor: color,
                            borderRadius: 7,
                            maxBarThickness: 52,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: { padding: { top: 20 } },
                        scales: {
                            x: {
                                grid: { display: false },
                                border: { display: false },
                                ticks: {
                                    color: '#475569',
                                    font: { size: 8, weight: '600' },
                                    maxRotation: 0,
                                    autoSkip: false,
                                },
                            },
                            y: {
                                beginAtZero: true,
                                border: { display: false },
                                grid: { color: 'rgba(148, 163, 184, 0.16)' },
                                ticks: {
                                    maxTicksLimit: 5,
                                    callback: value => compactRupiah(value),
                                },
                            },
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: context => formatRupiah(context.raw),
                                },
                            },
                        },
                    },
                    plugins: [valueLabels],
                });
            }

            function truncateCanvasText(ctx, text, maxWidth) {
                if (maxWidth <= 0) return '';
                if (ctx.measureText(text).width <= maxWidth) return text;

                const ellipsis = '…';
                let truncated = text;
                while (truncated.length > 0 && ctx.measureText(`${truncated}${ellipsis}`).width > maxWidth) {
                    truncated = truncated.slice(0, -1);
                }

                return truncated.length > 0 ? `${truncated}${ellipsis}` : '';
            }

            function renderTopTenCostChart({
                rows,
                canvas,
                container,
                emptyState,
                instanceKey,
                pluginId,
                datasetLabel,
                color,
            }) {
                if (!canvas || !container || !emptyState) return;

                const safeRows = Array.isArray(rows) ? rows.slice(0, 10) : [];
                const sections = safeRows.map(item => String(item.section || ''));
                const amounts = safeRows.map(item => Number(item.amount || 0));
                const hasData = safeRows.length > 0;

                container.classList.toggle('hidden', !hasData);
                emptyState.classList.toggle('hidden', hasData);
                emptyState.classList.toggle('flex', !hasData);

                if (window[instanceKey]) {
                    window[instanceKey].destroy();
                    window[instanceKey] = null;
                }

                if (!hasData) {
                    return;
                }

                const sectionLabelsPlugin = {
                    id: pluginId,
                    afterDatasetsDraw(chart) {
                        const { ctx, chartArea } = chart;
                        const metadata = chart.getDatasetMeta(0);

                        ctx.save();
                        ctx.font = '600 11px sans-serif';
                        ctx.textAlign = 'left';
                        ctx.textBaseline = 'middle';

                        metadata.data.forEach((bar, index) => {
                            const label = `${sections[index]} · ${formatRupiah(amounts[index])}`;
                            const insideX = chartArea.left + 9;
                            const insideWidth = bar.x - insideX - 8;
                            const fullLabelWidth = ctx.measureText(label).width;

                            if (insideWidth >= fullLabelWidth) {
                                ctx.fillStyle = '#ffffff';
                                ctx.fillText(label, insideX, bar.y);
                                return;
                            }

                            const outsideX = Math.max(chartArea.left + 7, bar.x + 8);
                            const outsideWidth = chartArea.right - outsideX - 6;
                            const outsideLabel = truncateCanvasText(ctx, label, outsideWidth);

                            if (outsideLabel !== '') {
                                ctx.fillStyle = '#334155';
                                ctx.fillText(outsideLabel, outsideX, bar.y);
                            }
                        });

                        ctx.restore();
                    },
                };

                container.style.height = `${Math.max(320, Math.min(620, (safeRows.length * 54) + 52))}px`;
                window[instanceKey] = new Chart(canvas, {
                    type: 'bar',
                    data: {
                        labels: sections,
                        datasets: [{
                            label: datasetLabel,
                            data: amounts,
                            backgroundColor: color,
                            borderRadius: 6,
                            maxBarThickness: 34,
                            categoryPercentage: 0.76,
                            barPercentage: 0.86,
                        }],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                beginAtZero: true,
                                grace: '15%',
                                grid: { display: false },
                                border: { display: false },
                                ticks: {
                                    maxTicksLimit: 5,
                                    callback: value => compactRupiah(value),
                                },
                            },
                            y: {
                                grid: { display: false },
                                border: { display: false },
                                ticks: {
                                    display: false,
                                },
                            },
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    title: items => sections[items[0]?.dataIndex] || '',
                                    label: context => `${datasetLabel}: ${formatRupiah(context.raw)}`,
                                },
                            },
                        },
                    },
                    plugins: [sectionLabelsPlugin],
                });
            }

            function renderOverhaulPrognosisChart(rows) {
                const safeRows = Array.isArray(rows) ? rows : [];
                const labels = safeRows.map(item => item.label);
                const amounts = safeRows.map(item => Number(item.amount || 0));

                if (window.overhaulPrognosisChartInstance) {
                    window.overhaulPrognosisChartInstance.destroy();
                    window.overhaulPrognosisChartInstance = null;
                }

                const overhaulValueLabels = {
                    id: 'overhaulValueLabels',
                    afterDatasetsDraw(chart) {
                        const { ctx, chartArea } = chart;
                        const metadata = chart.getDatasetMeta(0);

                        ctx.save();
                        ctx.fillStyle = '#334155';
                        ctx.font = '600 10px sans-serif';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'bottom';

                        metadata.data.forEach((bar, index) => {
                            const y = Math.max(chartArea.top + 12, bar.y - 6);
                            ctx.fillText(formatRupiah(amounts[index]), bar.x, y);
                        });

                        ctx.restore();
                    },
                };

                window.overhaulPrognosisChartInstance = new Chart(overhaulPrognosisCanvas, {
                    type: 'bar',
                    data: {
                        labels,
                        datasets: [{
                            label: 'Prognosa Biaya',
                            data: amounts,
                            backgroundColor: ['#f59e0b', '#d97706', '#b45309'],
                            borderRadius: 8,
                            maxBarThickness: 56,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                grid: { display: false },
                                border: { display: false },
                                ticks: {
                                    color: '#475569',
                                    font: { size: 10 },
                                },
                            },
                            y: {
                                beginAtZero: true,
                                display: false,
                                grid: { display: false },
                                border: { display: false },
                                grace: '15%',
                            },
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: context => context.label + ': ' + formatRupiah(context.raw),
                                },
                            },
                        },
                    },
                    plugins: [overhaulValueLabels],
                });
            }

            function formatRupiah(value) {
                return `Rp ${Number(value || 0).toLocaleString('id-ID')}`;
            }

            function compactRupiah(value) {
                const number = Number(value || 0);
                if (number >= 1000000000) return `Rp ${(number / 1000000000).toLocaleString('id-ID')} M`;
                if (number >= 1000000) return `Rp ${(number / 1000000).toLocaleString('id-ID')} jt`;
                if (number >= 1000) return `Rp ${(number / 1000).toLocaleString('id-ID')} rb`;
                return `Rp ${number.toLocaleString('id-ID')}`;
            }

            if (hasRealizationChart) {
                applyFiltersButton.addEventListener('click', function () {
                    const startMonth = startMonthSelect.value;
                    const endMonth = endMonthSelect.value;

                    if (startMonth && endMonth && parseInt(startMonth) > parseInt(endMonth)) {
                        alert('Bulan mulai tidak boleh lebih besar dari bulan akhir!');
                        return;
                    }

                    localStorage.setItem('monthlyRealizationStartMonth', startMonth);
                    localStorage.setItem('monthlyRealizationEndMonth', endMonth);

                    fetchRealizationData(startMonth, endMonth);
                });

                renderTopTenCostCharts(initialTopTenCostSections, initialTopTenMaintenanceCostSections);
                renderOverhaulPrognosisChart(initialOverhaulPrognosis);
                loadMonths();
                loadSavedFilters();
            } else {
                renderTopTenCostCharts(initialTopTenCostSections, initialTopTenMaintenanceCostSections);
                renderOverhaulPrognosisChart(initialOverhaulPrognosis);
            }

            renderOutstandingStageChart(
                nonMaintenanceOutstandingCanvas,
                'nonMaintenanceOutstandingChartInstance',
            );
            renderOutstandingStageChart(
                capexOutstandingCanvas,
                'capexOutstandingChartInstance',
            );

            [globalAgreementSelect, globalYearSelect].forEach(select => {
                select?.addEventListener('change', () => globalFilterForm?.submit());
            });
        });
    </script>
</x-layouts.admin>
