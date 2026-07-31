<x-layouts.admin title="Detail Transaksi Inventory">
    <div class="admin-compact space-y-4">
        @include('admin.inventory.partials.header', ['icon'=>'receipt-text','title'=>'Detail Transaksi','description'=>$transaction->transaction_number])
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            @php($actor=$transaction->inventoryUser ?? $transaction->womsUser)
            <dl class="grid gap-4 text-sm md:grid-cols-2 lg:grid-cols-3">
                @foreach([
                    'Waktu'=>$transaction->transaction_at?->format('d/m/Y H:i'),
                    'Sumber'=>str($transaction->source)->replace('_',' ')->title(),
                    'Jenis'=>str($transaction->transaction_type)->replace('_',' ')->title(),
                    'Barang'=>$transaction->item_uid_snapshot.' · '.$transaction->item_name_snapshot,
                    'Jumlah'=>number_format((int)$transaction->quantity,0,',','.').' '.$transaction->unit_snapshot,
                    'Perubahan Stok'=>number_format((int)$transaction->stock_before,0,',','.').' → '.number_format((int)$transaction->stock_after,0,',','.'),
                    'Pelaku'=>$actor?->name ?? 'Sistem',
                    'Nomor Pegawai'=>$transaction->inventoryUser?->employee_number ?? '-',
                    'Departemen'=>$transaction->inventoryUser?->department ?? '-',
                    'Jenis Permintaan'=>$transaction->requestType?->name ?? '-',
                    'Tujuan'=>$transaction->purpose ?? '-',
                    'Catatan'=>$transaction->notes ?? '-',
                    'Referensi'=>$transaction->reference_number ?? '-',
                ] as $label=>$value)<div><dt class="text-slate-500">{{ $label }}</dt><dd class="mt-1 font-semibold">{{ $value }}</dd></div>@endforeach
            </dl>
        </section>
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"><h2 class="font-bold">Attachment</h2><div class="mt-3 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">@forelse($transaction->attachments as $attachment)<a target="_blank" href="{{ route('admin.inventory.attachments.show',$attachment) }}" class="rounded-lg border p-3 font-semibold text-blue-700">{{ str($attachment->attachment_type)->replace('_',' ')->title() }}<span class="block text-xs font-normal text-slate-500">{{ $attachment->original_name }}</span></a>@empty<p class="text-sm text-slate-500">Tidak ada attachment.</p>@endforelse</div></section>
        <a href="{{ route('admin.inventory.transactions.index') }}" class="inline-flex rounded-lg border px-4 py-2 font-semibold">Kembali</a>
    </div>
</x-layouts.admin>
