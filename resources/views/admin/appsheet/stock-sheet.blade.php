<x-layouts.admin title="STOCK">
    @php
        $stockTitle = match ($stockKind) {
            'consumable-gudang' => 'Stock Consumable Gudang',
            'material-bms' => 'Stock Material BMS',
            'material-gudang' => 'Stock Material Gudang',
        };
        $isMaterialGudang = $stockKind === 'material-gudang';
        $columnCount = match ($stockKind) {
            'material-gudang' => 8,
            'consumable-gudang' => 6,
            default => 5,
        };
    @endphp
    <div
        class="min-w-0 space-y-5"
        x-data="{
            consumableInfoOpen: false,
            consumableInfo: { title: '', name: '', type: '' },
            openConsumableInfo(item) {
                this.consumableInfo = item;
                this.consumableInfoOpen = true;
            },
            closeConsumableInfo() {
                this.consumableInfoOpen = false;
            },
        }"
        x-on:keydown.escape.window="closeConsumableInfo()"
    >
        @include('admin.appsheet.partials.stock-header')
        @include('admin.appsheet.partials.google-messages')

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm" aria-label="Filter {{ $stockTitle }}">
            <div class="mb-3 flex items-center gap-2 text-xs font-bold text-slate-700">
                <i data-lucide="sliders-horizontal" class="h-4 w-4 text-blue-600" aria-hidden="true"></i>
                Filter persediaan
            </div>
            <form method="GET" action="{{ url()->current() }}" class="appsheet-stock-filters">
                <div class="appsheet-stock-filter-field appsheet-stock-filter-search">
                    <label for="stock-search" class="mb-1.5 block text-[11px] font-semibold text-slate-600">Search</label>
                    <input id="stock-search" name="search" value="{{ $filters['search'] }}" type="search" placeholder="Kode, nama, keterangan, petugas..." autocomplete="off" x-on:input.debounce.500ms="$el.form.requestSubmit()" class="block h-10 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3 text-base text-slate-700 outline-none transition focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100 sm:text-xs">
                </div>
                @if ($typeFilter !== null)
                    <div class="appsheet-stock-filter-field">
                        <label for="stock-type" class="mb-1.5 block text-[11px] font-semibold text-slate-600">{{ $isMaterial ? 'MRP Type' : 'Jenis Consumable' }}</label>
                        <select id="stock-type" name="{{ $typeFilter }}" class="block h-10 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3 text-base text-slate-700 outline-none focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100 sm:text-xs">
                            <option value="">{{ $isMaterial ? 'Semua MRP type' : 'Semua jenis consumable' }}</option>
                            @foreach ($types as $type)
                                <option value="{{ $type }}" @selected($filters[$typeFilter] === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                @if ($isMaterial)
                    <div class="appsheet-stock-filter-field">
                        <label for="stock-location" class="mb-1.5 block text-[11px] font-semibold text-slate-600">Lokasi</label>
                        <select id="stock-location" name="location" class="block h-10 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3 text-base text-slate-700 outline-none focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100 sm:text-xs">
                            <option value="">Semua lokasi</option>
                            @foreach ($locations as $location)
                                <option value="{{ $location }}" @selected($filters['location'] === $location)>{{ $location }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="appsheet-stock-filter-field">
                        <label for="stock-status" class="mb-1.5 block text-[11px] font-semibold text-slate-600">Status Stok</label>
                        <select id="stock-status" name="status" class="block h-10 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3 text-base text-slate-700 outline-none focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100 sm:text-xs">
                            <option value="">Semua status stok</option>
                            <option value="tersedia" @selected($filters['status'] === 'tersedia')>Tersedia</option>
                            <option value="habis" @selected($filters['status'] === 'habis')>Habis</option>
                        </select>
                    </div>
                @endif
                <div class="appsheet-stock-filter-actions">
                    <button type="submit" class="inline-flex h-10 items-center gap-2 whitespace-nowrap rounded-xl bg-blue-600 px-4 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700">
                        <i data-lucide="search" class="h-3.5 w-3.5" aria-hidden="true"></i>
                        Terapkan Filter
                    </button>
                    <a href="{{ url()->current() }}" class="inline-flex h-10 items-center gap-1.5 whitespace-nowrap rounded-xl px-3 text-xs font-semibold text-slate-500 transition hover:bg-slate-100 hover:text-blue-600">
                        <i data-lucide="rotate-ccw" class="h-3.5 w-3.5" aria-hidden="true"></i>
                        Reset
                    </a>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-2 py-2 sm:px-3">
                @include('admin.appsheet.partials.stock-tabs')
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1080px] text-xs">
                    <caption class="sr-only">{{ $stockTitle }}</caption>
                    <thead class="border-b border-slate-200 bg-slate-50/80 text-[10px] uppercase tracking-[0.12em] text-slate-500">
                        <tr>
                            <th scope="col" class="px-5 py-3.5 text-left font-semibold">{{ $isMaterial ? 'Item Material' : 'Item Consumable' }}</th>
                            @unless ($isMaterial)
                                <th scope="col" class="px-4 py-3.5 text-left font-semibold">Jenis</th>
                            @endunless
                            @if ($isMaterialGudang)
                                <th scope="col" class="px-4 py-3.5 text-left font-semibold">MRP Type</th>
                                <th scope="col" class="px-4 py-3.5 text-left font-semibold">Deskripsi</th>
                                <th scope="col" class="whitespace-nowrap px-4 py-3.5 text-right font-semibold">Qty Capex</th>
                            @endif
                            @if ($isMaterial)
                                <th scope="col" class="whitespace-nowrap px-4 py-3.5 text-right font-semibold">Stok Saat Ini</th>
                                <th scope="col" class="px-4 py-3.5 text-left font-semibold">Lokasi</th>
                                <th scope="col" class="px-4 py-3.5 text-left font-semibold">Status</th>
                            @else
                                <th scope="col" class="px-4 py-3.5 text-right font-semibold">Konsinyasi</th>
                                <th scope="col" class="whitespace-nowrap px-4 py-3.5 text-right font-semibold">Non Konsinyasi</th>
                                <th scope="col" class="px-4 py-3.5 text-right font-semibold">Minimum</th>
                            @endif
                            <th scope="col" class="px-4 py-3.5 text-left font-semibold">Pembaruan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($rows as $row)
                            <tr class="group align-top text-slate-700 transition-colors hover:bg-blue-50/40">
                                <td class="min-w-64 max-w-sm break-words px-5 py-4">
                                    @if ($isMaterial)
                                        <p class="text-sm font-bold leading-relaxed text-slate-900">{{ $row['name'] ?: '-' }}</p>
                                        <p class="mt-1.5 font-mono text-[11px] font-semibold text-blue-600">{{ $row['code'] ?: '-' }}</p>
                                    @else
                                        @php
                                            $itemDescription = trim((string) ($row['description'] ?? ''));
                                            $consumableName = trim((string) ($row['name'] ?? ''));
                                            $hasItemDescription = $itemDescription !== ''
                                                && preg_match('/^[\s\p{Pd}]+$/u', $itemDescription) !== 1;
                                            $hasConsumableName = $consumableName !== ''
                                                && preg_match('/^[\s\p{Pd}]+$/u', $consumableName) !== 1;
                                            $itemTitle = $hasItemDescription
                                                ? $itemDescription
                                                : ($hasConsumableName ? $consumableName : '-');
                                        @endphp
                                        <div class="flex items-start gap-2">
                                            <p class="min-w-0 text-sm font-bold leading-relaxed text-slate-900">{{ $itemTitle }}</p>
                                            <button
                                                type="button"
                                                class="mt-0.5 inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-blue-600 transition hover:border-blue-300 hover:bg-blue-100 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                                aria-label="Lihat detail {{ $itemTitle }}"
                                                title="Lihat detail item"
                                                x-on:click="openConsumableInfo(@js([
                                                    'title' => $itemTitle,
                                                    'name' => $row['name'] ?: '-',
                                                    'type' => $row['type'] ?: '-',
                                                ]))"
                                            >
                                                <i data-lucide="info" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                        <p class="mt-1.5 font-mono text-[11px] font-semibold text-blue-600">{{ $row['code'] ?: '-' }}</p>
                                    @endif
                                </td>
                                @unless ($isMaterial)
                                    <td class="min-w-40 max-w-xs break-words px-4 py-4 font-semibold text-slate-700">{{ $row['type'] ?: '-' }}</td>
                                @endunless
                                @if ($isMaterialGudang)
                                    <td class="px-4 py-4">{{ $row['type'] ?: '-' }}</td>
                                    <td class="min-w-48 max-w-xs break-words px-4 py-4 leading-relaxed">{{ $row['description'] ?: '-' }}</td>
                                    <td class="whitespace-nowrap px-4 py-4 text-right tabular-nums">
                                        @include('admin.appsheet.partials.stock-quantity', ['quantity' => $row['capex'], 'unit' => $row['unit']])
                                    </td>
                                @endif
                                @if ($isMaterial)
                                    <td class="whitespace-nowrap px-4 py-4 text-right tabular-nums">
                                        @include('admin.appsheet.partials.stock-quantity', ['quantity' => $row['quantity'], 'unit' => $row['unit']])
                                    </td>
                                    <td class="min-w-40 max-w-xs break-words px-4 py-4">
                                        <div class="flex items-start gap-1.5 leading-relaxed">
                                            <i data-lucide="map-pin" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-slate-400" aria-hidden="true"></i>
                                            <span>{{ $row['location'] ?: '-' }}</span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-4">
                                        @if ($row['status'] === 'habis')
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-1 text-[10px] font-bold text-rose-700 ring-1 ring-rose-200"><span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span> Habis</span>
                                        @elseif ($row['status'] === 'tersedia')
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-200"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Tersedia</span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold text-slate-500 ring-1 ring-slate-200"><span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span> Tidak diketahui</span>
                                        @endif
                                    </td>
                                @else
                                    @foreach (['consignment', 'non_consignment', 'minimum'] as $quantityField)
                                        <td class="whitespace-nowrap px-4 py-4 text-right tabular-nums">
                                            @include('admin.appsheet.partials.stock-quantity', ['quantity' => $row[$quantityField], 'unit' => $row['unit']])
                                        </td>
                                    @endforeach
                                @endif
                                <td class="min-w-44 px-4 py-4">
                                    <p class="font-semibold text-slate-800">{{ $row['updated_by'] ?: '-' }}</p>
                                    <div class="mt-1 flex items-center gap-1 text-[10px] text-slate-400">
                                        <i data-lucide="clock-3" class="h-3 w-3" aria-hidden="true"></i>
                                        <span class="tabular-nums">{{ $row['date_display'] ?: '-' }}{{ $row['time_display'] ? ' · '.$row['time_display'] : '' }}</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $columnCount }}" class="px-5 py-16 text-center">
                                    <span class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400"><i data-lucide="package-search" class="h-6 w-6" aria-hidden="true"></i></span>
                                    <p class="mt-4 text-sm font-semibold text-slate-700">Persediaan belum dapat ditampilkan</p>
                                    <p class="mx-auto mt-1 max-w-sm text-xs leading-relaxed text-slate-500">
                                        @if ($sheetError || $googleConnectionError)
                                            Data {{ $stockTitle }} belum dapat dimuat. Coba perbarui koneksi atau muat ulang halaman.
                                        @elseif (! $googleConnected)
                                            Hubungkan Google untuk menampilkan {{ $stockTitle }}.
                                        @elseif ($totalRows > 0)
                                            Tidak ada item yang sesuai dengan filter saat ini.
                                        @else
                                            Data {{ $stockTitle }} belum tersedia.
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

        @unless ($isMaterial)
            @include('admin.appsheet.partials.stock-consumable-info-modal')
        @endunless
    </div>
</x-layouts.admin>
