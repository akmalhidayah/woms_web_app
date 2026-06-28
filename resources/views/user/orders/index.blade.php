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
                    ['label' => 'Order Bengkel', 'value' => $stats['workshop_orders']],
                    ['label' => 'Order Jasa', 'value' => $stats['service_orders']],
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
                'label' => 'PO Order Jasa',
                'value' => $stats['po_ready'],
                'icon' => 'file-check-2',
                'iconClass' => 'bg-amber-50 text-amber-700 ring-amber-100',
                'accent' => 'bg-amber-500',
            ],
            [
                'label' => 'BAST Order Jasa',
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

    <div class="user-dashboard space-y-6" data-user-dashboard>
        <section class="dashboard-premium-card sticky top-[92px] z-20 rounded-[1.15rem] p-3.5 shadow-lg shadow-slate-900/5 backdrop-blur sm:top-[118px] sm:p-4 lg:top-[150px]">
            <form method="GET" action="{{ route('user.dashboard') }}" class="grid gap-3 lg:grid-cols-[minmax(220px,1.6fr)_minmax(160px,0.9fr)_minmax(130px,0.6fr)_minmax(110px,0.45fr)_auto] lg:items-end" data-dashboard-filter-form>
                <div class="space-y-1.5">
                    <label for="notification_number" class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-500">Order / Notifikasi</label>
                    <input
                        id="notification_number"
                        type="text"
                        name="notification_number"
                        value="{{ $filters['notification_number'] }}"
                        placeholder="Cari nomor order / notifikasi..."
                        class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 placeholder:text-slate-400 shadow-sm transition focus:border-red-300 focus:outline-none focus:ring-4 focus:ring-red-100"
                    >
                </div>

                <div class="space-y-1.5">
                    <label for="unit_work" class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-500">Unit</label>
                    <select id="unit_work" name="unit_work" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 shadow-sm transition focus:border-red-300 focus:outline-none focus:ring-4 focus:ring-red-100">
                        <option value="">Semua Unit</option>
                        @foreach ($units as $u)
                            <option value="{{ $u }}" @selected($filters['unit_work'] === $u)>{{ $u }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label for="sortOrder" class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-500">Sortir</label>
                    <select id="sortOrder" name="sortOrder" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 shadow-sm transition focus:border-red-300 focus:outline-none focus:ring-4 focus:ring-red-100">
                        <option value="latest" @selected($filters['sortOrder'] === 'latest')>Terbaru</option>
                        <option value="oldest" @selected($filters['sortOrder'] === 'oldest')>Terlama</option>
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label for="entries" class="text-[11px] font-black uppercase tracking-[0.16em] text-slate-500">Limit</label>
                    <select id="entries" name="entries" class="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 shadow-sm transition focus:border-red-300 focus:outline-none focus:ring-4 focus:ring-red-100">
                        @foreach ([10, 25, 50, 100] as $n)
                            <option value="{{ $n }}" @selected((int) $filters['entries'] === $n)>{{ $n }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex gap-2">
                    <button type="submit" class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-xl border border-red-800 bg-red-800 px-4 text-sm font-bold text-white shadow-sm transition hover:bg-red-900 focus:outline-none focus:ring-4 focus:ring-red-100 lg:flex-none" aria-label="Terapkan filter">
                        <i data-lucide="filter" class="h-4 w-4"></i>
                        <span class="lg:hidden xl:inline">Filter</span>
                    </button>
                    <a href="{{ route('user.dashboard') }}" class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white px-4 text-sm font-bold text-slate-600 shadow-sm transition hover:border-red-200 hover:text-red-800 focus:outline-none focus:ring-4 focus:ring-red-100 lg:flex-none" aria-label="Reset filter" data-dashboard-reset>
                        <i data-lucide="rotate-ccw" class="h-4 w-4"></i>
                        <span class="lg:hidden xl:inline">Reset</span>
                    </a>
                </div>
            </form>
        </section>

        <section class="dashboard-content grid items-start gap-3 sm:grid-cols-2 xl:grid-cols-[1.18fr_1.18fr_0.88fr_0.88fr_0.88fr]">
            @foreach ($summaryCards as $card)
                <article class="dashboard-soft-card group rounded-[1.15rem] px-4 py-4 transition hover:-translate-y-0.5 hover:border-red-100 hover:shadow-lg">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ring-1 {{ $card['iconClass'] }}">
                                <i data-lucide="{{ $card['icon'] }}" class="h-5 w-5"></i>
                            </div>
                            <div class="min-w-0">
                                <div class="truncate text-[11px] font-black uppercase tracking-[0.14em] text-slate-500">{{ $card['label'] }}</div>
                                <div class="mt-1 text-[2rem] font-black leading-none tracking-tight text-slate-950" data-count-up data-count-value="{{ $card['value'] }}">{{ $card['value'] }}</div>
                            </div>
                        </div>
                    </div>

                    @if (isset($card['breakdown']))
                        <div class="mt-3 flex items-center gap-4 border-t border-slate-100 pt-3">
                            @foreach ($card['breakdown'] as $item)
                                <div class="min-w-0">
                                    <div class="truncate text-[10px] font-black uppercase tracking-[0.12em] text-slate-400">{{ $item['label'] }}</div>
                                    <div class="mt-0.5 text-lg font-black leading-none text-slate-900" data-count-up data-count-value="{{ $item['value'] }}">{{ $item['value'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </article>
            @endforeach
        </section>

        <section class="dashboard-content grid gap-4 xl:grid-cols-2">
            <article class="dashboard-soft-card rounded-[1.35rem] p-4 sm:p-5">
                <div class="mb-5 flex items-start justify-between gap-4">
                    <div>
                        <h2 class="flex items-center gap-2 text-base font-black text-slate-950">
                            <i data-lucide="bar-chart-3" class="h-5 w-5 text-red-700"></i>
                            Top 10 Unit Kerja - Approved
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">Jumlah order yang sudah approved</p>
                    </div>
                </div>

                @if ($hasApprovedData)
                    <div class="space-y-4" role="img" aria-label="Top 10 Unit Kerja Approved">
                        @foreach ($approvedRows as $row)
                            @php
                                $percent = $approvedMax > 0 ? max(7, ($row['value'] / $approvedMax) * 100) : 0;
                            @endphp
                            <div class="space-y-2" title="{{ $row['label'] }}: {{ (int) $row['value'] }} order approved">
                                <div class="flex items-center justify-between gap-3 text-sm">
                                    <span class="min-w-0 truncate font-bold text-slate-700">{{ \Illuminate\Support\Str::limit($row['label'], 34) }}</span>
                                    <span class="shrink-0 font-black text-slate-950" data-count-up data-count-value="{{ (int) $row['value'] }}">{{ (int) $row['value'] }}</span>
                                </div>
                                <div class="dashboard-bar-track">
                                    <div class="dashboard-bar-fill" style="--bar-width: {{ number_format($percent, 2, '.', '') }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center">
                        <div class="mx-auto mb-4 grid max-w-sm gap-2">
                            @foreach ([70, 88, 54, 76] as $width)
                                <div class="dashboard-skeleton-block h-3" style="width: {{ $width }}%"></div>
                            @endforeach
                        </div>
                        <p class="text-sm font-semibold text-slate-700">Belum ada data approved yang dapat ditampilkan.</p>
                    </div>
                @endif
            </article>

            <article class="dashboard-soft-card rounded-[1.35rem] p-4 sm:p-5">
                <div class="mb-5 flex items-start justify-between gap-4">
                    <div>
                        <h2 class="flex items-center gap-2 text-base font-black text-slate-950">
                            <i data-lucide="badge-dollar-sign" class="h-5 w-5 text-red-700"></i>
                            Top 10 Unit Kerja - Total Biaya LHPP
                        </h2>
                        <p class="mt-1 text-sm text-slate-500">Akumulasi total biaya pekerjaan</p>
                    </div>
                </div>

                @if ($hasBiayaData)
                    <div class="space-y-4" role="img" aria-label="Top 10 Unit Kerja Total Biaya LHPP">
                        @foreach ($biayaRows as $row)
                            @php
                                $percent = $biayaMax > 0 ? max(7, ($row['value'] / $biayaMax) * 100) : 0;
                            @endphp
                            <div class="space-y-2" title="{{ $row['label'] }}: Rp {{ number_format($row['value'], 0, ',', '.') }}">
                                <div class="flex items-center justify-between gap-3 text-sm">
                                    <span class="min-w-0 truncate font-bold text-slate-700">{{ \Illuminate\Support\Str::limit($row['label'], 34) }}</span>
                                    <span class="shrink-0 font-black text-slate-950" data-count-up data-count-format="currency" data-count-value="{{ (int) $row['value'] }}">Rp {{ number_format($row['value'], 0, ',', '.') }}</span>
                                </div>
                                <div class="dashboard-bar-track">
                                    <div class="dashboard-bar-fill is-currency" style="--bar-width: {{ number_format($percent, 2, '.', '') }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center">
                        <div class="mx-auto mb-4 grid max-w-sm gap-2">
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
                <div class="hidden overflow-hidden rounded-[1.35rem] border border-slate-200 bg-white shadow-sm md:block">
                    <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                        <thead class="bg-slate-50 text-[11px] font-black uppercase tracking-[0.14em] text-slate-500">
                            <tr>
                                <th class="px-4 py-3">Order / Notif</th>
                                <th class="px-4 py-3">Nama Pekerjaan</th>
                                <th class="px-4 py-3">Unit</th>
                                <th class="px-4 py-3">Seksi</th>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3">Prioritas</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($orders as $order)
                                <tr class="{{ $order['is_completed'] ? 'bg-emerald-50/45 hover:bg-emerald-50/70' : 'bg-white hover:bg-slate-50/80' }} transition">
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
                                        <a href="{{ $order['show_url'] }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-red-800 bg-red-800 px-3.5 py-2 text-xs font-bold text-white shadow-sm transition hover:bg-red-900 focus:outline-none focus:ring-4 focus:ring-red-100">
                                            <i data-lucide="arrow-up-right" class="h-3.5 w-3.5"></i>
                                            Lihat Detail
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="space-y-3 md:hidden">
                    @foreach ($orders as $order)
                        <article class="dashboard-soft-card rounded-[1.35rem] p-4 {{ $order['is_completed'] ? 'bg-emerald-50/60' : 'bg-white' }}">
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

                            <div class="mt-3 rounded-2xl border border-slate-200 bg-white/85 p-3">
                                <div class="text-[10px] font-black uppercase tracking-[0.14em] text-slate-400">Nama Pekerjaan</div>
                                <div class="mt-1.5 line-clamp-2 text-sm font-black leading-5 text-slate-900">{{ $order['nama_pekerjaan'] }}</div>
                                <div class="mt-3 grid gap-1.5 text-xs leading-5 text-slate-600">
                                    <div><span class="font-bold text-slate-500">Unit:</span> {{ $order['unit_kerja'] ?: '-' }}</div>
                                    <div><span class="font-bold text-slate-500">Seksi:</span> {{ $order['seksi'] ?: '-' }}</div>
                                    <div><span class="font-bold text-slate-500">Tanggal:</span> {{ $order['tanggal_order'] ?: '-' }}</div>
                                </div>
                            </div>

                            <a href="{{ $order['show_url'] }}" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-2xl border border-red-800 bg-red-800 px-4 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-red-900 focus:outline-none focus:ring-4 focus:ring-red-100">
                                <i data-lucide="arrow-up-right" class="h-4 w-4"></i>
                                Lihat Detail
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
</x-layouts.user>
