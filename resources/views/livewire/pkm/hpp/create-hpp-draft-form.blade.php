<div
    x-data="{
        groups: @js($groups),
        category: @js(old('kategori_pekerjaan', $hpp?->kategori_pekerjaan ?? 'Fabrikasi')),
        area: @js(\App\Support\HppApprovalFlow::displayArea((string) old('area_pekerjaan', $hpp?->area_pekerjaan ?? 'Dalam'))),
        flowMatrix: @js($flowMatrix),
        catalog: @js($contractCatalog),
        total() {
            return this.groups.reduce((sum, group) => sum + group.items.reduce((itemSum, item) => itemSum + (Number(item.qty || 0) * Number(item.harga_satuan || 0)), 0), 0);
        },
        flow() {
            const categoryKey = this.category === 'Konstruksi' ? 'Konstruksi' : 'Fabrikasi';
            const areaKey = this.area.toLowerCase().includes('luar') ? 'Luar' : (this.area.toLowerCase().includes('workshop') ? 'Workshop' : 'Dalam');
            const bucket = this.total() > 250000000 ? 'over' : 'under';
            return this.flowMatrix?.[categoryKey]?.[areaKey]?.[bucket] ?? [];
        },
        addGroup() {
            this.groups.push({ title: 'Material/Jasa', items: [this.emptyItem()] });
        },
        addItem(group) { group.items.push(this.emptyItem()); },
        emptyItem() {
            return { sub_jenis_item: '', kategori_item: '', nama_item: '', jumlah_item: '', qty: '1', satuan: '', harga_satuan: '0', keterangan: '' };
        },
        unique(values) {
            return [...new Set(values.filter(value => String(value || '').trim() !== ''))];
        },
        groupOptions() {
            return this.unique(this.catalog.map(row => row.jenis_item));
        },
        subTypeOptions(group) {
            return this.unique(this.catalog.filter(row => row.jenis_item === group.title).map(row => row.sub_jenis_item));
        },
        categoryOptions(group, item) {
            return this.unique(this.catalog.filter(row => row.jenis_item === group.title && (!item.sub_jenis_item || row.sub_jenis_item === item.sub_jenis_item)).map(row => row.kategori_item));
        },
        itemOptions(group, item) {
            const rows = this.catalog.filter(row => row.jenis_item === group.title
                && (!item.sub_jenis_item || row.sub_jenis_item === item.sub_jenis_item)
                && (!item.kategori_item || row.kategori_item === item.kategori_item));
            if (item.nama_item && !rows.some(row => row.nama_item === item.nama_item)) {
                rows.push({ nama_item: item.nama_item, satuan: item.satuan, harga_satuan: item.harga_satuan });
            }
            return rows;
        },
        applyCatalogItem(group, item) {
            const row = this.itemOptions(group, item).find(candidate => candidate.nama_item === item.nama_item);
            if (!row) return;
            item.satuan = row.satuan;
            item.harga_satuan = row.harga_satuan;
        },
    }"
    class="space-y-4"
>
    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
            <ul class="list-disc pl-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    <form method="POST" action="{{ $submitRoute }}" class="space-y-4">
        @csrf
        @if ($isEdit) @method('PUT') @endif
        @if ($isEdit)
            <input type="hidden" name="hpp_updated_at" value="{{ $hpp->getRawOriginal('updated_at') }}">
        @endif

        <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <h1 class="text-lg font-bold text-slate-900">{{ $isEdit ? 'Edit Draft HPP' : 'Buat Draft HPP' }}</h1>
            <p class="mt-1 text-xs text-slate-500">Panel PKM hanya menyimpan draft. Submit approval tetap dilakukan Admin.</p>

            <div class="mt-4 grid gap-3 md:grid-cols-2">
                <label class="space-y-1 text-xs font-semibold text-slate-700">
                    <span>Order</span>
                    <select name="order_id" required @disabled($isEdit) class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="">Pilih order</option>
                        @foreach ($orders as $order)
                            <option value="{{ $order->id }}" @selected((int) old('order_id', $hpp?->order_id) === $order->id)>
                                {{ $order->nomor_order }} - {{ $order->nama_pekerjaan }} | {{ $order->unit_kerja }} | {{ $order->seksi }}
                            </option>
                        @endforeach
                    </select>
                    @if ($isEdit)<input type="hidden" name="order_id" value="{{ $hpp->order_id }}">@endif
                </label>
                <label class="space-y-1 text-xs font-semibold text-slate-700">
                    <span>Outline Agreement</span>
                    <select name="outline_agreement_id" required class="w-full rounded-lg border-slate-300 text-sm">
                        <option value="">Pilih OA</option>
                        @foreach ($agreements as $agreement)
                            <option value="{{ $agreement->id }}" @selected((int) old('outline_agreement_id', $hpp?->outline_agreement_id) === $agreement->id)>
                                {{ $agreement->nomor_oa }} - {{ $agreement->nama_kontrak }} | {{ $agreement->unitWork?->name }}
                                | {{ $agreement->current_period_start?->format('d/m/Y') }} - {{ $agreement->current_period_end?->format('d/m/Y') }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <label class="space-y-1 text-xs font-semibold text-slate-700">
                    <span>Kategori Pekerjaan</span>
                    <select name="kategori_pekerjaan" x-model="category" class="w-full rounded-lg border-slate-300 text-sm">
                        @foreach ($kategoriOptions as $option)<option value="{{ $option }}">{{ $option }}</option>@endforeach
                    </select>
                </label>
                <label class="space-y-1 text-xs font-semibold text-slate-700">
                    <span>Area Pekerjaan</span>
                    <select name="area_pekerjaan" x-model="area" class="w-full rounded-lg border-slate-300 text-sm">
                        @foreach ($areaOptions as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                    </select>
                </label>
                <label class="space-y-1 text-xs font-semibold text-slate-700 md:col-span-2">
                    <span>Cost Centre</span>
                    <input name="cost_centre" value="{{ old('cost_centre', $hpp?->cost_centre) }}" class="w-full rounded-lg border-slate-300 text-sm">
                </label>
            </div>
        </section>

        <section class="space-y-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-slate-900">Item HPP</h2>
                <button type="button" @click="addGroup()" class="rounded-lg bg-slate-700 px-3 py-2 text-xs font-semibold text-white">Tambah Group</button>
            </div>
            <template x-for="(group, groupIndex) in groups" :key="groupIndex">
                <div class="rounded-xl border border-slate-200 p-3">
                    <div class="flex gap-2">
                        <select :name="`jenis_label_visible[${groupIndex}]`" x-model="group.title" class="flex-1 rounded-lg border-slate-300 text-sm">
                            <option value="">Pilih jenis item</option>
                            <template x-for="value in unique([...groupOptions(), group.title])" :key="value"><option :value="value" x-text="value"></option></template>
                        </select>
                        <button type="button" @click="addItem(group)" class="rounded-lg bg-blue-600 px-3 text-xs font-semibold text-white">Tambah Item</button>
                    </div>
                    <template x-for="(item, itemIndex) in group.items" :key="itemIndex">
                        <div class="mt-3 grid gap-2 rounded-lg bg-slate-50 p-3 md:grid-cols-4">
                            <select :name="`sub_jenis_item[${groupIndex}][${itemIndex}]`" x-model="item.sub_jenis_item" class="rounded-lg border-slate-300 text-xs">
                                <option value="">Pilih subjenis</option>
                                <template x-for="value in unique([...subTypeOptions(group), item.sub_jenis_item])" :key="value"><option :value="value" x-text="value"></option></template>
                            </select>
                            <select :name="`kategori_item[${groupIndex}][${itemIndex}]`" x-model="item.kategori_item" class="rounded-lg border-slate-300 text-xs">
                                <option value="">Pilih kategori</option>
                                <template x-for="value in unique([...categoryOptions(group, item), item.kategori_item])" :key="value"><option :value="value" x-text="value"></option></template>
                            </select>
                            <select :name="`nama_item[${groupIndex}][${itemIndex}]`" x-model="item.nama_item" @change="applyCatalogItem(group, item)" class="rounded-lg border-slate-300 text-xs">
                                <option value="">Pilih nama item</option>
                                <template x-for="row in itemOptions(group, item)" :key="`${row.nama_item}-${row.satuan}-${row.harga_satuan}`"><option :value="row.nama_item" x-text="row.nama_item"></option></template>
                            </select>
                            <input :name="`jumlah_item[${groupIndex}][${itemIndex}]`" x-model="item.jumlah_item" placeholder="Jumlah" class="rounded-lg border-slate-300 text-xs">
                            <input type="number" step="any" :name="`qty[${groupIndex}][${itemIndex}]`" x-model="item.qty" placeholder="Qty" class="rounded-lg border-slate-300 text-xs">
                            <input :name="`satuan[${groupIndex}][${itemIndex}]`" x-model="item.satuan" placeholder="Satuan" class="rounded-lg border-slate-300 text-xs">
                            <input type="number" step="any" :name="`harga_satuan[${groupIndex}][${itemIndex}]`" x-model="item.harga_satuan" placeholder="Harga satuan" class="rounded-lg border-slate-300 text-xs">
                            <input :value="new Intl.NumberFormat('id-ID').format(Number(item.qty || 0) * Number(item.harga_satuan || 0))" readonly class="rounded-lg border-slate-300 bg-white text-xs">
                            <input :name="`keterangan[${groupIndex}][${itemIndex}]`" x-model="item.keterangan" placeholder="Keterangan" class="rounded-lg border-slate-300 text-xs md:col-span-4">
                        </div>
                    </template>
                </div>
            </template>
            <div class="text-right text-sm font-bold text-slate-900">Total: Rp <span x-text="new Intl.NumberFormat('id-ID').format(total())"></span></div>
        </section>

        <section class="rounded-xl border border-blue-100 bg-blue-50 p-4">
            <h2 class="text-sm font-bold text-blue-900">Preview Flow Approval (read-only)</h2>
            <div class="mt-2 flex flex-wrap gap-2">
                <template x-for="(role, index) in flow()" :key="`${index}-${role}`">
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-semibold text-blue-700" x-text="`${index + 1}. ${role}`"></span>
                </template>
            </div>
        </section>

        <div class="flex justify-end gap-2">
            <a href="{{ route('pkm.hpp.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Kembali</a>
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white">
                {{ $isEdit ? 'Update Draft' : 'Simpan Draft' }}
            </button>
        </div>
    </form>
</div>
