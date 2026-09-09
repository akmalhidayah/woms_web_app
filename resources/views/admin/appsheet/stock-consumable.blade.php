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
                    <input id="stock-consumable-search" name="search" value="{{ $filters['search'] }}" type="search" placeholder="No material, consumable, deskripsi..." class="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700">
                </div>
                <div>
                    <label for="stock-consumable-type" class="mb-1.5 block text-[11px] font-semibold text-slate-600">Jenis Consumable</label>
                    <select id="stock-consumable-type" name="jenis" class="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700">
                        <option value="">Semua jenis consumable</option>
                        @foreach ($types as $type)
                            <option value="{{ $type }}" @selected($filters['jenis'] === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="stock-consumable-status" class="mb-1.5 block text-[11px] font-semibold text-slate-600">Status Stok</label>
                    <select id="stock-consumable-status" name="status" class="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700">
                        <option value="">Semua status stok</option>
                        <option value="habis" @selected($filters['status'] === 'habis')>Habis</option>
                        <option value="rendah" @selected($filters['status'] === 'rendah')>Rendah</option>
                        <option value="aman" @selected($filters['status'] === 'aman')>Aman</option>
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
                {{ number_format($rows->total(), 0, ',', '.') }} data ditemukan dari {{ number_format($totalRows, 0, ',', '.') }} data
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <caption class="sr-only">Stock Consumable</caption>
                    <thead class="border-b border-slate-200 bg-slate-50 text-[10px] uppercase tracking-[0.12em] text-slate-500">
                        <tr>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left font-semibold">No Material</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left font-semibold">Jenis Consumable</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left font-semibold">Consumable</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left font-semibold">Deskripsi</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-right font-semibold">Qty Konsinyasi</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-right font-semibold">Qty Non Konsinyasi</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left font-semibold">Unit</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left font-semibold">Updated By</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left font-semibold">Updated Date</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-right font-semibold">Minimum</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr class="border-b border-slate-100 align-top text-slate-700 hover:bg-slate-50">
                                <td class="whitespace-nowrap px-4 py-3">{{ $row['NO MATERIAL'] }}</td>
                                <td class="px-4 py-3">{{ $row['JENIS CONSUMABLE'] }}</td>
                                <td class="min-w-48 max-w-xs break-words px-4 py-3">{{ $row['CONSUMABLE'] }}</td>
                                <td class="min-w-48 max-w-xs break-words px-4 py-3">{{ $row['DESKRIPSI'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $row['QTY KONSINYASI'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $row['QTY NON KONSINYASI'] }}</td>
                                <td class="px-4 py-3">{{ $row['UNIT'] }}</td>
                                <td class="max-w-xs break-words px-4 py-3">{{ $row['UPD. BY'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $row['_date_display'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $row['MIN'] }}</td>
                            </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="px-5 py-14 text-center">
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
