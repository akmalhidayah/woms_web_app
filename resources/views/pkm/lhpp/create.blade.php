        @php
            $formTitle = $formTitle ?? 'Buat BAST Termin 1';
            $formAction = $formAction ?? route('pkm.lhpp.store');
            $formMethod = $formMethod ?? 'POST';
            $submitLabel = $submitLabel ?? 'Simpan';
            $terminType = $terminType ?? 'termin_1';
            $terminLabel = $terminLabel ?? ($terminType === 'termin_2' ? 'Termin 2' : 'Termin 1');
            $bastDate = old('tanggal_bast', $bastDate ?? now()->format('Y-m-d'));
            $tanggalMulaiPekerjaan = old('tanggal_mulai_pekerjaan', $tanggalMulaiPekerjaan ?? '');
            $tanggalSelesaiPekerjaan = old('tanggal_selesai_pekerjaan', $tanggalSelesaiPekerjaan ?? '');
            $bastOrderOptions = collect($bastOrderOptions ?? []);
            $selectedBastOrder = (string) old('nomor_order', $selectedBastOrder ?? '');
            $selectedThreshold = (string) old('approval_threshold', $selectedThreshold ?? 'under_250');
            $materialRows = collect($initialMaterialRows ?? [
                ['name' => '', 'volume' => '', 'unit' => 'Jam', 'unit_price' => '', 'amount' => '0.00', 'amount_display' => '0'],
            ]);
            $serviceRows = collect($initialServiceRows ?? [
                ['name' => '', 'volume' => '', 'unit' => 'Jam', 'unit_price' => '', 'amount' => '0.00', 'amount_display' => '0'],
            ]);
            $initialCalculation = $initialCalculation ?? [
                'subtotal_material' => '0.00',
                'subtotal_jasa' => '0.00',
                'total_aktual_biaya' => '0.00',
                'termin_1_nilai' => '0.00',
                'termin_2_nilai' => '0.00',
                'subtotal_material_display' => '0',
                'subtotal_jasa_display' => '0',
                'total_aktual_biaya_display' => '0',
                'termin_1_nilai_display' => '0',
                'termin_2_nilai_display' => '0',
            ];
        @endphp

        <div class="space-y-5">
            <section class="overflow-hidden rounded-[1.8rem] border border-slate-200 bg-white px-5 py-5 text-slate-900 shadow-sm">
                <h1 class="text-[2rem] font-black leading-none tracking-tight text-slate-900">{{ $formTitle }}</h1>
            </section>

            <section
                x-data="pkmLhppCreateForm({
                    approvalThreshold: @js($selectedThreshold),
                    orderOptions: @js($bastOrderOptions->values()->all()),
                    selectedOrder: @js($selectedBastOrder),
                    calculateUrl: @js(route('pkm.lhpp.calculate')),
                    terminType: @js($terminType),
                    unitOptions: ['Jam', 'Kg', 'M2', 'CM3', 'Liter'],
                    materialRows: @js($materialRows->values()->all()),
                    serviceRows: @js($serviceRows->values()->all()),
                    calculation: @js($initialCalculation),
                })"
                x-init="recalculate()"
                class="rounded-[1.6rem] border border-slate-200 bg-white p-5 shadow-sm"
            >
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-4">
                    <div>
                        <h2 class="text-[16px] font-black text-slate-900">{{ $formTitle }}</h2>
                        <p class="mt-1 text-[12px] text-slate-500">Versi front-end ini mengikuti struktur dokumen asli, tapi saya buat lebih nyaman untuk input di web.</p>
                    </div>
                    <div class="flex flex-wrap items-end gap-3">
                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2.5">
                            <div class="text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Rule Approval &amp; PDF</div>
                            <div class="mt-1.5 min-w-[210px] rounded-xl border border-slate-300 bg-white px-4 py-2 text-[12px] font-bold text-slate-700">
                                <span x-text="approvalThresholdLabel()"></span>
                            </div>
                            <div class="mt-1.5 text-[10px] leading-snug text-slate-500">
                                Menentukan alur approval dan format PDF BAST
                            </div>
                        </div>
                        <button type="submit" form="pkm-lhpp-create-form" class="inline-flex items-center gap-2 rounded-xl bg-[#ca642f] px-4 py-2 text-[12px] font-bold text-white transition hover:bg-[#b85b2b]">
                            <i data-lucide="save" class="h-4 w-4"></i>
                            {{ $submitLabel }}
                        </button>
                    </div>
                </div>

                <form id="pkm-lhpp-create-form" method="POST" action="{{ $formAction }}" class="mt-5 space-y-5">
                    @csrf
                    @if (strtoupper($formMethod) !== 'POST')
                        @method($formMethod)
                    @endif
                    <input type="hidden" name="termin_type" value="{{ $terminType }}">
                    <input type="hidden" name="approval_threshold" :value="approvalThreshold">
                    @if ($errors->has('form'))
                        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                            {{ $errors->first('form') }}
                        </div>
                    @endif
                    <div class="grid gap-4 xl:grid-cols-[1.42fr_0.58fr]">
                        <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="grid gap-3 sm:grid-cols-[190px_16px_minmax(0,1fr)]">
                                <label class="text-[12px] font-semibold uppercase tracking-[0.08em] text-slate-700">Tanggal BAST</label>
                                <div aria-hidden="true"></div>
                                <input type="date" name="tanggal_bast" value="{{ $bastDate }}" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-[#ca642f] focus:outline-none">

                                <label class="text-[12px] font-semibold uppercase tracking-[0.08em] text-slate-700">Nomor Order</label>
                                <div aria-hidden="true"></div>
                                <div class="relative">
                                    <select name="nomor_order" x-model="selectedOrder" x-init="$nextTick(() => { $el.value = selectedOrder; })" class="w-full appearance-none rounded-xl border border-slate-300 bg-white px-3 py-2 pr-10 text-sm text-slate-700 focus:border-[#ca642f] focus:outline-none">
                                        <option value="">Pilih Nomor Order</option>
                                        <template x-for="order in orderOptions" :key="order.nomor_order">
                                            <option :value="order.nomor_order" :selected="order.nomor_order === selectedOrder" x-text="order.nomor_order"></option>
                                        </template>
                                    </select>
                                    <i data-lucide="chevron-down" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"></i>
                                </div>

                                <label class="text-[12px] font-semibold uppercase tracking-[0.08em] text-slate-700">Deskripsi Pekerjaan</label>
                                <div aria-hidden="true"></div>
                                <input type="text" x-bind:value="currentOrder().deskripsi_pekerjaan" readonly class="rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700 focus:outline-none">

                                <label class="text-[12px] font-semibold uppercase tracking-[0.08em] text-slate-700">Unit Kerja Peminta (User)</label>
                                <div aria-hidden="true"></div>
                                <input type="text" x-bind:value="currentOrder().unit_kerja_peminta" readonly class="rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700 focus:outline-none">

                                <label class="text-[12px] font-semibold uppercase tracking-[0.08em] text-slate-700">Purchasing Order (P.O)</label>
                                <div aria-hidden="true"></div>
                                <input type="text" x-bind:value="currentOrder().purchase_order_number" readonly class="rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700 focus:outline-none">

                                <label class="text-[12px] font-semibold uppercase tracking-[0.08em] text-slate-700">Tanggal Dimulainya Pekerjaan</label>
                                <div aria-hidden="true"></div>
                                <input type="date" name="tanggal_mulai_pekerjaan" value="{{ $tanggalMulaiPekerjaan }}" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-[#ca642f] focus:outline-none">

                                <label class="text-[12px] font-semibold uppercase tracking-[0.08em] text-slate-700">Tanggal Selesainya Pekerjaan</label>
                                <div aria-hidden="true"></div>
                                <input type="date" name="tanggal_selesai_pekerjaan" value="{{ $tanggalSelesaiPekerjaan }}" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-[#ca642f] focus:outline-none">
                            </div>
                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
                            <div class="rounded-2xl border border-slate-300 bg-slate-50 p-3">
                                <div class="text-center text-[11px] font-black uppercase tracking-[0.14em] text-slate-700">Nilai HPP</div>
                                <div class="mt-3 rounded-xl border border-slate-300 bg-white px-3 py-3 text-right text-[16px] font-black text-slate-900" x-text="`Rp. ${formatCurrency(currentOrder().nilai_ece)}`"></div>
                            </div>

                            <div class="mt-3 rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="text-[10px] font-semibold uppercase tracking-[0.08em] text-slate-500">Flow Approval</div>
                                        <div class="mt-1 text-[13px] font-black text-slate-900">BAST {{ $terminLabel }}</div>
                                    </div>
                                    <span class="inline-flex items-center rounded-full bg-orange-50 px-2.5 py-1 text-[10px] font-bold text-[#ca642f] ring-1 ring-orange-200" x-text="approvalThreshold === 'over_250' ? 'Diatas 250 JT' : 'Dibawah 250 JT'"></span>
                                </div>

                                <div class="mt-3 space-y-2.5">
                                    <div class="flex items-start gap-2.5">
                                        <div class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-[10px] font-black text-slate-700">1</div>
                                        <div class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                            <div class="text-[11px] font-bold text-slate-900">Manager Peminta</div>
                                        </div>
                                    </div>

                                    <div class="ml-3 h-3 w-px bg-slate-300"></div>

                                    <div class="flex items-start gap-2.5">
                                        <div class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-[10px] font-black text-slate-700">2</div>
                                        <div class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                            <div class="text-[11px] font-bold text-slate-900">Manager Pengendali</div>
                                        </div>
                                    </div>

                                    <div class="ml-3 h-3 w-px bg-slate-300"></div>

                                    <div class="flex items-start gap-2.5">
                                        <div class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 text-[10px] font-black text-slate-700">3</div>
                                        <div class="min-w-0 flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                            <div class="text-[11px] font-bold text-slate-900">GM Pengendali</div>
                                        </div>
                                    </div>

                                    <template x-if="approvalThreshold === 'over_250'">
                                        <div>
                                            <div class="ml-3 h-3 w-px bg-slate-300"></div>
                                            <div class="flex items-start gap-2.5">
                                                <div class="mt-0.5 inline-flex h-6 w-6 items-center justify-center rounded-full bg-[#fde9db] text-[10px] font-black text-[#ca642f]">4</div>
                                                <div class="min-w-0 flex-1 rounded-xl border border-orange-200 bg-orange-50 px-3 py-2">
                                                    <div class="text-[11px] font-bold text-[#9a4f28]">Dirops</div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                            <div class="text-[13px] font-bold text-slate-900">Aktual Pemakaian Material</div>
                            <button type="button" @click="addMaterialRow()" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-[11px] font-bold text-slate-700 transition hover:bg-slate-50">
                                <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                                Tambah Baris
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full border-collapse text-[11px] text-slate-800">
                                <thead>
                                    <tr class="bg-slate-100">
                                        <th class="w-[52px] border border-slate-300 px-2 py-2 text-center font-bold">No.</th>
                                        <th class="border border-slate-300 px-2 py-2 text-left font-bold">A. Aktual Pemakaian Material</th>
                                        <th class="w-[220px] border border-slate-300 px-2 py-2 text-center font-bold">Total Durasi / Volume / Luasan Pekerjaan<br><span class="font-medium">(Jam/Kg/M2/CM3/Liter)</span></th>
                                        <th class="w-[150px] border border-slate-300 px-2 py-2 text-center font-bold">Harga Satuan<br><span class="font-medium">(Rp)</span></th>
                                        <th class="w-[170px] border border-slate-300 px-2 py-2 text-center font-bold">Jumlah<br><span class="font-medium">(Rp)</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(row, index) in materialRows" :key="`material-${index}`">
                                        <tr>
                                            <td class="border border-slate-300 px-2 py-2 text-center align-top font-semibold" x-text="index + 1"></td>
                                            <td class="border border-slate-300 px-2 py-2">
                                                <input type="text" x-model="row.name" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-[#ca642f] focus:outline-none">
                                                <input type="hidden" :name="`material_rows[${index}][name]`" x-model="row.name">
                                            </td>
                                            <td class="border border-slate-300 px-2 py-2">
                                                <div class="flex flex-nowrap items-center gap-1.5">
                                                    <input type="text" x-model="row.volume" @change="recalculate()" @blur="recalculate()" class="min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-right text-sm text-slate-700 focus:border-[#ca642f] focus:outline-none">
                                                    <input type="hidden" :name="`material_rows[${index}][volume]`" x-model="row.volume">
                                                    <div class="relative">
                                                        <select x-model="row.unit" @change="recalculate()" class="w-[68px] shrink-0 appearance-none rounded-lg border border-slate-300 bg-white px-2 py-2 pr-6 text-[11px] text-slate-700 focus:border-[#ca642f] focus:outline-none">
                                                            <template x-for="unitOption in unitOptions" :key="unitOption">
                                                                <option :value="unitOption" x-text="unitOption"></option>
                                                            </template>
                                                        </select>
                                                        <input type="hidden" :name="`material_rows[${index}][unit]`" x-model="row.unit">
                                                        <i data-lucide="chevron-down" class="pointer-events-none absolute right-1.5 top-1/2 h-3 w-3 -translate-y-1/2 text-slate-500"></i>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="border border-slate-300 px-2 py-2">
                                                <input type="text" x-model="row.unit_price" @change="recalculate()" @blur="recalculate()" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-right text-sm text-slate-700 focus:border-[#ca642f] focus:outline-none">
                                                <input type="hidden" :name="`material_rows[${index}][unit_price]`" x-model="row.unit_price">
                                            </td>
                                            <td class="border border-slate-300 px-2 py-2">
                                                <input type="text" :value="row.amount_display ?? '0'" readonly class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-right text-sm font-semibold text-slate-700 focus:outline-none">
                                            </td>
                                        </tr>
                                    </template>
                                    <tr class="bg-[#fff7df]">
                                        <td colspan="4" class="border border-slate-300 px-2 py-2 font-bold">SUB TOTAL ( A )</td>
                                        <td class="border border-slate-300 px-2 py-2 text-right font-black" x-text="calculation.subtotal_material_display"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                            <div class="text-[13px] font-bold text-slate-900">Aktual Biaya Jasa</div>
                            <button type="button" @click="addServiceRow()" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-[11px] font-bold text-slate-700 transition hover:bg-slate-50">
                                <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                                Tambah Baris
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full border-collapse text-[11px] text-slate-800">
                                <thead>
                                    <tr class="bg-slate-100">
                                        <th class="w-[52px] border border-slate-300 px-2 py-2 text-center font-bold">No.</th>
                                        <th class="border border-slate-300 px-2 py-2 text-left font-bold">B. Aktual Biaya Jasa</th>
                                        <th class="w-[220px] border border-slate-300 px-2 py-2 text-center font-bold">Total Durasi / Volume / Luasan Pekerjaan<br><span class="font-medium">(Jam/Kg/M2/CM3/Liter)</span></th>
                                        <th class="w-[150px] border border-slate-300 px-2 py-2 text-center font-bold">Harga Satuan<br><span class="font-medium">(Rp)</span></th>
                                        <th class="w-[170px] border border-slate-300 px-2 py-2 text-center font-bold">Jumlah<br><span class="font-medium">(Rp)</span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="(row, index) in serviceRows" :key="`service-${index}`">
                                        <tr>
                                            <td class="border border-slate-300 px-2 py-2 text-center align-top font-semibold" x-text="index + 1"></td>
                                            <td class="border border-slate-300 px-2 py-2">
                                                <input type="text" x-model="row.name" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-[#ca642f] focus:outline-none">
                                                <input type="hidden" :name="`service_rows[${index}][name]`" x-model="row.name">
                                            </td>
                                            <td class="border border-slate-300 px-2 py-2">
                                                <div class="flex flex-nowrap items-center gap-1.5">
                                                    <input type="text" x-model="row.volume" @change="recalculate()" @blur="recalculate()" class="min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-right text-sm text-slate-700 focus:border-[#ca642f] focus:outline-none">
                                                    <input type="hidden" :name="`service_rows[${index}][volume]`" x-model="row.volume">
                                                    <div class="relative">
                                                        <select x-model="row.unit" @change="recalculate()" class="w-[68px] shrink-0 appearance-none rounded-lg border border-slate-300 bg-white px-2 py-2 pr-6 text-[11px] text-slate-700 focus:border-[#ca642f] focus:outline-none">
                                                            <template x-for="unitOption in unitOptions" :key="unitOption">
                                                                <option :value="unitOption" x-text="unitOption"></option>
                                                            </template>
                                                        </select>
                                                        <input type="hidden" :name="`service_rows[${index}][unit]`" x-model="row.unit">
                                                        <i data-lucide="chevron-down" class="pointer-events-none absolute right-1.5 top-1/2 h-3 w-3 -translate-y-1/2 text-slate-500"></i>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="border border-slate-300 px-2 py-2">
                                                <input type="text" x-model="row.unit_price" @change="recalculate()" @blur="recalculate()" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-right text-sm text-slate-700 focus:border-[#ca642f] focus:outline-none">
                                                <input type="hidden" :name="`service_rows[${index}][unit_price]`" x-model="row.unit_price">
                                            </td>
                                            <td class="border border-slate-300 px-2 py-2">
                                                <input type="text" :value="row.amount_display ?? '0'" readonly class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-right text-sm font-semibold text-slate-700 focus:outline-none">
                                            </td>
                                        </tr>
                                    </template>
                                    <tr class="bg-[#fff7df]">
                                        <td colspan="4" class="border border-slate-300 px-2 py-2 font-bold">SUB TOTAL ( B )</td>
                                        <td class="border border-slate-300 px-2 py-2 text-right font-black" x-text="calculation.subtotal_jasa_display"></td>
                                    </tr>
                                    <tr class="bg-slate-100">
                                        <td colspan="4" class="border border-slate-300 px-2 py-2 font-black">TOTAL AKTUAL BIAYA ( A + B )</td>
                                        <td class="border border-slate-300 px-2 py-2 text-right font-black" x-text="calculation.total_aktual_biaya_display"></td>
                                    </tr>
                                    <tr class="bg-slate-200">
                                        <td colspan="4" class="border border-slate-300 px-2 py-2 font-black" x-text="terminType === 'termin_2' ? 'TERMIN 2 (5% x Total Actual Biaya)' : 'TERMIN 1 (95% x Total Actual Biaya)'"></td>
                                        <td class="border border-slate-300 px-2 py-2 text-right font-black" x-text="terminType === 'termin_2' ? calculation.termin_2_nilai_display : calculation.termin_1_nilai_display"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </form>
            </section>
        </div>

        <script>
            function pkmLhppCreateForm(config) {
                return {
                    approvalThreshold: config.approvalThreshold,
                    orderOptions: config.orderOptions,
                    selectedOrder: config.selectedOrder,
                    calculateUrl: config.calculateUrl,
                    terminType: config.terminType,
                    unitOptions: config.unitOptions,
                    materialRows: config.materialRows,
                    serviceRows: config.serviceRows,
                    calculation: config.calculation,
                    currentOrder() {
                        return this.orderOptions.find((item) => item.nomor_order === this.selectedOrder) ?? {
                            nomor_order: '',
                            deskripsi_pekerjaan: '',
                            unit_kerja_peminta: '',
                            unit_kerja: '',
                            seksi: '',
                            purchase_order_number: '',
                            nilai_ece: 0,
                        };
                    },
                    approvalThresholdLabel() {
                        return this.approvalThreshold === 'over_250' ? 'Diatas 250 JT' : 'Dibawah 250 JT';
                    },
                    formatCurrency(value) {
                        const amount = Number(value || 0);
                        return new Intl.NumberFormat('id-ID').format(amount);
                    },
                    async recalculate() {
                        try {
                            const response = await fetch(this.calculateUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') ?? '',
                                },
                                body: JSON.stringify({
                                    material_rows: this.materialRows,
                                    service_rows: this.serviceRows,
                                }),
                            });

                            if (!response.ok) {
                                return;
                            }

                            const result = await response.json();
                            this.materialRows = result.material_rows;
                            this.serviceRows = result.service_rows;
                            this.calculation = result.totals;
                            this.approvalThreshold = this.resolveThreshold();

                            this.$nextTick(() => {
                                if (window.lucide?.createIcons) {
                                    window.lucide.createIcons();
                                }
                            });
                        } catch (error) {
                            console.error('Failed to calculate LHPP totals.', error);
                        }
                    },
                    addMaterialRow() {
                        this.materialRows.push({ name: '', volume: '', unit: 'Jam', unit_price: '', amount: '0.00', amount_display: '0' });
                    },
                    addServiceRow() {
                        this.serviceRows.push({ name: '', volume: '', unit: 'Jam', unit_price: '', amount: '0.00', amount_display: '0' });
                    },
                    resolveThreshold() {
                        const thresholdBase = this.terminType === 'termin_2'
                            ? Number(this.calculation.termin_2_nilai || 0)
                            : Number(this.calculation.termin_1_nilai || 0);

                        return thresholdBase > 250000000 ? 'over_250' : 'under_250';
                    },
                };
            }
        </script>
