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
        $documentOnProcessHPPCount = $documentOnProcessHPPCount ?? 0;
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
            ],
            [
                'title' => 'Pending Process (Jasa)',
                'value' => $pendingProcessJasa,
                'icon' => 'hourglass',
                'wrap' => 'bg-[#ffca19]',
                'iconColor' => 'text-[#ab7700]',
                'valueColor' => 'text-[#ab7700]',
            ],
            [
                'title' => 'Document On Process (HPP)',
                'value' => $documentOnProcessHPPCount,
                'icon' => 'file-text',
                'wrap' => 'bg-[#9da6b2]',
                'iconColor' => 'text-[#31435e]',
                'valueColor' => 'text-[#25344d]',
            ],
            [
                'title' => 'Approval Process (HPP)',
                'value' => $approvalProcessHPPCount,
                'icon' => 'badge-check',
                'wrap' => 'bg-[#49d97a]',
                'iconColor' => 'text-[#0b8a57]',
                'valueColor' => 'text-[#0b7d4f]',
            ],
            [
                'title' => 'PR/PO Process (HPP Approved)',
                'value' => $documentOnProcessPOCount,
                'icon' => 'alert-circle',
                'wrap' => 'bg-[#fb6a6f]',
                'iconColor' => 'text-[#a71922]',
                'valueColor' => 'text-[#a71922]',
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
                    <a href="#" class="flex h-40 min-w-0 flex-col items-center justify-center rounded-2xl px-3 text-center shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $card['wrap'] }}">
                        <i data-lucide="{{ $card['icon'] }}" class="h-8 w-8 {{ $card['iconColor'] }}"></i>
                        <div class="mt-4 text-[13px] font-medium leading-5 text-slate-800">{{ $card['title'] }}</div>
                        <div class="mt-2 text-2xl font-bold {{ $card['valueColor'] }}">{{ $card['value'] }}</div>
                    </a>
                @endforeach
            </div>

            <div class="hidden gap-4 md:flex md:flex-nowrap">
                @foreach ($processCards as $card)
                    <a href="#" class="flex h-40 min-w-0 flex-1 flex-col items-center justify-center rounded-2xl px-3 text-center shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $card['wrap'] }}">
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
                        <div class="text-xs leading-5 text-slate-700">On Process PR/PO</div>
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
                    <span class="text-slate-500">Subtotal potensi</span>
                    <span class="font-bold text-slate-900">{{ $rp($totalAmount2) }}</span>
                </div>
            </article>

            <article class="rounded-[1.5rem] border border-slate-200 bg-white p-6 shadow-sm">
                <div class="mb-4 flex items-center gap-2">
                    <i data-lucide="badge-info" class="h-4 w-4 text-slate-600"></i>
                    <h3 class="text-base font-semibold text-slate-800">Ringkasan Kuota Anggaran</h3>
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
                    @endphp
                    <div class="rounded-xl border border-sky-200 bg-sky-50 px-4 py-4">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.12em] text-sky-700">Kuota Kontrak Actual</div>
                        <div class="mt-1 text-2xl font-bold text-slate-900">Rp. {{ number_format($kuotaKontrakActual, 0, ',', '.') }}</div>
                        <div class="mt-1 text-xs leading-5 text-sky-700">
                            = Total Kuota (Rp. {{ number_format($totalKuotaKontrak, 0, ',', '.') }}) - (Potensi + Realisasi) (Rp. {{ number_format($totalSeluruhAmount, 0, ',', '.') }})
                        </div>
                    </div>

                    <div class="rounded-xl border border-slate-200 bg-white px-4 py-4">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.12em] text-slate-700">Total Kuota Kontrak</div>
                        <div class="mt-1 text-2xl font-bold text-slate-900">Rp. {{ number_format($totalKuotaKontrak, 0, ',', '.') }}</div>
                        <div class="mt-1 text-xs text-slate-500">
                            Periode:
                            {{ $periodeKontrak['start'] ? \Carbon\Carbon::parse($periodeKontrak['start'])->format('d M Y') : '-' }}
                            s/d
                            {{ $periodeKontrak['end'] ? \Carbon\Carbon::parse($periodeKontrak['end'])->format('d M Y') : '-' }}
                            @if (!empty($periodeKontrak['adendum']))
                                <span>, adendum s/d {{ \Carbon\Carbon::parse($periodeKontrak['adendum'])->format('d M Y') }}</span>
                            @endif
                        </div>
                    </div>

                    @if (!is_null($targetPemeliharaan))
                        @php
                            $isArrayTarget = is_array($targetPemeliharaan);
                            if ($isArrayTarget) {
                                $totalTargetInt = 0;
                                foreach ($targetPemeliharaan as $x) {
                                    $totalTargetInt += $cleanNumber($x);
                                }
                            } else {
                                $totalTargetInt = $cleanNumber($targetPemeliharaan);
                            }
                        @endphp
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-4">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.12em] text-emerald-700">Target Biaya Pemeliharaan</div>
                            <div class="mt-1 text-2xl font-bold text-slate-900">{{ $rp($totalTargetInt) }}</div>

                            @if ($isArrayTarget)
                                @php $years = $latestKuotaAnggaran->tahun ?? null; @endphp
                                @if (is_array($years) && count($years) === count($targetPemeliharaan))
                                    <div class="mt-2 grid gap-1 text-xs text-emerald-800">
                                        @foreach ($targetPemeliharaan as $i => $val)
                                            <div>{{ $years[$i] }}: {{ $rp($val) }}</div>
                                        @endforeach
                                    </div>
                                @endif
                            @endif
                        </div>
                    @endif

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

                <div class="mt-6 flex flex-col gap-5 lg:flex-row lg:items-center">
                    <canvas id="realisasiBiayaPieChart" class="max-h-[180px] max-w-[180px]"></canvas>
                    <div id="chartLegend" class="grid flex-1 gap-2 text-xs text-slate-700"></div>
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

            function fetchYears() {
                fetch('/admin/get-years')
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
                }
            }

            function fetchData(startYear, endYear, startMonth = null, endMonth = null) {
                const queryParams = new URLSearchParams({
                    startYear,
                    endYear,
                    ...(startMonth && { startMonth }),
                    ...(endMonth && { endMonth })
                }).toString();

                fetch(`/admin/realisasi-biaya?${queryParams}`)
                    .then(response => response.json())
                    .then(data => {
                        if (!Array.isArray(data)) throw new Error('Format data tidak valid.');

                        const labels = data.map(item => `${item.year}-${item.month || 'N/A'}`);
                        const values = data.map(item => item.total);

                        const ctx = document.getElementById('realisasiBiayaPieChart');
                        if (window.realisasiBiayaChart) window.realisasiBiayaChart.destroy();
                        window.realisasiBiayaChart = new Chart(ctx, {
                            type: 'pie',
                            data: {
                                labels,
                                datasets: [{
                                    data: values,
                                    backgroundColor: ['#4CAF50', '#2196F3', '#FF5722', '#FFC107']
                                }]
                            },
                            options: {
                                responsive: true,
                                plugins: {
                                    tooltip: {
                                        callbacks: {
                                            label: function (context) {
                                                return `${context.label}: Rp ${context.raw.toLocaleString('id-ID')}`;
                                            }
                                        }
                                    }
                                }
                            }
                        });

                        updateLegend(labels, values);
                    })
                    .catch(error => {
                        console.error('Error saat memproses data:', error);
                        alert('Terjadi kesalahan saat mengambil data.');
                    });
            }

            function updateLegend(labels, values) {
                const legend = document.getElementById('chartLegend');
                legend.innerHTML = '';
                labels.forEach((label, index) => {
                    legend.innerHTML += `
                        <div class="grid grid-cols-[12px_1fr_auto] items-center gap-2 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                            <span class="h-2.5 w-2.5 rounded-full" style="background-color: ${window.realisasiBiayaChart.data.datasets[0].backgroundColor[index]};"></span>
                            <span>${label}</span>
                            <span class="font-semibold">Rp ${values[index].toLocaleString('id-ID')}</span>
                        </div>`;
                });
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
