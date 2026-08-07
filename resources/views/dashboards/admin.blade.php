<x-layouts.admin title="Dashboard Admin">
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

        $outstandingNotifications = $outstandingNotifications ?? 0;
        $pendingProcessJasa = $pendingProcessJasa ?? 0;
        $approvalProcessHPPCount = $approvalProcessHPPCount ?? 0;
        $documentOnProcessPOCount = $documentOnProcessPOCount ?? 0;

        $documentOnProcessHPPAmount = $documentOnProcessHPPAmount ?? 0;
        $approvalProcessHPPAmount = $approvalProcessHPPAmount ?? 0;
        $documentOnProcessPOAmount = $documentOnProcessPOAmount ?? 0;
        $documentPRPOAmount = $documentPRPOAmount ?? 0;
        $urgentAmount = $urgentAmount ?? 0;
        $totalAmount1 = $totalAmount1 ?? 0;
        $totalAmount2 = $totalAmount2 ?? 0;
        $totalSeluruhAmount = $totalSeluruhAmount ?? 0;
        $totalKuotaKontrak = $totalKuotaKontrak ?? 0;
        $sisaKuotaKontrak = $sisaKuotaKontrak ?? 0;
        $targetPemeliharaan = $targetPemeliharaan ?? null;
        $totalJasaPemeliharaan = $totalJasaPemeliharaan ?? 0;
        $sisaBiayaPemeliharaan = $sisaBiayaPemeliharaan ?? 0;
        $maintenanceTargetYear = $maintenanceTargetYear ?? now()->year;
        $maintenanceAnnualTarget = $maintenanceAnnualTarget ?? 0;
        $maintenanceRealization = $maintenanceRealization ?? 0;
        $maintenanceOutstanding = $maintenanceOutstanding ?? 0;
        $maintenancePrognosis = $maintenancePrognosis ?? 0;
        $maintenanceRemainingTarget = $maintenanceRemainingTarget ?? 0;
        $maintenanceTargetUsagePercentageLabel = $maintenanceTargetUsagePercentageLabel ?? '0';
        $maintenanceTargetUsageProgressWidth = $maintenanceTargetUsageProgressWidth ?? '0';
        $maintenanceLpjStatusAmount = $maintenanceLpjStatusAmount ?? 0;
        $maintenanceInvoiceStatusAmount = $maintenanceInvoiceStatusAmount ?? 0;
        $maintenanceAlreadyRealized = $maintenanceAlreadyRealized ?? $maintenanceRealization;
        $totalRealisasiBiaya = $totalRealisasiBiaya ?? 0;
        $totalPaguKontrak = $totalPaguKontrak ?? $totalKuotaKontrak;
        $totalOutstandingBiaya = $totalOutstandingBiaya ?? 0;
        $totalPrognosaBiaya = $totalPrognosaBiaya ?? 0;
        $totalAnggaranTersedia = $totalAnggaranTersedia ?? 0;
        $latestKuotaAnggaran = $latestKuotaAnggaran ?? null;
        $periodeKontrak = $periodeKontrak ?? ['start' => null, 'end' => null, 'adendum' => null];

        $processCards = [
            [
                'title' => 'Outstanding Order',
                'value' => $outstandingNotifications,
                'icon' => 'bell',
                'wrap' => 'bg-[#5f9ae8]',
                'iconColor' => 'text-[#2453d4]',
                'valueColor' => 'text-[#2453d4]',
                'url' => route('admin.hpp.index'),
            ],
            [
                'title' => 'Document On Process (HPP)',
                'value' => $pendingProcessJasa,
                'icon' => 'hourglass',
                'wrap' => 'bg-[#ffca19]',
                'iconColor' => 'text-[#ab7700]',
                'valueColor' => 'text-[#ab7700]',
                'url' => route('admin.hpp.index', ['status' => \App\Models\Hpp::STATUS_IN_REVIEW]),
            ],
            [
                'title' => 'Approval Process (HPP)',
                'value' => $approvalProcessHPPCount,
                'icon' => 'badge-check',
                'wrap' => 'bg-[#49d97a]',
                'iconColor' => 'text-[#0b8a57]',
                'valueColor' => 'text-[#0b7d4f]',
                'url' => route('admin.budget-verification.index'),
            ],
            [
                'title' => 'PR/PO Process (HPP Approved)',
                'value' => $documentOnProcessPOCount,
                'icon' => 'alert-circle',
                'wrap' => 'bg-[#fb6a6f]',
                'iconColor' => 'text-[#a71922]',
                'valueColor' => 'text-[#a71922]',
                'url' => route('admin.purchase-order.index'),
            ],
        ];

        $contractPeriodLabel = collect([
            $periodeKontrak['start']
                ? strtoupper(\Carbon\Carbon::parse($periodeKontrak['start'])->locale('id')->translatedFormat('M Y'))
                : null,
            $periodeKontrak['end']
                ? strtoupper(\Carbon\Carbon::parse($periodeKontrak['end'])->locale('id')->translatedFormat('M Y'))
                : null,
        ])->filter()->join(' - ');

        $remainingBudgetIsNegative = $sisaKuotaKontrak < 0;
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

        <header class="dashboard-header grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm lg:grid-cols-[minmax(0,1fr)_minmax(260px,auto)] lg:items-stretch">
            <div class="flex min-w-0 items-center gap-3">
                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <i data-lucide="layout-dashboard" class="h-5 w-5"></i>
                </span>
                <div class="min-w-0">
                    <h1 class="text-base font-bold tracking-[0.08em] text-slate-900 sm:text-lg">DASHBOARD BIAYA JASA</h1>
                    <p class="mt-1 text-[11px] leading-4 text-slate-500 sm:text-xs">
                        Ringkasan prognosa, realisasi, dan penggunaan anggaran.
                    </p>
                </div>
            </div>

            <aside class="contract-budget-summary min-w-0 rounded-xl border border-blue-200 bg-gradient-to-br from-blue-50 to-white px-4 py-3 lg:text-right">
                <div class="text-[9px] font-bold uppercase tracking-[0.16em] text-blue-700">Pagu Kontrak Periode</div>
                <div class="mt-1 text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-500">
                    {{ $contractPeriodLabel !== '' ? $contractPeriodLabel : '-' }}
                </div>
                <div class="mt-1 break-words text-lg font-extrabold leading-6 text-slate-950 sm:text-xl">
                    {{ $rp($totalPaguKontrak) }}
                </div>
            </aside>
        </header>

        <section class="general-cost-section rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
            <div class="mb-3 flex items-center gap-2">
                <i data-lucide="wallet-cards" class="h-4 w-4 text-blue-600"></i>
                <h2 class="text-[12px] font-bold tracking-[0.08em] text-slate-800">GENERAL BIAYA JASA</h2>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <article class="min-w-0 rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <div class="text-[9px] font-bold uppercase tracking-[0.12em] text-slate-500">Total Prognosa Biaya</div>
                    <div class="mt-2 break-words text-base font-bold text-slate-900">{{ $rp($totalPrognosaBiaya) }}</div>
                    <div class="mt-1 text-[9px] font-semibold text-slate-500">{{ $prognosaPercentageLabel ?? '0' }}% dari pagu</div>
                </article>
                <article class="min-w-0 rounded-xl border border-emerald-200 bg-emerald-50 p-3">
                    <div class="text-[9px] font-bold uppercase tracking-[0.12em] text-emerald-700">Realisasi Biaya</div>
                    <div class="mt-2 break-words text-base font-bold text-slate-900">{{ $rp($totalRealisasiBiaya) }}</div>
                    <div class="mt-1 text-[9px] font-semibold text-emerald-700">{{ $realisasiPercentageLabel ?? '0' }}% dari pagu</div>
                </article>
                <article class="min-w-0 rounded-xl border border-blue-200 bg-blue-50 p-3">
                    <div class="text-[9px] font-bold uppercase tracking-[0.12em] text-blue-700">Outstanding Biaya</div>
                    <div class="mt-2 break-words text-base font-bold text-slate-900">{{ $rp($totalOutstandingBiaya) }}</div>
                    <div class="mt-1 text-[9px] font-semibold text-blue-700">{{ $outstandingPercentageLabel ?? '0' }}% dari pagu</div>
                </article>
                <article class="min-w-0 rounded-xl border p-3 {{ $remainingBudgetClasses }}">
                    <div class="text-[9px] font-bold uppercase tracking-[0.12em]">Anggaran Tersedia</div>
                    <div class="mt-2 break-words text-base font-bold">{{ $rp($totalAnggaranTersedia) }}</div>
                    <div class="mt-1 text-[9px] font-semibold opacity-80">{{ $anggaranTersediaPercentageLabel ?? '0' }}% dari pagu</div>
                </article>
            </div>
        </section>

        <section class="main-cost-grid grid grid-cols-1 items-stretch gap-3 xl:grid-cols-12">
            <article class="maintenance-panel flex min-w-0 flex-col rounded-xl border border-slate-200 bg-white p-3 shadow-sm xl:col-span-5">
                <div class="flex items-start gap-2 border-b border-slate-100 pb-2.5">
                    <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                        <i data-lucide="wrench" class="h-3.5 w-3.5"></i>
                    </span>
                    <h2 class="pt-1 text-[11px] font-bold leading-4 tracking-[0.06em] text-slate-800">
                        PROGNOSA DAN REALISASI BIAYA PEMELIHARAAN
                    </h2>
                </div>

                <div class="mt-3 grid gap-3 md:grid-cols-12">
                    <section class="rounded-xl border border-slate-200 bg-slate-50 p-3 md:col-span-4">
                        <div class="text-center text-[9px] font-bold uppercase tracking-[0.12em] text-slate-500">Target Tahunan {{ $maintenanceTargetYear }}</div>
                        <div class="dashboard-chart-placeholder mx-auto mt-3 aspect-square w-full max-w-[138px] rounded-full p-[10px]" style="background: conic-gradient(#10b981 {{ $maintenanceTargetUsageProgressWidth }}%, #e2e8f0 {{ $maintenanceTargetUsageProgressWidth }}%);">
                            <div class="flex h-full w-full items-center justify-center rounded-full bg-white text-center">
                                <div class="px-2">
                                    <div class="text-[8px] uppercase tracking-[0.1em] text-slate-400">Pemakaian</div>
                                    <div class="mt-1 text-sm font-bold text-emerald-700">{{ $maintenanceTargetUsagePercentageLabel }}%</div>
                                    <div class="mt-1 break-words text-[9px] font-semibold text-slate-700">
                                        {{ $rp($maintenanceAnnualTarget) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 text-center">
                            <div class="text-[8px] font-semibold uppercase tracking-[0.1em] text-slate-400">Sisa Target</div>
                            <div class="mt-1 break-words text-[10px] font-bold {{ $maintenanceRemainingTarget < 0 ? 'text-rose-700' : 'text-slate-800' }}">
                                {{ $rp($maintenanceRemainingTarget) }}
                            </div>
                        </div>
                    </section>

                    <section class="rounded-xl border border-slate-200 bg-white p-3 md:col-span-8">
                        <h3 class="text-[9px] font-bold uppercase tracking-[0.12em] text-slate-500">Ringkasan Biaya</h3>
                        <dl class="mt-2 divide-y divide-slate-100 rounded-lg border border-slate-100">
                            <div class="flex items-center justify-between gap-3 px-3 py-3">
                                <dt class="text-[10px] font-medium text-slate-600">Outstanding Biaya</dt>
                                <dd class="shrink-0 text-[11px] font-bold text-slate-900">{{ $rp($maintenanceOutstanding) }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 px-3 py-3">
                                <dt class="text-[10px] font-medium text-slate-600">Realisasi Biaya</dt>
                                <dd class="shrink-0 text-[11px] font-bold text-slate-900">{{ $rp($maintenanceRealization) }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 px-3 py-3">
                                <dt class="text-[10px] font-medium text-slate-600">Total Prognosa Biaya</dt>
                                <dd class="shrink-0 text-[11px] font-bold text-slate-900">{{ $rp($maintenancePrognosis) }}</dd>
                            </div>
                        </dl>
                    </section>
                </div>

                <section class="mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <h3 class="text-[9px] font-bold uppercase tracking-[0.12em] text-slate-600">Monitoring Realisasi Anggaran Pemeliharaan</h3>
                    <div class="mt-2 grid gap-2 sm:grid-cols-2">
                        <div class="rounded-lg border border-slate-200 bg-white p-3">
                            <div class="text-[9px] font-semibold uppercase tracking-[0.1em] text-slate-500">Biaya Status LPJ</div>
                            <div class="mt-2 text-sm font-bold text-slate-800">{{ $rp($maintenanceLpjStatusAmount) }}</div>
                        </div>
                        <div class="rounded-lg border border-slate-200 bg-white p-3">
                            <div class="text-[9px] font-semibold uppercase tracking-[0.1em] text-slate-500">Biaya Status Invoice</div>
                            <div class="mt-2 text-sm font-bold text-slate-800">{{ $rp($maintenanceInvoiceStatusAmount) }}</div>
                        </div>
                    </div>
                </section>

                <section class="mt-3 flex min-h-[170px] flex-1 flex-col rounded-xl border border-slate-200 bg-white p-3">
                    <h3 class="text-[9px] font-bold uppercase tracking-[0.12em] text-slate-600">Realisasi Per Bulan</h3>
                    <div class="dashboard-chart-placeholder mt-3 flex min-h-[120px] flex-1 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50 px-4 text-center text-[10px] text-slate-400">
                        Area grafik realisasi per bulan
                    </div>
                </section>
            </article>

            <div class="right-cost-column grid min-w-0 gap-3 xl:col-span-7">
                @foreach ([
                    ['title' => 'PROGNOSA DAN REALISASI BIAYA NON PEMELIHARAAN', 'icon' => 'building-2', 'accent' => 'text-violet-600', 'background' => 'bg-violet-50'],
                    ['title' => 'PROGNOSA DAN REALISASI BIAYA CAPEX', 'icon' => 'landmark', 'accent' => 'text-cyan-600', 'background' => 'bg-cyan-50'],
                ] as $costPanel)
                    <article class="flex min-w-0 flex-col rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                        <div class="flex items-start gap-2 border-b border-slate-100 pb-2.5">
                            <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg {{ $costPanel['background'] }} {{ $costPanel['accent'] }}">
                                <i data-lucide="{{ $costPanel['icon'] }}" class="h-3.5 w-3.5"></i>
                            </span>
                            <h2 class="pt-1 text-[11px] font-bold leading-4 tracking-[0.06em] text-slate-800">
                                {{ $costPanel['title'] }}
                            </h2>
                        </div>

                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                <div class="text-[9px] font-semibold uppercase tracking-[0.1em] text-slate-500">Total Prognosa Biaya</div>
                                <div class="mt-2 text-sm font-bold text-slate-900">Rp -</div>
                            </div>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                <div class="text-[9px] font-semibold uppercase tracking-[0.1em] text-slate-500">Total Realisasi Biaya</div>
                                <div class="mt-2 text-sm font-bold text-slate-900">Rp -</div>
                            </div>
                        </div>

                        <section class="mt-3 flex-1 rounded-xl border border-slate-200 bg-white p-3">
                            <h3 class="text-[9px] font-bold uppercase tracking-[0.12em] text-slate-600">Outstanding Biaya</h3>
                            <div class="mt-3 grid gap-2 sm:grid-cols-3">
                                @foreach (['WAITING APPROVAL', 'HPP APPROVED', 'PURCHASE ORDER'] as $stage)
                                    <div class="dashboard-chart-placeholder flex min-h-[76px] items-end rounded-lg border border-dashed border-slate-300 bg-slate-50 p-2.5">
                                        <span class="text-[8px] font-semibold leading-3 tracking-[0.08em] text-slate-500">{{ $stage }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="bottom-cost-grid grid grid-cols-1 items-stretch gap-3 xl:grid-cols-12">
            <article class="overhaul-panel flex min-w-0 flex-col rounded-xl border border-slate-200 bg-white p-3 shadow-sm xl:col-span-5">
                <div class="mb-2 flex items-center gap-2 border-b border-slate-100 pb-2.5">
                    <i data-lucide="trending-up" class="h-4 w-4 text-amber-500"></i>
                    <h2 class="text-[11px] font-bold tracking-[0.08em] text-slate-800">PROGNOSA OVERHAUL</h2>
                </div>

                <div class="mb-2 grid gap-2 sm:grid-cols-3">
                    @foreach (['OVERHAUL TONASA 4', 'OVERHAUL TONASA 5', 'OVERHAUL T.2,3'] as $overhaulLabel)
                        <div class="rounded-lg border border-amber-100 bg-amber-50 px-2.5 py-2 text-center text-[8px] font-semibold leading-3 tracking-[0.06em] text-amber-800">
                            {{ $overhaulLabel }}
                        </div>
                    @endforeach
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

            <article class="top-ten-panel flex min-w-0 flex-col rounded-xl border border-slate-200 bg-white p-3 shadow-sm xl:col-span-7">
                <div class="mb-2 flex items-center gap-2 border-b border-slate-100 pb-2.5">
                    <i data-lucide="bar-chart-3" class="h-4 w-4 text-blue-500"></i>
                    <h2 class="text-[11px] font-bold tracking-[0.08em] text-slate-800">TOP TEN PEMICU BIAYA</h2>
                </div>

                <div class="grid min-w-0 flex-1 grid-cols-1 gap-3 xl:grid-cols-2">
                    <section class="flex min-w-0 flex-col rounded-xl border border-blue-100 bg-slate-50 p-3">
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
                        <div id="topTenGeneralCostEmptyState" class="hidden flex-1 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-white px-3 py-8 text-center text-xs text-slate-500">
                            Belum ada data HPP pada periode ini.
                        </div>
                    </section>

                    <section class="flex min-w-0 flex-col rounded-xl border border-emerald-100 bg-slate-50 p-3">
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
                        <div id="topTenMaintenanceCostEmptyState" class="hidden flex-1 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-white px-3 py-8 text-center text-xs text-slate-500">
                            Belum ada data HPP kategori Pemeliharaan pada periode ini.
                        </div>
                    </section>
                </div>
            </article>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const startYearSelect = document.getElementById('startYear');
            const endYearSelect = document.getElementById('endYear');
            const startMonthSelect = document.getElementById('startMonth');
            const endMonthSelect = document.getElementById('endMonth');
            const applyFiltersButton = document.getElementById('applyFilters');
            const chartLegend = document.getElementById('chartLegend');
            const chartTotal = document.getElementById('chartTotal');
            const chartEmptyState = document.getElementById('chartEmptyState');
            const chartCanvas = document.getElementById('realisasiBiayaPieChart');
            const documentPrPoAmount = document.getElementById('documentPrPoAmount');
            const urgentRealizationAmount = document.getElementById('urgentRealizationAmount');
            const realizationSubtotal = document.getElementById('realizationSubtotal');
            const totalRealizationAmount = document.getElementById('totalRealizationAmount');
            const budgetRealizationAmount = document.getElementById('budgetRealizationAmount');
            const budgetUsagePercentage = document.getElementById('budgetUsagePercentage');
            const budgetUsageProgress = document.getElementById('budgetUsageProgress');
            const budgetUsageProgressbar = budgetUsageProgress?.parentElement ?? null;
            const budgetUsageAmount = document.getElementById('budgetUsageAmount');
            const remainingContractBudgetCard = document.getElementById('remainingContractBudgetCard');
            const remainingContractBudget = document.getElementById('remainingContractBudget');
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
            const potentialAmount = Number(@json($totalOutstandingBiaya ?? 0));
            const contractBudget = Number(@json($totalPaguKontrak ?? 0));
            const yearsEndpoint = @json(url('/admin/get-years'));
            const chartEndpoint = @json(url('/admin/realisasi-biaya'));
            const chartColors = {
                normal: '#2563eb',
                urgent: '#f97316',
            };
            const hasRealizationChart = [
                startYearSelect,
                endYearSelect,
                startMonthSelect,
                endMonthSelect,
                applyFiltersButton,
                chartLegend,
                chartTotal,
                chartEmptyState,
                chartCanvas,
            ].every(Boolean);
            const monthNames = {
                1: 'Jan', 2: 'Feb', 3: 'Mar', 4: 'Apr', 5: 'Mei', 6: 'Jun',
                7: 'Jul', 8: 'Agu', 9: 'Sep', 10: 'Okt', 11: 'Nov', 12: 'Des',
            };

            function fetchYears() {
                fetch(yearsEndpoint)
                    .then(response => response.json())
                    .then(data => {
                        startYearSelect.innerHTML = '<option value="" selected disabled>Pilih Tahun</option>';
                        endYearSelect.innerHTML = '<option value="" selected disabled>Pilih Tahun</option>';
                        data.forEach(year => {
                            const option = `<option value="${year}">${year}</option>`;
                            startYearSelect.innerHTML += option;
                            endYearSelect.innerHTML += option;
                        });

                        loadSavedFilters();
                    })
                    .catch(error => console.error('Error fetching years:', error));
            }

            function loadMonths() {
                const months = [
                    { number: 1, name: 'Januari' }, { number: 2, name: 'Februari' }, { number: 3, name: 'Maret' },
                    { number: 4, name: 'April' }, { number: 5, name: 'Mei' }, { number: 6, name: 'Juni' },
                    { number: 7, name: 'Juli' }, { number: 8, name: 'Agustus' }, { number: 9, name: 'September' },
                    { number: 10, name: 'Oktober' }, { number: 11, name: 'November' }, { number: 12, name: 'Desember' }
                ];

                [startMonthSelect, endMonthSelect].forEach(select => {
                    select.innerHTML = '<option value="" selected disabled>Pilih Bulan</option>';
                    months.forEach(month => {
                        select.innerHTML += `<option value="${month.number}">${month.name}</option>`;
                    });
                });
            }

            function loadSavedFilters() {
                const savedStartYear = localStorage.getItem('startYear');
                const savedEndYear = localStorage.getItem('endYear');
                const savedStartMonth = localStorage.getItem('startMonth');
                const savedEndMonth = localStorage.getItem('endMonth');

                if (savedStartYear) startYearSelect.value = savedStartYear;
                if (savedEndYear) endYearSelect.value = savedEndYear;
                if (savedStartMonth) startMonthSelect.value = savedStartMonth;
                if (savedEndMonth) endMonthSelect.value = savedEndMonth;

                if (savedStartYear && savedEndYear) {
                    fetchData(savedStartYear, savedEndYear, savedStartMonth, savedEndMonth);
                    return;
                }

                renderChart(initialChartData);
                renderTopTenCostCharts(initialTopTenCostSections, initialTopTenMaintenanceCostSections);
                renderOverhaulPrognosisChart(initialOverhaulPrognosis);
            }

            function fetchData(startYear, endYear, startMonth = null, endMonth = null) {
                const queryParams = new URLSearchParams({
                    startYear,
                    endYear,
                    includeTopTen: '1',
                    ...(startMonth && { startMonth }),
                    ...(endMonth && { endMonth })
                }).toString();

                fetch(`${chartEndpoint}?${queryParams}`)
                    .then(response => response.json())
                    .then(data => {
                        if (
                            !Array.isArray(data.realization) ||
                            !Array.isArray(data.top_ten) ||
                            !Array.isArray(data.top_ten_maintenance) ||
                            !Array.isArray(data.overhaul)
                        ) {
                            throw new Error('Format data tidak valid.');
                        }

                        renderChart(data.realization);
                        renderTopTenCostCharts(data.top_ten, data.top_ten_maintenance);
                        renderOverhaulPrognosisChart(data.overhaul);
                    })
                    .catch(error => {
                        console.error('Error saat memproses data:', error);
                        alert('Terjadi kesalahan saat mengambil data.');
                    });
            }

            function renderChart(rows) {
                const labels = rows.map(item => item.label || `${monthNames[item.month] || item.month} ${item.year}`);
                const normalValues = rows.map(item => Number(item.normal_total || 0));
                const urgentValues = rows.map(item => Number(item.urgent_total || 0));
                const normalTotal = normalValues.reduce((sum, value) => sum + value, 0);
                const urgentTotal = urgentValues.reduce((sum, value) => sum + value, 0);
                const total = rows.reduce((sum, item) => sum + Number(item.total || 0), 0);

                updateRealizationSummary(normalTotal, urgentTotal);
                chartTotal.textContent = formatRupiah(total);
                chartEmptyState.classList.toggle('hidden', rows.length > 0);
                chartCanvas.classList.toggle('hidden', rows.length === 0);

                if (window.realisasiBiayaChart) window.realisasiBiayaChart.destroy();

                if (rows.length > 0) {
                    window.realisasiBiayaChart = new Chart(chartCanvas, {
                        type: 'bar',
                        data: {
                            labels,
                            datasets: [
                                {
                                    label: 'Document PR/PO (LHPP)',
                                    data: normalValues,
                                    backgroundColor: chartColors.normal,
                                    borderRadius: 8,
                                },
                                {
                                    label: 'Pekerjaan Urgent',
                                    data: urgentValues,
                                    backgroundColor: chartColors.urgent,
                                    borderRadius: 8,
                                },
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                x: {
                                    stacked: true,
                                    grid: { display: false },
                                },
                                y: {
                                    stacked: true,
                                    beginAtZero: true,
                                    ticks: {
                                        callback: value => compactRupiah(value),
                                    },
                                },
                            },
                            plugins: {
                                legend: {
                                    display: false,
                                },
                                tooltip: {
                                    callbacks: {
                                        label: context => `${context.dataset.label}: ${formatRupiah(context.raw)}`,
                                        footer: items => {
                                            const index = items[0]?.dataIndex ?? 0;
                                            return `Total: ${formatRupiah(rows[index]?.total || 0)}`;
                                        },
                                    },
                                },
                            },
                        },
                    });
                }

                updateLegend(rows);
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

            function updateRealizationSummary(normalTotal, urgentTotal) {
                if (![
                    documentPrPoAmount,
                    urgentRealizationAmount,
                    realizationSubtotal,
                    totalRealizationAmount,
                    budgetRealizationAmount,
                    remainingContractBudget,
                ].every(Boolean)) {
                    return;
                }

                const realizationTotal = normalTotal + urgentTotal;
                const potentialAndRealization = potentialAmount + realizationTotal;
                const remainingBudget = contractBudget - potentialAndRealization;

                documentPrPoAmount.textContent = formatRupiah(normalTotal);
                urgentRealizationAmount.textContent = formatRupiah(urgentTotal);
                realizationSubtotal.textContent = formatRupiah(realizationTotal);
                totalRealizationAmount.textContent = `Total Realisasi Biaya: ${formatRupiah(realizationTotal)}`;
                budgetRealizationAmount.textContent = formatRupiah(realizationTotal);
                remainingContractBudget.textContent = formatRupiah(remainingBudget);
                updateBudgetUsage(potentialAndRealization);
                updateRemainingBudgetState(remainingBudget);
            }

            function updateBudgetUsage(usedAmount) {
                if (!budgetUsagePercentage || !budgetUsageProgress || !budgetUsageProgressbar || !budgetUsageAmount) {
                    return;
                }

                const usagePercentage = contractBudget > 0 ? (usedAmount / contractBudget) * 100 : 0;
                const visualPercentage = Math.min(100, Math.max(0, usagePercentage));
                const percentageLabel = new Intl.NumberFormat('id-ID', {
                    maximumFractionDigits: 2,
                }).format(usagePercentage);

                budgetUsagePercentage.textContent = `${percentageLabel}%`;
                budgetUsageProgress.style.width = `${visualPercentage}%`;
                budgetUsageProgressbar.setAttribute('aria-valuenow', visualPercentage.toFixed(2));
                budgetUsageProgressbar.setAttribute('aria-valuetext', `${percentageLabel}%`);
                budgetUsageAmount.textContent = `${formatRupiah(usedAmount)} dari ${formatRupiah(contractBudget)}`;
            }

            function updateRemainingBudgetState(remainingBudget) {
                if (!remainingContractBudgetCard || !remainingContractBudget) {
                    return;
                }

                const isNegative = remainingBudget < 0;

                remainingContractBudgetCard.classList.toggle('border-rose-200', isNegative);
                remainingContractBudgetCard.classList.toggle('bg-rose-50', isNegative);
                remainingContractBudgetCard.classList.toggle('border-yellow-200', !isNegative);
                remainingContractBudgetCard.classList.toggle('bg-yellow-50', !isNegative);
                remainingContractBudget.classList.toggle('text-rose-700', isNegative);
                remainingContractBudget.classList.toggle('text-yellow-900', !isNegative);
            }

            function updateLegend(rows) {
                chartLegend.innerHTML = '';

                rows.forEach(item => {
                    chartLegend.innerHTML += `
                        <div class="rounded-lg border border-slate-200 bg-white px-2 py-1.5">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-semibold text-slate-700">${item.label || `${monthNames[item.month] || item.month} ${item.year}`}</span>
                                <span class="font-bold text-slate-900">${formatRupiah(item.total || 0)}</span>
                            </div>
                            <div class="mt-1 grid gap-0.5 text-[10px] text-slate-500">
                                <div class="flex items-center justify-between gap-2">
                                    <span><span class="mr-1 inline-block h-2 w-2 rounded-full" style="background-color:${chartColors.normal}"></span>Document PR/PO</span>
                                    <span>${formatRupiah(item.normal_total || 0)}</span>
                                </div>
                                <div class="flex items-center justify-between gap-2">
                                    <span><span class="mr-1 inline-block h-2 w-2 rounded-full" style="background-color:${chartColors.urgent}"></span>Urgent</span>
                                    <span>${formatRupiah(item.urgent_total || 0)}</span>
                                </div>
                            </div>
                        </div>`;
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
                    const startYear = startYearSelect.value;
                    const endYear = endYearSelect.value;
                    const startMonth = startMonthSelect.value;
                    const endMonth = endMonthSelect.value;

                    if (!startYear || !endYear) {
                        alert('Pilih rentang tahun terlebih dahulu!');
                        return;
                    }

                    if (parseInt(startYear) > parseInt(endYear)) {
                        alert('Tahun mulai tidak boleh lebih besar dari tahun akhir!');
                        return;
                    }

                    if (startYear === endYear && startMonth && endMonth && parseInt(startMonth) > parseInt(endMonth)) {
                        alert('Bulan mulai tidak boleh lebih besar dari bulan akhir!');
                        return;
                    }

                    localStorage.setItem('startYear', startYear);
                    localStorage.setItem('endYear', endYear);
                    if (startMonth) localStorage.setItem('startMonth', startMonth);
                    if (endMonth) localStorage.setItem('endMonth', endMonth);

                    fetchData(startYear, endYear, startMonth, endMonth);
                });

                fetchYears();
                loadMonths();
            } else {
                renderTopTenCostCharts(initialTopTenCostSections, initialTopTenMaintenanceCostSections);
                renderOverhaulPrognosisChart(initialOverhaulPrognosis);
            }
        });
    </script>
</x-layouts.admin>
