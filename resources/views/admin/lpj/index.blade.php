<x-layouts.admin title="LPJ / PPL">
    @php
        $lpjPplUploadHint = 'Maks. 10 MB • Format: PDF, DOC, DOCX';
    @endphp

    <div class="space-y-4">
        <section class="rounded-[1.25rem] border border-sky-100 px-4 py-3.5 shadow-sm" style="background: linear-gradient(135deg, #f2f9ff 0%, #fbfdff 48%, #ecf6ff 100%);">
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

        <section class="overflow-hidden rounded-[1.25rem] border border-slate-200 bg-white shadow-sm">
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

            <div class="overflow-x-auto">
                <table class="min-w-full table-fixed border-collapse text-[10px] text-slate-700">
                    <colgroup>
                        <col class="w-[12%]">
                        <col class="w-[11%]">
                        <col class="w-[23%]">
                        <col class="w-[15%]">
                        <col class="w-[12%]">
                        <col class="w-[19%]">
                        <col class="w-[8%]">
                    </colgroup>
                    <thead class="border-b border-slate-200 bg-slate-50 text-slate-600 uppercase tracking-wide">
                        <tr>
                            <th class="px-3 py-2.5 text-left font-semibold">Nomor Order</th>
                            <th class="px-3 py-2.5 text-left font-semibold">Tanggal Update</th>
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
                                $updatedAt = $lpj?->updated_at?->format('Y-m-d H:i') ?? '-';
                                $waktuPengerjaan = ($row->tanggal_mulai_pekerjaan && $row->tanggal_selesai_pekerjaan)
                                    ? ($row->tanggal_mulai_pekerjaan->diffInDays($row->tanggal_selesai_pekerjaan) + 1).' Hari'
                                    : '-';
                                $totalBiaya = (float) ($row->total_aktual_biaya ?? 0);
                                $termin1Paid = ($row->termin1_status ?? 'belum') === 'sudah';
                                $termin2Paid = ($row->termin2_status ?? 'belum') === 'sudah';
                                $unitRingkas = $row->seksi ?: ($row->order?->seksi ?? '-');
                                $poLabel = $nomorPo !== '-' ? 'PO-'.$nomorPo : 'PO belum ada';
                                $initialTermin = filled($lpj?->lpj_number_termin2)
                                    || filled($lpj?->ppl_number_termin2)
                                    || filled($lpj?->lpj_document_path_termin2)
                                    || filled($lpj?->ppl_document_path_termin2)
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
                                data-ppl-url-t2="{{ e($lpj?->ppl_document_path_termin2 ? Storage::url($lpj->ppl_document_path_termin2) : '') }}">
                                <td class="px-3 py-3 align-top">
                                    <div class="space-y-1">
                                        <div class="text-[13px] font-bold text-slate-900">{{ $nomorOrder }}</div>
                                        <div class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[9px] font-semibold text-emerald-700 ring-1 ring-emerald-200">
                                            {{ $poLabel }}
                                        </div>
                                    </div>
                                </td>

                                <td class="px-3 py-3 align-top text-[10px] font-medium text-slate-700">
                                    {{ $updatedAt }}
                                </td>

                                <td class="px-3 py-3 align-top">
                                    <form id="lpj-form-{{ $row->id }}" action="{{ route('admin.lpj.update', ['lhppId' => $row->id]) }}" method="POST" enctype="multipart/form-data" class="space-y-1.5">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="search" value="{{ $search }}">
                                        <input type="hidden" name="po" value="{{ $selectedPo }}">
                                        <input type="hidden" name="page" value="{{ $lpjRows->currentPage() }}">

                                        <div class="flex items-center justify-between gap-2">
                                            <label class="text-[9px] font-semibold uppercase tracking-[0.1em] text-slate-500">Termin</label>
                                            <select id="termin-select-{{ $row->id }}" name="selected_termin" class="h-8 rounded-lg border border-slate-300 bg-white px-2 py-1 text-[10px] text-slate-700 focus:border-sky-500 focus:outline-none" onchange="window.adminLpjApplyTermin('{{ $row->id }}', this.value)">
                                                <option value="1">Termin 1</option>
                                                <option value="2">Termin 2</option>
                                            </select>
                                        </div>

                                        <div class="grid grid-cols-2 gap-2">
                                            <div class="space-y-1">
                                                <label class="block text-[9px] font-medium text-slate-500">Nomor LPJ</label>
                                                <input id="lpj-number-{{ $row->id }}" type="text" name="lpj_number" placeholder="Nomor LPJ" class="h-8 w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-[10px] text-slate-700 placeholder:text-slate-400 focus:border-sky-500 focus:outline-none">
                                            </div>
                                            <div class="space-y-1">
                                                <label class="block text-[9px] font-medium text-slate-500">Nomor PPL</label>
                                                <input id="ppl-number-{{ $row->id }}" type="text" name="ppl_number" placeholder="Nomor PPL" class="h-8 w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-[10px] text-slate-700 placeholder:text-slate-400 focus:border-sky-500 focus:outline-none">
                                            </div>
                                        </div>
                                    </form>
                                </td>

                                <td class="px-3 py-3 align-top">
                                    <div class="space-y-1.5">
                                        <div class="rounded-lg border border-slate-200 bg-white px-2.5 py-2 shadow-sm">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="min-w-0">
                                                    <div class="text-[9px] font-semibold uppercase tracking-[0.1em] text-slate-500">LPJ</div>
                                                    <a id="lpj-link-{{ $row->id }}" href="#" target="_blank" rel="noopener" class="mt-0.5 hidden truncate text-[9px] font-semibold text-rose-600 hover:underline">Lihat dokumen</a>
                                                    <div id="lpj-empty-{{ $row->id }}" class="mt-0.5 text-[9px] text-slate-400">Belum ada file</div>
                                                    <div id="lpj-file-name-{{ $row->id }}" class="mt-0.5 truncate text-[9px] text-slate-500"></div>
                                                </div>
                                                <label class="inline-flex h-7 shrink-0 cursor-pointer items-center gap-1 rounded-lg bg-emerald-600 px-2.5 py-1 text-[9px] font-semibold text-white transition hover:bg-emerald-700">
                                                    <i data-lucide="upload" class="h-3 w-3"></i>
                                                    <span id="lpj-upload-text-{{ $row->id }}">Upload T1</span>
                                                    <input type="file" name="lpj_document" form="lpj-form-{{ $row->id }}" accept=".pdf,.doc,.docx" class="hidden" onchange="window.adminLpjSetFileName('{{ $row->id }}', 'lpj', this)">
                                                </label>
                                            </div>
                                            <div class="mt-1 text-[9px] text-slate-500">
                                                {{ $lpjPplUploadHint }}
                                            </div>
                                        </div>

                                        <div class="rounded-lg border border-slate-200 bg-white px-2.5 py-2 shadow-sm">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="min-w-0">
                                                    <div class="text-[9px] font-semibold uppercase tracking-[0.1em] text-slate-500">PPL</div>
                                                    <a id="ppl-link-{{ $row->id }}" href="#" target="_blank" rel="noopener" class="mt-0.5 hidden truncate text-[9px] font-semibold text-rose-600 hover:underline">Lihat dokumen</a>
                                                    <div id="ppl-empty-{{ $row->id }}" class="mt-0.5 text-[9px] text-slate-400">Belum ada file</div>
                                                    <div id="ppl-file-name-{{ $row->id }}" class="mt-0.5 truncate text-[9px] text-slate-500"></div>
                                                </div>
                                                <label class="inline-flex h-7 shrink-0 cursor-pointer items-center gap-1 rounded-lg bg-emerald-600 px-2.5 py-1 text-[9px] font-semibold text-white transition hover:bg-emerald-700">
                                                    <i data-lucide="upload" class="h-3 w-3"></i>
                                                    <span id="ppl-upload-text-{{ $row->id }}">Upload T1</span>
                                                    <input type="file" name="ppl_document" form="lpj-form-{{ $row->id }}" accept=".pdf,.doc,.docx" class="hidden" onchange="window.adminLpjSetFileName('{{ $row->id }}', 'ppl', this)">
                                                </label>
                                            </div>
                                            <div class="mt-1 text-[9px] text-slate-500">
                                                {{ $lpjPplUploadHint }}
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-3 py-3 align-top">
                                    <div class="space-y-1.5 rounded-lg border border-slate-200 bg-white px-2.5 py-2 shadow-sm">
                                        <div class="flex items-center justify-between gap-2 text-[9px]">
                                            <span class="whitespace-nowrap font-medium text-slate-500">Termin 1</span>
                                            <select id="termin1-status-{{ $row->id }}" name="termin1_status" form="lpj-form-{{ $row->id }}" class="h-7 rounded-lg border px-2 py-1 text-[9px] font-semibold {{ $termin1Paid ? 'border-emerald-300 bg-emerald-50 text-emerald-700' : 'border-amber-300 bg-amber-50 text-amber-700' }}" onchange="window.adminLpjApplyPaymentState(this)">
                                                <option value="belum" @selected(! $termin1Paid)>Belum</option>
                                                <option value="sudah" @selected($termin1Paid)>Sudah</option>
                                            </select>
                                        </div>
                                        <div class="flex items-center justify-between gap-2 text-[9px]">
                                            <span class="whitespace-nowrap font-medium text-slate-500">Termin 2</span>
                                            <select id="termin2-status-{{ $row->id }}" name="termin2_status" form="lpj-form-{{ $row->id }}" class="h-7 rounded-lg border px-2 py-1 text-[9px] font-semibold {{ $termin2Paid ? 'border-emerald-300 bg-emerald-50 text-emerald-700' : 'border-amber-300 bg-amber-50 text-amber-700' }}" onchange="window.adminLpjApplyPaymentState(this)">
                                                <option value="belum" @selected(! $termin2Paid)>Belum</option>
                                                <option value="sudah" @selected($termin2Paid)>Sudah</option>
                                            </select>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-3 py-3 align-top">
                                    <div class="space-y-1 rounded-lg border border-slate-200 bg-slate-50 px-2.5 py-2 shadow-sm">
                                        <div>
                                            <div class="text-[10px] font-medium leading-snug text-slate-700">{{ $unitRingkas }}</div>
                                        </div>
                                        <div class="border-t border-slate-200 pt-1.5">
                                            <div class="text-[9px] font-semibold uppercase tracking-[0.1em] text-slate-500">Total BAST</div>
                                            <div class="mt-1 text-[11px] font-bold text-slate-900">Rp {{ number_format($totalBiaya, 0, ',', '.') }}</div>
                                        </div>
                                        <div class="border-t border-slate-200 pt-1.5">
                                            <div class="text-[9px] font-semibold uppercase tracking-[0.1em] text-slate-500">Waktu Pengerjaan</div>
                                            <div class="mt-1 text-[10px] font-medium text-slate-700">{{ $waktuPengerjaan }}</div>
                                        </div>
                                    </div>
                                </td>

                                <td class="px-3 py-3 text-center align-top">
                                    <button type="submit" form="lpj-form-{{ $row->id }}" class="inline-flex h-8 items-center gap-1 rounded-lg bg-sky-600 px-2.5 py-1.5 text-[10px] font-semibold text-white transition hover:bg-sky-700">
                                        <i data-lucide="save" class="h-3 w-3"></i>
                                        Simpan
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
            if (!target) {
                return;
            }

            target.textContent = inputElement.files && inputElement.files[0]
                ? `File: ${inputElement.files[0].name}`
                : '';
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
            const lpjEmpty = document.getElementById(`lpj-empty-${rowId}`);
            const pplEmpty = document.getElementById(`ppl-empty-${rowId}`);
            const lpjUploadText = document.getElementById(`lpj-upload-text-${rowId}`);
            const pplUploadText = document.getElementById(`ppl-upload-text-${rowId}`);
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

            if (lpjUploadText) {
                lpjUploadText.textContent = termin === '2' ? 'Upload T2' : 'Upload T1';
            }

            if (pplUploadText) {
                pplUploadText.textContent = termin === '2' ? 'Upload T2' : 'Upload T1';
            }

            if (lpjFileName) {
                lpjFileName.textContent = '';
            }

            if (pplFileName) {
                pplFileName.textContent = '';
            }

            if (lpjLink && lpjEmpty) {
                if (lpjUrl) {
                    lpjLink.href = lpjUrl;
                    lpjLink.textContent = `Lihat LPJ T${termin}`;
                    lpjLink.classList.remove('hidden');
                    lpjEmpty.classList.add('hidden');
                } else {
                    lpjLink.classList.add('hidden');
                    lpjEmpty.classList.remove('hidden');
                }
            }

            if (pplLink && pplEmpty) {
                if (pplUrl) {
                    pplLink.href = pplUrl;
                    pplLink.textContent = `Lihat PPL T${termin}`;
                    pplLink.classList.remove('hidden');
                    pplEmpty.classList.add('hidden');
                } else {
                    pplLink.classList.add('hidden');
                    pplEmpty.classList.remove('hidden');
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
