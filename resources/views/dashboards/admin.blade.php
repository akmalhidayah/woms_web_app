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
        $totalRealisasiBiaya = $totalRealisasiBiaya ?? 0;
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

        <section class="rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
            <div class="flex items-center gap-2.5">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                    <i data-lucide="bar-chart-3" class="h-3.5 w-3.5"></i>
                </span>
                <div>
                    <h1 class="text-[1.1rem] font-bold leading-tight tracking-tight text-slate-900">Dashboard Admin</h1>
                    <p class="text-[11px] text-slate-500">Ringkasan proses notifikasi, HPP, dan approval.</p>
                </div>
            </div>
        </section>

        <section class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
            <h2 class="mb-2 text-[13px] font-semibold text-slate-800">Order Process</h2>

            <div class="grid grid-cols-1 gap-2.5 sm:grid-cols-2 md:hidden">
                @foreach ($processCards as $card)
                    <a href="{{ $card['url'] }}" class="flex h-24 min-w-0 flex-col items-center justify-center rounded-lg px-2.5 text-center shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $card['wrap'] }}">
                        <i data-lucide="{{ $card['icon'] }}" class="h-5 w-5 {{ $card['iconColor'] }}"></i>
                        <div class="mt-1.5 text-[11px] font-medium leading-4 text-slate-800">{{ $card['title'] }}</div>
                        <div class="text-lg font-bold {{ $card['valueColor'] }}">{{ $card['value'] }}</div>
                    </a>
                @endforeach
            </div>

            <div class="hidden gap-2.5 md:flex md:flex-nowrap">
                @foreach ($processCards as $card)
                    <a href="{{ $card['url'] }}" class="flex h-24 min-w-0 flex-1 flex-col items-center justify-center rounded-lg px-2.5 text-center shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $card['wrap'] }}">
                        <i data-lucide="{{ $card['icon'] }}" class="h-5 w-5 {{ $card['iconColor'] }}"></i>
                        <div class="mt-1.5 text-[11px] font-medium leading-4 text-slate-800">{{ $card['title'] }}</div>
                        <div class="text-lg font-bold {{ $card['valueColor'] }}">{{ $card['value'] }}</div>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="grid gap-3 xl:grid-cols-2">
            <article class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                <div class="mb-2 flex items-center gap-2">
                    <i data-lucide="badge-dollar-sign" class="h-4 w-4 text-emerald-500"></i>
                    <h3 class="text-[13px] font-semibold text-slate-800">Potensi Biaya (Cost)</h3>
                </div>

                <div class="grid gap-2.5 md:grid-cols-3">
                    <div class="rounded-lg border border-slate-200 bg-white p-2.5 shadow-sm">
                        <div class="text-[11px] leading-4 text-slate-700">Document On Process (HPP)</div>
                        <div class="mt-2 text-right text-xs font-semibold text-slate-900">{{ $rp($documentOnProcessHPPAmount) }}</div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-2.5 shadow-sm">
                        <div class="text-[11px] leading-4 text-slate-700">Approval Process (HPP)</div>
                        <div class="mt-2 text-right text-xs font-semibold text-slate-900">{{ $rp($approvalProcessHPPAmount) }}</div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-2.5 shadow-sm">
                        <div class="text-[11px] leading-4 text-slate-700">PR/PO On Process</div>
                        <div class="mt-2 text-right text-xs font-semibold text-slate-900">{{ $rp($documentOnProcessPOAmount) }}</div>
                    </div>
                </div>

                <div class="mt-2 flex justify-end gap-2 text-[11px]">
                    <span class="text-slate-500">Subtotal potensi</span>
                    <span class="font-bold text-slate-900">{{ $rp($totalAmount1) }}</span>
                </div>
            </article>

            <article class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                <div class="mb-2 flex items-center gap-2">
                    <i data-lucide="pie-chart" class="h-4 w-4 text-blue-500"></i>
                    <h3 class="text-[13px] font-semibold text-slate-800">Realisasi Biaya (LPJ)</h3>
                </div>

                <div class="grid gap-2.5 md:grid-cols-2">
                    <div class="rounded-lg border border-slate-200 bg-white p-2.5 shadow-sm">
                        <div class="text-[11px] leading-4 text-slate-700">Document PR/PO (LHPP)</div>
                        <div id="documentPrPoAmount" class="mt-2 text-right text-xs font-semibold text-slate-900">{{ $rp($documentPRPOAmount) }}</div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white p-2.5 shadow-sm">
                        <div class="text-[11px] leading-4 text-slate-700">Pekerjaan Urgent</div>
                        <div id="urgentRealizationAmount" class="mt-2 text-right text-xs font-semibold text-slate-900">{{ $rp($urgentAmount) }}</div>
                    </div>
                </div>

                <div class="mt-2 flex justify-end gap-2 text-[11px]">
                    <span class="text-slate-500">Subtotal realisasi</span>
                    <span id="realizationSubtotal" class="font-bold text-slate-900">{{ $rp($totalAmount2) }}</span>
                </div>
            </article>
        </section>

        <section class="dashboard-compact-grid grid gap-2 lg:grid-cols-2">
            <article class="rounded-xl border border-slate-200 bg-white p-2 shadow-sm">
                @php
                    $remainingBudgetIsNegative = $sisaKuotaKontrak < 0;
                    $remainingBudgetCardClasses = $remainingBudgetIsNegative
                        ? 'border-rose-200 bg-rose-50'
                        : 'border-yellow-200 bg-yellow-50';
                    $remainingBudgetValueClasses = $remainingBudgetIsNegative
                        ? 'text-rose-700'
                        : 'text-yellow-900';
                    $remainingMaintenanceClasses = $sisaBiayaPemeliharaan < 0
                        ? 'text-rose-700'
                        : 'text-slate-900';
                @endphp

                <div class="mb-2 flex flex-wrap items-start justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <i data-lucide="badge-info" class="h-3.5 w-3.5 text-slate-600"></i>
                        <h3 class="text-[13px] font-semibold text-slate-800">Ringkasan Kuota Anggaran</h3>
                    </div>
                    <div class="text-right text-[8.5px] leading-3 text-slate-500">
                        <div class="font-semibold uppercase tracking-[0.12em] text-slate-400">Periode OA</div>
                        <div>
                            {{ $periodeKontrak['start'] ? \Carbon\Carbon::parse($periodeKontrak['start'])->format('d M Y') : '-' }}
                            s/d
                            {{ $periodeKontrak['end'] ? \Carbon\Carbon::parse($periodeKontrak['end'])->format('d M Y') : '-' }}
                        </div>
                    </div>
                </div>

                <div class="grid gap-2 sm:grid-cols-2">
                    <div class="min-w-0 rounded-lg border border-slate-200 bg-slate-50 p-2.5">
                        <div class="text-[10px] font-semibold text-slate-600">Kuota Anggaran</div>
                        <div class="mt-1 break-words text-sm font-bold leading-5 text-slate-900">{{ $rp($totalKuotaKontrak) }}</div>
                    </div>
                    <div class="min-w-0 rounded-lg border border-blue-200 bg-blue-50 p-2.5">
                        <div class="text-[10px] font-semibold text-blue-700">Potensi Biaya</div>
                        <div id="budgetPotentialAmount" class="mt-1 break-words text-sm font-bold leading-5 text-slate-900">{{ $rp($totalAmount1) }}</div>
                    </div>
                    <div class="min-w-0 rounded-lg border border-emerald-200 bg-emerald-50 p-2.5">
                        <div class="text-[10px] font-semibold text-emerald-700">Realisasi Biaya</div>
                        <div id="budgetRealizationAmount" class="mt-1 break-words text-sm font-bold leading-5 text-slate-900">{{ $rp($totalRealisasiBiaya) }}</div>
                    </div>
                    <div id="remainingContractBudgetCard" class="min-w-0 rounded-lg border p-2.5 {{ $remainingBudgetCardClasses }}">
                        <div class="text-[10px] font-semibold text-slate-700">Sisa Kuota Kontrak</div>
                        <div id="remainingContractBudget" class="mt-1 break-words text-sm font-bold leading-5 {{ $remainingBudgetValueClasses }}">{{ $rp($sisaKuotaKontrak) }}</div>
                    </div>
                </div>

                <div class="mt-2 rounded-lg border border-slate-200 bg-white p-2.5">
                    <div class="flex items-center justify-between gap-3 text-[10px]">
                        <span class="font-semibold text-slate-700">Pemakaian Kuota</span>
                        <span id="budgetUsagePercentage" class="font-bold text-blue-700">{{ $budgetUsagePercentageLabel }}%</span>
                    </div>
                    <div
                        class="mt-2 h-2 overflow-hidden rounded-full bg-slate-200"
                        role="progressbar"
                        aria-label="Pemakaian Kuota"
                        aria-valuenow="{{ $budgetUsageProgressWidth }}"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        aria-valuetext="{{ $budgetUsagePercentageLabel }}%"
                    >
                        <div id="budgetUsageProgress" class="h-full rounded-full bg-blue-600 transition-[width]" style="width: {{ $budgetUsageProgressWidth }}%"></div>
                    </div>
                    <div id="budgetUsageAmount" class="mt-1.5 break-words text-[9px] text-slate-500">
                        {{ $rp($totalPemakaianKuota) }} dari {{ $rp($totalKuotaKontrak) }}
                    </div>
                </div>

                <div class="mt-2 rounded-lg border border-slate-200 bg-slate-50 p-2.5">
                    <h4 class="text-[10px] font-semibold text-slate-700">Ringkasan Biaya Pemeliharaan</h4>
                    <dl class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        <div class="min-w-0 rounded-md bg-white p-2">
                            <dt class="text-[9px] leading-3 text-slate-500">Target Biaya Pemeliharaan</dt>
                            <dd class="mt-1 break-words text-[11px] font-semibold text-slate-900">{{ $rp($targetPemeliharaan) }}</dd>
                        </div>
                        <div class="min-w-0 rounded-md bg-white p-2">
                            <dt class="text-[9px] leading-3 text-slate-500">Total Jasa Pemeliharaan</dt>
                            <dd class="mt-1 break-words text-[11px] font-semibold text-slate-900">{{ $rp($totalJasaPemeliharaan) }}</dd>
                        </div>
                        <div class="min-w-0 rounded-md bg-white p-2 sm:col-span-2 lg:col-span-1">
                            <dt class="text-[9px] leading-3 text-slate-500">Sisa Target Pemeliharaan</dt>
                            <dd class="mt-1 break-words text-[11px] font-semibold {{ $remainingMaintenanceClasses }}">{{ $rp($sisaBiayaPemeliharaan) }}</dd>
                        </div>
                    </dl>
                </div>
            </article>

            <article class="rounded-xl border border-slate-200 bg-white p-2 shadow-sm">
                <div id="totalRealizationAmount" class="rounded-lg bg-emerald-100 px-2.5 py-1 text-center text-[10px] font-bold text-slate-900">
                    Total Realisasi Biaya: Rp {{ number_format($totalRealisasiBiaya, 0, ',', '.') }}
                </div>

                <div class="mt-1.5 grid gap-2 text-[10px] text-slate-700 xl:grid-cols-2">
                    <div>
                        <p class="mb-1 text-[9px] text-slate-500">Sortir per rentang tahun.</p>
                        <div class="grid gap-1.5 md:grid-cols-[1fr_auto_1fr] md:items-center">
                            <div class="grid gap-1">
                                <label for="startYear" class="text-[9px] text-slate-600">Dari Tahun</label>
                                <select id="startYear" class="w-full rounded-md border border-slate-300 bg-white px-2 py-1 text-[10px] focus:border-blue-500 focus:outline-none">
                                    <option value="" selected disabled>Pilih Tahun</option>
                                </select>
                            </div>
                            <span class="hidden text-[9px] text-slate-600 md:block">sampai</span>
                            <div class="grid gap-1">
                                <label for="endYear" class="text-[9px] text-slate-600">Sampai Tahun</label>
                                <select id="endYear" class="w-full rounded-md border border-slate-300 bg-white px-2 py-1 text-[10px] focus:border-blue-500 focus:outline-none">
                                    <option value="" selected disabled>Pilih Tahun</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div>
                        <p class="mb-1 text-[9px] text-slate-500">Sortir per rentang bulan.</p>
                        <div class="grid gap-1.5 md:grid-cols-[1fr_auto_1fr] md:items-center">
                            <div class="grid gap-1">
                                <label for="startMonth" class="text-[9px] text-slate-600">Dari Bulan</label>
                                <select id="startMonth" class="w-full rounded-md border border-slate-300 bg-white px-2 py-1 text-[10px] focus:border-blue-500 focus:outline-none">
                                    <option value="" selected disabled>Pilih Bulan</option>
                                </select>
                            </div>
                            <span class="hidden text-[9px] text-slate-600 md:block">sampai</span>
                            <div class="grid gap-1">
                                <label for="endMonth" class="text-[9px] text-slate-600">Sampai Bulan</label>
                                <select id="endMonth" class="w-full rounded-md border border-slate-300 bg-white px-2 py-1 text-[10px] focus:border-blue-500 focus:outline-none">
                                    <option value="" selected disabled>Pilih Bulan</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-end justify-start lg:col-span-2">
                        <button id="applyFilters" class="rounded-md bg-blue-600 px-2.5 py-1.5 text-[10px] font-semibold text-white transition hover:bg-blue-700">
                            Terapkan
                        </button>
                    </div>
                </div>

                <div class="mt-2 rounded-lg border border-slate-200 bg-slate-50 p-2">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="text-[11px] font-semibold text-slate-800">Grafik Realisasi Biaya</div>
                        <div id="chartTotal" class="text-[11px] font-bold text-slate-600">Rp 0</div>
                    </div>
                    <div class="mt-1.5 h-28">
                        <canvas id="realisasiBiayaPieChart" class="h-full w-full"></canvas>
                    </div>
                    <div id="chartEmptyState" class="hidden rounded-lg border border-dashed border-slate-300 bg-white px-3 py-4 text-center text-xs text-slate-500">
                        Belum ada data realisasi biaya pada rentang ini.
                    </div>
                    <div id="chartLegend" class="mt-2 grid gap-1.5 text-[10px] text-slate-700 md:grid-cols-2"></div>
                </div>
            </article>
        </section>

        <section class="grid items-stretch gap-3 xl:grid-cols-2">
            <article class="flex h-full flex-col rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                    <div class="flex items-center gap-2">
                        <i data-lucide="trending-up" class="h-4 w-4 text-amber-500"></i>
                        <h3 class="text-[13px] font-semibold text-slate-800">Prognosa Biaya Overhaul</h3>
                    </div>
                    <div class="text-[10px] text-slate-500">
                        Total Prognosa:
                        <span id="overhaulPrognosisTotal" class="font-bold text-slate-800">
                            {{ $rp(array_sum(array_column($overhaulPrognosis, 'amount'))) }}
                        </span>
                    </div>
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

            <article class="flex h-full flex-col rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                <div class="mb-2 flex items-center gap-2">
                    <i data-lucide="bar-chart-3" class="h-4 w-4 text-blue-500"></i>
                    <h3 class="text-[13px] font-semibold text-slate-800">Top Ten Unit Kerja Pemicu Biaya</h3>
                </div>

                <div id="topTenCostChartContainer" class="relative min-h-[180px] flex-1">
                    <canvas
                        id="topTenCostChart"
                        class="h-full w-full"
                        role="img"
                        aria-label="Grafik Top Ten Unit Kerja Pemicu Biaya"
                    ></canvas>
                </div>
                <div id="topTenCostEmptyState" class="hidden flex-1 items-center justify-center rounded-lg border border-dashed border-slate-300 bg-slate-50 px-3 py-8 text-center text-xs text-slate-500">
                    Belum ada data HPP yang telah disubmit.
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
            const budgetUsageProgressbar = budgetUsageProgress.parentElement;
            const budgetUsageAmount = document.getElementById('budgetUsageAmount');
            const remainingContractBudgetCard = document.getElementById('remainingContractBudgetCard');
            const remainingContractBudget = document.getElementById('remainingContractBudget');
            const topTenCostChartContainer = document.getElementById('topTenCostChartContainer');
            const topTenCostCanvas = document.getElementById('topTenCostChart');
            const topTenCostEmptyState = document.getElementById('topTenCostEmptyState');
            const overhaulPrognosisCanvas = document.getElementById('overhaulPrognosisChart');
            const overhaulPrognosisTotal = document.getElementById('overhaulPrognosisTotal');
            const initialChartData = @json($realizationChartData ?? []);
            const initialTopTenCostSections = @json($topTenCostSections ?? []);
            const initialOverhaulPrognosis = @json($overhaulPrognosis ?? []);
            const potentialAmount = Number(@json($totalAmount1 ?? 0));
            const contractBudget = Number(@json($totalKuotaKontrak ?? 0));
            const yearsEndpoint = @json(url('/admin/get-years'));
            const chartEndpoint = @json(url('/admin/realisasi-biaya'));
            const chartColors = {
                normal: '#2563eb',
                urgent: '#f97316',
            };
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
                renderTopTenCostChart(initialTopTenCostSections);
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
                            !Array.isArray(data.overhaul)
                        ) {
                            throw new Error('Format data tidak valid.');
                        }

                        renderChart(data.realization);
                        renderTopTenCostChart(data.top_ten);
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

            function renderTopTenCostChart(rows) {
                const hasData = rows.length > 0;

                topTenCostChartContainer.classList.toggle('hidden', !hasData);
                topTenCostEmptyState.classList.toggle('hidden', hasData);
                topTenCostEmptyState.classList.toggle('flex', !hasData);

                if (window.topTenCostChartInstance) {
                    window.topTenCostChartInstance.destroy();
                    window.topTenCostChartInstance = null;
                }

                if (!hasData) {
                    return;
                }

                topTenCostChartContainer.style.height = `${Math.max(180, Math.min(360, (rows.length * 34) + 44))}px`;
                window.topTenCostChartInstance = new Chart(topTenCostCanvas, {
                    type: 'bar',
                    data: {
                        labels: rows.map(item => item.section),
                        datasets: [{
                            label: 'Nilai HPP',
                            data: rows.map(item => Number(item.amount || 0)),
                            backgroundColor: '#2563eb',
                            borderRadius: 6,
                            maxBarThickness: 24,
                        }],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: {
                                    callback: value => compactRupiah(value),
                                },
                            },
                            y: {
                                grid: { display: false },
                                ticks: {
                                    autoSkip: false,
                                    color: '#475569',
                                    font: { size: 10 },
                                },
                            },
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: context => `Nilai HPP: ${formatRupiah(context.raw)}`,
                                },
                            },
                        },
                    },
                });
            }

            function renderOverhaulPrognosisChart(rows) {
                const safeRows = Array.isArray(rows) ? rows : [];
                const labels = safeRows.map(item => item.label);
                const amounts = safeRows.map(item => Number(item.amount || 0));
                const total = amounts.reduce((sum, amount) => sum + amount, 0);

                overhaulPrognosisTotal.textContent = formatRupiah(total);

                if (window.overhaulPrognosisChartInstance) {
                    window.overhaulPrognosisChartInstance.destroy();
                    window.overhaulPrognosisChartInstance = null;
                }

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
                                ticks: {
                                    color: '#475569',
                                    font: { size: 10 },
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
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: context => context.label + ': ' + formatRupiah(context.raw),
                                },
                            },
                        },
                    },
                });
            }

            function updateRealizationSummary(normalTotal, urgentTotal) {
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
        });
    </script>
</x-layouts.admin>
