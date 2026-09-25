<div
    x-data="{
        open: false,
        submitting: false,
        item: { identifier: '', code: '', name: '', unit: '', stockIn: null, stockOut: null, fields: [] },
        show(payload) {
            this.item = {
                identifier: payload.identifier || '',
                code: payload.code || payload.identifier || '',
                name: payload.name || '-',
                unit: payload.unit || 'unit',
                stockIn: payload.stockIn,
                stockOut: payload.stockOut,
                fields: (payload.fields || []).map((field) => ({ ...field, value: String(field.value) })),
            };
            this.submitting = false;
            this.open = true;
            document.body.classList.add('overflow-hidden');
        },
        close() {
            if (this.submitting) return;
            this.open = false;
            document.body.classList.remove('overflow-hidden');
        },
        number(value) {
            if (value === null || value === undefined || String(value).trim() === '') return null;
            const parsed = Number(value);
            return Number.isFinite(parsed) ? parsed : null;
        },
        format(value) {
            const number = this.number(value);
            return number === null ? '-' : new Intl.NumberFormat('id-ID', { maximumFractionDigits: 20 }).format(number);
        },
        adjustment() {
            if (@js($stockKind) !== 'consumable-bms' || !this.item.fields[0]) return null;
            const current = this.number(this.item.fields[0].originalValue);
            const next = this.number(this.item.fields[0].value);
            return current === null || next === null ? null : next - current;
        },
    }"
    x-on:open-stock-editor.window="show($event.detail)"
    x-on:keydown.escape.window="if (open) close()"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-[90] flex items-center justify-center bg-slate-950/55 p-4 backdrop-blur-sm"
    role="dialog"
    aria-modal="true"
    aria-labelledby="stock-edit-title"
    x-on:click.self="close()"
>
    <div class="w-full max-w-lg overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl">
        <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-slate-50 px-5 py-4">
            <div class="min-w-0">
                <h2 id="stock-edit-title" class="text-base font-bold text-slate-900">Edit Stock</h2>
                <p class="mt-1 truncate text-xs text-slate-500">
                    <span class="font-mono font-semibold text-blue-600" x-text="item.code"></span>
                    <span aria-hidden="true"> • </span>
                    <span x-text="item.name"></span>
                </p>
            </div>
            <button type="button" x-on:click="close()" :disabled="submitting" class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-slate-400 transition hover:bg-slate-200 hover:text-slate-700 disabled:cursor-not-allowed disabled:opacity-50" aria-label="Tutup modal edit stock">
                <i data-lucide="x" class="h-4 w-4" aria-hidden="true"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.appsheet.stock.update', ['stockKind' => $stockKind]) }}" x-on:submit="submitting = true">
            @csrf
            @method('PATCH')
            <input type="hidden" name="identifier" :value="item.identifier">

            <div class="max-h-[70vh] space-y-4 overflow-y-auto px-5 py-5">
                @if ($stockKind === 'consumable-bms')
                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-3">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">Stok Saat Ini</p>
                            <p class="mt-1 text-lg font-black text-slate-900"><span x-text="format(item.fields[0]?.originalValue)"></span> <span class="text-xs text-slate-500" x-text="item.unit"></span></p>
                        </div>
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-3">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-emerald-600">Stock In</p>
                            <p class="mt-1 text-lg font-black text-emerald-700"><span x-text="format(item.stockIn)"></span> <span class="text-xs" x-text="item.unit"></span></p>
                        </div>
                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-3 py-3">
                            <p class="text-[10px] font-semibold uppercase tracking-wide text-amber-600">Stock Out</p>
                            <p class="mt-1 text-lg font-black text-amber-700"><span x-text="format(item.stockOut)"></span> <span class="text-xs" x-text="item.unit"></span></p>
                        </div>
                    </div>
                @endif

                <template x-for="field in item.fields" :key="field.name">
                    <div>
                        <label class="mb-1.5 block text-xs font-semibold text-slate-700" :for="`stock-field-${field.name}`" x-text="field.label"></label>
                        <div class="flex items-center gap-2">
                            <input
                                type="number"
                                step="any"
                                required
                                inputmode="decimal"
                                :id="`stock-field-${field.name}`"
                                :name="field.name"
                                x-model="field.value"
                                class="block h-11 min-w-0 flex-1 rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            >
                            <span class="min-w-10 text-xs font-semibold text-slate-500" x-text="item.unit"></span>
                        </div>
                        <input type="hidden" :name="field.originalName" :value="field.originalValue">
                    </div>
                </template>

                @if ($stockKind === 'consumable-bms')
                    <div class="rounded-xl border px-4 py-3" :class="adjustment() === null || adjustment() === 0 ? 'border-slate-200 bg-slate-50' : (adjustment() > 0 ? 'border-emerald-200 bg-emerald-50' : 'border-amber-200 bg-amber-50')">
                        <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-500">Perubahan</p>
                        <p class="mt-1 text-lg font-black" :class="adjustment() > 0 ? 'text-emerald-700' : (adjustment() < 0 ? 'text-amber-700' : 'text-slate-700')">
                            <span x-text="adjustment() === null ? '-' : `${adjustment() > 0 ? '+' : ''}${format(adjustment())}`"></span>
                            <span class="text-xs" x-text="item.unit"></span>
                        </p>
                        <p class="mt-0.5 text-xs font-semibold text-slate-600" x-text="adjustment() > 0 ? 'Penambahan Stock' : (adjustment() < 0 ? 'Pengurangan Stock' : 'Tidak ada perubahan')"></p>
                    </div>
                @endif

                <p class="text-[11px] leading-relaxed text-slate-500">Sebelum menyimpan, sistem akan membaca ulang nilai terbaru langsung dari Google Sheets untuk mencegah perubahan pengguna lain tertimpa.</p>
            </div>

            <div class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:justify-end">
                <button type="button" x-on:click="close()" :disabled="submitting" class="inline-flex h-10 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 text-xs font-semibold text-slate-700 transition hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50">Batal</button>
                <button type="submit" :disabled="submitting" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 text-xs font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">
                    <i data-lucide="save" class="h-3.5 w-3.5" aria-hidden="true"></i>
                    <span x-text="submitting ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                </button>
            </div>
        </form>
    </div>
</div>
