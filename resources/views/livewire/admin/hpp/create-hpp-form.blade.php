<div
    x-data="hppPreview({
        orderOptions: @js($this->orderOptions),
        kategoriOptions: @js($this->kategoriOptions),
        areaOptions: @js($this->areaOptions),
    })"
    class="space-y-6"
>
    <section class="rounded-[1.5rem] border border-blue-100 px-6 py-5 shadow-sm" style="background: linear-gradient(135deg, #eef4ff 0%, #f8fbff 48%, #e6f1ff 100%);">
        <div class="flex items-center gap-4">
            <span class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-200">
                <i data-lucide="pencil-line" class="h-6 w-6"></i>
            </span>
            <div>
                <h1 class="text-[1.65rem] font-bold leading-none tracking-tight text-slate-900">Buat HPP</h1>
                <p class="mt-2 text-[13px] text-slate-500">Preview approval flow otomatis di samping field tanpa backend.</p>
            </div>
        </div>
    </section>

    <section class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
        <div class="mb-4 flex items-start justify-between gap-3">
            <div>
                <h2 class="text-[15px] font-semibold text-slate-900">Input HPP</h2>
                <p class="mt-1 text-[12px] text-slate-500">Preview berubah langsung saat kategori, area, atau bucket nilai dipilih.</p>
            </div>
            <span class="rounded-full bg-blue-50 px-3 py-1 text-[10px] font-semibold text-blue-700">Front-End Preview</span>
        </div>

        <div class="xl:flex xl:items-start xl:gap-6">
            <div class="min-w-0 flex-1 space-y-4">
                <div class="space-y-1.5">
                    <label for="order_pekerjaan" class="text-[12px] font-semibold text-slate-700">Order Pekerjaan</label>
                    <select
                        id="order_pekerjaan"
                        name="order_pekerjaan"
                        x-model="selectedOrder"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[13px] text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                        <template x-for="order in orderOptions" :key="order.value">
                            <option
                                :value="order.value"
                                :data-job="order.description"
                                :data-unit="order.requesting_unit"
                                x-text="order.label"
                            ></option>
                        </template>
                    </select>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-1.5">
                        <label for="nilai-hpp-bucket" class="text-[12px] font-semibold text-slate-700">Nilai HPP</label>
                        <select
                            id="nilai-hpp-bucket"
                            x-model="nilaiBucket"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[13px] text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        >
                            <option value="under">&lt;= 250 JT</option>
                            <option value="over">&gt; 250 JT</option>
                        </select>
                        <p class="text-[11px] text-slate-400">Disederhanakan ke dua bucket agar preview lebih stabil.</p>
                    </div>

                    <div class="space-y-1.5">
                        <label for="kategori-pekerjaan" class="text-[12px] font-semibold text-slate-700">Kategori Pekerjaan</label>
                        <select
                            id="kategori-pekerjaan"
                            x-model="kategoriPekerjaan"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[13px] text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        >
                            <template x-for="option in kategoriOptions" :key="option">
                                <option :value="option" x-text="option"></option>
                            </template>
                        </select>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label for="area-pekerjaan" class="text-[12px] font-semibold text-slate-700">Area Pekerjaan</label>
                    <select
                        id="area-pekerjaan"
                        x-model="areaPekerjaan"
                        class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[13px] text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                    >
                        <template x-for="option in areaOptions" :key="option">
                            <option :value="option" x-text="option"></option>
                        </template>
                    </select>
                    <p class="text-[11px] text-slate-400">Area disederhanakan hanya ke Dalam dan Luar.</p>
                </div>

                <div class="grid gap-4 border-t border-slate-200 pt-4 md:grid-cols-2">
                    <div class="space-y-1.5">
                        <label for="cost-centre" class="text-[12px] font-semibold text-slate-700">Cost Centre</label>
                        <input
                            id="cost-centre"
                            type="text"
                            name="cost_centre"
                            x-model="costCentre"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-[13px] text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        >
                    </div>

                    <div class="space-y-1.5 md:col-span-2">
                        <label for="deskripsi" class="text-[12px] font-semibold text-slate-700">Deskripsi</label>
                        <textarea
                            id="deskripsi"
                            name="description"
                            rows="3"
                            x-model="description"
                            readonly
                            class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-[13px] text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        ></textarea>
                    </div>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-1.5">
                        <label for="unit_kerja_peminta" class="text-[12px] font-semibold text-slate-700">Unit Kerja Peminta</label>
                        <input
                            id="unit_kerja_peminta"
                            type="text"
                            name="requesting_unit"
                            x-model="requestingUnit"
                            class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-[13px] text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        >
                    </div>

                    <div class="space-y-1.5">
                        <label for="unit-kerja-pengendali" class="text-[12px] font-semibold text-slate-700">Unit Kerja Pengendali</label>
                        <input
                            id="unit-kerja-pengendali"
                            type="text"
                            name="controlling_unit"
                            x-model="controllingUnit"
                            readonly
                            class="w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2.5 text-[13px] text-slate-700"
                        >
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label for="outline-agreement" class="text-[12px] font-semibold text-slate-700">Outline Agreement (OA)</label>
                    <input
                        id="outline-agreement"
                        type="text"
                        name="outline_agreement"
                        x-model="outlineAgreement"
                        readonly
                        class="w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2.5 text-[13px] text-slate-700"
                    >
                    <p class="text-[11px] text-slate-500">
                        Periode:
                        <span x-text="outlinePeriod"></span>
                    </p>
                </div>
            </div>

            <div class="mt-5 xl:mt-0 xl:w-[340px] xl:shrink-0">
                <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <h3 class="text-[13px] font-semibold text-slate-900">Snapshot Approval Flow</h3>
                            <p class="mt-1 text-[11px] text-slate-500">Langsung berubah dari kombinasi pilihan user.</p>
                        </div>
                        <span class="rounded-full bg-white px-2.5 py-1 text-[10px] font-medium text-slate-600 ring-1 ring-slate-200" x-text="`${approvalFlow.length} step`"></span>
                    </div>

                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="rounded-full bg-blue-100 px-2.5 py-1 text-[10px] font-semibold tracking-wide text-blue-700" x-text="previewCase"></span>
                        <span class="rounded-full bg-white px-2.5 py-1 text-[10px] font-medium text-slate-600 ring-1 ring-slate-200" x-text="nilaiBucket === 'over' ? 'OVER 250 JT' : 'UNDER 250 JT'"></span>
                    </div>

                    <div class="mt-3 rounded-xl border border-slate-200 bg-white px-3 py-2">
                        <div class="text-[10px] uppercase tracking-[0.16em] text-slate-400">Kombinasi Aktif</div>
                        <div class="mt-1 text-[11px] font-semibold text-slate-700" x-text="`${kategoriPekerjaan} / ${areaPekerjaan} / ${nilaiBucket === 'over' ? '>250JT' : '<=250JT'}`"></div>
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
            <span class="text-[12px] text-slate-500">Tambahkan jenis, lalu tambahkan item di dalamnya.</span>
        </div>

        <div id="jenis-container" class="mt-6 space-y-6"></div>

        <div class="mt-6 border-t border-slate-200 pt-4">
            <label for="total_keseluruhan" class="text-[12px] font-semibold text-slate-700">Total Keseluruhan (Rp)</label>
            <input
                type="number"
                name="total_amount"
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
        <button type="button" name="action" value="draft" class="inline-flex items-center rounded-xl bg-slate-600 px-4 py-2 text-[13px] font-semibold text-white transition hover:bg-slate-700">
            Simpan Draft
        </button>
        <button type="button" name="action" value="submit" class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-[13px] font-semibold text-white transition hover:bg-blue-700">
            Submit
        </button>
    </div>
</div>

<script>
    function hppPreview(config) {
        return {
            orderOptions: config.orderOptions,
            kategoriOptions: config.kategoriOptions,
            areaOptions: config.areaOptions,
            selectedOrder: config.orderOptions[0]?.value ?? '',
            kategoriPekerjaan: 'Fabrikasi',
            areaPekerjaan: 'Dalam',
            nilaiBucket: 'under',
            costCentre: '',
            description: '',
            requestingUnit: '',
            controllingUnit: '',
            outlineAgreement: '',
            outlinePeriod: '-',
            init() {
                this.syncOrderFields();
                this.$watch('selectedOrder', () => this.syncOrderFields());
            },
            get selectedOrderData() {
                return this.orderOptions.find((order) => order.value === this.selectedOrder) ?? {};
            },
            syncOrderFields() {
                this.costCentre = this.selectedOrderData.cost_centre ?? '';
                this.description = this.selectedOrderData.description ?? '';
                this.requestingUnit = this.selectedOrderData.requesting_unit ?? '';
                this.controllingUnit = this.selectedOrderData.controlling_unit ?? 'Unit of Workshop & Design';
                this.outlineAgreement = this.selectedOrderData.outline_agreement ?? '';
                this.outlinePeriod = this.selectedOrderData.oa_period ?? '-';
            },
            get previewCase() {
                const prefix = this.kategoriPekerjaan === 'Fabrikasi' ? 'FAB' : 'KONS';
                const area = this.areaPekerjaan.toUpperCase();
                const bucket = this.nilaiBucket === 'over' ? 'OVER250' : 'UNDER250';

                return `${prefix}-${area}-${bucket}`;
            },
            get approvalFlow() {
                const flows = {
                    Fabrikasi: {
                        Dalam: {
                            under: [
                                'Manager Pengendali',
                                'SM Pengendali',
                                'Manager Peminta',
                                'SM Peminta',
                                'GM Peminta',
                                'GM Pengendali',
                            ],
                            over: [
                                'Manager Pengendali',
                                'SM Pengendali',
                                'Manager Peminta',
                                'SM Peminta',
                                'GM Peminta',
                                'GM Pengendali',
                                'DIROPS',
                            ],
                        },
                        Luar: {
                            under: [
                                'Planner Control',
                                'Manager Pengendali',
                                'SM Pengendali',
                                'Manager Peminta',
                                'SM Peminta',
                                'GM Peminta',
                                'GM Pengendali',
                            ],
                            over: [
                                'Planner Control',
                                'Manager Pengendali',
                                'SM Pengendali',
                                'Manager Peminta',
                                'SM Peminta',
                                'GM Peminta',
                                'GM Pengendali',
                                'DIROPS',
                            ],
                        },
                    },
                    Konstruksi: {
                        Dalam: {
                            under: [
                                'Manager Counter Part',
                                'SM Counter Part',
                                'Manager Pengendali',
                                'SM Pengendali',
                                'Manager Peminta',
                                'SM Peminta',
                                'GM Peminta',
                                'GM Pengendali',
                            ],
                            over: [
                                'Manager Counter Part',
                                'SM Counter Part',
                                'Manager Pengendali',
                                'SM Pengendali',
                                'Manager Peminta',
                                'SM Peminta',
                                'GM Peminta',
                                'GM Pengendali',
                                'DIROPS',
                            ],
                        },
                        Luar: {
                            under: [
                                'Manager Counter Part',
                                'SM Counter Part',
                                'Planner Control',
                                'Manager Pengendali',
                                'SM Pengendali',
                                'Manager Peminta',
                                'SM Peminta',
                                'GM Peminta',
                                'GM Pengendali',
                            ],
                            over: [
                                'Manager Counter Part',
                                'SM Counter Part',
                                'Planner Control',
                                'Manager Pengendali',
                                'SM Pengendali',
                                'Manager Peminta',
                                'SM Peminta',
                                'GM Peminta',
                                'GM Pengendali',
                                'DIROPS',
                            ],
                        },
                    },
                };

                return flows[this.kategoriPekerjaan]?.[this.areaPekerjaan]?.[this.nilaiBucket] ?? [];
            },
        };
    }

    document.addEventListener('DOMContentLoaded', () => {
        const selectOrder = document.getElementById('order_pekerjaan');
        const deskripsi = document.getElementById('deskripsi');
        const unitKerja = document.getElementById('unit_kerja_peminta');
        const container = document.getElementById('jenis-container');
        const tambahJenisBtn = document.getElementById('tambah-jenis-btn');
        const totalAllEl = document.getElementById('total_keseluruhan');

        if (!container || !tambahJenisBtn || !totalAllEl) {
            return;
        }

        if (selectOrder && deskripsi && unitKerja) {
            const syncOrderPreview = () => {
                const opt = selectOrder.selectedOptions[0];
                deskripsi.value = opt?.getAttribute('data-job') || '';
                unitKerja.value = opt?.getAttribute('data-unit') || '';
            };

            syncOrderPreview();
            selectOrder.addEventListener('change', syncOrderPreview);
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
            const labelInput = wrap.querySelector('.jenis-label');

            labelInput.addEventListener('input', () => {
                updateHiddenJenisForGroup(g, labelInput.value);
            });

            wrap.querySelector('.tambah-item').addEventListener('click', () => {
                addItem(itemsContainer, subtotalEl, g, null, labelInput.value);
            });

            wrap.querySelector('.hapus-jenis').addEventListener('click', () => {
                wrap.remove();
                updateGrandTotal();
            });

            if (preset?.items && Array.isArray(preset.items) && preset.items.length) {
                preset.items.forEach((it) => addItem(itemsContainer, subtotalEl, g, it, titleVal));
                recalcSubtotal(itemsContainer, subtotalEl);
            } else {
                addItem(itemsContainer, subtotalEl, g, null, titleVal);
            }

            return wrap;
        }

        function addItem(list, subtotalEl, gIndex, data = null, groupLabel = '') {
            const item = document.createElement('div');
            item.className = 'uraian-item rounded-2xl border border-slate-200 bg-white p-4 shadow-sm';

            item.innerHTML = `
                <div class="mb-3 flex items-center justify-between gap-2">
                    <h4 class="text-[13px] font-semibold text-slate-800">Item</h4>
                    <button type="button" class="remove-item text-[12px] font-semibold text-rose-600 transition hover:text-rose-700">
                        Hapus
                    </button>
                </div>

                <input type="hidden" name="jenis_item[${gIndex}][]" class="jenis-hidden" value="${escapeAttr(groupLabel)}">

                <div class="mb-3 grid gap-3 md:grid-cols-2">
                    <div>
                        <input type="text" name="nama_item[${gIndex}][]" value="${escapeAttr(data?.nama_item ?? '')}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-[12px] text-slate-700" placeholder="Nama Item (plate/besi/dll)">
                    </div>
                    <div>
                        <input type="text" name="jumlah_item[${gIndex}][]" value="${escapeAttr(data?.jumlah_item ?? '')}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-[12px] text-slate-700" placeholder="Quantity">
                    </div>
                </div>

                <div class="mb-3 grid gap-3 lg:grid-cols-4">
                    <input type="number" name="qty[${gIndex}][]" value="${escapeAttr(data?.qty ?? '')}" min="0" step="0.01" class="qty rounded-xl border border-slate-300 px-3 py-2.5 text-[12px] text-slate-700" placeholder="Berat/Jmlh Jam/jmlh luasan">
                    <input type="text" name="satuan[${gIndex}][]" value="${escapeAttr(data?.satuan ?? '')}" class="rounded-xl border border-slate-300 px-3 py-2.5 text-[12px] text-slate-700" placeholder="Satuan">
                    <input type="number" name="harga_satuan[${gIndex}][]" value="${escapeAttr(data?.harga_satuan ?? '')}" min="0" step="0.01" class="harga-satuan rounded-xl border border-slate-300 px-3 py-2.5 text-[12px] text-slate-700" placeholder="Harga Satuan">
                    <input type="number" name="harga_total[${gIndex}][]" value="${escapeAttr(data?.harga_total ?? '')}" class="harga-total rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-[12px] font-semibold text-slate-700" placeholder="Harga Total" readonly>
                </div>

                <div>
                    <input type="text" name="keterangan[${gIndex}][]" value="${escapeAttr(data?.keterangan ?? '')}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-[12px] text-slate-700" placeholder="Keterangan (opsional)">
                </div>
            `;

            list.appendChild(item);

            const wrapBlock = list.closest('.jenis-block');
            const labelEl = wrapBlock ? wrapBlock.querySelector('.jenis-label') : null;
            if (labelEl) {
                const hidden = item.querySelector('.jenis-hidden');
                hidden.value = labelEl.value || '';
            }

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

        function updateHiddenJenisForGroup(gIndex, newLabel) {
            const groupWrap = container.querySelector(`.items-container[data-g="${gIndex}"]`);
            if (!groupWrap) return;
            groupWrap.querySelectorAll('.jenis-hidden').forEach((hidden) => {
                hidden.value = newLabel;
            });
        }

        function recalcSubtotal(list, subtotalEl) {
            let subtotal = 0;
            list.querySelectorAll('.harga-total').forEach((ht) => {
                subtotal += parseFloat(ht.value) || 0;
            });
            subtotalEl.dataset.raw = String(subtotal);
            subtotalEl.textContent = subtotal.toLocaleString('id-ID');
            updateGrandTotal();
        }

        function updateGrandTotal() {
            let grand = 0;
            document.querySelectorAll('.subtotal').forEach((subtotal) => {
                grand += parseFloat(subtotal.dataset.raw || '0') || 0;
            });
            totalAllEl.value = grand.toFixed(2);
        }

        function escapeAttr(value) {
            if (value == null) return '';
            return String(value).replace(/"/g, '&quot;').replace(/</g, '&lt;');
        }

        addJenis();
    });
</script>
