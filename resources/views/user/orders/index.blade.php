<x-layouts.user>
    @php
        $summaryCards = [
            [
                'label' => 'Total Order',
                'value' => $stats['total_orders'],
                'icon' => 'clipboard-list',
                'iconClass' => 'bg-red-50 text-red-700 ring-red-100',
                'accent' => 'bg-red-700',
                'breakdown' => [
                    ['label' => 'Bengkel', 'value' => $stats['workshop_orders']],
                    ['label' => 'Jasa', 'value' => $stats['service_orders']],
                ],
            ],
            [
                'label' => 'Order Selesai',
                'value' => $stats['completed_orders'],
                'icon' => 'circle-check-big',
                'iconClass' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
                'accent' => 'bg-emerald-600',
                'breakdown' => [
                    ['label' => 'Bengkel', 'value' => $stats['completed_workshop_orders']],
                    ['label' => 'Jasa', 'value' => $stats['completed_service_orders']],
                ],
            ],
            [
                'label' => 'Emergency',
                'value' => $stats['emergency_orders'],
                'icon' => 'siren',
                'iconClass' => 'bg-rose-50 text-rose-700 ring-rose-100',
                'accent' => 'bg-rose-600',
            ],
            [
                'label' => 'PO Jasa',
                'value' => $stats['po_ready'],
                'icon' => 'file-check-2',
                'iconClass' => 'bg-amber-50 text-amber-700 ring-amber-100',
                'accent' => 'bg-amber-500',
            ],
            [
                'label' => 'BAST Jasa',
                'value' => $stats['bast_ready'],
                'icon' => 'badge-check',
                'iconClass' => 'bg-sky-50 text-sky-700 ring-sky-100',
                'accent' => 'bg-sky-600',
            ],
        ];

        $approvedLabels = $chartApproved['labels'] ?? [];
        $approvedValues = array_map(static fn ($value) => (float) $value, $chartApproved['values'] ?? []);
        $approvedMax = max($approvedValues ?: [0]);
        $approvedRows = collect($approvedLabels)->map(fn ($label, $index) => [
            'label' => (string) $label,
            'value' => (float) ($approvedValues[$index] ?? 0),
        ])->values();
        $hasApprovedData = $approvedRows->contains(fn ($row) => $row['value'] > 0);

        $biayaLabels = $chartBiaya['labels'] ?? [];
        $biayaValues = array_map(static fn ($value) => (float) $value, $chartBiaya['values'] ?? []);
        $biayaMax = max($biayaValues ?: [0]);
        $biayaRows = collect($biayaLabels)->map(fn ($label, $index) => [
            'label' => (string) $label,
            'value' => (float) ($biayaValues[$index] ?? 0),
        ])->values();
        $hasBiayaData = $biayaRows->contains(fn ($row) => $row['value'] > 0);

    @endphp

    <div
        class="user-dashboard -mt-5 space-y-4 sm:-mt-6 lg:-mt-6"
        data-user-dashboard
        x-data="{ filterOpen: window.innerWidth >= 768, isDesktop: window.innerWidth >= 768 }"
        x-init="filterOpen = window.innerWidth >= 768; isDesktop = window.innerWidth >= 768"
        @resize.window="isDesktop = window.innerWidth >= 768; if (isDesktop) filterOpen = true"
    >
        <button
            type="button"
            class="fixed right-4 top-[5.75rem] z-50 inline-flex h-10 items-center gap-2 rounded-full border border-red-700 bg-red-800 px-3.5 text-sm font-black text-white shadow-xl shadow-red-950/20 transition hover:bg-red-900 focus:outline-none focus:ring-4 focus:ring-red-100 md:hidden"
            @click="filterOpen = !filterOpen"
            :aria-expanded="filterOpen.toString()"
            aria-controls="dashboard-filter-panel"
        >
            <i x-show="!filterOpen" data-lucide="sliders-horizontal" class="h-4 w-4"></i>
            <i x-show="filterOpen" data-lucide="x" class="h-4 w-4"></i>
            <span x-text="filterOpen ? 'Tutup' : 'Filter'"></span>
        </button>

        <section
            id="dashboard-filter-panel"
            class="dashboard-premium-card fixed left-3 right-3 top-[8.75rem] z-40 max-h-[58vh] overflow-y-auto rounded-2xl p-2.5 shadow-2xl shadow-slate-900/20 backdrop-blur md:sticky md:left-auto md:right-auto md:top-0 md:z-20 md:mt-4 md:max-h-none md:overflow-visible md:shadow-lg md:shadow-slate-900/5"
            x-show="filterOpen || isDesktop"
            x-transition.opacity.duration.150ms
            x-cloak
        >
            <form method="GET" action="{{ route('user.dashboard') }}" class="grid grid-cols-2 gap-2 md:gap-3 lg:grid-cols-[minmax(220px,1.6fr)_minmax(160px,0.9fr)_minmax(130px,0.6fr)_minmax(110px,0.45fr)_auto] lg:items-end" data-dashboard-filter-form>
                <div class="col-span-2 space-y-1 md:space-y-1.5 lg:col-span-1">
                    <label for="notification_number" class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-500">Order / Notifikasi</label>
                    <input
                        id="notification_number"
                        type="text"
                        name="notification_number"
                        value="{{ $filters['notification_number'] }}"
                        placeholder="Cari nomor order / notifikasi..."
                        class="h-9 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-800 placeholder:text-slate-400 shadow-sm transition focus:border-red-300 focus:outline-none focus:ring-4 focus:ring-red-100 md:h-10 md:px-3.5"
                    >
                </div>

                <div class="col-span-2 space-y-1 md:space-y-1.5 lg:col-span-1">
                    <label for="unit_work" class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-500">Unit</label>
                    <select id="unit_work" name="unit_work" class="h-9 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-800 shadow-sm transition focus:border-red-300 focus:outline-none focus:ring-4 focus:ring-red-100 md:h-10 md:px-3.5">
                        <option value="">Semua Unit</option>
                        @foreach ($units as $u)
                            <option value="{{ $u }}" @selected($filters['unit_work'] === $u)>{{ $u }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1 md:space-y-1.5">
                    <label for="sortOrder" class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-500">Sortir</label>
                    <select id="sortOrder" name="sortOrder" class="h-9 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-800 shadow-sm transition focus:border-red-300 focus:outline-none focus:ring-4 focus:ring-red-100 md:h-10 md:px-3.5">
                        <option value="latest" @selected($filters['sortOrder'] === 'latest')>Terbaru</option>
                        <option value="oldest" @selected($filters['sortOrder'] === 'oldest')>Terlama</option>
                    </select>
                </div>

                <div class="space-y-1 md:space-y-1.5">
                    <label for="entries" class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-500">Limit</label>
                    <select id="entries" name="entries" class="h-9 w-full rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-800 shadow-sm transition focus:border-red-300 focus:outline-none focus:ring-4 focus:ring-red-100 md:h-10 md:px-3.5">
                        @foreach ([10, 25, 50, 100] as $n)
                            <option value="{{ $n }}" @selected((int) $filters['entries'] === $n)>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-span-2 flex gap-2 lg:col-span-1">
                    <button type="submit" class="inline-flex h-9 flex-1 items-center justify-center gap-2 rounded-xl border border-red-800 bg-red-800 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-red-900 focus:outline-none focus:ring-4 focus:ring-red-100 md:h-10 lg:flex-none" aria-label="Terapkan filter">
                        <i data-lucide="filter" class="h-4 w-4"></i>
                        <span class="lg:hidden xl:inline">Filter</span>
                    </button>
                    <a href="{{ route('user.dashboard') }}" class="inline-flex h-9 flex-1 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-600 shadow-sm transition hover:border-red-200 hover:text-red-800 focus:outline-none focus:ring-4 focus:ring-red-100 md:h-10 lg:flex-none" aria-label="Reset filter" data-dashboard-reset>
                        <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                        <span class="lg:hidden xl:inline">Reset</span>
                    </a>
                </div>
            </form>
        </section>

        <section class="dashboard-content grid items-stretch gap-3 sm:grid-cols-2 xl:grid-cols-[1.18fr_1.18fr_0.88fr_0.88fr_0.88fr]">
            @foreach ($summaryCards as $card)
                <article class="dashboard-soft-card group flex min-h-[112px] items-center rounded-[1.15rem] px-4 py-3.5 transition hover:-translate-y-0.5 hover:border-red-100 hover:shadow-lg">
                    <div class="flex w-full items-center justify-between gap-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ring-1 {{ $card['iconClass'] }}">
                                <i data-lucide="{{ $card['icon'] }}" class="h-5 w-5"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="truncate text-[11px] font-black uppercase tracking-[0.14em] text-slate-500">{{ $card['label'] }}</div>
                                <div class="mt-1 text-[2rem] font-black leading-none tracking-tight text-slate-950" data-count-up data-count-value="{{ $card['value'] }}">{{ $card['value'] }}</div>
                            </div>
                        </div>

                        @if (isset($card['breakdown']))
                            <div class="grid shrink-0 gap-2 border-l border-slate-100 pl-4">
                                @foreach ($card['breakdown'] as $item)
                                    <div class="min-w-[4.25rem]">
                                        <div class="truncate text-[10px] font-black uppercase tracking-[0.12em] text-slate-400">{{ $item['label'] }}</div>
                                        <div class="mt-0.5 text-lg font-black leading-none text-slate-900" data-count-up data-count-value="{{ $item['value'] }}">{{ $item['value'] }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </article>
            @endforeach
        </section>

        <section class="dashboard-content grid gap-4 xl:grid-cols-2">
            <article class="dashboard-soft-card rounded-[1.15rem] p-3.5 sm:p-4">
                <div class="mb-3 flex items-start justify-between gap-4">
                    <div>
                        <h2 class="flex items-center gap-2 text-base font-black text-slate-950">
                            <i data-lucide="line-chart" class="h-5 w-5 text-red-700"></i>
                            Top 10 Unit Kerja - Approved
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">Jumlah order yang sudah approved</p>
                    </div>
                </div>

                @if ($hasApprovedData)
                    <div class="relative h-[12.5rem]" role="img" aria-label="Top 10 Unit Kerja Approved">
                        <canvas id="chartApproved" class="h-full w-full"></canvas>
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-center">
                        <div class="mx-auto mb-3 grid max-w-sm gap-2">
                            @foreach ([70, 88, 54, 76] as $width)
                                <div class="dashboard-skeleton-block h-3" style="width: {{ $width }}%"></div>
                            @endforeach
                        </div>
                        <p class="text-sm font-semibold text-slate-700">Belum ada data approved yang dapat ditampilkan.</p>
                    </div>
                @endif
            </article>

            <article class="dashboard-soft-card rounded-[1.15rem] p-3.5 sm:p-4">
                <div class="mb-3 flex items-start justify-between gap-4">
                    <div>
                        <h2 class="flex items-center gap-2 text-base font-black text-slate-950">
                            <i data-lucide="badge-dollar-sign" class="h-5 w-5 text-red-700"></i>
                            Top 10 Unit Kerja - Total Biaya LHPP
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">Akumulasi total biaya pekerjaan</p>
                    </div>
                </div>

                @if ($hasBiayaData)
                    <div class="relative h-[12.5rem]" role="img" aria-label="Top 10 Unit Kerja Total Biaya LHPP">
                        <canvas id="chartBiaya" class="h-full w-full"></canvas>
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-5 text-center">
                        <div class="mx-auto mb-3 grid max-w-sm gap-2">
                            @foreach ([82, 62, 76, 48] as $width)
                                <div class="dashboard-skeleton-block h-3" style="width: {{ $width }}%"></div>
                            @endforeach
                        </div>
                        <p class="text-sm font-semibold text-slate-700">Belum ada data biaya LHPP yang dapat ditampilkan.</p>
                    </div>
                @endif
            </article>
        </section>

        <section class="dashboard-content">
            @if ($orders->count() > 0)
                <div class="hidden overflow-hidden border border-slate-200 bg-white shadow-sm md:block">
                    <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                        <thead class="bg-[#7f1017] text-[11px] font-extrabold uppercase tracking-[0.1em] text-red-50">
                            <tr>
                                <th class="px-4 py-2.5">Order / Notif</th>
                                <th class="px-4 py-2.5">Nama Pekerjaan</th>
                                <th class="px-4 py-2.5">Unit</th>
                                <th class="px-4 py-2.5">Seksi</th>
                                <th class="px-4 py-2.5">Tanggal</th>
                                <th class="px-4 py-2.5">Prioritas</th>
                                <th class="px-4 py-2.5 text-right">
                                    <span class="sr-only">Lihat Detail</span>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($orders as $order)
                                <tr
                                    class="{{ $order['is_completed'] ? 'bg-emerald-50/45 hover:bg-emerald-50/70' : 'bg-white hover:bg-slate-50/80' }} cursor-pointer transition focus-within:bg-red-50/40"
                                    role="link"
                                    tabindex="0"
                                    title="Lihat detail {{ $order['nomor_order'] }}"
                                    onclick="window.location.href = @js($order['show_url'])"
                                    onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.location.href = @js($order['show_url']); }"
                                >
                                    <td class="whitespace-nowrap px-4 py-4 align-top">
                                        <div class="max-w-[160px] truncate font-black tracking-tight text-slate-950">{{ $order['nomor_order'] }}</div>
                                        <div class="mt-1 max-w-[160px] truncate text-xs text-slate-500">Notif: {{ $order['notifikasi'] ?: '-' }}</div>
                                    </td>
                                    <td class="px-4 py-4 align-top">
                                        <div class="max-w-md line-clamp-2 font-bold leading-5 text-slate-900">{{ $order['nama_pekerjaan'] }}</div>
                                    </td>
                                    <td class="px-4 py-4 align-top text-slate-600">{{ $order['unit_kerja'] ?: '-' }}</td>
                                    <td class="px-4 py-4 align-top text-slate-600">{{ $order['seksi'] ?: '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 align-top text-slate-600">{{ $order['tanggal_order'] ?: '-' }}</td>
                                    <td class="px-4 py-4 align-top">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-black ring-1 {{ $order['prioritas_badge_classes'] }}">
                                            {{ $order['prioritas_label'] }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right align-top">
                                        <a href="{{ $order['show_url'] }}" class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-red-800 bg-red-800 text-white shadow-sm transition hover:bg-red-900 focus:outline-none focus:ring-4 focus:ring-red-100" title="Lihat Detail" aria-label="Lihat Detail {{ $order['nomor_order'] }}" onclick="event.stopPropagation();">
                                            <i data-lucide="arrow-up-right" class="h-4 w-4"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="space-y-3 md:hidden">
                    @foreach ($orders as $order)
                        <article
                            class="dashboard-soft-card cursor-pointer rounded-xl p-4 transition hover:border-red-100 {{ $order['is_completed'] ? 'bg-emerald-50/60' : 'bg-white' }}"
                            role="link"
                            tabindex="0"
                            title="Lihat detail {{ $order['nomor_order'] }}"
                            onclick="window.location.href = @js($order['show_url'])"
                            onkeydown="if (event.key === 'Enter' || event.key === ' ') { event.preventDefault(); window.location.href = @js($order['show_url']); }"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="text-[10px] font-black uppercase tracking-[0.16em] text-slate-400">Order / Notif</div>
                                    <div class="mt-1 truncate text-lg font-black tracking-tight text-slate-950">{{ $order['nomor_order'] }}</div>
                                    <div class="mt-1 truncate text-xs text-slate-500">Notif: {{ $order['notifikasi'] ?: '-' }}</div>
                                </div>
                                <span class="inline-flex shrink-0 items-center rounded-full px-2.5 py-1 text-[10px] font-black ring-1 {{ $order['prioritas_badge_classes'] }}">
                                    {{ $order['prioritas_label'] }}
                                </span>
                            </div>

                            <div class="mt-3 rounded-xl border border-slate-200 bg-white/85 p-3">
                                <div class="text-[10px] font-black uppercase tracking-[0.14em] text-slate-400">Nama Pekerjaan</div>
                                <div class="mt-1.5 line-clamp-2 text-sm font-black leading-5 text-slate-900">{{ $order['nama_pekerjaan'] }}</div>
                                <div class="mt-3 grid gap-1.5 text-xs leading-5 text-slate-600">
                                    <div><span class="font-bold text-slate-500">Unit:</span> {{ $order['unit_kerja'] ?: '-' }}</div>
                                    <div><span class="font-bold text-slate-500">Seksi:</span> {{ $order['seksi'] ?: '-' }}</div>
                                    <div><span class="font-bold text-slate-500">Tanggal:</span> {{ $order['tanggal_order'] ?: '-' }}</div>
                                </div>
                            </div>

                            <a href="{{ $order['show_url'] }}" class="mt-3 inline-flex h-10 w-10 items-center justify-center rounded-lg border border-red-800 bg-red-800 text-white shadow-sm transition hover:bg-red-900 focus:outline-none focus:ring-4 focus:ring-red-100" title="Lihat Detail" aria-label="Lihat Detail {{ $order['nomor_order'] }}" onclick="event.stopPropagation();">
                                <i data-lucide="arrow-up-right" class="h-4 w-4"></i>
                            </a>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="rounded-[1.35rem] border border-dashed border-slate-300 bg-white px-6 py-14 text-center shadow-sm">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400">
                        <i data-lucide="folder-search-2" class="h-6 w-6"></i>
                    </div>
                    <h2 class="mt-4 text-lg font-black text-slate-950">Belum ada order yang tersedia.</h2>
                    <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-slate-500">Saat data order sudah tersedia di sistem, seluruh progress dan dokumennya akan muncul otomatis di dashboard ini.</p>
                </div>
            @endif
        </section>

        @if ($orders->hasPages())
            <div class="dashboard-content rounded-[1.35rem] border border-slate-200 bg-white px-4 py-3 shadow-sm">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

    @if ($hasApprovedData || $hasBiayaData)
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const approvedLabels = @json($approvedLabels);
                const approvedValues = @json($approvedValues);
                const biayaLabels = @json($biayaLabels);
                const biayaValues = @json($biayaValues);

                const createLineChart = (canvasId, labels, values, options = {}) => {
                    const canvas = document.getElementById(canvasId);

                    if (! canvas || ! values.some((value) => Number(value) > 0)) {
                        return;
                    }

                    new Chart(canvas, {
                        type: 'line',
                        data: {
                            labels,
                            datasets: [{
                                data: values,
                                borderColor: options.color || '#b91c1c',
                                backgroundColor: 'rgba(185, 28, 28, 0.08)',
                                borderWidth: 4,
                                tension: 0.38,
                                fill: false,
                                pointRadius: 5,
                                pointHoverRadius: 7,
                                pointBorderWidth: 4,
                                pointBackgroundColor: '#ffffff',
                                pointBorderColor: options.color || '#b91c1c',
                                pointHitRadius: 18,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            animation: {
                                duration: 1050,
                                easing: 'easeOutQuart',
                            },
                            layout: {
                                padding: {
                                    top: 10,
                                    right: 12,
                                    bottom: 6,
                                    left: 12,
                                },
                            },
                            interaction: {
                                intersect: false,
                                mode: 'nearest',
                            },
                            plugins: {
                                legend: {
                                    display: false,
                                },
                                tooltip: {
                                    backgroundColor: 'rgba(15, 23, 42, 0.94)',
                                    titleColor: '#ffffff',
                                    bodyColor: '#f8fafc',
                                    displayColors: false,
                                    padding: 12,
                                    cornerRadius: 12,
                                    callbacks: {
                                        title(items) {
                                            return items[0]?.label || '';
                                        },
                                        label(context) {
                                            const value = context.parsed.y || 0;

                                            return options.format === 'currency'
                                                ? `Total biaya: Rp ${Number(value).toLocaleString('id-ID')}`
                                                : `${Number(value).toLocaleString('id-ID')} order approved`;
                                        },
                                    },
                                },
                            },
                            scales: {
                                x: {
                                    display: false,
                                    grid: {
                                        display: false,
                                        drawBorder: false,
                                    },
                                    border: {
                                        display: false,
                                    },
                                },
                                y: {
                                    display: false,
                                    beginAtZero: true,
                                    suggestedMax: Math.max(...values.map(Number), 1) * 1.18,
                                    grid: {
                                        display: false,
                                        drawBorder: false,
                                    },
                                    border: {
                                        display: false,
                                    },
                                },
                            },
                        },
                    });
                };

                createLineChart('chartApproved', approvedLabels, approvedValues, {
                    color: '#b91c1c',
                });
                createLineChart('chartBiaya', biayaLabels, biayaValues, {
                    color: '#991b1b',
                    format: 'currency',
                });
            });
        </script>
    @endif
</x-layouts.user>
