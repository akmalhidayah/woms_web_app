<x-layouts.user>
    @php
        $toneClasses = [
            'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            'blue' => 'bg-red-50 text-red-700 ring-red-200',
            'amber' => 'bg-amber-50 text-amber-700 ring-amber-200',
            'rose' => 'bg-rose-50 text-rose-700 ring-rose-200',
            'slate' => 'bg-stone-50 text-stone-700 ring-stone-200',
        ];

        $cardAccentClasses = [
            'emerald' => 'border-l-4 border-l-emerald-400',
            'blue' => 'border-l-4 border-l-red-400',
            'amber' => 'border-l-4 border-l-amber-400',
            'rose' => 'border-l-4 border-l-rose-400',
            'slate' => 'border-l-4 border-l-stone-300',
        ];

        $summaryCards = [
            [
                'label' => 'Total Order',
                'value' => $stats['total_orders'],
                'icon' => 'clipboard-list',
                'iconClass' => 'bg-sky-50 text-sky-700 ring-sky-100',
                'breakdown' => [
                    [
                        'label' => 'Order Bengkel',
                        'value' => $stats['workshop_orders'],
                    ],
                    [
                        'label' => 'Order Jasa',
                        'value' => $stats['service_orders'],
                    ],
                ],
            ],
            [
                'label' => 'Order Selesai',
                'value' => $stats['completed_orders'],
                'icon' => 'circle-check-big',
                'iconClass' => 'bg-violet-50 text-violet-700 ring-violet-100',
                'breakdown' => [
                    [
                        'label' => 'Bengkel',
                        'value' => $stats['completed_workshop_orders'],
                    ],
                    [
                        'label' => 'Jasa',
                        'value' => $stats['completed_service_orders'],
                    ],
                ],
            ],
            [
                'label' => 'Emergency',
                'value' => $stats['emergency_orders'],
                'icon' => 'siren',
                'iconClass' => 'bg-rose-50 text-rose-700 ring-rose-100',
            ],
            [
                'label' => 'PO Order Jasa',
                'value' => $stats['po_ready'],
                'icon' => 'file-check-2',
                'iconClass' => 'bg-amber-50 text-amber-700 ring-amber-100',
            ],
            [
                'label' => 'BAST Order Jasa',
                'value' => $stats['bast_ready'],
                'icon' => 'badge-check',
                'iconClass' => 'bg-emerald-50 text-emerald-700 ring-emerald-100',
            ],
        ];
    @endphp

    <div class="space-y-4">
        <section class="sticky top-[68px] z-20 rounded-2xl border border-stone-200 bg-white p-2.5 shadow-sm">
            <div class="overflow-x-auto">
                <form method="GET" action="{{ route('user.dashboard') }}" class="flex min-w-[760px] items-center gap-2">
                    <input
                        type="text"
                        name="notification_number"
                        value="{{ $filters['notification_number'] }}"
                        placeholder="Cari nomor order / notifikasi..."
                        class="h-10 min-w-0 flex-1 rounded-xl border border-stone-200 bg-white px-3 text-sm text-stone-700 placeholder:text-stone-400 focus:border-red-300 focus:outline-none focus:ring-2 focus:ring-red-100"
                    >

                    <select name="unit_work" class="h-10 w-[180px] rounded-xl border border-stone-200 bg-white px-3 text-sm text-stone-700 focus:border-red-300 focus:outline-none focus:ring-2 focus:ring-red-100">
                        <option value="">Semua Unit</option>
                        @foreach ($units as $u)
                            <option value="{{ $u }}" @selected($filters['unit_work'] === $u)>{{ $u }}</option>
                        @endforeach
                    </select>

                    <select name="sortOrder" class="h-10 w-[140px] rounded-xl border border-stone-200 bg-white px-3 text-sm text-stone-700 focus:border-red-300 focus:outline-none focus:ring-2 focus:ring-red-100">
                        <option value="latest" @selected($filters['sortOrder'] === 'latest')>Terbaru</option>
                        <option value="oldest" @selected($filters['sortOrder'] === 'oldest')>Terlama</option>
                    </select>

                    <select name="entries" class="h-10 w-[90px] rounded-xl border border-stone-200 bg-white px-3 text-sm text-stone-700 focus:border-red-300 focus:outline-none focus:ring-2 focus:ring-red-100">
                        @foreach ([10, 25, 50, 100] as $n)
                            <option value="{{ $n }}" @selected((int) $filters['entries'] === $n)>{{ $n }}</option>
                        @endforeach
                    </select>

                    <button type="submit" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-red-800 bg-red-800 text-white transition hover:bg-red-900" title="Terapkan filter" aria-label="Terapkan filter">
                        <i data-lucide="filter" class="h-4 w-4"></i>
                    </button>
                    <a href="{{ route('user.dashboard') }}" class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-stone-200 bg-white text-stone-600 transition hover:border-red-200 hover:text-red-800" title="Reset filter" aria-label="Reset filter">
                        <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                    </a>
                </form>
            </div>
        </section>

        <section class="grid gap-2.5 sm:grid-cols-2 xl:grid-cols-[1.22fr_1.22fr_0.82fr_0.82fr_0.82fr]">
            @foreach ($summaryCards as $card)
                <article class="flex min-h-[76px] items-center justify-between gap-3 rounded-2xl border border-stone-200 bg-white px-3 py-2.5 shadow-sm">
                    <div class="min-w-0">
                        <div class="text-[9px] font-bold uppercase tracking-[0.16em] text-stone-500">{{ $card['label'] }}</div>
                        <div class="mt-1 text-xl font-black leading-none text-stone-900">{{ $card['value'] }}</div>
                    </div>

                    @if (isset($card['breakdown']))
                        <div class="ml-auto flex min-w-0 flex-1 items-center justify-end gap-3">
                            @foreach ($card['breakdown'] as $item)
                                <div class="min-w-0 border-l border-stone-100 pl-3 text-right first:border-l-0 first:pl-0">
                                    <div class="text-[8px] font-bold uppercase leading-tight tracking-[0.1em] text-stone-400">{{ $item['label'] }}</div>
                                    <div class="mt-1 text-sm font-black leading-none text-stone-900">{{ $item['value'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ring-1 {{ $card['iconClass'] }}">
                        <i data-lucide="{{ $card['icon'] }}" class="h-5 w-5"></i>
                    </div>
                </article>
            @endforeach
        </section>

        <section class="grid gap-3 xl:grid-cols-2">
            <div class="rounded-2xl border border-stone-200 bg-white p-3.5 shadow-sm">
                <div class="mb-2.5 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="flex items-center gap-2 text-sm font-semibold text-stone-900">
                            <i data-lucide="line-chart" class="h-4 w-4 text-red-700"></i>
                            Top 10 Unit Kerja - Approved
                        </h3>
                        <p class="mt-1 text-[11px] text-stone-500">Jumlah order yang sudah approved</p>
                    </div>
                </div>
                <div class="relative h-48">
                    <canvas id="chartNotifikasi"></canvas>
                </div>
            </div>

            <div class="rounded-2xl border border-stone-200 bg-white p-3.5 shadow-sm">
                <div class="mb-2.5 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="flex items-center gap-2 text-sm font-semibold text-stone-900">
                            <i data-lucide="badge-dollar-sign" class="h-4 w-4 text-red-700"></i>
                            Top 10 Unit Kerja - Total Biaya LHPP
                        </h3>
                        <p class="mt-1 text-[11px] text-stone-500">Akumulasi total biaya pekerjaan</p>
                    </div>
                </div>
                <div class="relative h-48">
                    <canvas id="chartBiaya"></canvas>
                </div>
            </div>
        </section>

        <section>
            @if ($orders->count() > 0)
                <div class="hidden overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm md:block">
                    <table class="min-w-full divide-y divide-stone-100 text-left text-xs">
                        <thead class="bg-stone-50 text-[10px] font-bold uppercase tracking-[0.18em] text-stone-500">
                            <tr>
                                <th class="px-3 py-2.5">Order / Notif</th>
                                <th class="px-3 py-2.5">Nama Pekerjaan</th>
                                <th class="px-3 py-2.5">Unit</th>
                                <th class="px-3 py-2.5">Seksi</th>
                                <th class="px-3 py-2.5">Tanggal</th>
                                <th class="px-3 py-2.5">Prioritas</th>
                                <th class="px-3 py-2.5 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-stone-100">
                            @foreach ($orders as $order)
                                <tr class="{{ $order['is_completed'] ? 'bg-emerald-50/70 hover:bg-emerald-50' : 'bg-white hover:bg-stone-50' }} transition">
                                    <td class="whitespace-nowrap px-3 py-2.5 align-top">
                                        <div class="max-w-[150px] truncate text-sm font-black tracking-tight text-stone-900">{{ $order['nomor_order'] }}</div>
                                        <div class="mt-0.5 max-w-[150px] truncate text-[11px] text-stone-500">Notif: {{ $order['notifikasi'] ?: '-' }}</div>
                                    </td>
                                    <td class="px-3 py-2.5 align-top">
                                        <div class="max-w-md line-clamp-2 text-xs font-bold leading-5 text-stone-900">{{ $order['nama_pekerjaan'] }}</div>
                                    </td>
                                    <td class="px-3 py-2.5 align-top text-stone-600">{{ $order['unit_kerja'] ?: '-' }}</td>
                                    <td class="px-3 py-2.5 align-top text-stone-600">{{ $order['seksi'] ?: '-' }}</td>
                                    <td class="whitespace-nowrap px-3 py-2.5 align-top text-stone-600">{{ $order['tanggal_order'] ?: '-' }}</td>
                                    <td class="px-3 py-2.5 align-top">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold ring-1 {{ $order['prioritas_badge_classes'] }}">
                                            {{ $order['prioritas_label'] }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-2.5 text-right align-top">
                                        <a href="{{ $order['show_url'] }}" class="inline-flex items-center justify-center gap-1.5 rounded-lg border border-red-800 bg-red-800 px-3 py-1.5 text-[11px] font-semibold text-white transition hover:bg-red-900">
                                            <i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i>
                                            Lihat Detail
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="space-y-2.5 md:hidden">
                    @foreach ($orders as $order)
                        <article class="rounded-2xl border p-3 shadow-sm {{ $order['is_completed'] ? 'border-emerald-200 bg-emerald-50/60' : 'border-stone-200 bg-white' }}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="text-[9px] font-bold uppercase tracking-[0.18em] text-stone-400">Order / Notif</div>
                                    <div class="mt-1 truncate text-base font-black tracking-tight text-stone-900">{{ $order['nomor_order'] }}</div>
                                    <div class="mt-0.5 truncate text-[11px] text-stone-500">Notif: {{ $order['notifikasi'] ?: '-' }}</div>
                                </div>
                                <span class="inline-flex shrink-0 items-center rounded-full px-2 py-0.5 text-[9px] font-bold ring-1 {{ $order['prioritas_badge_classes'] }}">
                                    {{ $order['prioritas_label'] }}
                                </span>
                            </div>

                            <div class="mt-2.5 rounded-xl border border-stone-200 bg-white/85 p-2.5">
                                <div class="text-[9px] font-bold uppercase tracking-[0.16em] text-stone-400">Nama Pekerjaan</div>
                                <div class="mt-1 line-clamp-2 text-xs font-black leading-5 text-stone-900">{{ $order['nama_pekerjaan'] }}</div>
                                <div class="mt-2 grid gap-1 text-[11px] leading-4 text-stone-600">
                                    <div><span class="font-semibold text-stone-500">Unit:</span> {{ $order['unit_kerja'] ?: '-' }}</div>
                                    <div><span class="font-semibold text-stone-500">Seksi:</span> {{ $order['seksi'] ?: '-' }}</div>
                                    <div><span class="font-semibold text-stone-500">Tanggal:</span> {{ $order['tanggal_order'] ?: '-' }}</div>
                                </div>
                            </div>

                            <a href="{{ $order['show_url'] }}" class="mt-2.5 inline-flex w-full items-center justify-center gap-1.5 rounded-xl border border-red-800 bg-red-800 px-3 py-2 text-[11px] font-semibold text-white transition hover:bg-red-900">
                                <i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i>
                                Lihat Detail
                            </a>
                        </article>
                    @endforeach
                </div>
            @else
                <div class="rounded-[22px] border border-dashed border-stone-300 bg-white px-6 py-14 text-center shadow-sm">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-stone-100 text-stone-400">
                        <i data-lucide="folder-search-2" class="h-6 w-6"></i>
                    </div>
                    <h2 class="mt-4 text-lg font-black text-stone-900">Belum ada order yang tersedia.</h2>
                    <p class="mt-2 text-sm leading-6 text-stone-500">Saat data order sudah tersedia di sistem, seluruh progress dan dokumennya akan muncul otomatis di dashboard ini.</p>
                </div>
            @endif
        </section>

        <section class="hidden">
            @forelse ($orders as $order)
                <article class="overflow-hidden rounded-[22px] border border-stone-200 bg-white shadow-sm transition hover:border-red-200 {{ $cardAccentClasses[$order['status_tone']] ?? $cardAccentClasses['slate'] }}">
                    <div class="grid gap-0 xl:grid-cols-[280px_minmax(0,1fr)]">
                        <div class="border-b border-stone-200 bg-stone-50/70 p-4 xl:border-b-0 xl:border-r">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="text-[10px] font-bold uppercase tracking-[0.22em] text-stone-400">Order / Notif</div>
                                    <div class="mt-2 truncate text-xl font-black tracking-tight text-stone-900">{{ $order['nomor_order'] }}</div>
                                    <div class="mt-1 truncate text-xs text-stone-500">Notif: {{ $order['notifikasi'] ?: '-' }}</div>
                                </div>
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-bold ring-1 {{ $order['prioritas_badge_classes'] }}">
                                    {{ $order['prioritas_label'] }}
                                </span>
                            </div>

                            <div class="mt-4 rounded-2xl border border-stone-200 bg-white p-3">
                                <div class="text-[10px] font-bold uppercase tracking-[0.2em] text-stone-400">Nama Pekerjaan</div>
                                <div class="mt-2 line-clamp-2 text-sm font-bold leading-5 text-stone-900">{{ $order['nama_pekerjaan'] }}</div>

                                <div class="mt-3 grid gap-2 text-xs text-stone-600">
                                    <div><span class="font-semibold text-stone-500">Unit:</span> {{ $order['unit_kerja'] ?: '-' }}</div>
                                    <div><span class="font-semibold text-stone-500">Seksi:</span> {{ $order['seksi'] ?: '-' }}</div>
                                    <div><span class="font-semibold text-stone-500">Tanggal:</span> {{ $order['tanggal_order'] ?: '-' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="p-4">
                            <div class="flex flex-col gap-3">
                                <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                                    <div class="space-y-1">
                                        <div class="text-[10px] font-bold uppercase tracking-[0.22em] text-stone-400">Fase Saat Ini</div>
                                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold ring-1 {{ $toneClasses[$order['status_tone']] ?? $toneClasses['slate'] }}">
                                            {{ $order['status_label'] }}
                                        </span>
                                    </div>

                                    <a href="{{ $order['show_url'] }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-red-800 bg-red-800 px-3.5 py-2 text-xs font-semibold text-white transition hover:bg-red-900">
                                        <i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i>
                                        Lihat Detail
                                    </a>
                                </div>

                                @if ($order['is_workshop_only'])
                                    <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                                        <a href="{{ $order['quick_links']['abnormalitas'] ?: '#' }}" class="rounded-xl border px-3 py-2 text-center text-xs font-semibold transition {{ $order['quick_links']['abnormalitas'] ? 'border-stone-200 bg-white text-stone-700 hover:border-red-200 hover:text-red-800' : 'cursor-not-allowed border-stone-200 bg-stone-50 text-stone-400' }}">
                                            Abnormalitas
                                        </a>
                                        <a href="{{ $order['quick_links']['gambar_teknik'] ?: '#' }}" class="rounded-xl border px-3 py-2 text-center text-xs font-semibold transition {{ $order['quick_links']['gambar_teknik'] ? 'border-stone-200 bg-white text-stone-700 hover:border-red-200 hover:text-red-800' : 'cursor-not-allowed border-stone-200 bg-stone-50 text-stone-400' }}">
                                            Gambar Teknik
                                        </a>
                                        <a href="{{ $order['quick_links']['scope_of_work'] ?: '#' }}" class="rounded-xl border px-3 py-2 text-center text-xs font-semibold transition {{ $order['quick_links']['scope_of_work'] ? 'border-stone-200 bg-white text-stone-700 hover:border-red-200 hover:text-red-800' : 'cursor-not-allowed border-stone-200 bg-stone-50 text-stone-400' }}">
                                            Scope of Work
                                        </a>
                                        <a href="{{ $order['quick_links']['quality_control'] ?: '#' }}" class="rounded-xl border px-3 py-2 text-center text-xs font-semibold transition {{ $order['quick_links']['quality_control'] ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:border-emerald-300 hover:bg-emerald-100' : 'cursor-not-allowed border-stone-200 bg-stone-50 text-stone-400' }}">
                                            PDF QC
                                        </a>
                                    </div>
                                @else
                                    <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-5">
                                        <a href="{{ $order['quick_links']['abnormalitas'] ?: '#' }}" class="rounded-xl border px-3 py-2 text-center text-xs font-semibold transition {{ $order['quick_links']['abnormalitas'] ? 'border-stone-200 bg-white text-stone-700 hover:border-red-200 hover:text-red-800' : 'cursor-not-allowed border-stone-200 bg-stone-50 text-stone-400' }}">
                                            Abnormalitas
                                        </a>
                                        <a href="{{ $order['quick_links']['hpp'] ?: '#' }}" class="rounded-xl border px-3 py-2 text-center text-xs font-semibold transition {{ $order['quick_links']['hpp'] ? 'border-stone-200 bg-white text-stone-700 hover:border-red-200 hover:text-red-800' : 'cursor-not-allowed border-stone-200 bg-stone-50 text-stone-400' }}">
                                            HPP
                                        </a>
                                        <a href="{{ $order['quick_links']['bast_termin_1'] ?: '#' }}" class="rounded-xl border px-3 py-2 text-center text-xs font-semibold transition {{ $order['quick_links']['bast_termin_1'] ? 'border-stone-200 bg-white text-stone-700 hover:border-red-200 hover:text-red-800' : 'cursor-not-allowed border-stone-200 bg-stone-50 text-stone-400' }}">
                                            BAST Termin 1
                                        </a>
                                        <a href="{{ $order['quick_links']['bast_termin_2'] ?: '#' }}" class="rounded-xl border px-3 py-2 text-center text-xs font-semibold transition {{ $order['quick_links']['bast_termin_2'] ? 'border-stone-200 bg-white text-stone-700 hover:border-red-200 hover:text-red-800' : 'cursor-not-allowed border-stone-200 bg-stone-50 text-stone-400' }}">
                                            BAST Termin 2
                                        </a>
                                        <a href="{{ $order['quick_links']['quality_control'] ?: '#' }}" class="rounded-xl border px-3 py-2 text-center text-xs font-semibold transition {{ $order['quick_links']['quality_control'] ? 'border-emerald-200 bg-emerald-50 text-emerald-700 hover:border-emerald-300 hover:bg-emerald-100' : 'cursor-not-allowed border-stone-200 bg-stone-50 text-stone-400' }}">
                                            PDF QC
                                        </a>
                                    </div>
                                @endif

                                @if ($order['garansi'])
                                    <div class="rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-xs text-stone-700">
                                        Garansi tersedia: {{ $order['garansi']['months'] }} bulan{{ $order['garansi']['end'] ? ' • sampai '.$order['garansi']['end'] : '' }}
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-[22px] border border-dashed border-stone-300 bg-white px-6 py-14 text-center shadow-sm">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-stone-100 text-stone-400">
                        <i data-lucide="folder-search-2" class="h-6 w-6"></i>
                    </div>
                    <h2 class="mt-4 text-lg font-black text-stone-900">Belum ada order yang tersedia.</h2>
                    <p class="mt-2 text-sm leading-6 text-stone-500">Saat data order sudah tersedia di sistem, seluruh progress dan dokumennya akan muncul otomatis di dashboard ini.</p>
                </div>
            @endforelse
        </section>

        @if ($orders->hasPages())
            <div class="rounded-[20px] border border-stone-200 bg-white px-4 py-3 shadow-sm">
                {{ $orders->links() }}
            </div>
        @endif
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const approvedLabels = @json($chartApproved['labels']);
            const approvedValues = @json($chartApproved['values']);
            const biayaLabels = @json($chartBiaya['labels']);
            const biayaValues = @json($chartBiaya['values']);

            const modernChartOptions = {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                animation: {
                    duration: 1200,
                    easing: 'easeOutCubic'
                },
                layout: {
                    padding: {
                        top: 8,
                        right: 8,
                        bottom: 0,
                        left: 0
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(28,25,23,0.92)',
                        titleColor: '#ffffff',
                        bodyColor: '#f5f5f4',
                        displayColors: false,
                        padding: 12,
                        cornerRadius: 12,
                        titleFont: {
                            size: 12,
                            weight: '700'
                        },
                        bodyFont: {
                            size: 12
                        }
                    }
                },
                scales: {
                    x: {
                        display: true,
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        border: {
                            display: false
                        },
                        ticks: {
                            color: '#78716c',
                            font: {
                                size: 10,
                                weight: '600'
                            },
                            maxRotation: 0,
                            minRotation: 0,
                            callback: function (value) {
                                const label = this.getLabelForValue(value) || '';

                                return label.length > 16 ? label.slice(0, 16) + '…' : label;
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#78716c',
                            font: {
                                size: 11,
                                weight: '600'
                            },
                            padding: 10
                        },
                        grid: {
                            display: false,
                            drawBorder: false,
                            drawTicks: false
                        },
                        border: {
                            display: false
                        }
                    }
                },
                elements: {
                    line: {
                        borderWidth: 3,
                        tension: 0.42,
                        capBezierPoints: true
                    },
                    point: {
                        radius: 4,
                        hoverRadius: 6,
                        hitRadius: 18,
                        borderWidth: 2,
                        backgroundColor: '#ffffff'
                    }
                }
            };

            const approvedCanvas = document.getElementById('chartNotifikasi');
            if (approvedCanvas) {
                new Chart(approvedCanvas, {
                    type: 'line',
                    data: {
                        labels: approvedLabels,
                        datasets: [{
                            data: approvedValues,
                            borderColor: '#991b1b',
                            backgroundColor: 'rgba(153, 27, 27, 0.08)',
                            fill: true,
                            pointBackgroundColor: '#991b1b',
                            pointHoverBackgroundColor: '#991b1b',
                            pointHoverBorderColor: '#ffffff',
                            pointBorderColor: '#991b1b'
                        }]
                    },
                    options: {
                        ...modernChartOptions,
                        plugins: {
                            ...modernChartOptions.plugins,
                            tooltip: {
                                ...modernChartOptions.plugins.tooltip,
                                callbacks: {
                                    title: function (items) {
                                        return items[0]?.label || '';
                                    },
                                    label: function (context) {
                                        return (context.parsed.y || 0) + ' order approved';
                                    }
                                }
                            }
                        }
                    }
                });
            }

            const biayaCanvas = document.getElementById('chartBiaya');
            if (biayaCanvas) {
                new Chart(biayaCanvas, {
                    type: 'line',
                    data: {
                        labels: biayaLabels,
                        datasets: [{
                            data: biayaValues,
                            borderColor: '#7f1d1d',
                            backgroundColor: 'rgba(127, 29, 29, 0.08)',
                            fill: true,
                            pointBackgroundColor: '#7f1d1d',
                            pointHoverBackgroundColor: '#7f1d1d',
                            pointHoverBorderColor: '#ffffff',
                            pointBorderColor: '#7f1d1d'
                        }]
                    },
                    options: {
                        ...modernChartOptions,
                        scales: {
                            ...modernChartOptions.scales,
                            y: {
                                ...modernChartOptions.scales.y,
                                ticks: {
                                    color: '#78716c',
                                    font: {
                                        size: 11,
                                        weight: '600'
                                    },
                                    callback: function (value) {
                                        return 'Rp ' + new Intl.NumberFormat('id-ID', {
                                            notation: 'compact',
                                            compactDisplay: 'short',
                                            maximumFractionDigits: 1
                                        }).format(value);
                                    }
                                }
                            }
                        },
                        plugins: {
                            ...modernChartOptions.plugins,
                            tooltip: {
                                ...modernChartOptions.plugins.tooltip,
                                callbacks: {
                                    title: function (items) {
                                        return items[0]?.label || '';
                                    },
                                    label: function (context) {
                                        return 'Rp ' + new Intl.NumberFormat('id-ID').format(context.parsed.y || 0);
                                    }
                                }
                            }
                        }
                    }
                });
            }
        });
    </script>
</x-layouts.user>
