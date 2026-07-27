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
    $initialTermin = ! $isWithoutWarranty && (
        filled($lpj?->lpj_number_termin2)
        || filled($lpj?->ppl_number_termin2)
        || filled($lpj?->lpj_document_path_termin2)
        || filled($lpj?->ppl_document_path_termin2)
    ) ? '2' : '1';
    $documentName = fn (?string $path): string => $path ? basename(str_replace('\\', '/', $path)) : '';
    $mobileConfig = [
        'initialTermin' => $initialTermin,
        'withoutWarranty' => $isWithoutWarranty,
        'numbers' => [
            '1' => [
                'lpj' => $lpj?->lpj_number_termin1 ?? '',
                'ppl' => $lpj?->ppl_number_termin1 ?? '',
            ],
            '2' => [
                'lpj' => $lpj?->lpj_number_termin2 ?? '',
                'ppl' => $lpj?->ppl_number_termin2 ?? '',
            ],
        ],
        'documents' => [
            '1' => [
                'lpj' => [
                    'url' => $lpj?->lpj_document_path_termin1 ? Storage::url($lpj->lpj_document_path_termin1) : '',
                    'name' => $documentName($lpj?->lpj_document_path_termin1),
                ],
                'ppl' => [
                    'url' => $lpj?->ppl_document_path_termin1 ? Storage::url($lpj->ppl_document_path_termin1) : '',
                    'name' => $documentName($lpj?->ppl_document_path_termin1),
                ],
            ],
            '2' => [
                'lpj' => [
                    'url' => $lpj?->lpj_document_path_termin2 ? Storage::url($lpj->lpj_document_path_termin2) : '',
                    'name' => $documentName($lpj?->lpj_document_path_termin2),
                ],
                'ppl' => [
                    'url' => $lpj?->ppl_document_path_termin2 ? Storage::url($lpj->ppl_document_path_termin2) : '',
                    'name' => $documentName($lpj?->ppl_document_path_termin2),
                ],
            ],
        ],
    ];
@endphp

<article
    data-mobile-lpj-card="{{ $row->id }}"
    x-data="adminLpjMobileCard(@js($mobileConfig))"
    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
>
    <form action="{{ route('admin.lpj.update', ['lhppId' => $row->id]) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PATCH')
        <input type="hidden" name="search" value="{{ $search }}">
        <input type="hidden" name="po" value="{{ $selectedPo }}">
        <input type="hidden" name="page" value="{{ $currentPage }}">
        <input type="hidden" name="remove_lpj_document" x-model="removeLpj">
        <input type="hidden" name="remove_ppl_document" x-model="removePpl">

        <div class="border-b border-slate-100 bg-gradient-to-r from-sky-50 to-white p-4">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <div class="break-words text-base font-black leading-tight text-slate-900">{{ $nomorOrder }}</div>
                    <div class="mt-1 break-words text-xs font-semibold text-blue-600">Notif: {{ $notifikasi }}</div>
                </div>
                <span class="shrink-0 rounded-full bg-white px-2.5 py-1 text-[10px] font-bold text-sky-700 ring-1 ring-sky-200">
                    {{ $waktuPengerjaan }}
                </span>
            </div>
            <div class="mt-3 text-sm font-bold leading-snug text-slate-800">{{ $namaPekerjaan }}</div>
            <div class="mt-2 grid grid-cols-1 gap-1 text-xs text-slate-500 sm:grid-cols-2">
                <div><span class="font-semibold text-slate-600">PO:</span> {{ $nomorPo }}</div>
                <div><span class="font-semibold text-slate-600">Unit:</span> {{ $unitKerja }}</div>
                <div class="sm:col-span-2"><span class="font-semibold text-slate-600">Seksi:</span> {{ $seksi }}</div>
            </div>
        </div>

        <div class="space-y-4 p-4">
            <section>
                <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Termin Dokumen</label>
                <select name="selected_termin" x-model="termin" @change="applyTermin()" class="h-10 w-full rounded-xl border border-slate-300 bg-white px-3 text-sm font-semibold text-slate-700 focus:border-sky-500 focus:outline-none">
                    <option value="1">Termin 1</option>
                    @unless ($isWithoutWarranty)
                        <option value="2">Termin 2</option>
                    @endunless
                </select>
            </section>

            <section class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Nomor LPJ</label>
                    <input type="text" name="lpj_number" x-model="lpjNumber" placeholder="Masukkan nomor LPJ" class="h-10 w-full rounded-xl border border-slate-300 px-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-sky-500 focus:outline-none">
                </div>
                <div>
                    <label class="mb-1.5 block text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Nomor PPL</label>
                    <input type="text" name="ppl_number" x-model="pplNumber" placeholder="Masukkan nomor PPL" class="h-10 w-full rounded-xl border border-slate-300 px-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-sky-500 focus:outline-none">
                </div>
            </section>

            <section class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach (['lpj' => 'LPJ', 'ppl' => 'PPL'] as $type => $label)
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                        <div class="mb-2 text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Dokumen {{ $label }}</div>
                        <div x-show="hasDocument('{{ $type }}')" x-cloak class="flex min-w-0 items-center gap-2 rounded-lg border bg-white p-2">
                            <a
                                x-show="!selectedFileName('{{ $type }}') && currentDocument('{{ $type }}').url"
                                :href="currentDocument('{{ $type }}').url"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-sky-700"
                                aria-label="Lihat PDF {{ $label }}"
                            >
                                <i data-lucide="file-text" class="h-4 w-4"></i>
                            </a>
                            <span class="min-w-0 flex-1 truncate text-xs font-semibold text-slate-700" x-text="documentName('{{ $type }}')"></span>
                            <button type="button" @click="removeDocument('{{ $type }}')" class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-rose-50 text-rose-600" aria-label="Hapus PDF {{ $label }}">
                                <i data-lucide="trash-2" class="h-4 w-4"></i>
                            </button>
                        </div>
                        <label x-show="!hasDocument('{{ $type }}')" x-cloak class="flex h-10 cursor-pointer items-center justify-center gap-2 rounded-xl bg-emerald-600 px-3 text-xs font-bold text-white transition hover:bg-emerald-700">
                            <i data-lucide="upload" class="h-4 w-4"></i>
                            Upload {{ $label }}
                            <input x-ref="{{ $type }}Input" type="file" name="{{ $type }}_document" accept=".pdf,application/pdf" class="hidden" @change="selectFile('{{ $type }}', $event)">
                        </label>
                    </div>
                @endforeach
            </section>

            <section>
                <div class="mb-2 text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500">Status Pembayaran</div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-3">
                        <span class="w-20 text-xs font-bold text-slate-600">Termin 1</span>
                        <select name="termin1_status" class="h-9 min-w-0 flex-1 rounded-lg border px-2 text-xs font-semibold {{ $termin1Paid ? 'border-emerald-300 bg-emerald-50 text-emerald-700' : 'border-amber-300 bg-amber-50 text-amber-700' }}" onchange="window.adminLpjApplyPaymentState(this)">
                            <option value="belum" @selected(! $termin1Paid)>Belum</option>
                            <option value="sudah" @selected($termin1Paid)>Sudah</option>
                        </select>
                    </label>
                    @unless ($isWithoutWarranty)
                        <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-3">
                            <span class="w-20 text-xs font-bold text-slate-600">Termin 2</span>
                            <select name="termin2_status" class="h-9 min-w-0 flex-1 rounded-lg border px-2 text-xs font-semibold {{ $termin2Paid ? 'border-emerald-300 bg-emerald-50 text-emerald-700' : 'border-amber-300 bg-amber-50 text-amber-700' }}" onchange="window.adminLpjApplyPaymentState(this)">
                                <option value="belum" @selected(! $termin2Paid)>Belum</option>
                                <option value="sudah" @selected($termin2Paid)>Sudah</option>
                            </select>
                        </label>
                    @else
                        <input type="hidden" name="termin2_status" value="belum">
                        <div class="flex items-center justify-center rounded-xl bg-slate-100 p-3 text-xs font-semibold text-slate-500">Tanpa Termin 2</div>
                    @endunless
                </div>
            </section>
        </div>

        <div class="flex items-center justify-between gap-3 border-t border-slate-100 bg-slate-50 px-4 py-3">
            <div>
                <div class="text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400">Total Aktual</div>
                <div class="mt-0.5 text-sm font-black text-slate-900">Rp {{ number_format($totalBiaya, 0, ',', '.') }}</div>
            </div>
            <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-sky-600 px-4 text-xs font-bold text-white transition hover:bg-sky-700">
                <i data-lucide="save" class="h-4 w-4"></i>
                Simpan
            </button>
        </div>
    </form>
</article>
