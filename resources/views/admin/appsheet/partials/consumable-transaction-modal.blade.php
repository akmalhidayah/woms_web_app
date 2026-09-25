<div
    x-data="{
        open: false,
        submitting: false,
        resultsOpen: false,
        search: '',
        selected: null,
        inputType: 'STOCK IN',
        quantity: '',
        usagePurpose: '',
        requestType: '',
        items: @js($transactionItems),
        requestTypes: @js($requestTypes),
        show() {
            this.submitting = false;
            this.resultsOpen = false;
            this.search = '';
            this.selected = null;
            this.inputType = 'STOCK IN';
            this.quantity = '';
            this.usagePurpose = '';
            this.requestType = '';
            this.open = true;
            document.body.classList.add('overflow-hidden');
            this.$nextTick(() => this.$refs.search?.focus());
        },
        close() {
            if (this.submitting) return;
            this.open = false;
            this.resultsOpen = false;
            document.body.classList.remove('overflow-hidden');
        },
        filteredItems() {
            const needle = this.search.trim().toLocaleLowerCase('id-ID');
            return this.items.filter((item) => needle === ''
                || item.uid.toLocaleLowerCase('id-ID').includes(needle)
                || item.name.toLocaleLowerCase('id-ID').includes(needle))
                .slice(0, 50);
        },
        selectItem(item) {
            this.selected = item;
            this.search = `${item.uid} — ${item.name || '-'}`;
            this.resultsOpen = false;
            this.$nextTick(() => this.$refs.quantity?.focus());
        },
        parsedQuantity() {
            if (String(this.quantity).trim() === '') return null;
            const value = Number(this.quantity);
            return Number.isFinite(value) && value > 0 ? value : null;
        },
        stockAfter() {
            if (!this.selected || this.parsedQuantity() === null) return null;
            return this.inputType === 'STOCK IN'
                ? Number(this.selected.stock) + this.parsedQuantity()
                : Number(this.selected.stock) - this.parsedQuantity();
        },
        format(value) {
            const number = Number(value);
            return Number.isFinite(number)
                ? new Intl.NumberFormat('id-ID', { maximumFractionDigits: 20 }).format(number)
                : '-';
        },
        canSubmit() {
            if (!this.selected || this.parsedQuantity() === null) return false;
            if (this.inputType === 'STOCK OUT') {
                return this.usagePurpose.trim() !== '' && this.requestType !== '';
            }
            return true;
        },
    }"
    x-on:open-consumable-transaction.window="show()"
    x-on:keydown.escape.window="if (open) close()"
    x-show="open"
    x-cloak
    x-on:click.self="close()"
    class="fixed inset-0 z-[95] flex items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm"
    role="dialog"
    aria-modal="true"
    aria-labelledby="consumable-transaction-title"
>
    <div class="w-full max-w-2xl overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-slate-50 px-5 py-4">
            <div>
                <h2 id="consumable-transaction-title" class="text-base font-bold text-slate-900">Tambah Transaksi Consumable</h2>
                <p class="mt-1 text-xs text-slate-500">Transaksi akan dicatat ke History dan stock terbaru diperbarui di Google Sheets.</p>
            </div>
            <button type="button" x-on:click="close()" :disabled="submitting" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-200 hover:text-slate-700 disabled:opacity-50" aria-label="Tutup modal tambah transaksi">
                <i data-lucide="x" class="h-4 w-4" aria-hidden="true"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.appsheet.history-consumable.transactions.store') }}" x-on:submit="if (!canSubmit()) { $event.preventDefault(); return; } submitting = true">
            @csrf
            <input type="hidden" name="transaction_token" value="{{ $transactionToken }}">
            <input type="hidden" name="uid" :value="selected?.uid || ''">
            <input type="hidden" name="input_type" :value="inputType">

            <div class="max-h-[72vh] space-y-5 overflow-y-auto px-5 py-5">
                <div class="relative">
                    <label for="consumable-transaction-search" class="mb-1.5 block text-xs font-semibold text-slate-700">Consumable</label>
                    <div class="relative">
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-slate-400" aria-hidden="true"></i>
                        <input
                            x-ref="search"
                            id="consumable-transaction-search"
                            type="search"
                            x-model="search"
                            x-on:focus="resultsOpen = true"
                            x-on:input="selected = null; resultsOpen = true"
                            placeholder="Cari UID atau nama consumable..."
                            autocomplete="off"
                            class="block h-11 w-full rounded-xl border border-slate-300 bg-white pl-10 pr-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            role="combobox"
                            aria-controls="consumable-transaction-results"
                            :aria-expanded="resultsOpen"
                        >
                    </div>
                    <div id="consumable-transaction-results" x-show="resultsOpen" class="absolute z-20 mt-1 max-h-64 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl" role="listbox">
                        <template x-for="(item, index) in filteredItems()" :key="`${item.uid}-${index}`">
                            <button type="button" x-on:click="selectItem(item)" class="flex w-full items-start justify-between gap-3 rounded-lg px-3 py-2.5 text-left transition hover:bg-blue-50" role="option">
                                <span class="min-w-0">
                                    <span class="block truncate text-xs font-bold text-slate-900" x-text="item.name || '-'"></span>
                                    <span class="mt-0.5 block font-mono text-[10px] font-semibold text-blue-600" x-text="item.uid"></span>
                                </span>
                                <span class="shrink-0 text-[10px] font-semibold text-slate-500" x-text="`Stock: ${format(item.stock)} ${item.unit || 'unit'}`"></span>
                            </button>
                        </template>
                        <p x-show="filteredItems().length === 0" class="px-3 py-5 text-center text-xs text-slate-500">Consumable tidak ditemukan.</p>
                    </div>
                </div>

                <div x-show="selected" class="rounded-xl border border-blue-100 bg-blue-50/70 px-4 py-3">
                    <p class="text-sm font-bold text-slate-900" x-text="selected?.name || '-'"></p>
                    <div class="mt-1 flex flex-wrap gap-x-3 gap-y-1 text-[11px] text-slate-600">
                        <span class="font-mono font-semibold text-blue-700" x-text="selected?.uid"></span>
                        <span x-text="selected?.category || '-'"></span>
                        <span>Stok tersedia: <strong x-text="`${format(selected?.stock)} ${selected?.unit || 'unit'}`"></strong></span>
                    </div>
                </div>

                <fieldset>
                    <legend class="mb-2 text-xs font-semibold text-slate-700">Jenis Transaksi</legend>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" x-on:click="inputType = 'STOCK IN'" :class="inputType === 'STOCK IN' ? 'border-emerald-500 bg-emerald-50 text-emerald-700 ring-2 ring-emerald-100' : 'border-slate-200 bg-white text-slate-600'" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border text-xs font-bold transition">
                            <i data-lucide="arrow-down-to-line" class="h-4 w-4" aria-hidden="true"></i> STOCK IN
                        </button>
                        <button type="button" x-on:click="inputType = 'STOCK OUT'" :disabled="requestTypes.length === 0" :class="inputType === 'STOCK OUT' ? 'border-amber-500 bg-amber-50 text-amber-700 ring-2 ring-amber-100' : 'border-slate-200 bg-white text-slate-600'" class="inline-flex h-11 items-center justify-center gap-2 rounded-xl border text-xs font-bold transition disabled:cursor-not-allowed disabled:opacity-50">
                            <i data-lucide="arrow-up-from-line" class="h-4 w-4" aria-hidden="true"></i> STOCK OUT
                        </button>
                    </div>
                    <p x-show="requestTypes.length === 0" class="mt-2 text-[11px] text-amber-700">STOCK OUT belum tersedia karena pilihan Jenis Permintaan belum ditemukan pada History.</p>
                </fieldset>

                <div>
                    <label for="consumable-transaction-quantity" class="mb-1.5 block text-xs font-semibold text-slate-700">Qty</label>
                    <div class="flex items-center gap-2">
                        <input x-ref="quantity" id="consumable-transaction-quantity" name="quantity" type="number" step="any" min="0" required inputmode="decimal" x-model="quantity" class="block h-11 min-w-0 flex-1 rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                        <span class="min-w-12 text-xs font-semibold text-slate-500" x-text="selected?.unit || 'unit'"></span>
                    </div>
                </div>

                <div x-show="inputType === 'STOCK OUT'" class="space-y-4">
                    <div>
                        <label for="consumable-transaction-purpose" class="mb-1.5 block text-xs font-semibold text-slate-700">Tujuan Penggunaan</label>
                        <textarea id="consumable-transaction-purpose" name="usage_purpose" rows="3" maxlength="1000" :required="inputType === 'STOCK OUT'" x-model="usagePurpose" placeholder="Contoh: Perbaikan Rotary Feeder" class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"></textarea>
                    </div>
                    <div>
                        <label for="consumable-transaction-request-type" class="mb-1.5 block text-xs font-semibold text-slate-700">Jenis Permintaan</label>
                        <select id="consumable-transaction-request-type" name="request_type" :required="inputType === 'STOCK OUT'" x-model="requestType" class="block h-11 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100">
                            <option value="">Pilih jenis permintaan</option>
                            <template x-for="type in requestTypes" :key="type">
                                <option :value="type" x-text="type"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div x-show="selected" class="grid grid-cols-3 overflow-hidden rounded-xl border border-slate-200 bg-slate-50 text-center">
                    <div class="px-2 py-3">
                        <p class="text-[9px] font-semibold uppercase tracking-wide text-slate-400">Stok Saat Ini</p>
                        <p class="mt-1 text-sm font-black text-slate-800" x-text="format(selected?.stock)"></p>
                    </div>
                    <div class="border-x border-slate-200 px-2 py-3">
                        <p class="text-[9px] font-semibold uppercase tracking-wide text-slate-400" x-text="inputType"></p>
                        <p class="mt-1 text-sm font-black" :class="inputType === 'STOCK IN' ? 'text-emerald-700' : 'text-amber-700'" x-text="parsedQuantity() === null ? '-' : `${inputType === 'STOCK IN' ? '+' : '-'}${format(parsedQuantity())}`"></p>
                    </div>
                    <div class="px-2 py-3">
                        <p class="text-[9px] font-semibold uppercase tracking-wide text-slate-400">Stok Setelah</p>
                        <p class="mt-1 text-sm font-black" :class="stockAfter() !== null && stockAfter() < 0 ? 'text-rose-600' : 'text-slate-800'" x-text="stockAfter() === null ? '-' : format(stockAfter())"></p>
                    </div>
                </div>

                <p class="text-[11px] leading-relaxed text-slate-500">Preview memakai data halaman. Saat disimpan, sistem selalu membaca stock terbaru langsung dari Google Sheets dan menghitung ulang transaksi.</p>
            </div>

            <div class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:justify-end">
                <button type="button" x-on:click="close()" :disabled="submitting" class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 disabled:opacity-50">Batal</button>
                <button type="submit" :disabled="submitting || !canSubmit()" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">
                    <i data-lucide="save" class="h-3.5 w-3.5" aria-hidden="true"></i>
                    <span x-text="submitting ? 'Menyimpan...' : 'Simpan Transaksi'"></span>
                </button>
            </div>
        </form>
    </div>
</div>
