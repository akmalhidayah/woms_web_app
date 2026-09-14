<x-layouts.admin title="History Consumable">
    <div class="min-w-0 space-y-5">
        <section class="overflow-hidden rounded-[1.35rem] border border-blue-100 bg-gradient-to-r from-blue-50 via-white to-indigo-50 px-5 py-5 shadow-sm">
            <div class="flex flex-wrap items-center gap-4">
                <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-lg shadow-blue-200/70">
                    <i data-lucide="history" class="h-5 w-5" aria-hidden="true"></i>
                </span>
                <div class="min-w-0">
                    <h1 class="text-[1.3rem] font-bold leading-tight tracking-tight text-slate-900">History Consumable</h1>
                </div>
                @include('admin.appsheet.partials.google-connection', ['googleReturnTo' => 'history'])
            </div>
        </section>

        @include('admin.appsheet.partials.google-messages')

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm" aria-label="Filter History Consumable">
            <div class="mb-3 flex items-center gap-2 text-xs font-bold text-slate-700">
                <i data-lucide="sliders-horizontal" class="h-4 w-4 text-blue-600" aria-hidden="true"></i>
                Filter transaksi
            </div>
            <form method="GET" action="{{ route('admin.appsheet.history-consumable.index') }}" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-[minmax(260px,2fr)_minmax(130px,0.8fr)_minmax(150px,1fr)_minmax(150px,0.9fr)_auto] xl:items-end">
                <div>
                    <label for="history-consumable-search" class="mb-1.5 block text-[11px] font-semibold text-slate-600">Search</label>
                    <input id="history-consumable-search" name="search" value="{{ $filters['search'] }}" type="search" placeholder="UID, consumable, requester, tujuan..." autocomplete="off" x-on:input.debounce.500ms="$el.form.requestSubmit()" class="block h-10 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3 text-xs text-slate-700 outline-none transition focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100">
                </div>
                <div>
                    <label for="history-consumable-type" class="mb-1.5 block text-[11px] font-semibold text-slate-600">Input Type</label>
                    <select id="history-consumable-type" name="input_type" class="block h-10 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3 text-xs text-slate-700 outline-none focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100">
                        <option value="">Semua tipe transaksi</option>
                        <option value="STOCK IN" @selected($filters['input_type'] === 'STOCK IN')>STOCK IN</option>
                        <option value="STOCK OUT" @selected($filters['input_type'] === 'STOCK OUT')>STOCK OUT</option>
                    </select>
                </div>
                <div>
                    <label for="history-consumable-category" class="mb-1.5 block text-[11px] font-semibold text-slate-600">Category</label>
                    <select id="history-consumable-category" name="category" class="block h-10 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3 text-xs text-slate-700 outline-none focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100">
                        <option value="">Semua category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category }}" @selected($filters['category'] === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="history-consumable-date" class="mb-1.5 block text-[11px] font-semibold text-slate-600">Tanggal</label>
                    <input id="history-consumable-date" name="date" value="{{ $filters['date'] }}" type="date" class="block h-10 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3 text-xs text-slate-700 outline-none focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100">
                </div>
                <div class="flex h-10 items-center gap-2 sm:col-span-2 xl:col-span-1">
                    <button type="submit" class="inline-flex h-10 shrink-0 items-center gap-2 whitespace-nowrap rounded-xl bg-blue-600 px-4 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700">
                        <i data-lucide="search" class="h-3.5 w-3.5" aria-hidden="true"></i>
                        Terapkan Filter
                    </button>
                    <a href="{{ route('admin.appsheet.history-consumable.index') }}" class="inline-flex h-10 shrink-0 items-center gap-1.5 whitespace-nowrap rounded-xl px-3 text-xs font-semibold text-slate-500 transition hover:bg-slate-100 hover:text-blue-600">
                        <i data-lucide="rotate-ccw" class="h-3.5 w-3.5" aria-hidden="true"></i>
                        Reset
                    </a>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3.5 sm:px-5">
                <div>
                    <p class="text-xs font-bold text-slate-800">Riwayat transaksi</p>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1.5 text-[10px] font-semibold text-slate-600">
                    <i data-lucide="arrow-down-narrow-wide" class="h-3.5 w-3.5" aria-hidden="true"></i>
                    Terbaru lebih dahulu
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1060px] text-xs">
                    <caption class="sr-only">History Consumable</caption>
                    <thead class="border-b border-slate-200 bg-slate-50/80 text-[10px] uppercase tracking-[0.12em] text-slate-500">
                        <tr>
                            <th scope="col" class="whitespace-nowrap px-5 py-3.5 text-left font-semibold">Tanggal</th>
                            <th scope="col" class="px-4 py-3.5 text-left font-semibold">Consumable</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3.5 text-left font-semibold">Transaksi</th>
                            <th scope="col" class="px-4 py-3.5 text-left font-semibold">Requester</th>
                            <th scope="col" class="px-4 py-3.5 text-left font-semibold">Tujuan Penggunaan</th>
                            <th scope="col" class="px-4 py-3.5 text-left font-semibold">Jenis Permintaan</th>
                            <th scope="col" class="px-4 py-3.5 text-left font-semibold">Category</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($rows as $row)
                            @php
                                $requester = $row['_requester'];
                                $requesterMeta = collect([
                                    $requester['position'],
                                    $requester['regu'] !== '' ? 'Regu '.$requester['regu'] : '',
                                    $requester['shift'] !== '' ? 'Shift '.$requester['shift'] : '',
                                ])->filter()->join(' · ');
                                $isStockIn = mb_strtoupper(trim((string) $row['INPUT TYPE'])) === 'STOCK IN';
                            @endphp
                            <tr class="group align-middle text-slate-700 transition-colors hover:bg-blue-50/40">
                                <td class="whitespace-nowrap px-5 py-3.5 align-top">
                                    <div class="font-semibold tabular-nums text-slate-800">{{ $row['_date_display'] ?: '-' }}</div>
                                    @if ($row['_time_display'])
                                        <div class="mt-1 inline-flex items-center gap-1 text-[10px] tabular-nums text-slate-400">
                                            <i data-lucide="clock-3" class="h-3 w-3" aria-hidden="true"></i>
                                            {{ $row['_time_display'] }}
                                        </div>
                                    @endif
                                </td>
                                <td class="min-w-64 px-4 py-3.5 align-top">
                                    <p class="text-sm font-bold leading-relaxed text-slate-900">{{ $row['DESC.'] ?: '-' }}</p>
                                    <p class="mt-1 font-mono text-[11px] font-semibold text-blue-600">{{ $row['UID'] ?: 'Tanpa UID' }}</p>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3.5 align-top">
                                    <div class="flex flex-wrap items-center gap-1.5">
                                        <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold {{ $isStockIn ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200' }}">
                                            <i data-lucide="{{ $isStockIn ? 'arrow-down-to-line' : 'arrow-up-from-line' }}" class="h-3 w-3" aria-hidden="true"></i>
                                            {{ $row['INPUT TYPE'] ?: '-' }}
                                        </span>
                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold tabular-nums text-slate-700 ring-1 ring-slate-200">Qty {{ $row['QTY'] }}</span>
                                    </div>
                                </td>
                                <td class="min-w-60 px-4 py-3.5 align-top">
                                    <div class="flex items-center gap-3">
                                        <span class="relative inline-flex h-12 w-12 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-gradient-to-br from-blue-100 to-indigo-100 text-xs font-black text-blue-700 ring-1 ring-blue-200 shadow-sm">
                                            {{ $requester['initials'] }}
                                            @if ($row['_requester_avatar_url'])
                                                <img src="{{ $row['_requester_avatar_url'] }}" alt="Avatar {{ $requester['name'] }}" loading="lazy" class="absolute inset-0 h-full w-full bg-white object-contain object-center p-0.5" onerror="this.remove()">
                                            @endif
                                        </span>
                                        <div class="min-w-0">
                                            <p class="font-semibold leading-snug text-slate-900">{{ $requester['name'] }}</p>
                                            @if ($requesterMeta !== '')
                                                <p class="mt-1 max-w-48 text-[10px] leading-relaxed text-slate-400">{{ $requesterMeta }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="min-w-48 max-w-xs break-words px-4 py-3.5 align-top leading-relaxed">{{ $row['TUJUAN PENGGUNAAN'] ?: '-' }}</td>
                                <td class="px-4 py-3.5 align-top">
                                    <span class="inline-flex rounded-full bg-violet-50 px-2.5 py-1 text-[10px] font-semibold text-violet-700 ring-1 ring-violet-200">{{ $row['JENIS PERMINTAAN'] ?: 'Tidak diketahui' }}</span>
                                </td>
                                <td class="px-4 py-3.5 align-top">
                                    <span class="inline-flex rounded-full bg-sky-50 px-2.5 py-1 text-[10px] font-semibold text-sky-700 ring-1 ring-sky-200">{{ $row['CATEGORY'] ?: 'Tanpa category' }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-16 text-center">
                                    <span class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400"><i data-lucide="package-search" class="h-6 w-6" aria-hidden="true"></i></span>
                                    <p class="mt-4 text-sm font-semibold text-slate-700">History belum dapat ditampilkan</p>
                                    <p class="mx-auto mt-1 max-w-sm text-xs leading-relaxed text-slate-500">
                                        @if ($sheetError || $googleConnectionError)
                                            Data History Consumable belum dapat dimuat. Coba perbarui koneksi atau muat ulang halaman.
                                        @elseif (! $googleConnected)
                                            Hubungkan Google untuk menampilkan History Consumable.
                                        @elseif ($totalRows > 0)
                                            Tidak ada transaksi yang sesuai dengan filter saat ini.
                                        @else
                                            Data History Consumable belum tersedia.
                                        @endif
                                    </p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($rows->hasPages())
                <div class="border-t border-slate-200 bg-slate-50/40 px-4 py-3">{{ $rows->links() }}</div>
            @endif
        </section>
    </div>
</x-layouts.admin>
