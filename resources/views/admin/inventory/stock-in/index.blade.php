<x-layouts.admin title="Stok Masuk Inventory">
    <div class="admin-compact space-y-4">
        @include('admin.inventory.partials.header', ['icon' => 'package-plus', 'title' => 'Stok Masuk', 'description' => 'Pencatatan penerimaan stok oleh admin gudang.'])

        @if (session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
        @endif

        <div class="flex justify-end">
            <a href="{{ route('admin.inventory.stock-in.create') }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                <i data-lucide="package-plus" class="h-4 w-4"></i> Tambah Stok
            </a>
        </div>

        <form method="GET" class="grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-6">
            <input name="search" value="{{ request('search') }}" placeholder="Cari transaksi, UID, barang, referensi" class="rounded-lg border-slate-300 md:col-span-2">
            <select name="item_id" class="rounded-lg border-slate-300">
                <option value="">Semua barang</option>
                @foreach ($items as $item)
                    <option value="{{ $item->id }}" @selected((string) request('item_id') === (string) $item->id)>{{ $item->uid }} · {{ $item->name }}</option>
                @endforeach
            </select>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="rounded-lg border-slate-300">
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="rounded-lg border-slate-300">
            <button class="rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white">Filter</button>
        </form>

        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-[1180px] divide-y divide-slate-200 text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-4 py-3">Nomor Transaksi</th><th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">UID</th><th class="px-4 py-3">Nama Barang</th>
                            <th class="px-4 py-3 text-right">Jumlah Masuk</th><th class="px-4 py-3 text-right">Stok Sebelum</th>
                            <th class="px-4 py-3 text-right">Stok Setelah</th><th class="px-4 py-3">Unit</th>
                            <th class="px-4 py-3">Admin</th><th class="px-4 py-3">Referensi</th><th class="px-4 py-3">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($transactions as $transaction)
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3 font-semibold text-blue-700">{{ $transaction->transaction_number }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $transaction->transaction_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 font-semibold">{{ $transaction->item_uid_snapshot }}</td>
                                <td class="px-4 py-3">{{ $transaction->item_name_snapshot }}</td>
                                <td class="px-4 py-3 text-right font-bold text-emerald-700">+{{ $transaction->quantity }}</td>
                                <td class="px-4 py-3 text-right">{{ $transaction->stock_before }}</td>
                                <td class="px-4 py-3 text-right font-semibold">{{ $transaction->stock_after }}</td>
                                <td class="px-4 py-3">{{ $transaction->unit_snapshot }}</td>
                                <td class="px-4 py-3">{{ $transaction->womsUser?->name ?? '-' }}</td>
                                <td class="px-4 py-3">{{ $transaction->reference_number ?? '-' }}</td>
                                <td class="max-w-60 px-4 py-3">{{ $transaction->notes ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="px-4 py-10 text-center text-slate-500">Belum ada transaksi stok masuk.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if (method_exists($transactions, 'links'))<div class="border-t border-slate-200 px-4 py-3">{{ $transactions->links() }}</div>@endif
        </section>
    </div>
</x-layouts.admin>
