        @php
            $formTitle = $formTitle ?? 'Buat BAST Termin 1';
            $formAction = $formAction ?? route('pkm.lhpp.store');
            $formMethod = $formMethod ?? 'POST';
            $submitLabel = $submitLabel ?? 'Simpan';
            $terminType = $terminType ?? 'termin_1';
            $terminLabel = $terminLabel ?? ($terminType === 'termin_2' ? 'Termin 2' : 'Termin 1');
            $isTerminTwoLocked = $terminType === 'termin_2';
            $bastDate = old('tanggal_bast', $bastDate ?? now()->format('Y-m-d'));
            $tanggalMulaiPekerjaan = old('tanggal_mulai_pekerjaan', $tanggalMulaiPekerjaan ?? '');
            $tanggalSelesaiPekerjaan = old('tanggal_selesai_pekerjaan', $tanggalSelesaiPekerjaan ?? '');
            $selectedTipePekerjaan = (string) old('tipe_pekerjaan', $selectedTipePekerjaan ?? '');
            $isTipePekerjaanLocked = $terminType === 'termin_2' && $selectedTipePekerjaan !== '';
            $tipePekerjaanOptions = collect($tipePekerjaanOptions ?? [])
                ->map(fn ($label, $value) => ['value' => $value, 'label' => $label])
                ->values();
            $useFixedWorkDates = (bool) ($useFixedWorkDates ?? false);
            $bastOrderOptions = collect($bastOrderOptions ?? []);
            $selectedBastOrder = (string) old('nomor_order', $selectedBastOrder ?? '');
            $selectedThreshold = (string) old('approval_threshold', $selectedThreshold ?? 'under_250');
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
                    contractCatalog: @js($contractCatalog->values()->all()),
                    materialRows: @js($materialRows->values()->all()),
                    serviceRows: @js($serviceRows->values()->all()),
                    calculation: @js($initialCalculation),
                    isTerminTwoLocked: @js($isTerminTwoLocked),
                    isTipePekerjaanLocked: @js($isTipePekerjaanLocked),
                    selectedTipePekerjaan: @js($selectedTipePekerjaan),
                    tipePekerjaanOptions: @js($tipePekerjaanOptions->all()),
                    workStartDate: @js($tanggalMulaiPekerjaan),
                    workFinishDate: @js($tanggalSelesaiPekerjaan),
                    useFixedWorkDates: @js($useFixedWorkDates),
                    hppValueMatchesBast: false,
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

                <form id="pkm-lhpp-create-form" method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="mt-5 space-y-5">
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
                                    <select name="nomor_order" x-model="selectedOrder" x-init="$nextTick(() => { $el.value = selectedOrder; })" @change="applyHppSyncIfChecked()" class="w-full appearance-none rounded-xl border border-slate-300 bg-white px-3 py-2 pr-10 text-sm text-slate-700 focus:border-[#ca642f] focus:outline-none">
                                        <option value="">Pilih Nomor Order</option>
                                        <template x-for="order in orderOptions" :key="order.nomor_order">
                                            <option :value="order.nomor_order" :selected="order.nomor_order === selectedOrder" x-text="order.nomor_order"></option>
                                        </template>
                                    </select>
                                    <i data-lucide="chevron-down" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"></i>
                                </div>

                                <label class="text-[12px] font-semibold uppercase tracking-[0.08em] text-slate-700">Nomor Notifikasi</label>
                                <div aria-hidden="true"></div>
                                <input type="text" x-bind:value="currentOrder().notifikasi" readonly class="rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700 focus:outline-none">

                                <label class="text-[12px] font-semibold uppercase tracking-[0.08em] text-slate-700">Deskripsi Pekerjaan</label>
                                <div aria-hidden="true"></div>
                                <input type="text" x-bind:value="currentOrder().deskripsi_pekerjaan" readonly class="rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700 focus:outline-none">

                                <label class="text-[12px] font-semibold uppercase tracking-[0.08em] text-slate-700">Unit Kerja Peminta (User)</label>
                                <div aria-hidden="true"></div>
                                <input type="text" x-bind:value="currentOrder().unit_kerja_peminta" readonly class="rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700 focus:outline-none">

                                <label class="text-[12px] font-semibold uppercase tracking-[0.08em] text-slate-700">Purchasing Order (P.O)</label>
                                <div aria-hidden="true"></div>
                                <input type="text" x-bind:value="currentOrder().purchase_order_number" readonly class="rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm text-slate-700 focus:outline-none">

                                <label class="text-[12px] font-semibold uppercase tracking-[0.08em] text-slate-700">Tipe Pekerjaan</label>
                                <div aria-hidden="true"></div>
                                <div class="relative">
                                    <input type="hidden" name="tipe_pekerjaan" value="{{ $selectedTipePekerjaan }}" :value="resolvedTipePekerjaan()">
                                    <select x-model="selectedTipePekerjaan" :disabled="isTipePekerjaanLocked" class="w-full appearance-none rounded-xl border border-slate-300 bg-white px-3 py-2 pr-10 text-sm text-slate-700 focus:border-[#ca642f] focus:outline-none disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500">
                                        <option value="">Pilih Tipe Pekerjaan</option>
                                        <template x-for="option in tipePekerjaanOptions" :key="option.value">
                                            <option :value="option.value" x-text="option.label"></option>
                                        </template>
                                    </select>
                                    <i data-lucide="chevron-down" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"></i>
                                </div>

                                <label class="text-[12px] font-semibold uppercase tracking-[0.08em] text-slate-700">Tanggal Dimulainya Pekerjaan</label>
                                <div aria-hidden="true"></div>
                                <input type="date" name="tanggal_mulai_pekerjaan" value="{{ $tanggalMulaiPekerjaan }}" :value="resolvedWorkStartDate()" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-[#ca642f] focus:outline-none">

                                <label class="text-[12px] font-semibold uppercase tracking-[0.08em] text-slate-700">Tanggal Selesainya Pekerjaan</label>
                                <div aria-hidden="true"></div>
                                <input type="date" name="tanggal_selesai_pekerjaan" value="{{ $tanggalSelesaiPekerjaan }}" :value="resolvedWorkFinishDate()" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-[#ca642f] focus:outline-none">
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
                                                        <select x-model="row.name" :disabled="rowsLocked()" @change="handleNameChange(row); recalculate()" class="w-full appearance-none rounded-lg border border-slate-300 bg-white px-3 py-2 pr-8 text-[12px] text-slate-700 focus:border-[#ca642f] focus:outline-none disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500">
                                                            <option value="">Pilih Nama Item</option>
                                                            <template x-for="itemOption in getNameOptions(row.jenis_item, row.kategori_item)" :key="`material-name-${row.jenis_item}-${row.kategori_item || 'none'}-${itemOption.nama_item}`">
                                                                <option :value="itemOption.nama_item" x-text="itemOption.nama_item"></option>
                                                            </template>
                                                        </select>
                                                        <i data-lucide="chevron-down" class="pointer-events-none absolute right-2 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"></i>
                                                    </div>
                                                </div>
                                                <input type="hidden" :name="`material_rows[${index}][name]`" x-model="row.name">
                                            </td>
                                            <td class="border border-slate-300 px-2 py-2">
                                                <div class="flex flex-nowrap items-center gap-1.5">
                                                    <input type="text" x-model="row.volume" :readonly="rowsLocked()" @change="recalculate()" @blur="recalculate()" class="min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-right text-sm text-slate-700 focus:border-[#ca642f] focus:outline-none read-only:cursor-not-allowed read-only:bg-slate-50 read-only:text-slate-500">
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
                                                        <select x-model="row.name" :disabled="rowsLocked()" @change="handleNameChange(row); recalculate()" class="w-full appearance-none rounded-lg border border-slate-300 bg-white px-3 py-2 pr-8 text-[12px] text-slate-700 focus:border-[#ca642f] focus:outline-none disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-500">
                                                            <option value="">Pilih Nama Item</option>
                                                            <template x-for="itemOption in getNameOptions(row.jenis_item, row.kategori_item)" :key="`service-name-${row.jenis_item}-${row.kategori_item || 'none'}-${itemOption.nama_item}`">
                                                                <option :value="itemOption.nama_item" x-text="itemOption.nama_item"></option>
                                                            </template>
                                                        </select>
                                                        <i data-lucide="chevron-down" class="pointer-events-none absolute right-2 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500"></i>
                                                    </div>
                                                </div>
                                                <input type="hidden" :name="`service_rows[${index}][name]`" x-model="row.name">
                                            </td>
                                            <td class="border border-slate-300 px-2 py-2">
                                                <div class="flex flex-nowrap items-center gap-1.5">
                                                    <input type="text" x-model="row.volume" :readonly="rowsLocked()" @change="recalculate()" @blur="recalculate()" class="min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-2.5 py-2 text-right text-sm text-slate-700 focus:border-[#ca642f] focus:outline-none read-only:cursor-not-allowed read-only:bg-slate-50 read-only:text-slate-500">
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
                                        <td colspan="4" class="border border-slate-300 px-2 py-2 font-black" x-text="terminType === 'termin_2' ? 'TERMIN 2 (5% x Total Actual Biaya)' : 'TERMIN 1 (95% x Total Actual Biaya)'"></td>
                                        <td class="border border-slate-300 px-2 py-2 text-right font-black" x-text="terminType === 'termin_2' ? calculation.termin_2_nilai_display : calculation.termin_1_nilai_display"></td>
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
                    contractCatalog: config.contractCatalog,
                    materialRows: config.materialRows,
                    serviceRows: config.serviceRows,
                    calculation: config.calculation,
                    isTerminTwoLocked: config.isTerminTwoLocked,
                    selectedTipePekerjaan: config.selectedTipePekerjaan,
                    isTipePekerjaanLocked: config.isTipePekerjaanLocked,
                    tipePekerjaanOptions: config.tipePekerjaanOptions,
                    workStartDate: config.workStartDate,
                    workFinishDate: config.workFinishDate,
                    useFixedWorkDates: config.useFixedWorkDates,
                    hppValueMatchesBast: config.hppValueMatchesBast,
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
                        return { jenis_item: '', kategori_item: '', name: '', volume: '', unit: '', unit_price: '', amount: '0.00', amount_display: '0' };
                    },
                    normalizeHppRows(rows) {
                        if (!Array.isArray(rows) || rows.length === 0) {
                            return [this.emptyRow()];
                        }

                        return rows.map((row) => ({
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
                        if (this.hppValueMatchesBast) {
                            this.applyHppRows();
                        }
                    },
                    applyHppSyncIfChecked() {
                        if (this.hppValueMatchesBast) {
                            this.applyHppRows();
                        }
                    },
                    applyHppRows() {
                        const order = this.currentOrder();
                        this.materialRows = this.normalizeHppRows(order.hpp_material_rows);
                        this.serviceRows = this.normalizeHppRows(order.hpp_service_rows);
                        this.recalculate();
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
                        return this.approvalThreshold === 'over_250' ? 'Diatas 250 JT' : 'Dibawah 250 JT';
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
                        row.unit = '';
                        row.unit_price = '';
                    },
                    handleKategoriChange(row) {
                        row.name = '';
                        row.unit = '';
                        row.unit_price = '';
                    },
                    handleNameChange(row) {
                        const selectedItem = this.findCatalogItem(row.jenis_item, row.kategori_item, row.name);

                        if (!selectedItem) {
                            row.unit = '';
                            row.unit_price = '';
                            return;
                        }

                        row.unit = this.normalizeCatalogValue(selectedItem.satuan);
                        row.unit_price = this.normalizeCatalogValue(selectedItem.harga_satuan);
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
                        this.materialRows.push(this.emptyRow());
                    },
                    addServiceRow() {
                        this.serviceRows.push(this.emptyRow());
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
