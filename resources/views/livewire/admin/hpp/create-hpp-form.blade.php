<div
    x-data="hppForm({
        orderOptions: @js($orderOptions),
        outlineAgreementOptions: @js($outlineAgreementOptions),
        kategoriOptions: @js($kategoriOptions),
        areaOptions: @js($areaOptions),
        areaKeysByLabel: @js($areaKeysByLabel),
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
                <h1 class="text-[1.65rem] font-bold leading-none tracking-tight text-slate-900">{{ $isEdit ? 'Edit HPP' : 'Buat HPP' }}</h1>
                <p class="mt-2 text-[13px] text-slate-500">
                    {{ $isEdit ? 'Perbarui snapshot HPP yang sudah dibuat beserta rincian item dan approval flow-nya.' : 'Order pekerjaan diambil langsung dari database order, lalu nama pekerjaan dan unit kerja terisi otomatis.' }}
                </p>
            </div>
        </div>
    </section>

    <form method="POST" action="{{ $submitRoute }}" class="space-y-6">
        @csrf
        @if ($isEdit)
            @method('PUT')
        @endif

        <section class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm lg:p-6">
            <div class="mb-4 flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-[15px] font-semibold text-slate-900">Input HPP</h2>
                </div>
                <span class="rounded-full bg-emerald-50 px-3 py-1 text-[10px] font-semibold text-emerald-700">Order DB Connected</span>
            </div>

            @if ($orderOptions === [])
                <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-[13px] text-amber-700">
                    Belum ada order yang memenuhi syarat HPP. Order harus berstatus Approved (Jasa) atau Approved (Workshop + Jasa), sudah punya dokumen Abnormalitas, Gambar Teknik, Scope of Work, dan belum pernah dibuatkan HPP.
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
                        @if ($isEdit)
                            <input type="hidden" name="order_id" x-model="selectedOrder">
                        @endif
                        <select
                            id="order_id"
                            @if (! $isEdit) name="order_id" @endif
                            x-model="selectedOrder"
                            class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[13px] text-slate-700 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                            @disabled($orderOptions === [] || $isEdit)
                        >
                            <option value="">Pilih order pekerjaan</option>
                            <template x-for="order in orderOptions" :key="order.value">
                                <option :value="order.value" :selected="String(order.value) === String(selectedOrder)" x-text="order.label"></option>
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
                            @foreach ($areaOptions as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
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
                                    <option :value="agreement.value" :selected="String(agreement.value) === String(selectedOutlineAgreement)" x-text="agreement.label"></option>
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
                <span class="text-[12px] text-slate-500">Pilih jenis item dari master kontrak, lalu pilih sub jenis, kategori, dan nama item agar satuan serta harga satuan terisi otomatis.</span>
            </div>

            @if (empty($contractCatalog))
                <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-[13px] text-amber-700">
                    Belum ada master item kontrak. Tambahkan dulu data di menu Kontrak Jasa Fabrikasi Konstruksi agar dropdown item HPP bisa dipilih otomatis.
                </div>
            @endif

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
                {{ $isEdit ? 'Update Draft' : 'Simpan Draft' }}
            </button>
            <button type="submit" name="action" value="submit" class="inline-flex items-center rounded-xl bg-blue-600 px-4 py-2 text-[13px] font-semibold text-white transition hover:bg-blue-700" @disabled($orderOptions === [] || $outlineAgreementOptions === [])>
                {{ $isEdit ? 'Update & Submit' : 'Submit' }}
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
            areaKeysByLabel: config.areaKeysByLabel,
            bucketOptions: config.bucketOptions,
            flowMatrix: config.flowMatrix,
            selectedOrder: String(config.initialState.selectedOrder ?? ''),
            selectedOutlineAgreement: String(config.initialState.selectedOutlineAgreement ?? ''),
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
                    this.selectedOrder = String(this.orderOptions[0].value);
                }

                if (! this.selectedOutlineAgreement && this.outlineAgreementOptions.length > 0) {
                    this.selectedOutlineAgreement = String(this.outlineAgreementOptions[0].value);
                }

                this.syncOrderFields();
                this.syncOutlineAgreementFields();
                this.$watch('selectedOrder', () => this.syncOrderFields());
                this.$watch('selectedOutlineAgreement', () => this.syncOutlineAgreementFields());
            },
            get selectedOrderData() {
                return this.orderOptions.find((order) => String(order.value) === String(this.selectedOrder)) ?? {};
            },
            get selectedOutlineAgreementData() {
                return this.outlineAgreementOptions.find((agreement) => String(agreement.value) === String(this.selectedOutlineAgreement)) ?? {};
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
                const areaKey = this.areaKeysByLabel?.[this.areaPekerjaan] ?? this.areaPekerjaan;
                const area = areaKey.toUpperCase();
                const bucket = this.nilaiBucket === 'over' ? 'OVER250' : 'UNDER250';

                return `${prefix}-${area}-${bucket}`;
            },
            get approvalFlow() {
                const areaKey = this.areaKeysByLabel?.[this.areaPekerjaan] ?? this.areaPekerjaan;

                return this.flowMatrix?.[this.kategoriPekerjaan]?.[areaKey]?.[this.nilaiBucket] ?? [];
            },
        };
    }

    document.addEventListener('DOMContentLoaded', () => {
        const container = document.getElementById('jenis-container');
        const tambahJenisBtn = document.getElementById('tambah-jenis-btn');
        const totalAllEl = document.getElementById('total_keseluruhan');
        const presetGroups = @js($itemGroupPresets);
        const contractCatalog = @js($contractCatalog);

        if (! container || ! tambahJenisBtn || ! totalAllEl) {
            return;
        }

        let jenisCounter = 0;

        tambahJenisBtn.addEventListener('click', () => addJenis(null));

        function normalizeKey(value) {
            return String(value ?? '').trim();
        }

        function uniqueValues(values) {
            return [...new Set(values.map(normalizeKey).filter(Boolean))];
        }

        const jenisOptions = uniqueValues(contractCatalog.map((item) => item.jenis_item));

        function normalizeDecimalString(value) {
            const normalized = String(value ?? '').replace(/[^0-9.\-]/g, '').trim();

            if (!normalized || normalized === '-' || normalized === '.') {
                return '0';
            }

            const isNegative = normalized.startsWith('-');
            const unsigned = isNegative ? normalized.slice(1) : normalized;
            const [rawInteger = '0', rawDecimal = ''] = unsigned.split('.', 2);
            const integer = rawInteger.replace(/^0+(?=\d)/, '') || '0';
            const decimal = rawDecimal.replace(/0+$/, '');

            return `${isNegative ? '-' : ''}${integer}${decimal ? `.${decimal}` : ''}`;
        }

        function normalizeCurrencyDecimal(value) {
            const normalized = normalizeDecimalString(value);

            if (! normalized.includes('.')) {
                return `${normalized}.00`;
            }

            const [integer = '0', decimal = ''] = normalized.split('.', 2);

            return `${integer}.${decimal.padEnd(2, '0').slice(0, 2)}`;
        }

        function parseDecimalParts(value) {
            const normalized = normalizeDecimalString(value);
            const isNegative = normalized.startsWith('-');
            const unsigned = isNegative ? normalized.slice(1) : normalized;
            const [integer = '0', decimal = ''] = unsigned.split('.', 2);

            return {
                negative: isNegative,
                digits: BigInt(`${integer}${decimal}` || '0'),
                scale: decimal.length,
            };
        }

        function roundScaledBigInt(value, currentScale, targetScale) {
            if (currentScale <= targetScale) {
                return value * (10n ** BigInt(targetScale - currentScale));
            }

            const diff = currentScale - targetScale;
            const factor = 10n ** BigInt(diff);
            const quotient = value / factor;
            const remainder = value % factor;
            const threshold = factor / 2n;

            return remainder >= threshold ? quotient + 1n : quotient;
        }

        function formatScaledBigInt(value, scale) {
            const negative = value < 0n;
            const absolute = negative ? -value : value;
            const digits = absolute.toString().padStart(scale + 1, '0');
            const integer = digits.slice(0, Math.max(1, digits.length - scale));
            const decimal = scale > 0 ? digits.slice(-scale) : '';

            return `${negative ? '-' : ''}${integer}${scale > 0 ? `.${decimal}` : ''}`;
        }

        function multiplyToCurrencyString(left, right) {
            const leftParts = parseDecimalParts(left);
            const rightParts = parseDecimalParts(right);
            const sign = leftParts.negative === rightParts.negative ? 1n : -1n;
            const product = leftParts.digits * rightParts.digits * sign;
            const scaled = roundScaledBigInt(product, leftParts.scale + rightParts.scale, 2);

            return formatScaledBigInt(scaled, 2);
        }

        function addCurrencyStrings(left, right) {
            const leftParts = parseDecimalParts(left);
            const rightParts = parseDecimalParts(right);
            const leftScaled = roundScaledBigInt(leftParts.negative ? -leftParts.digits : leftParts.digits, leftParts.scale, 2);
            const rightScaled = roundScaledBigInt(rightParts.negative ? -rightParts.digits : rightParts.digits, rightParts.scale, 2);

            return formatScaledBigInt(leftScaled + rightScaled, 2);
        }

        function formatCurrencyDisplay(value) {
            const normalized = normalizeDecimalString(value);
            const [integerRaw = '0', decimalRaw = '00'] = normalized.split('.', 2);
            const negative = integerRaw.startsWith('-');
            const integer = negative ? integerRaw.slice(1) : integerRaw;
            const formattedInteger = integer.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            const decimal = (decimalRaw || '').padEnd(2, '0').slice(0, 2);

            return `${negative ? '-' : ''}${formattedInteger},${decimal}`;
        }

        function getSubJenisOptions(jenisItem) {
            return uniqueValues(
                contractCatalog
                    .filter((item) => normalizeKey(item.jenis_item) === normalizeKey(jenisItem))
                    .map((item) => item.sub_jenis_item),
            );
        }

        function getKategoriOptions(jenisItem, subJenisItem) {
            return uniqueValues(
                contractCatalog
                    .filter((item) =>
                        normalizeKey(item.jenis_item) === normalizeKey(jenisItem)
                        && normalizeKey(item.sub_jenis_item) === normalizeKey(subJenisItem)
                    )
                    .map((item) => item.kategori_item),
            );
        }

        function getItemOptions(jenisItem, subJenisItem, kategoriItem) {
            return contractCatalog.filter((item) =>
                normalizeKey(item.jenis_item) === normalizeKey(jenisItem)
                && normalizeKey(item.sub_jenis_item) === normalizeKey(subJenisItem)
                && normalizeKey(item.kategori_item) === normalizeKey(kategoriItem)
            );
        }

        function populateItemSelect(selectEl, options, placeholder, selectedValue = '') {
            const normalizedSelected = normalizeKey(selectedValue);
            const fragment = document.createDocumentFragment();
            const firstOption = document.createElement('option');
            firstOption.value = '';
            firstOption.textContent = placeholder;
            fragment.appendChild(firstOption);

            const seen = new Set();

            options.forEach((option) => {
                const value = normalizeKey(option?.nama_item);

                if (! value || seen.has(value)) {
                    return;
                }

                seen.add(value);

                const optionEl = document.createElement('option');
                optionEl.value = value;
                optionEl.textContent = option?.nama_item ?? value;
                optionEl.dataset.satuan = option?.satuan ?? '';
                optionEl.dataset.hargaSatuan = normalizeCurrencyDecimal(option?.harga_satuan ?? '0');
                optionEl.selected = value === normalizedSelected;
                fragment.appendChild(optionEl);
            });

            if (normalizedSelected && ! seen.has(normalizedSelected)) {
                const fallbackOption = document.createElement('option');
                fallbackOption.value = normalizedSelected;
                fallbackOption.textContent = selectedValue;
                fallbackOption.selected = true;
                fragment.appendChild(fallbackOption);
            }

            selectEl.innerHTML = '';
            selectEl.appendChild(fragment);
        }

        function populateSelect(selectEl, options, placeholder, selectedValue = '') {
            const normalizedSelected = normalizeKey(selectedValue);
            const fragment = document.createDocumentFragment();
            const firstOption = document.createElement('option');
            firstOption.value = '';
            firstOption.textContent = placeholder;
            fragment.appendChild(firstOption);

            const normalizedOptions = [];
            const seen = new Set();

            options.forEach((option) => {
                const value = normalizeKey(typeof option === 'string' ? option : option?.value);
                const label = typeof option === 'string' ? option : (option?.label ?? option?.value ?? '');

                if (! value || seen.has(value)) {
                    return;
                }

                seen.add(value);
                normalizedOptions.push({ value, label });
            });

            if (normalizedSelected && ! seen.has(normalizedSelected)) {
                normalizedOptions.unshift({ value: normalizedSelected, label: selectedValue });
            }

            normalizedOptions.forEach((option) => {
                const optionEl = document.createElement('option');
                optionEl.value = option.value;
                optionEl.textContent = option.label;
                optionEl.selected = normalizeKey(option.value) === normalizedSelected;
                fragment.appendChild(optionEl);
            });

            selectEl.innerHTML = '';
            selectEl.appendChild(fragment);
        }

        function addJenis(preset = null) {
            const g = jenisCounter++;
            const wrap = document.createElement('div');
            wrap.className = 'jenis-block rounded-2xl border border-slate-200 bg-slate-50 p-4 shadow-sm';
            const titleVal = preset?.title ?? (jenisOptions[0] ?? '');

            wrap.innerHTML = `
                <div class="mb-3 flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                    <div class="flex-1">
                        <label class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Jenis Item</label>
                        <select name="jenis_label_visible[${g}]" class="jenis-label mt-1 block w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[13px] text-slate-700"></select>
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
            const jenisLabelEl = wrap.querySelector('.jenis-label');

            populateSelect(
                jenisLabelEl,
                jenisOptions,
                jenisOptions.length > 0 ? 'Pilih jenis item' : 'Belum ada jenis item master',
                titleVal,
            );

            wrap.querySelector('.tambah-item').addEventListener('click', () => {
                addItem(itemsContainer, subtotalEl, g, null);
            });

            wrap.querySelector('.hapus-jenis').addEventListener('click', () => {
                wrap.remove();
                updateGrandTotal();
            });

            jenisLabelEl.addEventListener('change', () => {
                itemsContainer.querySelectorAll('.uraian-item').forEach((itemEl) => {
                    if (typeof itemEl.refreshContractOptions === 'function') {
                        itemEl.refreshContractOptions(true);
                    }
                });
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
            let initialSubJenis = data?.sub_jenis_item ?? '';
            let initialKategori = data?.kategori_item ?? '';
            let initialNamaItem = data?.nama_item ?? '';

            item.innerHTML = `
                <div class="mb-3 flex items-center justify-between gap-2">
                    <h4 class="text-[13px] font-semibold text-slate-800">Deskripsi Item</h4>
                    <button type="button" class="remove-item text-[12px] font-semibold text-rose-600 transition hover:text-rose-700">
                        Hapus
                    </button>
                </div>

                <div class="mb-3 grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-[11px] font-medium text-slate-600">Sub Jenis Item</label>
                        <select name="sub_jenis_item[${gIndex}][]" class="sub-jenis-item w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[12px] text-slate-700"></select>
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-medium text-slate-600">Kategori Item</label>
                        <select name="kategori_item[${gIndex}][]" class="kategori-item w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[12px] text-slate-700"></select>
                    </div>
                </div>

                <div class="mb-3 grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-[11px] font-medium text-slate-600">Nama Item</label>
                        <select name="nama_item[${gIndex}][]" class="nama-item w-full rounded-xl border border-slate-300 bg-white px-3 py-2.5 text-[12px] text-slate-700"></select>
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-medium text-slate-600">Ukuran & Quantity</label>
                        <input type="text" name="jumlah_item[${gIndex}][]" value="${escapeAttr(data?.jumlah_item ?? '')}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-[12px] text-slate-700" placeholder="Ukuran & Quantity">
                    </div>
                </div>

                <div class="mb-3 grid gap-3 lg:grid-cols-4">
                    <div>
                        <label class="mb-1 block text-[11px] font-medium text-slate-600">Qty</label>
                        <input type="number" name="qty[${gIndex}][]" value="${escapeAttr(data?.qty ?? '')}" min="0" step="0.01" class="qty w-full rounded-xl border border-slate-300 px-3 py-2.5 text-[12px] text-slate-700" placeholder="Total (Berat/Jmlh Jam/Luasan)">
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-medium text-slate-600">Satuan</label>
                        <input type="text" name="satuan[${gIndex}][]" value="${escapeAttr(data?.satuan ?? '')}" class="satuan w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-[12px] text-slate-700" placeholder="Satuan" readonly>
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-medium text-slate-600">Harga Satuan</label>
                        <input type="text" name="harga_satuan[${gIndex}][]" value="${escapeAttr(data?.harga_satuan ?? '')}" class="harga-satuan w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-[12px] text-slate-700" placeholder="Harga satuan" readonly>
                    </div>
                    <div>
                        <label class="mb-1 block text-[11px] font-medium text-slate-600">Harga Total</label>
                        <input type="text" name="harga_total[${gIndex}][]" value="${escapeAttr(data?.harga_total ?? '')}" class="harga-total w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2.5 text-[12px] font-semibold text-slate-700" placeholder="Harga total" readonly>
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-[11px] font-medium text-slate-600">Keterangan</label>
                    <input type="text" name="keterangan[${gIndex}][]" value="${escapeAttr(data?.keterangan ?? '')}" class="w-full rounded-xl border border-slate-300 px-3 py-2.5 text-[12px] text-slate-700" placeholder="Keterangan (opsional)">
                </div>
            `;

            list.appendChild(item);

            const jenisLabelEl = list.closest('.jenis-block').querySelector('.jenis-label');
            const subJenisEl = item.querySelector('.sub-jenis-item');
            const kategoriEl = item.querySelector('.kategori-item');
            const namaItemEl = item.querySelector('.nama-item');
            const qtyEl = item.querySelector('.qty');
            const satuanEl = item.querySelector('.satuan');
            const hsEl = item.querySelector('.harga-satuan');
            const htEl = item.querySelector('.harga-total');

            function recompute() {
                htEl.value = multiplyToCurrencyString(qtyEl.value, hsEl.value);
                htEl.setAttribute('value', htEl.value);
                recalcSubtotal(list, subtotalEl);
            }

            function syncSubJenis(reset = false) {
                const selectedValue = reset ? '' : (initialSubJenis || subJenisEl.value);
                const options = getSubJenisOptions(jenisLabelEl.value).map((value) => ({ value, label: value }));

                populateSelect(
                    subJenisEl,
                    options,
                    options.length > 0 ? 'Pilih sub jenis item' : 'Tanpa sub jenis',
                    selectedValue,
                );

                subJenisEl.disabled = options.length === 0;
                if (options.length === 0) {
                    subJenisEl.value = '';
                }

                initialSubJenis = '';
            }

            function syncKategori(reset = false) {
                const selectedValue = reset ? '' : (initialKategori || kategoriEl.value);
                const options = getKategoriOptions(jenisLabelEl.value, subJenisEl.value).map((value) => ({ value, label: value }));

                populateSelect(
                    kategoriEl,
                    options,
                    options.length > 0 ? 'Pilih kategori item' : 'Tanpa kategori',
                    selectedValue,
                );

                kategoriEl.disabled = options.length === 0;
                if (options.length === 0) {
                    kategoriEl.value = '';
                }

                initialKategori = '';
            }

            function syncNamaItem(reset = false) {
                const selectedValue = reset ? '' : (initialNamaItem || namaItemEl.value);
                const options = getItemOptions(jenisLabelEl.value, subJenisEl.value, kategoriEl.value);

                populateItemSelect(
                    namaItemEl,
                    options,
                    options.length > 0 ? 'Pilih nama item' : 'Tidak ada item tersedia',
                    selectedValue,
                );

                namaItemEl.disabled = options.length === 0;
                if (options.length === 0) {
                    namaItemEl.value = '';
                }

                initialNamaItem = '';
            }

            function syncItemMeta() {
                const selectedOption = namaItemEl.selectedOptions?.[0];
                const hargaSatuan = selectedOption?.dataset?.hargaSatuan ?? '';
                const satuan = selectedOption?.dataset?.satuan ?? '';

                if (! selectedOption || normalizeKey(namaItemEl.value) === '') {
                    satuanEl.value = '';
                    hsEl.value = '';
                    hsEl.setAttribute('value', '');
                    recompute();

                    return;
                }

                satuanEl.value = satuan;
                hsEl.value = hargaSatuan;
                hsEl.setAttribute('value', hsEl.value);
                recompute();
            }

            qtyEl.addEventListener('input', recompute);
            namaItemEl.addEventListener('change', syncItemMeta);
            subJenisEl.addEventListener('change', () => {
                initialKategori = '';
                initialNamaItem = '';
                syncKategori(true);
                syncNamaItem(true);
                syncItemMeta();
            });
            kategoriEl.addEventListener('change', () => {
                initialNamaItem = '';
                syncNamaItem(true);
                syncItemMeta();
            });

            item.querySelector('.remove-item').addEventListener('click', () => {
                item.remove();
                recalcSubtotal(list, subtotalEl);
            });

            item.refreshContractOptions = (reset = false) => {
                if (reset) {
                    initialSubJenis = '';
                    initialKategori = '';
                    initialNamaItem = '';
                }

                syncSubJenis(reset);
                syncKategori(reset);
                syncNamaItem(reset);
                syncItemMeta();
            };

            item.refreshContractOptions();
        }

        function recalcSubtotal(list, subtotalEl) {
            let subtotal = '0.00';

            list.querySelectorAll('.harga-total').forEach((ht) => {
                subtotal = addCurrencyStrings(subtotal, ht.value || '0');
            });

            subtotalEl.dataset.raw = subtotal;
            subtotalEl.textContent = formatCurrencyDisplay(subtotal);

            updateGrandTotal();
        }

        function updateGrandTotal() {
            let grand = '0.00';

            document.querySelectorAll('.subtotal').forEach((subtotal) => {
                grand = addCurrencyStrings(grand, subtotal.dataset.raw || '0');
            });

            totalAllEl.value = formatCurrencyDisplay(grand);
        }

        function escapeAttr(value) {
            if (value == null) return '';
            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        if (Array.isArray(presetGroups) && presetGroups.length > 0) {
            presetGroups.forEach((group) => addJenis(group));
        } else {
            addJenis();
        }
    });
</script>
