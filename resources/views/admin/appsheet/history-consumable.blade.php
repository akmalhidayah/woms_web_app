<x-layouts.admin title="History Consumable">
    <div class="min-w-0 space-y-4">
        <section class="rounded-[1.35rem] border border-blue-100 bg-blue-50 px-5 py-4 shadow-sm">
            <div class="flex flex-wrap items-center gap-4">
                <span class="inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-200">
                    <i data-lucide="history" class="h-5 w-5" aria-hidden="true"></i>
                </span>
                <div>
                    <h1 class="text-[1.3rem] font-bold leading-tight tracking-tight text-slate-900">History Consumable</h1>
                    <p class="mt-1 text-xs text-slate-500">Monitoring riwayat transaksi consumable dari AppSheet.</p>
                </div>
                @include('admin.appsheet.partials.google-connection', ['googleReturnTo' => 'history'])
            </div>
        </section>

        @include('admin.appsheet.partials.google-messages')

        <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm" aria-label="Filter History Consumable">
            <form method="GET" action="{{ route('admin.appsheet.history-consumable.index') }}" class="grid gap-3 md:grid-cols-4">
                <div>
                    <label for="history-consumable-search" class="mb-1.5 block text-[11px] font-semibold text-slate-600">Search</label>
                    <input id="history-consumable-search" name="search" value="{{ $filters['search'] }}" type="search" placeholder="UID, consumable, input by, tujuan..." class="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700">
                </div>
                <div>
                    <label for="history-consumable-type" class="mb-1.5 block text-[11px] font-semibold text-slate-600">Input Type</label>
                    <select id="history-consumable-type" name="input_type" class="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700">
                        <option value="">Semua</option>
                        <option value="STOCK IN" @selected($filters['input_type'] === 'STOCK IN')>STOCK IN</option>
                        <option value="STOCK OUT" @selected($filters['input_type'] === 'STOCK OUT')>STOCK OUT</option>
                    </select>
                </div>
                <div>
                    <label for="history-consumable-category" class="mb-1.5 block text-[11px] font-semibold text-slate-600">Category</label>
                    <select id="history-consumable-category" name="category" class="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700">
                        <option value="">Semua category</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category }}" @selected($filters['category'] === $category)>{{ $category }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="history-consumable-date" class="mb-1.5 block text-[11px] font-semibold text-slate-600">Tanggal</label>
                    <input id="history-consumable-date" name="date" value="{{ $filters['date'] }}" type="date" class="block w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs text-slate-700">
                </div>
                <div class="flex items-center gap-3 md:col-span-4">
                    <button type="submit" class="rounded-lg bg-blue-600 px-3 py-2 text-xs font-semibold text-white hover:bg-blue-700">Terapkan</button>
                    <a href="{{ route('admin.appsheet.history-consumable.index') }}" class="text-xs font-semibold text-slate-500 hover:text-blue-600">Reset</a>
                </div>
            </form>
        </section>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3 text-xs text-slate-500">
                {{ number_format($rows->total(), 0, ',', '.') }} data ditemukan dari {{ number_format($totalRows, 0, ',', '.') }} data · Terbaru lebih dahulu
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <caption class="sr-only">History Consumable</caption>
                    <thead class="border-b border-slate-200 bg-slate-50 text-[10px] uppercase tracking-[0.12em] text-slate-500">
                        <tr>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left font-semibold">Tanggal</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left font-semibold">UID</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left font-semibold">Consumable</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left font-semibold">Category</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left font-semibold">Tipe</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-right font-semibold">Qty</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left font-semibold">Tujuan Penggunaan</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left font-semibold">Jenis Permintaan</th>
                            <th scope="col" class="whitespace-nowrap px-4 py-3 text-left font-semibold">Input By</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr class="border-b border-slate-100 align-top text-slate-700 hover:bg-slate-50">
                                <td class="whitespace-nowrap px-4 py-3">{{ $row['_date_display'] }}</td>
                                <td class="max-w-xs break-words px-4 py-3">{{ $row['UID'] }}</td>
                                <td class="min-w-48 max-w-xs break-words px-4 py-3">{{ $row['DESC.'] }}</td>
                                <td class="px-4 py-3">{{ $row['CATEGORY'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $row['INPUT TYPE'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right tabular-nums">{{ $row['QTY'] }}</td>
                                <td class="min-w-48 max-w-xs break-words px-4 py-3">{{ $row['TUJUAN PENGGUNAAN'] }}</td>
                                <td class="px-4 py-3">{{ $row['JENIS PERMINTAAN'] }}</td>
                                <td class="max-w-xs break-words px-4 py-3">{{ $row['INPUT BY'] }}</td>
                            </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="px-5 py-14 text-center">
                                <i data-lucide="history" class="mx-auto h-8 w-8 text-slate-300" aria-hidden="true"></i>
                                <p class="mt-3 text-xs text-slate-500">
                                    @if ($sheetError || $googleConnectionError)
                                        Data History Consumable belum dapat dimuat.
                                    @elseif (! $googleConnected)
                                        Hubungkan Google untuk menampilkan History Consumable.
                                    @elseif ($totalRows > 0)
                                        Tidak ada data yang sesuai dengan filter.
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
                <div class="border-t border-slate-200 px-4 py-3">{{ $rows->links() }}</div>
            @endif
        </section>
    </div>
</x-layouts.admin>
