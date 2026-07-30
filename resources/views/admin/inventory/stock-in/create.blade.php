<x-layouts.admin title="Tambah Stok Inventory">
    <div class="admin-compact space-y-4">
        @include('admin.inventory.partials.header', ['icon' => 'package-plus', 'title' => 'Tambah Stok', 'description' => 'Tambahkan penerimaan stok melalui transaksi yang dapat diaudit.'])

        <form
            method="POST"
            action="{{ route('admin.inventory.stock-in.store') }}"
            x-data="{
                submitting: false,
                itemId: '{{ old('inventory_item_id', $selectedItemId) }}',
                quantity: '{{ old('quantity') }}',
                items: @js($items->keyBy('id')),
                get selected() { return this.items[this.itemId] || null },
                get estimate() {
                    if (!this.selected || !this.quantity || Number.isNaN(Number(this.quantity))) return '-';
                    return (Number(this.selected.current_stock) + Number(this.quantity)).toFixed(3) + ' ' + this.selected.unit;
                }
            }"
            x-on:submit="submitting = true"
            class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
        >
            @csrf
            <div class="grid gap-5 lg:grid-cols-3">
                <div class="space-y-4 lg:col-span-2">
                    <div>
                        <label for="inventory_item_id" class="mb-1 block text-sm font-semibold text-slate-700">Barang</label>
                        <select id="inventory_item_id" name="inventory_item_id" x-model="itemId" required class="w-full rounded-lg border-slate-300">
                            <option value="">Pilih barang aktif</option>
                            @foreach ($items as $item)
                                <option value="{{ $item->id }}">{{ $item->uid }} · {{ $item->name }} · Stok {{ $item->current_stock }} {{ $item->unit }}</option>
                            @endforeach
                        </select>
                        @error('inventory_item_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="quantity" class="mb-1 block text-sm font-semibold text-slate-700">Jumlah Stok Masuk</label>
                            <div class="flex">
                                <input id="quantity" name="quantity" x-model="quantity" value="{{ old('quantity') }}" inputmode="decimal" placeholder="0.000" required class="min-w-0 flex-1 rounded-l-lg border-slate-300">
                                <span class="inline-flex min-w-16 items-center justify-center rounded-r-lg border border-l-0 border-slate-300 bg-slate-50 px-3 text-sm font-semibold" x-text="selected?.unit || '-'"></span>
                            </div>
                            @error('quantity')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="transaction_at" class="mb-1 block text-sm font-semibold text-slate-700">Tanggal Transaksi</label>
                            <input id="transaction_at" type="datetime-local" name="transaction_at" value="{{ old('transaction_at', now()->format('Y-m-d\\TH:i')) }}" required readonly class="w-full rounded-lg border-slate-300 bg-slate-50">
                            <p class="mt-1 text-xs text-slate-500">Waktu transaksi ditetapkan oleh server saat disimpan.</p>
                            @error('transaction_at')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label for="reference_number" class="mb-1 block text-sm font-semibold text-slate-700">Nomor Referensi / Dokumen</label>
                        <input id="reference_number" name="reference_number" value="{{ old('reference_number') }}" maxlength="100" class="w-full rounded-lg border-slate-300">
                        @error('reference_number')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="notes" class="mb-1 block text-sm font-semibold text-slate-700">Catatan</label>
                        <textarea id="notes" name="notes" rows="4" maxlength="2000" class="w-full rounded-lg border-slate-300">{{ old('notes') }}</textarea>
                        @error('notes')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
                    </div>
                </div>
                <aside class="rounded-xl border border-blue-100 bg-blue-50 p-4">
                    <h2 class="font-bold text-slate-900">Ringkasan</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div><dt class="text-slate-500">Stok saat ini</dt><dd class="font-bold" x-text="selected ? selected.current_stock + ' ' + selected.unit : '-'"></dd></div>
                        <div><dt class="text-slate-500">Jumlah ditambahkan</dt><dd class="font-bold text-emerald-700" x-text="quantity ? '+ ' + quantity + ' ' + (selected?.unit || '') : '-'"></dd></div>
                        <div class="border-t border-blue-200 pt-3"><dt class="text-slate-500">Perkiraan stok setelah</dt><dd class="text-lg font-bold text-blue-700" x-text="estimate"></dd></div>
                    </dl>
                    <p class="mt-4 text-xs text-slate-500">Ringkasan ini hanya preview. Nilai akhir dihitung dan dikunci oleh layanan stok di server.</p>
                </aside>
            </div>
            <div class="mt-5 flex justify-end gap-2 border-t border-slate-200 pt-4">
                <a href="{{ route('admin.inventory.stock-in.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Batal</a>
                <button type="submit" :disabled="submitting" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60">
                    <i data-lucide="package-plus" class="h-4 w-4"></i>
                    <span x-text="submitting ? 'Menyimpan...' : 'Simpan Stok Masuk'"></span>
                </button>
            </div>
        </form>
    </div>
</x-layouts.admin>
