<x-layouts.admin title="Stock Consumable">
    <div class="min-w-0 space-y-4">
        <section class="rounded-[1.35rem] border border-blue-100 bg-blue-50 px-5 py-4 shadow-sm">
            <div class="flex flex-wrap items-center gap-4">
                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-200">
                    <i data-lucide="package" class="h-5 w-5" aria-hidden="true"></i>
                </span>
                <div>
                    <h1 class="text-[1.3rem] font-bold leading-tight tracking-tight text-slate-900">Stock Consumable</h1>
                    <p class="mt-1 text-xs text-slate-500">Monitoring stok consumable dari AppSheet.</p>
                </div>
                @include('admin.appsheet.partials.google-connection', ['googleReturnTo' => 'stock'])
            </div>
        </section>

        @include('admin.appsheet.partials.google-messages')

        <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm" aria-label="Filter Stock Consumable">
            <form method="GET" action="{{ route('admin.appsheet.stock-consumable.index') }}" class="grid gap-3 md:grid-cols-3">
                <div>
                    <label for="stock-consumable-search" class="mb-1.5 block text-[11px] font-semibold text-slate-600">Search</label>
                    <input id="stock-consumable-search" name="search" value="{{ $filters['search'] }}" type="search" placeholder="UID, jenis, deskripsi, sub category, lokasi..." class="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700">
                </div>
                <div>
                    <label for="stock-consumable-type" class="mb-1.5 block text-[11px] font-semibold text-slate-600">Jenis Consumable</label>
                    <select id="stock-consumable-type" name="jenis" class="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700">
                        <option value="">Semua jenis consumable</option>
                        @foreach ($subCategories as $subCategory)
                            <option value="{{ $subCategory }}" @selected($filters['jenis'] === $subCategory)>{{ $subCategory }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="stock-consumable-status" class="mb-1.5 block text-[11px] font-semibold text-slate-600">Status Stok</label>
                    <select id="stock-consumable-status" name="status" class="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700">
                        <option value="">Semua status stok</option>
                        <option value="habis" @selected($filters['status'] === 'habis')>Habis</option>
                        <option value="tersedia" @selected($filters['status'] === 'tersedia')>Tersedia</option>
                    </select>
                </div>
                <div class="flex items-center gap-3 md:col-span-3">
                    <button type="submit" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">Terapkan</button>
                    <a href="{{ route('admin.appsheet.stock-consumable.index') }}" class="text-xs font-semibold text-slate-500 hover:text-blue-600">Reset</a>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3 text-xs text-slate-500">
                {{ number_format($rows->total(), 0, ',', '.') }} data ditemukan dari {{ number_format($totalRows, 0, ',', '.') }} data consumable · Terbaru lebih dahulu
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <caption class="sr-only">Stock Consumable</caption>
                    <thead class="border-b border-slate-200 bg-slate-50 text-[10px] uppercase tracking-[0.12em] text-slate-500">
                        <tr>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left font-semibold">UID</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left font-semibold">Jenis</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left font-semibold">Deskripsi</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left font-semibold">Sub Category</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-right font-semibold">Stock In</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-right font-semibold">Stock Out</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-right font-semibold">Stok Saat Ini</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left font-semibold">Unit</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left font-semibold">Lokasi</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left font-semibold">Status</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left font-semibold">Updated By</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left font-semibold">Updated Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr class="border-b border-slate-100 align-top text-slate-700 hover:bg-slate-50">
                                <td class="whitespace-nowrap px-4 py-3">{{ $row['UID'] }}</td>
                                <td class="px-4 py-3">{{ $row['TYPE CATEGORY'] }}</td>
                                <td class="min-w-48 max-w-xs break-words px-4 py-3">{{ $row['DESC.'] }}</td>
                                <td class="px-4 py-3">{{ $row['SUB CATEGORY'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $row['STOCK IN'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $row['STOCK OUT'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $row['SPARE STOCK'] }}</td>
                                <td class="px-4 py-3">{{ $row['STN'] }}</td>
                                <td class="max-w-xs break-words px-4 py-3">{{ $row['LOC'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3">
                                    @if ($row['_stock_status'] === 'habis')
                                        <span class="inline-flex rounded-full bg-rose-50 px-2 py-1 text-[10px] font-semibold uppercase text-rose-700 ring-1 ring-rose-200">Habis</span>
                                    @elseif ($row['_stock_status'] === 'tersedia')
                                        <span class="inline-flex rounded-full bg-emerald-50 px-2 py-1 text-[10px] font-semibold uppercase text-emerald-700 ring-1 ring-emerald-200">Tersedia</span>
                                    @else
                                        <span class="text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="max-w-xs break-words px-4 py-3">{{ $row['INPUT. BY'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $row['_date_display'] }}</td>
                            </tr>
                        @empty
                        <tr>
                            <td colspan="12" class="px-5 py-14 text-center">
                                <i data-lucide="package" class="mx-auto h-8 w-8 text-slate-300" aria-hidden="true"></i>
                                <p class="mt-3 text-xs text-slate-500">
                                    @if ($sheetError || $googleConnectionError)
                                        Data Stock Consumable belum dapat dimuat.
                                    @elseif (! $googleConnected)
                                        Hubungkan Google untuk menampilkan Stock Consumable.
                                    @elseif ($totalRows > 0)
                                        Tidak ada data yang sesuai dengan filter.
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
                <div class="border-t border-slate-200 px-4 py-3">{{ $rows->links() }}</div>
            @endif
        </section>
    </div>
</x-layouts.admin>
