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

    <div class="space-y-5">
        <section class="rounded-[1.5rem] border border-slate-200 bg-white px-6 py-5 shadow-sm">
            <div class="flex items-center gap-3">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50 text-blue-600">
                    <i data-lucide="bar-chart-3" class="h-5 w-5"></i>
                </span>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Dashboard Admin</h1>
                    <p class="mt-1 text-sm text-slate-500">Ringkasan proses notifikasi, HPP, dan approval.</p>
                </div>
            </div>
        </section>

        <section class="rounded-[1.5rem] border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-5 text-[1.05rem] font-semibold text-slate-800">Order Process</h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:hidden">
                @foreach ($processCards as $card)
                    <a href="{{ $card['url'] }}" class="flex h-40 min-w-0 flex-col items-center justify-center rounded-2xl px-3 text-center shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $card['wrap'] }}">
                        <i data-lucide="{{ $card['icon'] }}" class="h-8 w-8 {{ $card['iconColor'] }}"></i>
                        <div class="mt-4 text-[13px] font-medium leading-5 text-slate-800">{{ $card['title'] }}</div>
                        <div class="mt-2 text-2xl font-bold {{ $card['valueColor'] }}">{{ $card['value'] }}</div>
                    </a>
                @endforeach
            </div>

            <div class="hidden gap-4 md:flex md:flex-nowrap">
                @foreach ($processCards as $card)
                    <a href="{{ $card['url'] }}" class="flex h-40 min-w-0 flex-1 flex-col items-center justify-center rounded-2xl px-3 text-center shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $card['wrap'] }}">
                        <i data-lucide="{{ $card['icon'] }}" class="h-8 w-8 {{ $card['iconColor'] }}"></i>
                        <div class="mt-4 text-[13px] font-medium leading-5 text-slate-800">{{ $card['title'] }}</div>
                        <div class="mt-2 text-2xl font-bold {{ $card['valueColor'] }}">{{ $card['value'] }}</div>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="grid gap-5 xl:grid-cols-2">
            <article class="rounded-[1.5rem] border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-5 flex items-center gap-2">
                    <i data-lucide="badge-dollar-sign" class="h-5 w-5 text-emerald-500"></i>
                    <h3 class="text-[1.05rem] font-semibold text-slate-800">Potensi Biaya (Cost)</h3>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="text-xs leading-5 text-slate-700">Document On Process (HPP)</div>
                        <div class="mt-4 text-right text-sm font-semibold text-slate-900">{{ $rp($documentOnProcessHPPAmount) }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="text-xs leading-5 text-slate-700">Approval Process (HPP)</div>
                        <div class="mt-4 text-right text-sm font-semibold text-slate-900">{{ $rp($approvalProcessHPPAmount) }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="text-xs leading-5 text-slate-700">PR/PO On Process</div>
                        <div class="mt-4 text-right text-sm font-semibold text-slate-900">{{ $rp($documentOnProcessPOAmount) }}</div>
                    </div>
                </div>

                <div class="mt-4 flex justify-end gap-2 text-xs">
                    <span class="text-slate-500">Subtotal potensi</span>
                    <span class="font-bold text-slate-900">{{ $rp($totalAmount1) }}</span>
                </div>
            </article>

            <article class="rounded-[1.5rem] border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-5 flex items-center gap-2">
                    <i data-lucide="pie-chart" class="h-5 w-5 text-blue-500"></i>
                    <h3 class="text-[1.05rem] font-semibold text-slate-800">Realisasi Biaya (LPJ)</h3>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="text-xs leading-5 text-slate-700">Document PR/PO (LHPP)</div>
                        <div class="mt-4 text-right text-sm font-semibold text-slate-900">{{ $rp($documentPRPOAmount) }}</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="text-xs leading-5 text-slate-700">Pekerjaan Urgent</div>
                        <div class="mt-4 text-right text-sm font-semibold text-slate-900">{{ $rp($urgentAmount) }}</div>
                    </div>
                </div>

                <div class="mt-4 flex justify-end gap-2 text-xs">
                    <span class="text-slate-500">Subtotal realisasi</span>
                    <span class="font-bold text-slate-900">{{ $rp($totalAmount2) }}</span>
                </div>
            </article>

            <article class="rounded-[1.5rem] border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <i data-lucide="badge-info" class="h-4 w-4 text-slate-600"></i>
                        <h3 class="text-base font-semibold text-slate-800">Ringkasan Kuota Anggaran</h3>
                    </div>
                    <div class="text-right text-[11px] leading-5 text-slate-500">
                        <div class="font-semibold uppercase tracking-[0.12em] text-slate-500">Kuota Anggaran</div>
                        <div class="text-sm font-bold text-slate-900">Rp. {{ number_format($totalKuotaKontrak, 0, ',', '.') }}</div>
                        <div>
                            Periode:
                            {{ $periodeKontrak['start'] ? \Carbon\Carbon::parse($periodeKontrak['start'])->format('d M Y') : '-' }}
                            s/d
                            {{ $periodeKontrak['end'] ? \Carbon\Carbon::parse($periodeKontrak['end'])->format('d M Y') : '-' }}
                        </div>
                    </div>
                </div>

                <div class="space-y-4">
                    <div class="rounded-xl border border-blue-200 bg-blue-50 px-4 py-4">
                        <div class="text-lg font-bold text-blue-900">
                            Potensi Biaya + Realisasi Biaya:
                            <span class="text-slate-900">Rp. {{ number_format($totalSeluruhAmount, 0, ',', '.') }}</span>
                        </div>
                    </div>

                    @php
                        $kuotaKontrakActual = ($totalKuotaKontrak ?? 0) - ($totalSeluruhAmount ?? 0);
                        $totalBiayaPemeliharaan = $cleanNumber($targetPemeliharaan);
                    @endphp
                    <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-4">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.12em] text-sky-700">Kuota Anggaran Actual</div>
                        <div class="mt-1 text-2xl font-bold text-slate-900">Rp. {{ number_format($kuotaKontrakActual, 0, ',', '.') }}</div>
                        <div class="mt-1 text-xs leading-5 text-sky-700">
                            = Kuota Anggaran (Rp. {{ number_format($totalKuotaKontrak, 0, ',', '.') }}) - (Potensi + Realisasi) (Rp. {{ number_format($totalSeluruhAmount, 0, ',', '.') }})
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-4">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-700">Total Biaya Pemeliharaan</div>
                        <div class="mt-1 text-2xl font-bold text-slate-900">Rp. {{ number_format($totalBiayaPemeliharaan, 0, ',', '.') }}</div>
                        <div class="mt-3 grid gap-1 text-xs text-slate-500">
                            <div class="flex items-center justify-between gap-3">
                                <span>Total Jasa Pemeliharaan</span>
                                <span class="font-semibold text-slate-800">Rp. {{ number_format($totalJasaPemeliharaan, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span>Sisa Biaya Pemeliharaan</span>
                                <span class="font-semibold text-slate-800">Rp. {{ number_format($sisaBiayaPemeliharaan, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl border border-yellow-200 bg-yellow-50 px-4 py-4">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.12em] text-yellow-700">Sisa Kuota Kontrak</div>
                        <div class="mt-1 text-2xl font-bold text-yellow-900">Rp. {{ number_format($sisaKuotaKontrak, 0, ',', '.') }}</div>
                    </div>
                </div>
            </article>

            <article class="rounded-[1.5rem] border border-slate-200 bg-white p-6 shadow-sm">
                <div class="rounded-xl bg-emerald-100 px-4 py-3 text-center text-sm font-bold text-slate-900">
                    Total Realisasi Biaya: Rp {{ number_format($totalRealisasiBiaya, 0, ',', '.') }}
                </div>

                <div class="mt-5 space-y-4 text-sm text-slate-700">
                    <div>
                        <p class="mb-3 text-xs text-slate-500">Sortir per rentang tahun untuk menampilkan data realisasi biaya.</p>
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <label for="startYear" class="text-sm text-slate-600">Dari Tahun:</label>
                                <select id="startYear" class="w-36 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                                    <option value="" selected disabled>Pilih Tahun</option>
                                </select>
                            </div>
                            <span class="text-sm text-slate-600">sampai</span>
                            <div class="flex flex-wrap items-center gap-2">
                                <label for="endYear" class="text-sm text-slate-600">Sampai Tahun:</label>
                                <select id="endYear" class="w-36 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                                    <option value="" selected disabled>Pilih Tahun</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div>
                        <p class="mb-3 text-xs text-slate-500">Sortir per rentang bulan untuk menampilkan data realisasi biaya.</p>
                        <div class="flex flex-wrap items-center gap-3">
                            <div class="flex flex-wrap items-center gap-2">
                                <label for="startMonth" class="text-sm text-slate-600">Dari Bulan:</label>
                                <select id="startMonth" class="w-36 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                                    <option value="" selected disabled>Pilih Bulan</option>
                                </select>
                            </div>
                            <span class="text-sm text-slate-600">sampai</span>
                            <div class="flex flex-wrap items-center gap-2">
                                <label for="endMonth" class="text-sm text-slate-600">Sampai Bulan:</label>
                                <select id="endMonth" class="w-36 rounded-md border border-slate-300 bg-white px-3 py-2 text-sm focus:border-blue-500 focus:outline-none">
                                    <option value="" selected disabled>Pilih Bulan</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-start">
                        <button id="applyFilters" class="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                            Terapkan
                        </button>
                    </div>
                </div>

                <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="text-sm font-semibold text-slate-800">Grafik Realisasi Biaya</div>
                        <div id="chartTotal" class="text-xs font-bold text-slate-600">Rp 0</div>
                    </div>
                    <div class="mt-4 h-72">
                        <canvas id="realisasiBiayaPieChart" class="h-full w-full"></canvas>
                    </div>
                    <div id="chartEmptyState" class="hidden rounded-lg border border-dashed border-slate-300 bg-white px-4 py-6 text-center text-sm text-slate-500">
                        Belum ada data realisasi biaya pada rentang ini.
                    </div>
                    <div id="chartLegend" class="mt-4 grid gap-2 text-xs text-slate-700 md:grid-cols-2"></div>
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
            const initialChartData = @json($realizationChartData ?? []);
            const yearsEndpoint = @json(route('admin.dashboard.years'));
            const chartEndpoint = @json(route('admin.dashboard.realization-chart'));
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
            }

            function fetchData(startYear, endYear, startMonth = null, endMonth = null) {
                const queryParams = new URLSearchParams({
                    startYear,
                    endYear,
                    ...(startMonth && { startMonth }),
                    ...(endMonth && { endMonth })
                }).toString();

                fetch(`${chartEndpoint}?${queryParams}`)
                    .then(response => response.json())
                    .then(data => {
                        if (!Array.isArray(data)) throw new Error('Format data tidak valid.');
                        renderChart(data);
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
                const total = rows.reduce((sum, item) => sum + Number(item.total || 0), 0);

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
                                    display: true,
                                    position: 'bottom',
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

            function updateLegend(rows) {
                chartLegend.innerHTML = '';

                rows.forEach(item => {
                    chartLegend.innerHTML += `
                        <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                            <div class="flex items-center justify-between gap-2">
                                <span class="font-semibold text-slate-700">${item.label || `${monthNames[item.month] || item.month} ${item.year}`}</span>
                                <span class="font-bold text-slate-900">${formatRupiah(item.total || 0)}</span>
                            </div>
                            <div class="mt-2 grid gap-1 text-[11px] text-slate-500">
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

                if (startMonth && endMonth && parseInt(startMonth) > parseInt(endMonth)) {
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
