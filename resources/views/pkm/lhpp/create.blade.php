        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/css/tom-select.css">
        <script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.3/dist/js/tom-select.complete.min.js"></script>
        @php
            $formTitle = $formTitle ?? 'Buat BAST Termin 1';
            $formAction = $formAction ?? route('pkm.lhpp.store');
            $formMethod = $formMethod ?? 'POST';
            $submitLabel = $submitLabel ?? 'Simpan';
            $terminType = $terminType ?? 'termin_1';
            $terminLabel = $terminLabel ?? ($terminType === 'termin_2' ? 'Termin 2' : 'Termin 1');
            $documentNo = $documentNo ?? null;
            $isWithoutWarranty = (bool) ($isWithoutWarranty ?? false);
            $isTerminTwoLocked = $terminType === 'termin_2';
            $bastDate = old('tanggal_bast', $bastDate ?? now()->format('Y-m-d'));
            $tanggalMulaiPekerjaan = old('tanggal_mulai_pekerjaan', $tanggalMulaiPekerjaan ?? '');
            $tanggalSelesaiPekerjaan = old('tanggal_selesai_pekerjaan', $tanggalSelesaiPekerjaan ?? '');
           $oldTipePekerjaan = old('tipe_pekerjaan');

$selectedTipePekerjaan = filled($oldTipePekerjaan)
    ? (string) $oldTipePekerjaan
    : (string) ($selectedTipePekerjaan ?? '');
            $isTipePekerjaanLocked = $terminType === 'termin_2' && $selectedTipePekerjaan !== '';
            $tipePekerjaanOptions = collect($tipePekerjaanOptions ?? [])
                ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                ->values();
            $useFixedWorkDates = (bool) ($useFixedWorkDates ?? false);
            $bastOrderOptions = collect($bastOrderOptions ?? []);
            $selectedBastOrder = (string) old('nomor_order', $selectedBastOrder ?? '');
            $selectedThreshold = (string) old('approval_threshold', $selectedThreshold ?? 'under_250');
            $approvalFlowMatrix = $approvalFlowMatrix ?? [];
            $selectedApprovalFlow = array_values((array) old('approval_flow', $selectedApprovalFlow ?? []));
            $existingImages = collect($existingImages ?? []);
            $materialRows = collect($initialMaterialRows ?? [
                ['jenis_item' => '', 'kategori_item' => '', 'name' => '', 'volume' => '', 'unit' => '', 'unit_price' => '', 'amount' => '0.00', 'amount_display' => '0'],
            ]);
            $serviceRows = collect($initialServiceRows ?? [
                ['jenis_item' => '', 'kategori_item' => '', 'name' => '', 'volume' => '', 'unit' => '', 'unit_price' => '', 'amount' => '0.00', 'amount_display' => '0'],
            ]);
            $contractCatalog = collect($contractCatalog ?? []);
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

        <div>
            <section class="overflow-hidden rounded-[1.2rem] border border-slate-200 bg-white px-4 py-3 text-slate-900 shadow-sm">
                <h1 class="text-[1.15rem] font-black leading-none tracking-tight text-slate-900">{{ $formTitle }}</h1>
            </section>

            <section
                x-data="pkmLhppCreateForm({
                    approvalThreshold: @js($selectedThreshold),
                    orderOptions: @js($bastOrderOptions->values()->all()),
                    selectedOrder: @js($selectedBastOrder),
                    calculateUrl: @js(route('pkm.lhpp.calculate')),
                    terminType: @js($terminType),
                    unitOptions: ['Jam', 'Kg', 'M2', 'CM3', 'Liter'],
                    contractCatalog: @js($contractCatalog->values()->all()),
                    materialRows: @js($materialRows->values()->all()),
                    serviceRows: @js($serviceRows->values()->all()),
                    calculation: @js($initialCalculation),
                    isWithoutWarranty: @js($isWithoutWarranty),
                    isTerminTwoLocked: @js($isTerminTwoLocked),
                    isTipePekerjaanLocked: @js($isTipePekerjaanLocked),
                    selectedTipePekerjaan: @js($selectedTipePekerjaan),
                    tipePekerjaanOptions: @js($tipePekerjaanOptions->all()),
                    approvalFlowMatrix: @js($approvalFlowMatrix),
                    approvalFlow: @js($selectedApprovalFlow),
                    approvalSignerPreview: @js($approvalSignerPreview ?? []),
                    workStartDate: @js($tanggalMulaiPekerjaan),
                    workFinishDate: @js($tanggalSelesaiPekerjaan),
                    useFixedWorkDates: @js($useFixedWorkDates),
                    hppValueMatchesBast: false,
                })"
                x-init="syncApprovalFlow(approvalFlow); refreshItemSelects(); recalculate()"
                class="mt-4 rounded-[1.2rem] border border-slate-200 bg-white p-4 shadow-sm"
            >
                <form id="pkm-lhpp-create-form" method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    @if (strtoupper($formMethod) !== 'POST')
                        @method($formMethod)
                    @endif
                    <input type="hidden" name="termin_type" value="{{ $terminType }}">
                    <input type="hidden" name="approval_threshold" :value="approvalThreshold">
                    <template x-for="(step, index) in approvalFlow" :key="`bast-approval-flow-input-${index}-${step}`">
                        <input type="hidden" name="approval_flow[]" :value="step">
                    </template>
                    @if ($errors->getBag('default')->any())
                        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                            <div class="font-semibold">BAST belum dapat disimpan.</div>
                            <ul class="mt-2 list-disc space-y-1 pl-5">
                                @foreach ($errors->getBag('default')->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <div class="grid min-w-0 gap-3 lg:grid-cols-[minmax(0,1fr)_240px]">
                        <div class="min-w-0 rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                            <div class="grid items-center gap-x-3 gap-y-2 sm:grid-cols-[150px_minmax(0,1fr)]">
                                <label class="text-[10px] font-semibold uppercase tracking-[0.06em] text-slate-700">Tanggal BAST</label>
                                <input type="date" name="tanggal_bast" value="{{ $bastDate }}" class="h-9 min-w-0 rounded-lg border border-slate-300 bg-white px-3 text-[12px] text-slate-700 focus:border-[#ca642f] focus:outline-none">

                                <label class="text-[10px] font-semibold uppercase tracking-[0.06em] text-slate-700">Nomor Order</label>
                                <div class="relative">
                                    <select name="nomor_order" x-model="selectedOrder" x-init="$nextTick(() => { $el.value = selectedOrder; })" @change="applyHppSyncIfChecked()" class="h-9 w-full appearance-none rounded-lg border border-slate-300 bg-white px-3 pr-9 text-[12px] text-slate-700 focus:border-[#ca642f] focus:outline-none">
                                        <option value="">Pilih Nomor Order</option>
                                        <template x-for="order in orderOptions" :key="order.nomor_order">
                                            <option :value="order.nomor_order" :selected="order.nomor_order === selectedOrder" x-text="order.nomor_order"></option>
                                        </template>
                                    </select>
                                    <i data-lucide="chevron-down" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"></i>
                                </div>

                                <label class="text-[10px] font-semibold uppercase tracking-[0.06em] text-slate-700">Nomor Notifikasi</label>
                                <input type="text" x-bind:value="currentOrder().notifikasi" readonly class="h-9 min-w-0 rounded-lg border border-slate-300 bg-slate-50 px-3 text-[12px] text-slate-700 focus:outline-none">

                                <label class="text-[10px] font-semibold uppercase tracking-[0.06em] text-slate-700">Deskripsi Pekerjaan</label>
                                <input type="text" x-bind:value="currentOrder().deskripsi_pekerjaan" readonly class="h-9 min-w-0 rounded-lg border border-slate-300 bg-slate-50 px-3 text-[12px] text-slate-700 focus:outline-none">

                                <label class="text-[10px] font-semibold uppercase tracking-[0.06em] text-slate-700">Unit Kerja Peminta</label>
                                <input type="text" x-bind:value="currentOrder().unit_kerja_peminta" readonly class="h-9 min-w-0 rounded-lg border border-slate-300 bg-slate-50 px-3 text-[12px] text-slate-700 focus:outline-none">

                                <label class="text-[10px] font-semibold uppercase tracking-[0.06em] text-slate-700">Purchase Order</label>
                                <input type="text" x-bind:value="currentOrder().purchase_order_number" readonly class="h-9 min-w-0 rounded-lg border border-slate-300 bg-slate-50 px-3 text-[12px] text-slate-700 focus:outline-none">

                                <label class="text-[10px] font-semibold uppercase tracking-[0.06em] text-slate-700">Tipe Pekerjaan</label>
                                <div class="relative">
                                    @if ($isTipePekerjaanLocked)
                                        <input type="hidden" name="tipe_pekerjaan" value="{{ $selectedTipePekerjaan }}">
                                    @endif

                                    <select
                                        @if (! $isTipePekerjaanLocked)
                                            name="tipe_pekerjaan"
                                        @endif
                                        x-model="selectedTipePekerjaan"
                                        :disabled="isTipePekerjaanLocked"
                                        class="h-9 w-full appearance-none rounded-lg border border-slate-300 bg-white px-3 pr-9 text-[12px] text-slate-700 focus:border-[#ca642f] focus:outline-none disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500"
                                    >
                                        <option value="">Pilih Tipe Pekerjaan</option>

                                        @foreach ($tipePekerjaanOptions as $option)
                                            <option
                                                value="{{ $option['value'] }}"
                                                @selected($selectedTipePekerjaan === $option['value'])
                                            >
                                                {{ $option['label'] }}
                                            </option>
                                        @endforeach
                                    </select>

                                    <i data-lucide="chevron-down" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"></i>
                                </div>
                                <label class="text-[10px] font-semibold uppercase tracking-[0.06em] text-slate-700">Tanggal Mulai</label>
                                <input type="date" name="tanggal_mulai_pekerjaan" value="{{ $tanggalMulaiPekerjaan }}" :value="resolvedWorkStartDate()" class="h-9 min-w-0 rounded-lg border border-slate-300 bg-white px-3 text-[12px] text-slate-700 focus:border-[#ca642f] focus:outline-none">

                                <label class="text-[10px] font-semibold uppercase tracking-[0.06em] text-slate-700">Tanggal Selesai</label>
                                <input type="date" name="tanggal_selesai_pekerjaan" value="{{ $tanggalSelesaiPekerjaan }}" :value="resolvedWorkFinishDate()" class="h-9 min-w-0 rounded-lg border border-slate-300 bg-white px-3 text-[12px] text-slate-700 focus:border-[#ca642f] focus:outline-none">
                            </div>
                        </div>

                        <div class="min-w-0 space-y-2">
                            <div class="rounded-xl border border-slate-200 bg-slate-50 p-2.5 shadow-sm">
                                <div class="flex items-center justify-between gap-2">
                                    <span class="text-[9px] font-bold uppercase tracking-[0.1em] text-slate-500">Rule Approval</span>
                                    <span class="rounded-md bg-white px-2 py-1 text-[9px] font-bold text-slate-700 ring-1 ring-slate-200" x-text="approvalThresholdLabel()"></span>
                                </div>
                                <div class="mt-2 flex items-center justify-between border-t border-slate-200 pt-2">
                                    <span class="text-[9px] font-bold uppercase tracking-[0.1em] text-slate-500">Nilai HPP</span>
                                    <span class="text-[12px] font-black text-slate-900" x-text="`Rp. ${formatCurrency(currentOrder().nilai_ece)}`"></span>
                                </div>
                            </div>

                            <div class="rounded-xl border border-slate-200 bg-white p-2.5 shadow-sm">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="text-[9px] font-semibold uppercase tracking-[0.08em] text-slate-500">Flow Approval</div>
                                        <div class="mt-0.5 text-[11px] font-black text-slate-900">BAST {{ $terminLabel }}</div>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-1.5">
                                        <span class="inline-flex items-center rounded-full bg-orange-50 px-2 py-0.5 text-[8px] font-bold text-[#ca642f] ring-1 ring-orange-200" x-text="approvalThreshold === 'over_250' ? '> Rp250 juta' : '≤ Rp250 juta'"></span>
                                        <span class="inline-flex items-center rounded-full bg-white px-2 py-0.5 text-[8px] font-bold text-slate-600 ring-1 ring-slate-200" x-text="`${approvalFlow.length} step`"></span>
                                    </div>
                                </div>

                                <div class="mt-2 flex items-center justify-between gap-2 border-t border-slate-100 pt-2">
                                    <span class="text-[8px] font-semibold text-slate-500" x-text="isDefaultApprovalFlow() ? 'Default flow' : 'Flow disesuaikan'"></span>
                                    <button
                                        type="button"
                                        class="rounded-md border border-slate-200 bg-white px-2 py-1 text-[8px] font-bold text-slate-600 transition hover:bg-slate-50"
                                        x-show="! isDefaultApprovalFlow()"
                                        @click="resetApprovalFlow()"
                                    >
                                        Reset Default
                                    </button>
                                </div>

                                <ol class="mt-2 space-y-1">
                                    <template x-for="(step, index) in approvalFlow" :key="`${approvalThreshold}-${step}-${index}`">
                                        <li
                                            class="flex items-center gap-1.5 rounded-md border px-2 py-1.5 transition"
                                            :class="index === 0 ? 'border-emerald-100 bg-emerald-50' : (step === 'DIROPS' ? 'border-orange-200 bg-orange-50' : 'border-slate-200 bg-slate-50')"
                                            draggable="true"
                                            @dragstart="startApprovalDrag(index)"
                                            @dragover.prevent
                                            @drop.prevent="dropApprovalStep(index)"
                                            @dragend="endApprovalDrag()"
                                        >
                                            <button
                                                type="button"
                                                class="inline-flex h-5 w-5 shrink-0 cursor-grab items-center justify-center rounded bg-white text-slate-400 ring-1 ring-slate-200 active:cursor-grabbing"
                                                title="Geser urutan"
                                            >
                                                <i data-lucide="grip-vertical" class="h-3 w-3"></i>
                                            </button>
                                            <div
                                                class="inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[8px] font-black"
                                                :class="index === 0 ? 'bg-emerald-600 text-white' : (step === 'DIROPS' ? 'bg-[#fde9db] text-[#ca642f]' : 'bg-slate-100 text-slate-700')"
                                                x-text="index + 1"
                                            ></div>
                                            <div class="min-w-0 flex-1">
                                                <div class="truncate text-[9px] font-bold" :class="step === 'DIROPS' ? 'text-[#9a4f28]' : 'text-slate-900'" x-text="step"></div>
                                                <div class="mt-0.5 truncate text-[8px] font-medium text-slate-500" x-text="approvalSignerName(step, index)"></div>
                                            </div>
                                            <div class="flex shrink-0 flex-col gap-1">
                                                <button
                                                    type="button"
                                                    class="inline-flex h-4 w-4 items-center justify-center rounded border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                                                    title="Naikkan"
                                                    @click="moveApprovalStep(index, index - 1)"
                                                    :disabled="! canMoveApprovalStep(index, index - 1)"
                                                >
                                                    <i data-lucide="chevron-up" class="h-3 w-3"></i>
                                                </button>
                                                <button
                                                    type="button"
                                                    class="inline-flex h-4 w-4 items-center justify-center rounded border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40"
                                                    title="Turunkan"
                                                    @click="moveApprovalStep(index, index + 1)"
                                                    :disabled="! canMoveApprovalStep(index, index + 1)"
                                                >
                                                    <i data-lucide="chevron-down" class="h-3 w-3"></i>
                                                </button>
                                            </div>
                                        </li>
                                    </template>
                                </ol>
                                <p class="mt-2 text-[8px] leading-relaxed text-slate-500">Urutan bisa digeser sebelum approval berjalan. DIROPS tetap di akhir karena memakai upload dokumen final.</p>
                                @error('approval_flow')
                                    <p class="mt-2 text-[9px] font-semibold text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    </div>

                    @unless ($isTerminTwoLocked)
                        <div class="rounded-2xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                            <label class="inline-flex items-center gap-3 text-[12px] font-bold text-slate-800">
                                <input type="checkbox" x-model="hppValueMatchesBast" @change="handleHppSyncToggle()" class="h-4 w-4 rounded border-slate-300 text-[#ca642f] focus:ring-[#ca642f]">
                                <span>Nilai BAST sama dengan HPP</span>
                            </label>
                        </div>
                    @endunless

                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                            <div>
                                <div class="text-[13px] font-bold text-slate-900">Aktual Pemakaian Material</div>
                                @if ($isTerminTwoLocked)
                                    <p class="mt-1 text-[11px] text-slate-500">Data material mengikuti BAST Termin 1 dan dikunci agar tidak berubah.</p>
                                @endif
                            </div>
                            @unless ($isTerminTwoLocked)
                                <button type="button" @click="addMaterialRow()" x-show="!rowsLocked()" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-[11px] font-bold text-slate-700 transition hover:bg-slate-50">
                                    <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                                    Tambah Baris
                                </button>
                            @endunless
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
                                                <div class="grid gap-2 md:grid-cols-3">
                                                    <div class="relative">
                                                        <select x-model="row.jenis_item" :disabled="rowsLocked()" @change="handleJenisChange(row); recalculate()" class="w-full appearance-none rounded-lg border border-slate-300 bg-white px-3 py-2 pr-8 text-[12px] text-slate-700 focus:border-[#ca642f] focus:outline-none disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500">
                                                            <option value="">Pilih Jenis Item</option>
                                                            <template x-for="jenisOption in getJenisOptions()" :key="`material-jenis-${jenisOption}`">
                                                                <option :value="jenisOption" x-text="jenisOption"></option>
                                                            </template>
                                                        </select>
                                                        <i data-lucide="chevron-down" class="pointer-events-none absolute right-2 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"></i>
                                                    </div>
                                                    <input type="hidden" :name="`material_rows[${index}][jenis_item]`" x-model="row.jenis_item">

                                                    <template x-if="hasKategoriOptions(row.jenis_item)">
                                                        <div class="relative">
                                                            <select x-model="row.kategori_item" :disabled="rowsLocked()" @change="handleKategoriChange(row); recalculate()" class="w-full appearance-none rounded-lg border border-slate-300 bg-white px-3 py-2 pr-8 text-[12px] text-slate-700 focus:border-[#ca642f] focus:outline-none disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500">
                                                                <option value="">Pilih Kategori Item</option>
                                                                <template x-for="kategoriOption in getKategoriOptions(row.jenis_item)" :key="`material-kategori-${row.jenis_item}-${kategoriOption.value}`">
                                                                    <option :value="kategoriOption.value" x-text="kategoriOption.label"></option>
                                                                </template>
                                                            </select>
                                                            <i data-lucide="chevron-down" class="pointer-events-none absolute right-2 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"></i>
                                                        </div>
                                                    </template>
                                                    <input type="hidden" :name="`material_rows[${index}][kategori_item]`" x-model="row.kategori_item">

                                                    <div class="relative" :class="hasKategoriOptions(row.jenis_item) ? '' : 'md:col-span-2'">
                                                        <select x-model="row.name" :disabled="rowsLocked()" @change="handleNameChange(row); recalculate()" class="js-bast-item-select w-full appearance-none rounded-lg border border-slate-300 bg-white px-3 py-2 pr-8 text-[12px] text-slate-700 focus:border-[#ca642f] focus:outline-none disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500">
                                                            <option value="">Pilih Nama Item</option>
                                                            <template x-for="itemOption in getNameOptions(row.jenis_item, row.kategori_item)" :key="`material-name-${row.jenis_item}-${row.kategori_item || 'none'}-${itemOption.nama_item}`">
                                                                <option :value="itemOption.nama_item" x-text="itemOption.nama_item"></option>
                                                            </template>
                                                        </select>
                                                        <i data-lucide="chevron-down" class="pointer-events-none absolute right-2 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"></i>
                                                    </div>
                                                </div>
                                                <input type="hidden" :name="`material_rows[${index}][name]`" x-model="row.name">
                                                <input type="hidden" :name="`material_rows[${index}][contract_item_id]`" x-model="row.contract_item_id">
                                            </td>
                                            <td class="border border-slate-300 px-2 py-2">
                                                <div class="flex flex-nowrap items-center gap-1.5">
                                                    <input type="number" min="0" step="0.001" x-model="row.volume" :readonly="rowsLocked()" @input.debounce.350ms="recalculate()" class="min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-right text-sm text-slate-700 focus:border-[#ca642f] focus:outline-none read-only:cursor-not-allowed read-only:bg-slate-50 read-only:text-slate-500">
                                                    <input type="hidden" :name="`material_rows[${index}][volume]`" x-model="row.volume">
                                                    <input type="text" x-model="row.unit" readonly class="w-[68px] shrink-0 rounded-lg border border-slate-300 bg-slate-50 px-2 py-2 text-center text-[11px] text-slate-700 focus:outline-none">
                                                    <input type="hidden" :name="`material_rows[${index}][unit]`" x-model="row.unit">
                                                </div>
                                            </td>
                                            <td class="border border-slate-300 px-2 py-2">
                                                <input type="text" x-model="row.unit_price" readonly class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-right text-sm text-slate-700 focus:outline-none">
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
                            <div>
                                <div class="text-[13px] font-bold text-slate-900">Aktual Biaya Jasa</div>
                                @if ($isTerminTwoLocked)
                                    <p class="mt-1 text-[11px] text-slate-500">Data jasa mengikuti BAST Termin 1 dan dikunci agar tidak berubah.</p>
                                @endif
                            </div>
                            @unless ($isTerminTwoLocked)
                                <button type="button" @click="addServiceRow()" x-show="!rowsLocked()" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-[11px] font-bold text-slate-700 transition hover:bg-slate-50">
                                    <i data-lucide="plus" class="h-3.5 w-3.5"></i>
                                    Tambah Baris
                                </button>
                            @endunless
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
                                                <div class="grid gap-2 md:grid-cols-3">
                                                    <div class="relative">
                                                        <select x-model="row.jenis_item" :disabled="rowsLocked()" @change="handleJenisChange(row); recalculate()" class="w-full appearance-none rounded-lg border border-slate-300 bg-white px-3 py-2 pr-8 text-[12px] text-slate-700 focus:border-[#ca642f] focus:outline-none disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500">
                                                            <option value="">Pilih Jenis Item</option>
                                                            <template x-for="jenisOption in getJenisOptions()" :key="`service-jenis-${jenisOption}`">
                                                                <option :value="jenisOption" x-text="jenisOption"></option>
                                                            </template>
                                                        </select>
                                                        <i data-lucide="chevron-down" class="pointer-events-none absolute right-2 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"></i>
                                                    </div>
                                                    <input type="hidden" :name="`service_rows[${index}][jenis_item]`" x-model="row.jenis_item">

                                                    <template x-if="hasKategoriOptions(row.jenis_item)">
                                                        <div class="relative">
                                                            <select x-model="row.kategori_item" :disabled="rowsLocked()" @change="handleKategoriChange(row); recalculate()" class="w-full appearance-none rounded-lg border border-slate-300 bg-white px-3 py-2 pr-8 text-[12px] text-slate-700 focus:border-[#ca642f] focus:outline-none disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500">
                                                                <option value="">Pilih Kategori Item</option>
                                                                <template x-for="kategoriOption in getKategoriOptions(row.jenis_item)" :key="`service-kategori-${row.jenis_item}-${kategoriOption.value}`">
                                                                    <option :value="kategoriOption.value" x-text="kategoriOption.label"></option>
                                                                </template>
                                                            </select>
                                                            <i data-lucide="chevron-down" class="pointer-events-none absolute right-2 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"></i>
                                                        </div>
                                                    </template>
                                                    <input type="hidden" :name="`service_rows[${index}][kategori_item]`" x-model="row.kategori_item">

                                                    <div class="relative" :class="hasKategoriOptions(row.jenis_item) ? '' : 'md:col-span-2'">
                                                        <select x-model="row.name" :disabled="rowsLocked()" @change="handleNameChange(row); recalculate()" class="js-bast-item-select w-full appearance-none rounded-lg border border-slate-300 bg-white px-3 py-2 pr-8 text-[12px] text-slate-700 focus:border-[#ca642f] focus:outline-none disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500">
                                                            <option value="">Pilih Nama Item</option>
                                                            <template x-for="itemOption in getNameOptions(row.jenis_item, row.kategori_item)" :key="`service-name-${row.jenis_item}-${row.kategori_item || 'none'}-${itemOption.nama_item}`">
                                                                <option :value="itemOption.nama_item" x-text="itemOption.nama_item"></option>
                                                            </template>
                                                        </select>
                                                        <i data-lucide="chevron-down" class="pointer-events-none absolute right-2 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"></i>
                                                    </div>
                                                </div>
                                                <input type="hidden" :name="`service_rows[${index}][name]`" x-model="row.name">
                                                <input type="hidden" :name="`service_rows[${index}][contract_item_id]`" x-model="row.contract_item_id">
                                            </td>
                                            <td class="border border-slate-300 px-2 py-2">
                                                <div class="flex flex-nowrap items-center gap-1.5">
                                                    <input type="number" min="0" step="0.001" x-model="row.volume" :readonly="rowsLocked()" @input.debounce.350ms="recalculate()" class="min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-right text-sm text-slate-700 focus:border-[#ca642f] focus:outline-none read-only:cursor-not-allowed read-only:bg-slate-50 read-only:text-slate-500">
                                                    <input type="hidden" :name="`service_rows[${index}][volume]`" x-model="row.volume">
                                                    <input type="text" x-model="row.unit" readonly class="w-[68px] shrink-0 rounded-lg border border-slate-300 bg-slate-50 px-2 py-2 text-center text-[11px] text-slate-700 focus:outline-none">
                                                    <input type="hidden" :name="`service_rows[${index}][unit]`" x-model="row.unit">
                                                </div>
                                            </td>
                                            <td class="border border-slate-300 px-2 py-2">
                                                <input type="text" x-model="row.unit_price" readonly class="w-full rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 text-right text-sm text-slate-700 focus:outline-none">
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
                                        <td colspan="4" class="border border-slate-300 px-2 py-2 font-black" x-text="isWithoutWarranty ? 'TOTAL DIBAYAR' : (terminType === 'termin_2' ? 'TERMIN 2 (5% x Total Actual Biaya)' : 'TERMIN 1 (95% x Total Actual Biaya)')"></td>
                                        <td class="border border-slate-300 px-2 py-2 text-right font-black" x-text="isWithoutWarranty ? calculation.total_aktual_biaya_display : (terminType === 'termin_2' ? calculation.termin_2_nilai_display : calculation.termin_1_nilai_display)"></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-4 py-3">
                            <div class="text-[13px] font-bold text-slate-900">Gambar Pekerjaan</div>
                            <p class="mt-1 text-[11px] text-slate-500">
                                {{ $terminType === 'termin_2'
                                    ? 'Gambar Termin 1 otomatis ikut tampil. Kalau perlu, kamu bisa tambah upload gambar baru untuk Termin 2.'
                                    : 'Upload bisa lebih dari satu gambar sekaligus.' }}
                            </p>
                        </div>
                        <div class="p-4 space-y-4">
                            <div>
                                <input type="file" name="gambar[]" multiple accept=".jpg,.jpeg,.png,.webp" class="block w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 file:mr-3 file:rounded-lg file:border-0 file:bg-[#ca642f] file:px-3 file:py-2 file:text-sm file:font-semibold file:text-white hover:file:bg-[#b85b2b]">
                                @error('gambar')
                                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                                @error('gambar.*')
                                    <p class="mt-2 text-sm text-rose-600">{{ $message }}</p>
                                @enderror
                            </div>

                            @if ($existingImages->isNotEmpty())
                                <div>
                                    <div class="mb-2 text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500">Gambar Tersimpan</div>
                                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                        @foreach ($existingImages as $image)
                                            <a href="{{ $image['url'] }}" target="_blank" rel="noopener noreferrer" class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-[11px] text-slate-700 transition hover:bg-slate-100">
                                                <div class="font-semibold">{{ $image['name'] }}</div>
                                                @if (! empty($image['source']))
                                                    <div class="mt-1 text-[10px] uppercase tracking-[0.08em] text-slate-500">{{ $image['source'] }}</div>
                                                @endif
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-4">
                        <span x-show="isCalculating" class="text-[10px] font-semibold text-amber-600">Menghitung...</span>
                        <span x-show="calculationError" x-text="calculationError" class="text-[10px] font-semibold text-rose-600"></span>
                        <button type="submit" :disabled="isCalculating || Boolean(calculationError)" class="inline-flex h-9 items-center gap-2 rounded-lg bg-[#ca642f] px-4 text-[11px] font-bold text-white shadow-sm transition hover:bg-[#b85b2b] disabled:cursor-not-allowed disabled:opacity-50">
                            <i data-lucide="save" class="h-3.5 w-3.5"></i>
                            {{ $submitLabel }}
                        </button>
                    </div>
                </form>
            </section>
        </div>

        <script>
            window.pkmLhppCreateForm = function (config) {
                return {
                    approvalThreshold: config.approvalThreshold,
                    orderOptions: config.orderOptions,
                    selectedOrder: config.selectedOrder,
                    calculateUrl: config.calculateUrl,
                    terminType: config.terminType,
                    unitOptions: config.unitOptions,
                    contractCatalog: config.contractCatalog,
                    materialRows: Array.isArray(config.materialRows) ? config.materialRows : [],
                    serviceRows: Array.isArray(config.serviceRows) ? config.serviceRows : [],
                    calculation: config.calculation && typeof config.calculation === 'object' ? config.calculation : {},
                    isWithoutWarranty: Boolean(config.isWithoutWarranty),
                    isTerminTwoLocked: Boolean(config.isTerminTwoLocked),
                    selectedTipePekerjaan: config.selectedTipePekerjaan,
                    isTipePekerjaanLocked: config.isTipePekerjaanLocked,
                    tipePekerjaanOptions: config.tipePekerjaanOptions,
                    approvalFlowMatrix: config.approvalFlowMatrix,
                    approvalFlow: Array.isArray(config.approvalFlow) ? config.approvalFlow : [],
                    approvalSignerPreview: config.approvalSignerPreview && typeof config.approvalSignerPreview === 'object' ? config.approvalSignerPreview : {},
                    draggedApprovalIndex: null,
                    workStartDate: config.workStartDate,
                    workFinishDate: config.workFinishDate,
                    useFixedWorkDates: config.useFixedWorkDates,
                    hppValueMatchesBast: Boolean(config.hppValueMatchesBast),
                    isCalculating: false,
                    calculationError: '',
                    calculationController: null,
                    calculationSequence: 0,
                    currentOrder() {
                        return this.orderOptions.find((item) => item.nomor_order === this.selectedOrder) ?? {
                            nomor_order: '',
                            notifikasi: '',
                            deskripsi_pekerjaan: '',
                            unit_kerja_peminta: '',
                            unit_kerja: '',
                            seksi: '',
                            purchase_order_number: '',
                            nilai_ece: 0,
                            tanggal_mulai_pekerjaan: '',
                            tanggal_selesai_pekerjaan: '',
                            hpp_material_rows: [],
                            hpp_service_rows: [],
                        };
                    },
                    rowsLocked() {
                        return this.isTerminTwoLocked || this.hppValueMatchesBast;
                    },
                    emptyRow() {
                        return { contract_item_id: '', jenis_item: '', kategori_item: '', name: '', volume: '', unit: '', unit_price: '', amount: '0.00', amount_display: '0' };
                    },
                    normalizeHppRows(rows) {
                        if (!Array.isArray(rows) || rows.length === 0) {
                            return [this.emptyRow()];
                        }

                        return rows.map((row) => ({
                            contract_item_id: String(row.contract_item_id ?? ''),
                            jenis_item: this.normalizeCatalogValue(row.jenis_item),
                            kategori_item: this.normalizeCatalogValue(row.kategori_item),
                            name: this.normalizeCatalogValue(row.name),
                            volume: String(row.volume ?? ''),
                            unit: this.normalizeCatalogValue(row.unit),
                            unit_price: String(row.unit_price ?? ''),
                            amount: String(row.amount ?? '0.00'),
                            amount_display: String(row.amount_display ?? '0'),
                        }));
                    },
                    handleHppSyncToggle() {
                        if (!this.hppValueMatchesBast) return;

                        if (!this.selectedOrder) {
                            this.hppValueMatchesBast = false;
                            this.showSyncMessage('Pilih nomor order terlebih dahulu.');
                            return;
                        }

                        this.applyHppRows();
                    },
                    applyHppSyncIfChecked() {
                        if (this.hppValueMatchesBast) {
                            this.applyHppRows();
                        }
                    },
                    applyHppRows() {
                        const order = this.currentOrder();

                        if ((!Array.isArray(order.hpp_material_rows) || order.hpp_material_rows.length === 0)
                            && (!Array.isArray(order.hpp_service_rows) || order.hpp_service_rows.length === 0)) {
                            this.hppValueMatchesBast = false;
                            this.showSyncMessage('Detail material dan jasa HPP tidak tersedia sehingga nilai belum dapat disalin otomatis.');
                            return;
                        }

                        this.materialRows = this.normalizeHppRows(order.hpp_material_rows);
                        this.serviceRows = this.normalizeHppRows(order.hpp_service_rows);
                        this.recalculate();
                    },
                    showSyncMessage(message) {
                        if (window.Swal) window.Swal.fire({ icon: 'warning', title: 'Data belum dapat disalin', text: message });
                        else window.alert(message);
                    },
                    resolvedTipePekerjaan() {
                        return this.selectedTipePekerjaan || '';
                    },
                    resolvedWorkStartDate() {
                        const current = this.currentOrder().tanggal_mulai_pekerjaan || '';

                        return this.useFixedWorkDates ? (this.workStartDate || current) : (current || this.workStartDate || '');
                    },
                    resolvedWorkFinishDate() {
                        const current = this.currentOrder().tanggal_selesai_pekerjaan || '';

                        return this.useFixedWorkDates ? (this.workFinishDate || current) : (current || this.workFinishDate || '');
                    },
                    approvalThresholdLabel() {
                        return this.approvalThreshold === 'over_250' ? '> Rp250 juta' : '≤ Rp250 juta';
                    },
                    defaultApprovalFlow() {
                        return [...(this.approvalFlowMatrix?.[this.approvalThreshold] ?? [])];
                    },
                    approvalSignerName(step, index) {
                        const byIndex = this.approvalSignerPreview?.by_index ?? {};
                        const indexValue = byIndex?.[index] ?? byIndex?.[String(index)];

                        if (indexValue) {
                            return indexValue;
                        }

                        const role = String(step ?? '').trim();
                        const orderKey = String(this.selectedOrder ?? '');
                        const orderMap = this.approvalSignerPreview?.orders?.[orderKey] ?? {};
                        const managerPkmMap = this.approvalSignerPreview?.manager_pkm ?? {};
                        const staticMap = this.approvalSignerPreview?.static ?? {};

                        if (role === 'Manager PKM') {
                            return managerPkmMap?.[this.resolvedTipePekerjaan()] || '-';
                        }

                        return orderMap?.[role]
                            || staticMap?.[role]
                            || '-';
                    },
                    syncApprovalFlow(candidate = []) {
                        const defaultFlow = this.defaultApprovalFlow();
                        const flow = Array.isArray(candidate)
                            ? candidate.map((step) => String(step ?? '').trim()).filter(Boolean)
                            : [];

                        this.approvalFlow = this.hasSameApprovalRoles(flow, defaultFlow) ? flow : defaultFlow;
                        this.refreshApprovalIcons();
                    },
                    resetApprovalFlow() {
                        this.approvalFlow = this.defaultApprovalFlow();
                        this.refreshApprovalIcons();
                    },
                    hasSameApprovalRoles(left, right) {
                        if (!Array.isArray(left) || !Array.isArray(right) || left.length !== right.length) {
                            return false;
                        }

                        const counts = (items) => items.reduce((result, item) => {
                            result[item] = (result[item] ?? 0) + 1;

                            return result;
                        }, {});
                        const leftCounts = counts(left);
                        const rightCounts = counts(right);

                        return Object.keys(leftCounts).length === Object.keys(rightCounts).length
                            && Object.keys(leftCounts).every((key) => leftCounts[key] === rightCounts[key]);
                    },
                    isDefaultApprovalFlow() {
                        const defaultFlow = this.defaultApprovalFlow();

                        return this.approvalFlow.length === defaultFlow.length
                            && this.approvalFlow.every((step, index) => step === defaultFlow[index]);
                    },
                    canMoveApprovalStep(from, to) {
                        if (to < 0 || to >= this.approvalFlow.length || from === to) {
                            return false;
                        }

                        const flow = [...this.approvalFlow];
                        const [step] = flow.splice(from, 1);
                        flow.splice(to, 0, step);

                        return !flow.includes('DIROPS') || flow[flow.length - 1] === 'DIROPS';
                    },
                    moveApprovalStep(from, to) {
                        if (!this.canMoveApprovalStep(from, to)) {
                            return;
                        }

                        const flow = [...this.approvalFlow];
                        const [step] = flow.splice(from, 1);
                        flow.splice(to, 0, step);
                        this.approvalFlow = flow;
                        this.refreshApprovalIcons();
                    },
                    startApprovalDrag(index) {
                        this.draggedApprovalIndex = index;
                    },
                    dropApprovalStep(index) {
                        if (this.draggedApprovalIndex === null) {
                            return;
                        }

                        this.moveApprovalStep(this.draggedApprovalIndex, index);
                        this.draggedApprovalIndex = null;
                    },
                    endApprovalDrag() {
                        this.draggedApprovalIndex = null;
                    },
                    refreshApprovalIcons() {
                        this.$nextTick(() => window.lucide?.createIcons());
                    },
                    formatCurrency(value) {
                        const amount = Number(value || 0);
                        return new Intl.NumberFormat('id-ID').format(amount);
                    },
                    normalizeCatalogValue(value) {
                        return String(value ?? '').trim();
                    },
                    getJenisOptions() {
                        return [...new Set(this.contractCatalog.map((item) => this.normalizeCatalogValue(item.jenis_item)).filter(Boolean))];
                    },
                    getKategoriOptions(jenisItem) {
                        const normalizedJenis = this.normalizeCatalogValue(jenisItem);

                        if (!normalizedJenis) {
                            return [];
                        }

                        const categories = this.contractCatalog
                            .filter((item) => this.normalizeCatalogValue(item.jenis_item) === normalizedJenis)
                            .map((item) => this.normalizeCatalogValue(item.kategori_item))
                            .filter(Boolean);

                        return [...new Set(categories)].map((value) => ({
                            value,
                            label: value,
                        }));
                    },
                    hasKategoriOptions(jenisItem) {
                        return this.getKategoriOptions(jenisItem).length > 0;
                    },
                    getNameOptions(jenisItem, kategoriItem) {
                        const normalizedJenis = this.normalizeCatalogValue(jenisItem);
                        const normalizedKategori = this.normalizeCatalogValue(kategoriItem);

                        if (!normalizedJenis) {
                            return [];
                        }

                        return this.contractCatalog.filter((item) =>
                            this.normalizeCatalogValue(item.jenis_item) === normalizedJenis
                            && this.normalizeCatalogValue(item.kategori_item) === normalizedKategori
                        );
                    },
                    findCatalogItem(jenisItem, kategoriItem, namaItem) {
                        const normalizedJenis = this.normalizeCatalogValue(jenisItem);
                        const normalizedKategori = this.normalizeCatalogValue(kategoriItem);
                        const normalizedNama = this.normalizeCatalogValue(namaItem);

                        return this.contractCatalog.find((item) =>
                            this.normalizeCatalogValue(item.jenis_item) === normalizedJenis
                            && this.normalizeCatalogValue(item.kategori_item) === normalizedKategori
                            && this.normalizeCatalogValue(item.nama_item) === normalizedNama
                        ) ?? null;
                    },
                    handleJenisChange(row) {
                        const kategoriOptions = this.getKategoriOptions(row.jenis_item);
                        const kategoriExists = kategoriOptions.some((item) => item.value === this.normalizeCatalogValue(row.kategori_item));

                        if (!kategoriExists) {
                            row.kategori_item = kategoriOptions[0]?.value ?? '';
                        }

                        row.name = '';
                        row.contract_item_id = '';
                        row.unit = '';
                        row.unit_price = '';
                    },
                    handleKategoriChange(row) {
                        row.name = '';
                        row.contract_item_id = '';
                        row.unit = '';
                        row.unit_price = '';
                    },
                    handleNameChange(row) {
                        const selectedItem = this.findCatalogItem(row.jenis_item, row.kategori_item, row.name);

                        if (!selectedItem) {
                            row.contract_item_id = '';
                            row.unit = '';
                            row.unit_price = '';
                            return;
                        }

                        row.contract_item_id = String(selectedItem.id ?? '');
                        row.unit = this.normalizeCatalogValue(selectedItem.satuan);
                        row.unit_price = this.normalizeCatalogValue(selectedItem.harga_satuan);
                    },
                    async recalculate() {
                        const materialRows = Array.isArray(this.materialRows) ? this.materialRows : [];
                        const serviceRows = Array.isArray(this.serviceRows) ? this.serviceRows : [];
                        this.calculationController?.abort();
                        this.calculationController = new AbortController();
                        const sequence = ++this.calculationSequence;
                        this.isCalculating = true;
                        this.calculationError = '';

                        try {
                            const response = await fetch(this.calculateUrl, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=\"csrf-token\"]')?.getAttribute('content') ?? '',
                                },
                                body: JSON.stringify({
                                    material_rows: materialRows,
                                    service_rows: serviceRows,
                                }),
                                signal: this.calculationController.signal,
                            });

                            if (!response.ok) {
                                const errorData = await response.json().catch(() => ({}));
                                throw new Error(errorData.message || 'Perhitungan tidak dapat diproses.');
                            }

                            const result = await response.json();
                            if (sequence !== this.calculationSequence) return;
                            this.materialRows = Array.isArray(result.material_rows) ? result.material_rows : materialRows;
                            this.serviceRows = Array.isArray(result.service_rows) ? result.service_rows : serviceRows;
                            this.calculation = result.totals && typeof result.totals === 'object' ? result.totals : this.calculation;
                            const nextThreshold = this.resolveThreshold();

                            if (nextThreshold !== this.approvalThreshold) {
                                this.approvalThreshold = nextThreshold;
                                this.resetApprovalFlow();
                            }

                            this.$nextTick(() => {
                                if (window.lucide?.createIcons) {
                                    window.lucide.createIcons();
                                }
                            });
                        } catch (error) {
                            if (error?.name !== 'AbortError') {
                                this.calculationError = error?.message || 'Gagal menghitung total. Periksa kembali item dan volume.';
                            }
                        } finally {
                            if (sequence === this.calculationSequence) this.isCalculating = false;
                        }
                    },
                    addMaterialRow() {
                        this.materialRows.push(this.emptyRow());
                        this.refreshItemSelects();
                    },
                    addServiceRow() {
                        this.serviceRows.push(this.emptyRow());
                        this.refreshItemSelects();
                    },
                    refreshItemSelects() {
                        this.$nextTick(() => {
                            this.$root.querySelectorAll('.js-bast-item-select').forEach((element) => {
                                if (element.disabled || element.tomselect || !window.TomSelect) return;
                                new window.TomSelect(element, {
                                    create: false,
                                    allowEmptyOption: true,
                                    maxItems: 1,
                                    placeholder: 'Cari nama item...',
                                });
                            });
                        });
                    },
                    resolveThreshold() {
                        const thresholdBase = this.terminType === 'termin_2'
                            ? Number(this.calculation.termin_2_nilai || 0)
                            : Number(this.calculation.termin_1_nilai || 0);

                        return thresholdBase > 250000000 ? 'over_250' : 'under_250';
                    },
                };
            };
        </script>
