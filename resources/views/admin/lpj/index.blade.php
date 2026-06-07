<x-layouts.admin title="LPJ / PPL">
    <div class="order-list-compact lpj-compact space-y-4">
        <section class="order-list-hero rounded-[1.25rem] border border-sky-100 px-4 py-3.5 shadow-sm" style="background: linear-gradient(135deg, #f2f9ff 0%, #fbfdff 48%, #ecf6ff 100%);">
            <div class="flex items-center gap-4">
                <span class="inline-flex h-10 w-10 items-center justify-center rounded-2xl bg-white text-sky-600 shadow-sm ring-1 ring-sky-200">
                    <i data-lucide="folder-open" class="h-4 w-4"></i>
                </span>
                <div>
                    <h1 class="text-[1.15rem] font-bold leading-none tracking-tight text-slate-900">LPJ / PPL</h1>
                    <p class="mt-1 text-[10px] text-slate-500">Kelola nomor dokumen, upload file, dan status pembayaran LPJ / PPL.</p>
                </div>
            </div>
        </section>

        <section class="order-list-panel overflow-hidden rounded-[1.25rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-4 py-3 overflow-x-auto">
                <form method="GET" action="{{ route('admin.lpj.index') }}" class="flex min-w-[760px] items-end gap-2">
                    <div class="w-[280px]">
                        <label class="mb-1 block text-[9px] font-semibold uppercase tracking-[0.12em] text-slate-500">Pencarian</label>
                        <div class="relative">
                            <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-[12px] w-[12px] -translate-y-1/2 text-slate-400"></i>
                            <input type="text" name="search" value="{{ $search }}" placeholder="Cari nomor order" class="w-full rounded-lg border border-slate-300 px-8 py-1.5 text-[10px] text-slate-700 placeholder:text-slate-400 focus:border-sky-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="w-[210px]">
                        <label class="mb-1 block text-[9px] font-semibold uppercase tracking-[0.12em] text-slate-500">PO</label>
                        <select name="po" class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-[10px] text-slate-700 focus:border-sky-500 focus:outline-none">
                            <option value="">-- Semua PO --</option>
                            @foreach ($poOptions as $poNum)
                                <option value="{{ $poNum }}" @selected($selectedPo === $poNum)>{{ $poNum }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="ml-auto flex items-center gap-2">
                        <button type="submit" class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-sky-600 px-3 text-[10px] font-semibold text-white transition hover:bg-sky-700">
                            <i data-lucide="filter" class="h-[12px] w-[12px]"></i>
                            Filter
                        </button>
                        <a href="{{ route('admin.lpj.index') }}" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 text-[10px] font-semibold text-slate-700 transition hover:bg-slate-50">
                            <i data-lucide="rotate-ccw" class="h-[12px] w-[12px]"></i>
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden">
                <table class="w-full table-fixed border-collapse text-[10px] text-slate-700">
                    <colgroup>
                        <col class="w-[14%]">
                        <col class="w-[20%]">
                        <col class="w-[18%]">
                        <col class="w-[12%]">
                        <col class="w-[12%]">
                        <col class="w-[17%]">
                        <col class="w-[7%]">
                    </colgroup>
                    <thead class="border-b border-slate-200 bg-slate-50 text-slate-600 uppercase tracking-wide">
                        <tr>
                            <th class="px-3 py-2.5 text-left font-semibold">Nomor Order</th>
                            <th class="px-3 py-2.5 text-left font-semibold">Detail Pekerjaan</th>
                            <th class="px-3 py-2.5 text-left font-semibold">Nomor LPJ / PPL</th>
                            <th class="px-3 py-2.5 text-left font-semibold">Dokumen (Termin)</th>
                            <th class="px-3 py-2.5 text-left font-semibold">Pembayaran</th>
                            <th class="px-3 py-2.5 text-left font-semibold">Ringkasan</th>
                            <th class="px-3 py-2.5 text-center font-semibold">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($lpjRows as $row)
                            @php
                                $lpj = $row->lpjPpl;
                                $nomorOrder = $row->nomor_order ?: ($row->order?->nomor_order ?? '-');
                                $nomorPo = $row->purchase_order_number ?: ($row->purchaseOrder?->purchase_order_number ?? '-');
                                $notifikasi = $row->order?->notifikasi ?? '-';
                                $namaPekerjaan = $row->deskripsi_pekerjaan ?: ($row->order?->nama_pekerjaan ?? '-');
                                $unitKerja = $row->unit_kerja ?: ($row->order?->unit_kerja ?? '-');
                                $seksi = $row->seksi ?: ($row->order?->seksi ?? '-');
                                $waktuPengerjaan = ($row->tanggal_mulai_pekerjaan && $row->tanggal_selesai_pekerjaan)
                                    ? ($row->tanggal_mulai_pekerjaan->diffInDays($row->tanggal_selesai_pekerjaan) + 1).' Hari'
                                    : '-';
                                $totalBiaya = (float) ($row->total_aktual_biaya ?? 0);
                                $isWithoutWarranty = (int) ($row->garansi?->garansi_months ?? -1) === 0;
                                $termin1Paid = ($row->termin1_status ?? 'belum') === 'sudah';
                                $termin2Paid = ! $isWithoutWarranty && ($row->termin2_status ?? 'belum') === 'sudah';
                                $initialTermin = ! $isWithoutWarranty && (filled($lpj?->lpj_number_termin2)
                                    || filled($lpj?->ppl_number_termin2)
                                    || filled($lpj?->lpj_document_path_termin2)
                                    || filled($lpj?->ppl_document_path_termin2))
                                        ? '2'
                                        : '1';
                            @endphp

                            <tr id="lpj-row-{{ $row->id }}"
                                class="odd:bg-white even:bg-slate-50 transition hover:bg-sky-50/40"
                                data-initial-termin="{{ $initialTermin }}"
                                data-lpj-number-t1="{{ e($lpj?->lpj_number_termin1 ?? '') }}"
                                data-ppl-number-t1="{{ e($lpj?->ppl_number_termin1 ?? '') }}"
                                data-lpj-number-t2="{{ e($lpj?->lpj_number_termin2 ?? '') }}"
                                data-ppl-number-t2="{{ e($lpj?->ppl_number_termin2 ?? '') }}"
                                data-lpj-url-t1="{{ e($lpj?->lpj_document_path_termin1 ? Storage::url($lpj->lpj_document_path_termin1) : '') }}"
                                data-ppl-url-t1="{{ e($lpj?->ppl_document_path_termin1 ? Storage::url($lpj->ppl_document_path_termin1) : '') }}"
                                data-lpj-url-t2="{{ e($lpj?->lpj_document_path_termin2 ? Storage::url($lpj->lpj_document_path_termin2) : '') }}"
                                data-ppl-url-t2="{{ e($lpj?->ppl_document_path_termin2 ? Storage::url($lpj->ppl_document_path_termin2) : '') }}"
                                data-without-warranty="{{ $isWithoutWarranty ? '1' : '0' }}">
                                <td class="px-3 py-3 align-top">
                                    <div class="space-y-1">
                                        <div class="break-words text-[12px] font-black leading-tight text-slate-900">{{ $nomorOrder }}</div>
                                        <div class="break-words text-[9px] font-medium leading-tight text-blue-600">
                                            <span class="font-semibold">Notif :</span> {{ $notifikasi }}
                                        </div>
                                        <div class="break-words text-[9px] font-medium leading-tight text-blue-600">
                                            <span class="font-semibold">PO :</span> {{ $nomorPo }}
                                        </div>
                                    </div>
                                </td>

                                <td class="px-3 py-3 align-top">
                                    <div class="space-y-1 text-[9px] leading-snug text-slate-600">
                                        <div class="text-[11px] font-bold text-slate-900">{{ $namaPekerjaan }}</div>
                                        <div>Unit: {{ $unitKerja }}</div>
                                        <div class="text-blue-600">Seksi: {{ $seksi }}</div>
                                    </div>
                                </td>

                                <td class="px-3 py-3 align-top">
                                    <form id="lpj-form-{{ $row->id }}" action="{{ route('admin.lpj.update', ['lhppId' => $row->id]) }}" method="POST" enctype="multipart/form-data">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="search" value="{{ $search }}">
                                        <input type="hidden" name="po" value="{{ $selectedPo }}">
                                        <input type="hidden" name="page" value="{{ $lpjRows->currentPage() }}">
                                        <input id="remove-lpj-document-{{ $row->id }}" type="hidden" name="remove_lpj_document" value="0">
                                        <input id="remove-ppl-document-{{ $row->id }}" type="hidden" name="remove_ppl_document" value="0">

                                        <div class="space-y-1.5">
                                            <select id="termin-select-{{ $row->id }}" name="selected_termin" title="Pilih termin" aria-label="Pilih termin" class="h-8 w-full rounded-lg border border-slate-300 bg-white px-2 py-1 text-[9px] text-slate-700 focus:border-sky-500 focus:outline-none" onchange="window.adminLpjApplyTermin('{{ $row->id }}', this.value)">
                                                <option value="1">Termin 1</option>
                                                @unless ($isWithoutWarranty)
                                                    <option value="2">Termin 2</option>
                                                @endunless
                                            </select>
                                            <div class="grid grid-cols-2 gap-1.5">
                                                <input id="lpj-number-{{ $row->id }}" type="text" name="lpj_number" aria-label="Nomor LPJ" placeholder="Nomor LPJ" class="h-8 min-w-0 w-full rounded-lg border border-slate-300 px-2 py-1 text-[9px] text-slate-700 placeholder:text-slate-400 focus:border-sky-500 focus:outline-none">
                                                <input id="ppl-number-{{ $row->id }}" type="text" name="ppl_number" aria-label="Nomor PPL" placeholder="Nomor PPL" class="h-8 min-w-0 w-full rounded-lg border border-slate-300 px-2 py-1 text-[9px] text-slate-700 placeholder:text-slate-400 focus:border-sky-500 focus:outline-none">
                                            </div>
                                        </div>
                                    </form>
                                </td>

                                <td class="px-3 py-3 align-top">
                                    <div class="flex items-start gap-1.5">
                                        <div class="min-w-0 flex-1 text-center">
                                            <div class="mb-1 text-[8px] font-bold text-slate-500">LPJ</div>
                                            <div id="lpj-document-{{ $row->id }}" class="relative mx-auto hidden w-fit">
                                                <button type="button" title="Hapus PDF LPJ" aria-label="Hapus PDF LPJ" class="absolute -right-2 -top-2 z-10 inline-flex h-4 w-4 items-center justify-center rounded-full bg-rose-600 text-white shadow-sm hover:bg-rose-700" onclick="window.adminLpjRemoveDocument('{{ $row->id }}', 'lpj')">
                                                    <i data-lucide="x" class="h-2.5 w-2.5"></i>
                                                </button>
                                                <a id="lpj-link-{{ $row->id }}" href="#" target="_blank" rel="noopener" title="Lihat PDF LPJ" aria-label="Lihat PDF LPJ" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-rose-200 bg-rose-50 text-rose-600 hover:bg-rose-100">
                                                    <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                                                </a>
                                            </div>
                                            <label id="lpj-upload-{{ $row->id }}" title="Upload PDF LPJ" aria-label="Upload PDF LPJ" class="mx-auto inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-md bg-emerald-600 text-white transition hover:bg-emerald-700">
                                                    <i data-lucide="upload" class="h-3.5 w-3.5"></i>
                                                <input id="lpj-input-{{ $row->id }}" type="file" name="lpj_document" form="lpj-form-{{ $row->id }}" accept=".pdf,application/pdf" class="hidden" onchange="window.adminLpjSetFileName('{{ $row->id }}', 'lpj', this)">
                                            </label>
                                            <div id="lpj-file-name-{{ $row->id }}" class="mt-1 truncate text-[8px] text-slate-500"></div>
                                        </div>

                                        <div class="min-w-0 flex-1 text-center">
                                            <div class="mb-1 text-[8px] font-bold text-slate-500">PPL</div>
                                            <div id="ppl-document-{{ $row->id }}" class="relative mx-auto hidden w-fit">
                                                <button type="button" title="Hapus PDF PPL" aria-label="Hapus PDF PPL" class="absolute -right-2 -top-2 z-10 inline-flex h-4 w-4 items-center justify-center rounded-full bg-rose-600 text-white shadow-sm hover:bg-rose-700" onclick="window.adminLpjRemoveDocument('{{ $row->id }}', 'ppl')">
                                                    <i data-lucide="x" class="h-2.5 w-2.5"></i>
                                                </button>
                                                <a id="ppl-link-{{ $row->id }}" href="#" target="_blank" rel="noopener" title="Lihat PDF PPL" aria-label="Lihat PDF PPL" class="inline-flex h-8 w-8 items-center justify-center rounded-md border border-rose-200 bg-rose-50 text-rose-600 hover:bg-rose-100">
                                                    <i data-lucide="file-text" class="h-3.5 w-3.5"></i>
                                                </a>
                                            </div>
                                            <label id="ppl-upload-{{ $row->id }}" title="Upload PDF PPL" aria-label="Upload PDF PPL" class="mx-auto inline-flex h-8 w-8 cursor-pointer items-center justify-center rounded-md bg-emerald-600 text-white transition hover:bg-emerald-700">
                                                <i data-lucide="upload" class="h-3.5 w-3.5"></i>
                                                <input id="ppl-input-{{ $row->id }}" type="file" name="ppl_document" form="lpj-form-{{ $row->id }}" accept=".pdf,application/pdf" class="hidden" onchange="window.adminLpjSetFileName('{{ $row->id }}', 'ppl', this)">
                                            </label>
                                            <div id="ppl-file-name-{{ $row->id }}" class="mt-1 truncate text-[8px] text-slate-500"></div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-3 py-3 align-top">
                                    <div class="space-y-1">
                                        <div class="grid grid-cols-[22px_1fr] items-center gap-1 text-[9px]">
                                            <span class="font-bold text-slate-500">T1</span>
                                            <select id="termin1-status-{{ $row->id }}" name="termin1_status" form="lpj-form-{{ $row->id }}" aria-label="Pembayaran termin 1" class="h-7 w-full rounded-md border px-1.5 py-1 text-[9px] font-semibold {{ $termin1Paid ? 'border-emerald-300 bg-emerald-50 text-emerald-700' : 'border-amber-300 bg-amber-50 text-amber-700' }}" onchange="window.adminLpjApplyPaymentState(this)">
                                                <option value="belum" @selected(! $termin1Paid)>Belum</option>
                                                <option value="sudah" @selected($termin1Paid)>Sudah</option>
                                            </select>
                                        </div>
                                        @unless ($isWithoutWarranty)
                                        <div class="grid grid-cols-[22px_1fr] items-center gap-1 text-[9px]">
                                            <span class="font-bold text-slate-500">T2</span>
                                            <select id="termin2-status-{{ $row->id }}" name="termin2_status" form="lpj-form-{{ $row->id }}" aria-label="Pembayaran termin 2" class="h-7 w-full rounded-md border px-1.5 py-1 text-[9px] font-semibold {{ $termin2Paid ? 'border-emerald-300 bg-emerald-50 text-emerald-700' : 'border-amber-300 bg-amber-50 text-amber-700' }}" onchange="window.adminLpjApplyPaymentState(this)">
                                                <option value="belum" @selected(! $termin2Paid)>Belum</option>
                                                <option value="sudah" @selected($termin2Paid)>Sudah</option>
                                            </select>
                                        </div>
                                        @else
                                            <input type="hidden" name="termin2_status" form="lpj-form-{{ $row->id }}" value="belum">
                                            <div class="rounded-md bg-slate-100 px-2 py-1 text-center text-[8px] font-semibold text-slate-500">Tanpa T2</div>
                                        @endunless
                                    </div>
                                </td>

                                <td class="px-3 py-3 align-top">
                                    <div class="space-y-1.5">
                                        <div class="text-[11px] font-black text-slate-900">Rp {{ number_format($totalBiaya, 0, ',', '.') }}</div>
                                        <div class="inline-flex rounded-full bg-sky-50 px-2 py-0.5 text-[9px] font-semibold text-sky-700 ring-1 ring-sky-200">
                                            {{ $waktuPengerjaan }}
                                        </div>
                                    </div>
                                </td>

                                <td class="px-3 py-3 text-center align-top">
                                    <button type="submit" form="lpj-form-{{ $row->id }}" title="Simpan LPJ / PPL" aria-label="Simpan LPJ / PPL" class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-sky-600 text-white transition hover:bg-sky-700">
                                        <i data-lucide="save" class="h-3.5 w-3.5"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-[11px] text-slate-500">Tidak ada data LPJ / PPL.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($lpjRows->hasPages())
                <div class="mt-4 border-t border-slate-200 px-4 py-4">
                    {{ $lpjRows->links() }}
                </div>
            @endif
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.adminLpjApplyPaymentState = function (selectElement) {
            selectElement.classList.remove(
                'border-emerald-300',
                'bg-emerald-50',
                'text-emerald-700',
                'border-amber-300',
                'bg-amber-50',
                'text-amber-700'
            );

            if (selectElement.value === 'sudah') {
                selectElement.classList.add('border-emerald-300', 'bg-emerald-50', 'text-emerald-700');
                return;
            }

            selectElement.classList.add('border-amber-300', 'bg-amber-50', 'text-amber-700');
        };

        window.adminLpjSetFileName = function (rowId, type, inputElement) {
            const target = document.getElementById(`${type}-file-name-${rowId}`);
            const documentBox = document.getElementById(`${type}-document-${rowId}`);
            const uploadButton = document.getElementById(`${type}-upload-${rowId}`);
            const link = document.getElementById(`${type}-link-${rowId}`);

            if (!target || !documentBox || !uploadButton || !link) {
                return;
            }

            const selectedFile = inputElement.files && inputElement.files[0];
            target.textContent = selectedFile ? selectedFile.name : '';

            if (selectedFile) {
                link.removeAttribute('href');
                link.classList.add('pointer-events-none');
                documentBox.classList.remove('hidden');
                uploadButton.classList.add('hidden');
            }
        };

        window.adminLpjRemoveDocument = function (rowId, type) {
            const row = document.getElementById(`lpj-row-${rowId}`);
            const terminSelect = document.getElementById(`termin-select-${rowId}`);
            const removeInput = document.getElementById(`remove-${type}-document-${rowId}`);
            const fileInput = document.getElementById(`${type}-input-${rowId}`);
            const fileName = document.getElementById(`${type}-file-name-${rowId}`);
            const documentBox = document.getElementById(`${type}-document-${rowId}`);
            const uploadButton = document.getElementById(`${type}-upload-${rowId}`);

            if (!row || !terminSelect || !removeInput || !fileInput || !fileName || !documentBox || !uploadButton) {
                return;
            }

            const suffix = terminSelect.value === '2' ? 'T2' : 'T1';
            const existingUrl = row.dataset[`${type}Url${suffix}`] || '';

            removeInput.value = existingUrl ? '1' : '0';
            fileInput.value = '';
            fileName.textContent = '';
            documentBox.classList.add('hidden');
            uploadButton.classList.remove('hidden');
        };

        window.adminLpjApplyTermin = function (rowId, termin) {
            const row = document.getElementById(`lpj-row-${rowId}`);
            if (!row) {
                return;
            }

            const lpjNumber = document.getElementById(`lpj-number-${rowId}`);
            const pplNumber = document.getElementById(`ppl-number-${rowId}`);
            const lpjLink = document.getElementById(`lpj-link-${rowId}`);
            const pplLink = document.getElementById(`ppl-link-${rowId}`);
            const lpjDocument = document.getElementById(`lpj-document-${rowId}`);
            const pplDocument = document.getElementById(`ppl-document-${rowId}`);
            const lpjUpload = document.getElementById(`lpj-upload-${rowId}`);
            const pplUpload = document.getElementById(`ppl-upload-${rowId}`);
            const lpjInput = document.getElementById(`lpj-input-${rowId}`);
            const pplInput = document.getElementById(`ppl-input-${rowId}`);
            const removeLpjInput = document.getElementById(`remove-lpj-document-${rowId}`);
            const removePplInput = document.getElementById(`remove-ppl-document-${rowId}`);
            const lpjFileName = document.getElementById(`lpj-file-name-${rowId}`);
            const pplFileName = document.getElementById(`ppl-file-name-${rowId}`);

            const suffix = termin === '2' ? 't2' : 't1';
            const lpjNumberValue = row.dataset[`lpjNumber${suffix.charAt(0).toUpperCase()}${suffix.slice(1)}`] || '';
            const pplNumberValue = row.dataset[`pplNumber${suffix.charAt(0).toUpperCase()}${suffix.slice(1)}`] || '';
            const lpjUrl = row.dataset[`lpjUrl${suffix.charAt(0).toUpperCase()}${suffix.slice(1)}`] || '';
            const pplUrl = row.dataset[`pplUrl${suffix.charAt(0).toUpperCase()}${suffix.slice(1)}`] || '';

            if (lpjNumber) {
                lpjNumber.value = lpjNumberValue;
            }

            if (pplNumber) {
                pplNumber.value = pplNumberValue;
            }

            if (lpjFileName) {
                lpjFileName.textContent = '';
            }

            if (pplFileName) {
                pplFileName.textContent = '';
            }

            if (lpjInput) {
                lpjInput.value = '';
            }

            if (pplInput) {
                pplInput.value = '';
            }

            if (removeLpjInput) {
                removeLpjInput.value = '0';
            }

            if (removePplInput) {
                removePplInput.value = '0';
            }

            if (lpjLink && lpjDocument && lpjUpload) {
                if (lpjUrl) {
                    lpjLink.href = lpjUrl;
                    lpjLink.title = `Lihat PDF LPJ T${termin}`;
                    lpjLink.setAttribute('aria-label', `Lihat PDF LPJ T${termin}`);
                    lpjLink.classList.remove('pointer-events-none');
                    lpjDocument.classList.remove('hidden');
                    lpjUpload.classList.add('hidden');
                } else {
                    lpjLink.removeAttribute('href');
                    lpjDocument.classList.add('hidden');
                    lpjUpload.classList.remove('hidden');
                }
            }

            if (pplLink && pplDocument && pplUpload) {
                if (pplUrl) {
                    pplLink.href = pplUrl;
                    pplLink.title = `Lihat PDF PPL T${termin}`;
                    pplLink.setAttribute('aria-label', `Lihat PDF PPL T${termin}`);
                    pplLink.classList.remove('pointer-events-none');
                    pplDocument.classList.remove('hidden');
                    pplUpload.classList.add('hidden');
                } else {
                    pplLink.removeAttribute('href');
                    pplDocument.classList.add('hidden');
                    pplUpload.classList.remove('hidden');
                }
            }
        };

        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('tr[id^="lpj-row-"]').forEach((row) => {
                const rowId = row.id.replace('lpj-row-', '');
                const select = document.getElementById(`termin-select-${rowId}`);
                const initialTermin = row.dataset.initialTermin || '1';

                if (select) {
                    select.value = initialTermin;
                }

                window.adminLpjApplyTermin(rowId, initialTermin);
            });

            document.querySelectorAll('select[id^="termin1-status-"], select[id^="termin2-status-"]').forEach((selectElement) => {
                window.adminLpjApplyPaymentState(selectElement);
            });
        });
    </script>

    @if (session('status'))
        <script>
            window.Swal?.fire({
                icon: 'success',
                title: 'Berhasil',
                text: @json(session('status')),
                confirmButtonText: 'OK',
            });
        </script>
    @endif

    @if ($errors->any())
        <script>
            window.Swal?.fire({
                icon: 'error',
                title: 'Gagal',
                text: @json($errors->first()),
                confirmButtonText: 'OK',
            });
        </script>
    @endif
</x-layouts.admin>
