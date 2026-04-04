<div
    x-data="hppForm({
        orderOptions: @js($orderOptions),
        outlineAgreementOptions: @js($outlineAgreementOptions),
        kategoriOptions: @js($kategoriOptions),
        areaOptions: @js($areaOptions),
        bucketOptions: @js($bucketOptions),
        flowMatrix: @js($flowMatrix),
        initialState: @js($initialState),
    })"
    class="space-y-6"
>
    @if ($errors->any())
        <div class="rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-[13px] text-rose-700">
            <div class="font-semibold">Data HPP belum bisa disimpan.</div>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="rounded-[1.5rem] border border-blue-100 px-6 py-5 shadow-sm" style="background: linear-gradient(135deg, #eef4ff 0%, #f8fbff 48%, #e6f1ff 100%);">
        <div class="flex items-center gap-4">
            <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-200">
                <i data-lucide="pencil-line" class="h-6 w-6"></i>
            </span>
            <div>
                <h1 class="text-[1.65rem] font-bold leading-none tracking-tight text-slate-900">Buat HPP</h1>
                <p class="mt-2 text-[13px] text-slate-500">Order pekerjaan diambil langsung dari database order, lalu nama pekerjaan dan unit kerja terisi otomatis.</p>
            </div>
        </div>
    </section>

    <form method="POST" action="{{ route('admin.hpp.store') }}" class="space-y-6">
        @csrf

        <section class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-[15px] font-semibold text-slate-900">Input HPP</h2>
                    <p class="mt-1 text-[12px] text-slate-500">Approval flow preview ikut berubah dari kategori, area, dan bucket nilai yang dipilih.</p>
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-[10px] font-semibold text-emerald-700">Order DB Connected</span>
            </div>

            @if ($orderOptions === [])
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-[13px] text-amber-700">
                    Belum ada data order. Buat order pekerjaan dulu sebelum membuat HPP.
                </div>
            @endif

            @if ($outlineAgreementOptions === [])
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-[13px] text-amber-700">
                    Belum ada Outline Agreement aktif. Buat OA aktif dulu agar HPP bisa mengambil unit pengendali dan periode OA dari database.
                </div>
            @endif

            <div class="xl:flex xl:items-start xl:gap-6">
                <div class="min-w-0 flex-1 space-y-4">
                    <div class="space-y-1.5">
                        <label for="order_id" class="text-[12px] font-semibold text-slate-700">Order Pekerjaan</label>
                        <select
                            id="order_id"
                            name="order_id"
                            x-model="selectedOrder"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[13px] text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                            @disabled($orderOptions === [])
                        >
                            <option value="">Pilih order pekerjaan</option>
                            <template x-for="order in orderOptions" :key="order.value">
                                <option :value="order.value" x-text="order.label"></option>
                            </template>
                        </select>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-1.5">
                            <label for="nilai_hpp_bucket" class="text-[12px] font-semibold text-slate-700">Nilai HPP</label>
                            <select
                                id="nilai_hpp_bucket"
                                name="nilai_hpp_bucket"
                                x-model="nilaiBucket"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[13px] text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                            >
                                @foreach ($bucketOptions as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <p class="text-[11px] text-slate-400">Bucket dipakai untuk membentuk preview jalur approval awal.</p>
                        </div>

                        <div class="space-y-1.5">
                            <label for="kategori_pekerjaan" class="text-[12px] font-semibold text-slate-700">Kategori Pekerjaan</label>
                            <select
                                id="kategori_pekerjaan"
                                name="kategori_pekerjaan"
                                x-model="kategoriPekerjaan"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[13px] text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                            >
                                @foreach ($kategoriOptions as $option)
                                    <option value="{{ $option }}">{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="space-y-1.5">
                        <label for="area_pekerjaan" class="text-[12px] font-semibold text-slate-700">Area Pekerjaan</label>
                        <select
                            id="area_pekerjaan"
                            name="area_pekerjaan"
                            x-model="areaPekerjaan"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[13px] text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        >
                            @foreach ($areaOptions as $option)
                                <option value="{{ $option }}">{{ $option }}</option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-slate-400">Area disederhanakan ke Dalam dan Luar sesuai flow approval saat ini.</p>
                    </div>

                    <div class="grid gap-4 border-t border-slate-200 pt-4 md:grid-cols-2">
                        <div class="space-y-1.5">
                            <label for="cost_centre" class="text-[12px] font-semibold text-slate-700">Cost Centre</label>
                            <input
                                id="cost_centre"
                                type="text"
                                name="cost_centre"
                                x-model="costCentre"
                                class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-[13px] text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                placeholder="Contoh: CC-WS-014"
                            >
                        </div>

                        <div class="space-y-1.5 md:col-span-2">
                            <label for="nama_pekerjaan_preview" class="text-[12px] font-semibold text-slate-700">Deskripsi / Nama Pekerjaan</label>
                            <textarea
                                id="nama_pekerjaan_preview"
                                rows="3"
                                x-model="namaPekerjaan"
                                readonly
                                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-[13px] text-slate-700"
                            ></textarea>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-1.5">
                            <label for="seksi_peminta_preview" class="text-[12px] font-semibold text-slate-700">Seksi Peminta</label>
                            <input
                                id="seksi_peminta_preview"
                                type="text"
                                x-model="seksiPeminta"
                                readonly
                                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-[13px] text-slate-700"
                            >
                            <p class="text-[11px] text-slate-500">
                                Unit Kerja:
                                <span x-text="unitKerja || '-'"></span>
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <label for="seksi_pengendali_preview" class="text-[12px] font-semibold text-slate-700">Seksi Pengendali</label>
                            <textarea
                                id="seksi_pengendali_preview"
                                rows="2"
                                x-model="seksiPengendali"
                                readonly
                                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-[13px] text-slate-700"
                            ></textarea>
                            <p class="text-[11px] text-slate-500">
                                Unit Kerja:
                                <span x-text="unitKerjaPengendali || '-'"></span>
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div class="space-y-1.5">
                            <label for="outline_agreement_id" class="text-[12px] font-semibold text-slate-700">Outline Agreement (OA)</label>
                            <select
                                id="outline_agreement_id"
                                name="outline_agreement_id"
                                x-model="selectedOutlineAgreement"
                                class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[13px] text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                @disabled($outlineAgreementOptions === [])
                            >
                                <option value="">Pilih Outline Agreement</option>
                                <template x-for="agreement in outlineAgreementOptions" :key="agreement.value">
                                    <option :value="agreement.value" x-text="agreement.label"></option>
                                </template>
                            </select>
                        </div>

                        <div class="space-y-1.5">
                            <label for="periode_outline_agreement" class="text-[12px] font-semibold text-slate-700">Periode OA</label>
                            <input
                                id="periode_outline_agreement"
                                type="text"
                                x-model="periodeOutlineAgreement"
                                readonly
                                class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-[13px] text-slate-700"
                            >
                        </div>
                    </div>
                </div>

                <div class="mt-5 xl:mt-0 xl:w-[340px] xl:shrink-0">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <h3 class="text-[13px] font-semibold text-slate-900">Snapshot Approval Flow</h3>
                                <p class="mt-1 text-[11px] text-slate-500">Preview jalur approval berdasarkan kategori, area, dan bucket nilai HPP.</p>
                            </div>
                            <span class="rounded-full bg-white px-2.5 py-1 text-[10px] font-medium text-slate-600 ring-1 ring-slate-200" x-text="`${approvalFlow.length} step`"></span>
                        </div>

                        <div class="mt-3 flex flex-wrap gap-2">
                            <span class="rounded-full bg-blue-100 px-2.5 py-1 text-[10px] font-semibold tracking-wide text-blue-700" x-text="previewCase || '-'"></span>
                            <span class="rounded-full bg-white px-2.5 py-1 text-[10px] font-medium text-slate-600 ring-1 ring-slate-200" x-text="bucketOptions[nilaiBucket] || '-'"></span>
                        </div>

                        <div class="mt-3 rounded-xl border border-slate-200 bg-white px-3 py-2">
                            <div class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Kombinasi Aktif</div>
                            <div class="mt-1 text-[11px] font-semibold text-slate-700" x-text="`${kategoriPekerjaan} / ${areaPekerjaan} / ${bucketOptions[nilaiBucket] || '-'}`"></div>
                        </div>

                        <ol class="mt-3 space-y-2">
                            <template x-for="(step, index) in approvalFlow" :key="`${previewCase}-${index}`">
                                <li
                                    class="flex items-start gap-2.5 rounded-xl border px-3 py-2"
                                    :class="index === 0 ? 'border-emerald-100 bg-emerald-50' : 'border-slate-200 bg-white'"
                                >
                                    <span
                                        class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold"
                                        :class="index === 0 ? 'bg-emerald-600 text-white' : 'bg-slate-100 text-slate-700'"
                                        x-text="index + 1"
                                    ></span>
                                    <div class="min-w-0">
                                        <div class="text-[11px] font-semibold leading-5 text-slate-800" x-text="step"></div>
                                        <div class="text-[10px]" :class="index === 0 ? 'text-emerald-700' : 'text-slate-500'" x-text="index === 0 ? 'Aktif pertama' : 'Waiting'"></div>
                                    </div>
                                </li>
                            </template>
                        </ol>
                    </div>
                </div>
            </div>
        </section>

        <section class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
            <div class="mt-2 flex flex-col items-start gap-3 md:flex-row md:items-center">
                <button
                    type="button"
                    id="tambah-jenis-btn"
                    class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-[13px] font-semibold text-white transition hover:bg-blue-700"
                >
                    Tambah Jenis
                </button>
                <span class="text-[12px] text-slate-500">Tambahkan jenis item lalu isi rincian perhitungan HPP di dalamnya.</span>
            </div>

            <div id="jenis-container" class="mt-6 space-y-6"></div>

            <div class="mt-6 border-t border-slate-200 pt-4">
                <label for="total_keseluruhan" class="text-[12px] font-semibold text-slate-700">Total Keseluruhan (Rp)</label>
                <input
                    type="text"
                    id="total_keseluruhan"
                    class="mt-1 w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-[13px] font-semibold text-slate-700"
                    readonly
                >
            </div>
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.hpp.index') }}" class="inline-flex items-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-[13px] font-semibold text-slate-600 transition hover:bg-slate-50">
                Kembali
            </a>
            <button type="submit" name="action" value="draft" class="inline-flex items-center rounded-xl bg-slate-600 px-4 py-2 text-[13px] font-semibold text-white transition hover:bg-slate-700" @disabled($orderOptions === [] || $outlineAgreementOptions === [])>
                Simpan Draft
            </button>
            <button type="submit" name="action" value="submit" class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-[13px] font-semibold text-white transition hover:bg-blue-700" @disabled($orderOptions === [] || $outlineAgreementOptions === [])>
                Submit
            </button>
        </div>
    </form>
</div>

<script>
    function hppForm(config) {
        return {
            orderOptions: config.orderOptions,
            outlineAgreementOptions: config.outlineAgreementOptions,
            kategoriOptions: config.kategoriOptions,
            areaOptions: config.areaOptions,
            bucketOptions: config.bucketOptions,
            flowMatrix: config.flowMatrix,
            selectedOrder: config.initialState.selectedOrder ?? '',
            selectedOutlineAgreement: config.initialState.selectedOutlineAgreement ?? '',
            kategoriPekerjaan: config.initialState.kategoriPekerjaan ?? 'Fabrikasi',
            areaPekerjaan: config.initialState.areaPekerjaan ?? 'Dalam',
            nilaiBucket: config.initialState.nilaiBucket ?? 'under',
            costCentre: config.initialState.costCentre ?? '',
            unitKerjaPengendali: '',
            periodeOutlineAgreement: config.initialState.periodeOutlineAgreement ?? '',
            namaPekerjaan: '',
            unitKerja: '',
            seksiPeminta: '',
            seksiPengendali: '',
            init() {
                if (! this.selectedOrder && this.orderOptions.length > 0) {
                    this.selectedOrder = this.orderOptions[0].value;
                }

                if (! this.selectedOutlineAgreement && this.outlineAgreementOptions.length > 0) {
                    this.selectedOutlineAgreement = this.outlineAgreementOptions[0].value;
                }

                this.syncOrderFields();
                this.syncOutlineAgreementFields();
                this.$watch('selectedOrder', () => this.syncOrderFields());
                this.$watch('selectedOutlineAgreement', () => this.syncOutlineAgreementFields());
            },
            get selectedOrderData() {
                return this.orderOptions.find((order) => order.value === this.selectedOrder) ?? {};
            },
            get selectedOutlineAgreementData() {
                return this.outlineAgreementOptions.find((agreement) => agreement.value === this.selectedOutlineAgreement) ?? {};
            },
            syncOrderFields() {
                this.namaPekerjaan = this.selectedOrderData.nama_pekerjaan ?? '';
                this.unitKerja = this.selectedOrderData.unit_kerja ?? '';
                this.seksiPeminta = this.selectedOrderData.seksi ?? '';
            },
            syncOutlineAgreementFields() {
                this.unitKerjaPengendali = this.selectedOutlineAgreementData.unit_kerja_pengendali ?? '';
                this.seksiPengendali = this.selectedOutlineAgreementData.seksi_pengendali ?? '';
                this.periodeOutlineAgreement = this.selectedOutlineAgreementData.periode_outline_agreement ?? '';
            },
            get previewCase() {
                if (! this.kategoriPekerjaan || ! this.areaPekerjaan || ! this.nilaiBucket) {
                    return null;
                }

                const prefix = this.kategoriPekerjaan === 'Fabrikasi' ? 'FAB' : 'KONS';
                const area = this.areaPekerjaan.toUpperCase();
                const bucket = this.nilaiBucket === 'over' ? 'OVER250' : 'UNDER250';

                return `${prefix}-${area}-${bucket}`;
            },
            get approvalFlow() {
                return this.flowMatrix?.[this.kategoriPekerjaan]?.[this.areaPekerjaan]?.[this.nilaiBucket] ?? [];
            },
        };
    }

    document.addEventListener('DOMContentLoaded', () => {
        const container = document.getElementById('jenis-container');
        const tambahJenisBtn = document.getElementById('tambah-jenis-btn');
        const totalAllEl = document.getElementById('total_keseluruhan');
        const presetGroups = @js($itemGroupPresets);

        if (! container || ! tambahJenisBtn || ! totalAllEl) {
            return;
        }

        let jenisCounter = 0;

        tambahJenisBtn.addEventListener('click', () => addJenis(null));

        function addJenis(preset = null) {
            const g = jenisCounter++;
            const wrap = document.createElement('div');
            wrap.className = 'jenis-block rounded-2xl border border-slate-200 bg-slate-50 p-4 shadow-sm';
            const titleVal = preset?.title ?? `Material/Jasa ${g + 1}`;

            wrap.innerHTML = `
                <div class="mb-3 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex-1">
                        <label class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Jenis Item</label>
                        <input type="text" name="jenis_label_visible[${g}]" class="jenis-label mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[13px] text-slate-700" value="${escapeAttr(titleVal)}" placeholder="Contoh: Material/Jasa">
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" class="hapus-jenis rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-[12px] font-semibold text-rose-700 transition hover:bg-rose-100">
                            Hapus Jenis
                        </button>
                        <button type="button" class="tambah-item rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-[12px] font-semibold text-emerald-700 transition hover:bg-emerald-100">
                            Tambah Item
                        </button>
                    </div>
                </div>

                <div class="items-container space-y-3" data-g="${g}"></div>

                <div class="mt-3 text-right text-[13px] text-slate-700">
                    <span>Subtotal: </span>
                    <span class="subtotal font-semibold text-blue-600" data-raw="0">0</span>
                </div>
            `;

            container.appendChild(wrap);

            const itemsContainer = wrap.querySelector('.items-container');
            const subtotalEl = wrap.querySelector('.subtotal');

            wrap.querySelector('.tambah-item').addEventListener('click', () => {
                addItem(itemsContainer, subtotalEl, g, null);
            });

            wrap.querySelector('.hapus-jenis').addEventListener('click', () => {
                wrap.remove();
                updateGrandTotal();
            });

            if (preset?.items && Array.isArray(preset.items) && preset.items.length > 0) {
                preset.items.forEach((item) => addItem(itemsContainer, subtotalEl, g, item));
            } else {
                addItem(itemsContainer, subtotalEl, g, null);
            }

            recalcSubtotal(itemsContainer, subtotalEl);
        }

        function addItem(list, subtotalEl, gIndex, data = null) {
            const item = document.createElement('div');
            item.className = 'uraian-item rounded-2xl border border-slate-200 bg-white p-4 shadow-sm';

            item.innerHTML = `
                <div class="mb-3 flex items-center justify-between gap-2">
                    <h4 class="text-[13px] font-semibold text-slate-800">Item</h4>
                    <button type="button" class="remove-item text-[12px] font-semibold text-rose-600 transition hover:text-rose-700">
                        Hapus
                    </button>
                </div>

                <div class="mb-3 grid gap-3 md:grid-cols-2">
                    <div>
                        <input type="text" name="nama_item[${gIndex}][]" value="${escapeAttr(data?.nama_item ?? '')}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-[12px] text-slate-700" placeholder="Nama item">
                    </div>
                    <div>
                        <input type="text" name="jumlah_item[${gIndex}][]" value="${escapeAttr(data?.jumlah_item ?? '')}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-[12px] text-slate-700" placeholder="Quantity">
                    </div>
                </div>

                <div class="mb-3 grid gap-3 lg:grid-cols-4">
                    <input type="number" name="qty[${gIndex}][]" value="${escapeAttr(data?.qty ?? '')}" min="0" step="0.01" class="qty rounded-xl border border-slate-300 px-3 py-2.5 text-[12px] text-slate-700" placeholder="Berat/Jmlh Jam/Luasan">
                    <input type="text" name="satuan[${gIndex}][]" value="${escapeAttr(data?.satuan ?? '')}" class="rounded-xl border border-slate-300 px-3 py-2.5 text-[12px] text-slate-700" placeholder="Satuan">
                    <input type="number" name="harga_satuan[${gIndex}][]" value="${escapeAttr(data?.harga_satuan ?? '')}" min="0" step="0.01" class="harga-satuan rounded-xl border border-slate-300 px-3 py-2.5 text-[12px] text-slate-700" placeholder="Harga satuan">
                    <input type="number" name="harga_total[${gIndex}][]" value="${escapeAttr(data?.harga_total ?? '')}" class="harga-total rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-[12px] font-semibold text-slate-700" placeholder="Harga total" readonly>
                </div>

                <div>
                    <input type="text" name="keterangan[${gIndex}][]" value="${escapeAttr(data?.keterangan ?? '')}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-[12px] text-slate-700" placeholder="Keterangan (opsional)">
                </div>
            `;

            list.appendChild(item);

            const qtyEl = item.querySelector('.qty');
            const hsEl = item.querySelector('.harga-satuan');
            const htEl = item.querySelector('.harga-total');

            function recompute() {
                const qty = parseFloat(qtyEl.value) || 0;
                const hs = parseFloat(hsEl.value) || 0;
                htEl.value = (qty * hs).toFixed(2);
                recalcSubtotal(list, subtotalEl);
            }

            qtyEl.addEventListener('input', recompute);
            hsEl.addEventListener('input', recompute);

            item.querySelector('.remove-item').addEventListener('click', () => {
                item.remove();
                recalcSubtotal(list, subtotalEl);
            });

            recompute();
        }

        function recalcSubtotal(list, subtotalEl) {
            let subtotal = 0;

            list.querySelectorAll('.harga-total').forEach((ht) => {
                subtotal += parseFloat(ht.value) || 0;
            });

            subtotalEl.dataset.raw = String(subtotal);
            subtotalEl.textContent = subtotal.toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });

            updateGrandTotal();
        }

        function updateGrandTotal() {
            let grand = 0;

            document.querySelectorAll('.subtotal').forEach((subtotal) => {
                grand += parseFloat(subtotal.dataset.raw || '0') || 0;
            });

            totalAllEl.value = grand.toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        }

        function escapeAttr(value) {
            if (value == null) return '';
            return String(value).replace(/"/g, '&quot;').replace(/</g, '&lt;');
        }

        if (Array.isArray(presetGroups) && presetGroups.length > 0) {
            presetGroups.forEach((group) => addJenis(group));
        } else {
            addJenis();
        }
    });
</script>
