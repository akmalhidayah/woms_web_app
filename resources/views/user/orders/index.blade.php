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
    @endphp

    <div class="space-y-4">
        <section class="sticky top-[88px] z-20 rounded-[22px] border border-stone-200 bg-white p-3.5 shadow-sm">
            <div class="overflow-x-auto">
                <form method="GET" action="{{ route('user.dashboard') }}" class="flex min-w-[760px] items-center gap-2">
                    <input
                        type="text"
                        name="notification_number"
                        value="{{ $filters['notification_number'] }}"
                        placeholder="Cari nomor order / notifikasi..."
                        class="h-[42px] min-w-0 flex-1 rounded-xl border border-stone-200 bg-white px-3 text-sm text-stone-700 placeholder:text-stone-400 focus:border-red-300 focus:outline-none focus:ring-2 focus:ring-red-100"
                    >

                    <select name="unit_work" class="h-[42px] w-[180px] rounded-xl border border-stone-200 bg-white px-3 text-sm text-stone-700 focus:border-red-300 focus:outline-none focus:ring-2 focus:ring-red-100">
                        <option value="">Semua Unit</option>
                        @foreach ($units as $u)
                            <option value="{{ $u }}" @selected($filters['unit_work'] === $u)>{{ $u }}</option>
                        @endforeach
                    </select>

                    <select name="sortOrder" class="h-[42px] w-[140px] rounded-xl border border-stone-200 bg-white px-3 text-sm text-stone-700 focus:border-red-300 focus:outline-none focus:ring-2 focus:ring-red-100">
                        <option value="latest" @selected($filters['sortOrder'] === 'latest')>Terbaru</option>
                        <option value="oldest" @selected($filters['sortOrder'] === 'oldest')>Terlama</option>
                    </select>

                    <select name="entries" class="h-[42px] w-[90px] rounded-xl border border-stone-200 bg-white px-3 text-sm text-stone-700 focus:border-red-300 focus:outline-none focus:ring-2 focus:ring-red-100">
                        @foreach ([10, 25, 50, 100] as $n)
                            <option value="{{ $n }}" @selected((int) $filters['entries'] === $n)>{{ $n }}</option>
                        @endforeach
                    </select>

                    <button type="submit" class="inline-flex h-[42px] w-[42px] shrink-0 items-center justify-center rounded-xl border border-red-800 bg-red-800 text-white transition hover:bg-red-900" title="Terapkan filter" aria-label="Terapkan filter">
                        <i data-lucide="filter" class="h-4 w-4"></i>
                    </button>
                    <a href="{{ route('user.dashboard') }}" class="inline-flex h-[42px] w-[42px] shrink-0 items-center justify-center rounded-xl border border-stone-200 bg-white text-stone-600 transition hover:border-red-200 hover:text-red-800" title="Reset filter" aria-label="Reset filter">
                        <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                    </a>
                </form>
            </div>
        </section>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-stone-200 bg-white p-3.5 shadow-sm">
                <div class="text-[10px] font-bold uppercase tracking-[0.24em] text-red-700">Total Order</div>
                <div class="mt-2 text-2xl font-black text-stone-900">{{ $stats['total_orders'] }}</div>
            </article>
            <article class="rounded-2xl border border-stone-200 bg-white p-3.5 shadow-sm">
                <div class="text-[10px] font-bold uppercase tracking-[0.24em] text-red-700">Emergency</div>
                <div class="mt-2 text-2xl font-black text-stone-900">{{ $stats['emergency_orders'] }}</div>
            </article>
            <article class="rounded-2xl border border-stone-200 bg-white p-3.5 shadow-sm">
                <div class="text-[10px] font-bold uppercase tracking-[0.24em] text-red-700">PO Ready</div>
                <div class="mt-2 text-2xl font-black text-stone-900">{{ $stats['po_ready'] }}</div>
            </article>
            <article class="rounded-2xl border border-stone-200 bg-white p-3.5 shadow-sm">
                <div class="text-[10px] font-bold uppercase tracking-[0.24em] text-red-700">BAST Ready</div>
                <div class="mt-2 text-2xl font-black text-stone-900">{{ $stats['bast_ready'] }}</div>
            </article>
        </section>

        <section class="grid gap-4 xl:grid-cols-2">
            <div class="rounded-[22px] border border-stone-200 bg-white p-4 shadow-sm">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="flex items-center gap-2 text-sm font-semibold text-stone-900">
                            <i data-lucide="line-chart" class="h-4 w-4 text-red-700"></i>
                            Top 10 Unit Kerja - Approved
                        </h3>
                        <p class="mt-1 text-[11px] text-stone-500">Jumlah order yang sudah approved</p>
                    </div>
                </div>
                <div class="relative h-56">
                    <canvas id="chartNotifikasi"></canvas>
                </div>
            </div>

            <div class="rounded-[22px] border border-stone-200 bg-white p-4 shadow-sm">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <div>
                        <h3 class="flex items-center gap-2 text-sm font-semibold text-stone-900">
                            <i data-lucide="badge-dollar-sign" class="h-4 w-4 text-red-700"></i>
                            Top 10 Unit Kerja - Total Biaya LHPP
                        </h3>
                        <p class="mt-1 text-[11px] text-stone-500">Akumulasi total biaya pekerjaan</p>
                    </div>
                </div>
                <div class="relative h-56">
                    <canvas id="chartBiaya"></canvas>
                </div>
            </div>
        </section>

        <section class="space-y-3">
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

                                <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
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
                                </div>

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
