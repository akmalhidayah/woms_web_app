<x-layouts.admin title="STOCK">
    <div
        class="min-w-0 space-y-5"
        x-data="{
            previewImageUrl: '',
            previewImageAlt: '',
            previewImageRequestId: 0,
            openImagePreview(thumbnailUrl, previewUrl, alt) {
                const requestId = ++this.previewImageRequestId;
                this.previewImageUrl = thumbnailUrl || previewUrl;
                this.previewImageAlt = alt;
                if (!previewUrl || previewUrl === thumbnailUrl) return;

                const preview = new Image();
                preview.onload = () => {
                    if (this.previewImageRequestId === requestId && this.previewImageUrl) {
                        this.previewImageUrl = previewUrl;
                    }
                };
                preview.src = previewUrl;
            },
            closeImagePreview() {
                this.previewImageRequestId++;
                this.previewImageUrl = '';
                this.previewImageAlt = '';
            },
        }"
        x-on:keydown.escape.window="closeImagePreview()"
    >
        @include('admin.appsheet.partials.stock-header')

        @include('admin.appsheet.partials.google-messages')

        <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm" aria-label="Filter Stock Consumable">
            <div class="mb-3 flex items-center gap-2 text-xs font-bold text-slate-700">
                <i data-lucide="sliders-horizontal" class="h-4 w-4 text-blue-600" aria-hidden="true"></i>
                Filter persediaan
            </div>
            <form method="GET" action="{{ route('admin.appsheet.stock-consumable.index') }}" class="grid gap-3 sm:grid-cols-2 xl:grid-cols-[minmax(280px,2fr)_minmax(180px,1fr)_minmax(150px,0.8fr)_auto] xl:items-end">
                <div>
                    <label for="stock-consumable-search" class="mb-1.5 block text-[11px] font-semibold text-slate-600">Search</label>
                    <input id="stock-consumable-search" name="search" value="{{ $filters['search'] }}" type="search" placeholder="UID, nama, ukuran, jenis, lokasi..." autocomplete="off" x-on:input.debounce.500ms="$el.form.requestSubmit()" class="block h-10 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3 text-xs text-slate-700 outline-none transition focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100">
                </div>
                <div>
                    <label for="stock-consumable-type" class="mb-1.5 block text-[11px] font-semibold text-slate-600">Jenis Consumable</label>
                    <select id="stock-consumable-type" name="jenis" class="block h-10 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3 text-xs text-slate-700 outline-none focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100">
                        <option value="">Semua jenis consumable</option>
                        @foreach ($subCategories as $subCategory)
                            <option value="{{ $subCategory }}" @selected($filters['jenis'] === $subCategory)>{{ $subCategory }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="stock-consumable-status" class="mb-1.5 block text-[11px] font-semibold text-slate-600">Status Stok</label>
                    <select id="stock-consumable-status" name="status" class="block h-10 w-full rounded-xl border border-slate-200 bg-slate-50/70 px-3 text-xs text-slate-700 outline-none focus:border-blue-400 focus:bg-white focus:ring-2 focus:ring-blue-100">
                        <option value="">Semua status stok</option>
                        <option value="habis" @selected($filters['status'] === 'habis')>Habis</option>
                        <option value="tersedia" @selected($filters['status'] === 'tersedia')>Tersedia</option>
                    </select>
                </div>
                <div class="flex h-10 items-center gap-2 sm:col-span-2 xl:col-span-1">
                    <button type="submit" class="inline-flex h-10 shrink-0 items-center gap-2 whitespace-nowrap rounded-xl bg-blue-600 px-4 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700">
                        <i data-lucide="search" class="h-3.5 w-3.5" aria-hidden="true"></i>
                        Terapkan Filter
                    </button>
                    <a href="{{ route('admin.appsheet.stock-consumable.index') }}" class="inline-flex h-10 shrink-0 items-center gap-1.5 whitespace-nowrap rounded-xl px-3 text-xs font-semibold text-slate-500 transition hover:bg-slate-100 hover:text-blue-600">
                        <i data-lucide="rotate-ccw" class="h-3.5 w-3.5" aria-hidden="true"></i>
                        Reset
                    </a>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 px-4 py-3.5 sm:px-5">
                <div>
                    <p class="text-xs font-bold text-slate-800">Persediaan consumable</p>
                </div>
                <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-3 py-1.5 text-[10px] font-semibold text-slate-600">
                    <i data-lucide="arrow-down-az" class="h-3.5 w-3.5" aria-hidden="true"></i>
                    UID terurut naik
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1080px] text-xs">
                    <caption class="sr-only">Stock Consumable</caption>
                    <thead class="border-b border-slate-200 bg-slate-50/80 text-[10px] uppercase tracking-[0.12em] text-slate-500">
                        <tr>
                            <th scope="col" class="px-5 py-3.5 text-left font-semibold">Item Consumable</th>
                            <th scope="col" class="px-4 py-3.5 text-left font-semibold">Jenis / Sub Category</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3.5 text-right font-semibold">Pergerakan</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3.5 text-right font-semibold">Stok Saat Ini</th>
                            <th scope="col" class="px-4 py-3.5 text-left font-semibold">Lokasi</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3.5 text-left font-semibold">Status</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3.5 text-left font-semibold">Pembaruan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($rows as $row)
                            @php
                                $stock = \App\Support\AppSheet\ConsumableData::number($row['SPARE STOCK']);
                                $stockIn = \App\Support\AppSheet\ConsumableData::number($row['STOCK IN']);
                                $stockOut = \App\Support\AppSheet\ConsumableData::number($row['STOCK OUT']);
                            @endphp
                            <tr class="group align-middle text-slate-700 transition-colors hover:bg-blue-50/40">
                                <td class="min-w-96 px-5 py-4 align-top">
                                    <div class="flex items-start gap-3.5">
                                        @if ($row['_image_url'])
                                            <button
                                                type="button"
                                                class="group relative inline-flex h-20 w-20 shrink-0 cursor-zoom-in items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 text-slate-400 shadow-sm ring-1 ring-slate-200 transition hover:ring-2 hover:ring-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                                aria-label="Lihat foto {{ $row['DESC.'] ?: 'item consumable' }}"
                                                title="Klik untuk melihat foto"
                                                x-on:click="openImagePreview(@js($row['_image_url']), @js($row['_image_preview_url']), @js('Foto '.($row['DESC.'] ?: 'item consumable')))"
                                            >
                                                <i data-lucide="package" class="h-6 w-6" aria-hidden="true"></i>
                                                <img src="{{ $row['_image_url'] }}" alt="Foto {{ $row['DESC.'] }}" loading="lazy" class="absolute inset-0 h-full w-full bg-white object-contain object-center p-1.5 transition duration-200 group-hover:scale-105" onerror="this.remove()">
                                                <span class="pointer-events-none absolute inset-0 flex items-center justify-center bg-slate-950/35 text-white opacity-0 transition group-hover:opacity-100" aria-hidden="true">
                                                    <i data-lucide="maximize-2" class="h-5 w-5"></i>
                                                </span>
                                            </button>
                                        @else
                                            <span class="relative inline-flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 text-slate-400 shadow-sm ring-1 ring-slate-200">
                                                <i data-lucide="package" class="h-6 w-6" aria-hidden="true"></i>
                                            </span>
                                        @endif
                                        <div class="min-w-0">
                                            <p class="text-sm font-bold leading-relaxed text-slate-900">{{ $row['DESC.'] ?: '-' }}</p>
                                            <div class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-[10px]">
                                                <span class="font-mono text-[11px] font-semibold text-blue-600">{{ $row['UID'] ?: 'Tanpa UID' }}</span>
                                                @if (trim((string) $row['SIZE']) !== '')
                                                    <span class="text-slate-300">•</span>
                                                    <span class="text-slate-500">Size {{ $row['SIZE'] }}</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="min-w-56 px-4 py-4 align-top">
                                    <div class="flex flex-wrap gap-1.5">
                                        <span class="inline-flex rounded-full bg-indigo-50 px-2.5 py-1 text-[10px] font-semibold text-indigo-700 ring-1 ring-indigo-200">{{ $row['SUB CATEGORY'] ?: 'Tanpa jenis' }}</span>
                                        <span class="inline-flex rounded-full bg-sky-50 px-2.5 py-1 text-[10px] font-semibold text-sky-700 ring-1 ring-sky-200">{{ $row['CATEGORY'] ?: 'Tanpa category' }}</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-right align-top tabular-nums">
                                    <div class="flex items-center justify-end gap-1.5 text-emerald-600">
                                        <i data-lucide="arrow-down-to-line" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                        <span class="font-semibold">{{ $stockIn === null ? '-' : number_format($stockIn, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="mt-2 flex items-center justify-end gap-1.5 text-amber-600">
                                        <i data-lucide="arrow-up-from-line" class="h-3.5 w-3.5" aria-hidden="true"></i>
                                        <span class="font-semibold">{{ $stockOut === null ? '-' : number_format($stockOut, 0, ',', '.') }}</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 text-right align-top">
                                    <p class="text-lg font-black tabular-nums {{ $row['_stock_status'] === 'habis' ? 'text-rose-600' : 'text-slate-900' }}">{{ $stock === null ? '-' : number_format($stock, 0, ',', '.') }}</p>
                                    <p class="mt-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-400">{{ $row['STN'] ?: 'unit' }}</p>
                                </td>
                                <td class="min-w-40 max-w-xs break-words px-4 py-4 align-top">
                                    <div class="flex items-start gap-1.5 leading-relaxed">
                                        <i data-lucide="map-pin" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-slate-400" aria-hidden="true"></i>
                                        <span>{{ $row['LOC'] ?: '-' }}</span>
                                    </div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-4 align-top">
                                    @if ($row['_stock_status'] === 'habis')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 px-2.5 py-1 text-[10px] font-bold text-rose-700 ring-1 ring-rose-200"><span class="h-1.5 w-1.5 rounded-full bg-rose-500"></span> Habis</span>
                                    @elseif ($row['_stock_status'] === 'tersedia')
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-[10px] font-bold text-emerald-700 ring-1 ring-emerald-200"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Tersedia</span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-semibold text-slate-500 ring-1 ring-slate-200"><span class="h-1.5 w-1.5 rounded-full bg-slate-400"></span> Tidak diketahui</span>
                                    @endif
                                </td>
                                <td class="min-w-44 px-4 py-4 align-top">
                                    <p class="font-semibold text-slate-800">{{ $row['INPUT. BY'] ?: '-' }}</p>
                                    <div class="mt-1 flex items-center gap-1 text-[10px] text-slate-400">
                                        <i data-lucide="clock-3" class="h-3 w-3" aria-hidden="true"></i>
                                        <span class="tabular-nums">{{ $row['_date_display'] ?: '-' }}{{ $row['_time_display'] ? ' · '.$row['_time_display'] : '' }}</span>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-16 text-center">
                                    <span class="mx-auto inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400"><i data-lucide="package-search" class="h-6 w-6" aria-hidden="true"></i></span>
                                    <p class="mt-4 text-sm font-semibold text-slate-700">Persediaan belum dapat ditampilkan</p>
                                    <p class="mx-auto mt-1 max-w-sm text-xs leading-relaxed text-slate-500">
                                        @if ($sheetError || $googleConnectionError)
                                            Data Stock Consumable belum dapat dimuat. Coba perbarui koneksi atau muat ulang halaman.
                                        @elseif (! $googleConnected)
                                            Hubungkan Google untuk menampilkan Stock Consumable.
                                        @elseif ($totalRows > 0)
                                            Tidak ada item yang sesuai dengan filter saat ini.
                                        @else
                                            Data Stock Consumable belum tersedia.
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

        @include('admin.appsheet.partials.image-preview-modal')
    </div>
</x-layouts.admin>
