<div class="overflow-x-auto">
    <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50 text-left text-[11px] uppercase tracking-wider text-slate-500">
            <tr>
                <th class="px-4 py-3">Waktu / Nomor</th>
                <th class="px-4 py-3">Barang</th>
                <th class="px-4 py-3">Jenis</th>
                <th class="px-4 py-3 text-right">Jumlah</th>
                <th class="px-4 py-3">Stok</th>
                <th class="px-4 py-3">Sumber / Pelaku</th>
                <th class="px-4 py-3">Aksi</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100 bg-white text-xs text-slate-700">
            @forelse ($transactions as $transaction)
                <tr>
                    <td class="whitespace-nowrap px-4 py-3">
                        <div class="font-semibold text-slate-800">{{ $transaction->transaction_at?->format('d/m/Y H:i') ?? '-' }}</div>
                        <div class="text-slate-500">{{ $transaction->transaction_number }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-semibold text-slate-800">{{ $transaction->item_name_snapshot }}</div>
                        <div class="text-slate-500">{{ $transaction->item_uid_snapshot }}</div>
                    </td>
                    <td class="px-4 py-3"><a href="{{ route('admin.inventory.transactions.show', $transaction) }}" class="rounded bg-blue-50 px-2 py-1 font-semibold text-blue-700">Detail</a></td>
                    <td class="whitespace-nowrap px-4 py-3">{{ str($transaction->transaction_type)->replace('_', ' ')->title() }}</td>
                    <td class="whitespace-nowrap px-4 py-3 text-right font-semibold">{{ $transaction->quantity }} {{ $transaction->unit_snapshot }}</td>
                    <td class="whitespace-nowrap px-4 py-3">{{ $transaction->stock_before }} → {{ $transaction->stock_after }}</td>
                    <td class="px-4 py-3">
                        <div class="font-medium">{{ str($transaction->source)->replace('_', ' ')->title() }}</div>
                        <div class="text-slate-500">{{ $transaction->inventoryUser?->name ?? $transaction->womsUser?->name ?? 'Sistem' }}</div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-slate-500">Belum ada transaksi.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
