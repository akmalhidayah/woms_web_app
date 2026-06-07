<x-layouts.admin title="BAST">
    @if (session('status'))
        <div id="admin-bast-status-alert" data-message="{{ session('status') }}" class="hidden"></div>
    @endif

    <div class="order-list-compact space-y-4">
        <section class="order-list-hero rounded-[1.35rem] border border-blue-100 px-5 py-4 shadow-sm" style="background: linear-gradient(135deg, #eef4ff 0%, #f8fbff 48%, #e6f1ff 100%);">
            <div class="flex items-center gap-4">
                <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-200">
                    <i data-lucide="file-text" class="h-[18px] w-[18px]"></i>
                </span>
                <div>
                    <h1 class="text-[1.3rem] font-bold leading-none tracking-tight text-slate-900">BAST</h1>
                    <p class="mt-1.5 text-[11px] text-slate-500">Cari dokumen berdasarkan nomor order, PO, unit kerja, dan progres dokumen BAST.</p>
                </div>
            </div>
        </section>

        <section class="order-list-panel overflow-hidden rounded-[1.35rem] border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-5 py-4 overflow-x-auto">
                <form method="GET" action="{{ route('admin.lhpp.index') }}" class="flex min-w-[640px] items-center gap-2">
                    <div class="relative min-w-0 flex-1">
                        <i data-lucide="search" class="pointer-events-none absolute left-3 top-1/2 h-[12px] w-[12px] -translate-y-1/2 text-slate-400"></i>
                        <input type="text" name="search" value="{{ $search }}" placeholder="Cari dokumen" class="w-full rounded-lg border border-slate-300 px-8 py-1.5 text-[10px] text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none">
                    </div>

                    <div class="ml-auto flex items-center gap-2">
                        <button type="submit" class="inline-flex h-8 items-center gap-1.5 rounded-lg bg-blue-600 px-3 text-[10px] font-semibold text-white transition hover:bg-blue-700">
                            <i data-lucide="filter" class="h-[12px] w-[12px]"></i>
                            Terapkan
                        </button>
                        <a href="{{ route('admin.lhpp.index') }}" class="inline-flex h-8 items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 text-[10px] font-semibold text-slate-700 transition hover:bg-slate-50">
                            <i data-lucide="rotate-ccw" class="h-[12px] w-[12px]"></i>
                            Reset
                        </a>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full table-fixed bg-white text-[11px] text-slate-700">
                    <colgroup>
                        <col class="w-[17%]">
                        <col class="w-[23%]">
                        <col class="w-[9%]">
                        <col class="w-[13%]">
                        <col class="w-[28%]">
                        <col class="w-[10%]">
                    </colgroup>
                    <thead class="bg-slate-100 text-slate-700 uppercase tracking-wide">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold">Order</th>
                            <th class="px-4 py-2 text-left font-semibold">Detail Pekerjaan</th>
                            <th class="px-4 py-2 text-left font-semibold">Waktu</th>
                            <th class="px-4 py-2 text-left font-semibold">Biaya / Garansi</th>
                            <th class="px-4 py-2 text-left font-semibold">Quality Control / Approval</th>
                            <th class="px-4 py-2 text-center font-semibold">PDF BAST</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($lhpps as $lhpp)
                            @php
                                $nomorOrder = $lhpp->nomor_order ?: ($lhpp->order?->nomor_order ?? '-');
                                $nomorPo = $lhpp->purchase_order_number ?: ($lhpp->purchaseOrder?->purchase_order_number ?? '-');
                                $notifikasi = $lhpp->notifikasi ?: ($lhpp->order?->notifikasi ?? '-');
                                $namaPekerjaan = $lhpp->deskripsi_pekerjaan ?: ($lhpp->order?->nama_pekerjaan ?? '-');
                                $terminTwo = $lhpp->terminTwo;
                                $pdfRefreshToken = now()->timestamp;
                                $seksi = $lhpp->seksi ?: ($lhpp->order?->seksi ?? '-');
                                $unitKerja = $lhpp->unit_kerja ?: ($lhpp->order?->unit_kerja ?? '-');
                                $tanggalSelesai = $lhpp->tanggal_selesai_pekerjaan
                                    ? $lhpp->tanggal_selesai_pekerjaan->format('d-m-Y')
                                    : '-';
                                $waktuPengerjaan = ($lhpp->tanggal_mulai_pekerjaan && $lhpp->tanggal_selesai_pekerjaan)
                                    ? ($lhpp->tanggal_mulai_pekerjaan->diffInDays($lhpp->tanggal_selesai_pekerjaan) + 1).' Hari'
                                    : '-';
                                $totalBiaya = (float) ($lhpp->total_aktual_biaya ?? 0);
                                $garansiMonths = $lhpp->garansi?->garansi_months;
                                $isWithoutWarranty = (int) ($garansiMonths ?? -1) === 0;
                                $qualityControlStatus = $lhpp->quality_control_status ?: 'pending';
                                $qualityControlSelectClass = match ($qualityControlStatus) {
                                    'approved' => 'border-emerald-300 bg-emerald-50 text-emerald-700',
                                    'rejected' => 'border-rose-300 bg-rose-50 text-rose-700',
                                    default => 'border-slate-300 bg-white text-slate-700',
                                };
                                $qualityControlHelper = match ($qualityControlStatus) {
                                    'approved' => 'Dokumen lolos quality control.',
                                    'rejected' => 'Dokumen ditolak dan perlu revisi.',
                                    default => 'Pilih approve atau reject untuk quality control.',
                                };
                                $qualityControlHelperClass = match ($qualityControlStatus) {
                                    'approved' => 'text-emerald-600',
                                    'rejected' => 'text-rose-600',
                                    default => 'text-slate-500',
                                };
                                $termin1Paid = ($lhpp->termin1_status ?? 'belum') === 'sudah';
                                $termin2Paid = ! $isWithoutWarranty && ($lhpp->termin2_status ?? 'belum') === 'sudah';
                                $termin1Amount = $termin1Paid
                                    ? (float) ($isWithoutWarranty ? $totalBiaya : ($lhpp->termin_1_nilai ?? round($totalBiaya * 0.95)))
                                    : null;
                                $termin2Amount = $termin2Paid
                                    ? (float) ($lhpp->termin_2_nilai ?? round($totalBiaya * 0.05))
                                    : null;
                                $hasTerminTwo = ! $isWithoutWarranty && filled($terminTwo?->id);
                                $activeSignature = $lhpp->activeSignature ?: $lhpp->signatures->first(fn (\App\Models\LhppBastSignature $signature): bool => $signature->isPending());
                                $activeApprovalLink = $activeSignature?->approvalUrl();
                                $activeApprovalWhatsappUrl = $activeApprovalLink ? \App\Support\ApprovalWhatsappLink::forBast($activeSignature) : null;
                                $isActiveApprovalExpired = $activeSignature?->tokenExpired() ?? false;
                                $isDiropsPending = $activeSignature?->role_key === 'dirops';
                                $terminTwoActiveSignature = $terminTwo?->activeSignature ?: ($terminTwo?->signatures?->first(fn (\App\Models\LhppBastSignature $signature): bool => $signature->isPending()));
                                $terminTwoActiveApprovalLink = $terminTwoActiveSignature?->approvalUrl();
                                $terminTwoActiveApprovalWhatsappUrl = $terminTwoActiveApprovalLink ? \App\Support\ApprovalWhatsappLink::forBast($terminTwoActiveSignature) : null;
                                $terminTwoIsActiveApprovalExpired = $terminTwoActiveSignature?->tokenExpired() ?? false;
                                $terminTwoIsDiropsPending = $terminTwoActiveSignature?->role_key === 'dirops';
                                $terminTwoDiropsSignedDocumentSignature = $terminTwo?->signatures?->first(
                                    fn (\App\Models\LhppBastSignature $signature): bool => $signature->role_key === 'dirops' && $signature->hasUploadedSignedDocument()
                                );
                                $terminTwoDiropsSignedDocumentUrl = $terminTwoDiropsSignedDocumentSignature
                                    ? route('admin.lhpp.dirops-document.show', ['lhppId' => $terminTwo->id])
                                    : null;
                                $diropsSignedDocumentSignature = $lhpp->signatures->first(
                                    fn (\App\Models\LhppBastSignature $signature): bool => $signature->role_key === 'dirops' && $signature->hasUploadedSignedDocument()
                                );
                                $diropsSignedDocumentUrl = $diropsSignedDocumentSignature
                                    ? route('admin.lhpp.dirops-document.show', ['lhppId' => $lhpp->id])
                                    : null;
                                $approvalStatus = $lhpp->approval_status ?? \App\Models\LhppBast::APPROVAL_IN_REVIEW;
                                $approvalLabel = match ($approvalStatus) {
                                    \App\Models\LhppBast::APPROVAL_APPROVED => 'Approval selesai',
                                    \App\Models\LhppBast::APPROVAL_REJECTED => 'Approval ditolak',
                                    default => $activeSignature
                                        ? 'Menunggu '.$activeSignature->role_label
                                        : ($qualityControlStatus === 'approved' ? 'Menunggu approval' : 'Menunggu QC Admin'),
                                };
                                $approvalClass = match ($approvalStatus) {
                                    \App\Models\LhppBast::APPROVAL_APPROVED => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                                    \App\Models\LhppBast::APPROVAL_REJECTED => 'bg-rose-50 text-rose-700 ring-rose-200',
                                    default => 'bg-blue-50 text-blue-700 ring-blue-200',
                                };
                                $approvalProgress = $lhpp->approvalProgressPercent();
                                $signedCount = $lhpp->approvalSignedCount();
                                $totalSteps = $lhpp->approvalStepCount();
                                $isApprovalComplete = $lhpp->approvalCompleted();
                                $approvalSummaryCaption = $isApprovalComplete ? 'Approval selesai' : 'Approval berjalan';
                                $approvalSummaryLabel = $isApprovalComplete
                                    ? 'Semua approver selesai'
                                    : ($activeSignature?->role_label ? 'Menunggu '.$activeSignature->role_label : ($qualityControlStatus === 'approved' ? 'Menunggu approval' : 'Menunggu QC Admin'));
                                $approvalChecklist = $lhpp->signatures->map(function (\App\Models\LhppBastSignature $signature): array {
                                    return [
                                        'label' => $signature->role_label,
                                        'name' => $signature->signer_name_snapshot ?: '-',
                                        'status' => $signature->status,
                                    ];
                                })->values();
                                $activeApprovalModalActions = [
                                    'link' => $activeApprovalLink && ! $isDiropsPending && ! $isActiveApprovalExpired ? $activeApprovalLink : '',
                                    'whatsapp_url' => $activeApprovalLink && ! $isDiropsPending && ! $isActiveApprovalExpired ? $activeApprovalWhatsappUrl : '',
                                    'resend_url' => $activeApprovalLink && ! $isDiropsPending && ! $isActiveApprovalExpired ? route('admin.lhpp.approval.resend', ['lhppId' => $lhpp->id]) : '',
                                ];
                            @endphp

                            <tr class="transition duration-150 hover:bg-slate-50">
                                <td class="px-4 py-3 align-top">
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
                                <td class="px-4 py-3 align-top">
                                    <div class="space-y-2 text-[10px] leading-snug text-slate-600">
                                        <div class="text-[11px] font-bold leading-snug text-slate-900">{{ $namaPekerjaan }}</div>
                                        <div>
                                            <span>Unit: {{ $unitKerja }}</span>
                                            <span class="mx-1 text-slate-300">|</span>
                                        </div>
                                        <div>
                                            <span class="text-blue-600">Seksi: {{ $seksi }}</span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="space-y-1.5">
                                        <div class="text-[11px] font-bold text-slate-900">{{ $tanggalSelesai }}</div>
                                        @if ($waktuPengerjaan !== '-')
                                            <span class="inline-flex rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700 ring-1 ring-blue-200">
                                                {{ $waktuPengerjaan }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="space-y-2">
                                        <div class="text-[12px] font-bold text-slate-900">Rp{{ number_format($totalBiaya, 0, ',', '.') }}</div>
                                        @if ($garansiMonths === null)
                                            <span class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold text-amber-700 ring-1 ring-amber-200">
                                                Belum diatur
                                            </span>
                                        @elseif ($isWithoutWarranty)
                                            <span class="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-700 ring-1 ring-slate-200">
                                                0 Bulan
                                            </span>
                                        @else
                                            <span class="inline-flex rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-semibold text-indigo-700 ring-1 ring-indigo-200">
                                                {{ $garansiMonths }} Bulan
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-4 py-3 align-top">
                                    <div class="flex items-start gap-2">
                                        <form method="POST" action="{{ route('admin.lhpp.quality-control', ['lhppId' => $lhpp->id]) }}" class="w-[104px] shrink-0 space-y-1">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="search" value="{{ $search }}">
                                            <input type="hidden" name="page" value="{{ $lhpps->currentPage() }}">
                                            <select name="quality_control_status" onchange="this.form.submit()" class="h-8 w-full rounded-lg border px-2 text-[10px] font-semibold focus:outline-none {{ $qualityControlSelectClass }}">
                                                <option value="pending" @selected($qualityControlStatus === 'pending')>Pilih</option>
                                                <option value="approved" @selected($qualityControlStatus === 'approved')>Setujui</option>
                                                <option value="rejected" @selected($qualityControlStatus === 'rejected')>Tolak</option>
                                            </select>
                                            <p class="text-[8px] leading-snug {{ $qualityControlHelperClass }}">{{ $qualityControlHelper }}</p>
                                        </form>

                                        <div class="min-w-0 flex-1 rounded-xl border border-blue-100 bg-blue-50 px-2 py-1.5 shadow-sm">
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex min-w-0 items-center gap-1.5">
                                                    <span class="inline-flex shrink-0 rounded-full bg-white px-1.5 py-0.5 text-[8px] font-bold text-blue-700 ring-1 ring-blue-100">
                                                        {{ $signedCount }}/{{ $totalSteps }} TTD
                                                    </span>
                                                    <span class="truncate text-[9px] font-semibold text-slate-800" title="{{ $approvalSummaryLabel }}">
                                                        {{ $approvalSummaryLabel }}
                                                    </span>
                                                </div>
                                                <button
                                                    type="button"
                                                    class="bast-approval-flow-trigger inline-flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:border-blue-200 hover:bg-blue-100 hover:text-blue-700"
                                                    data-title="{{ $nomorOrder }}"
                                                    data-progress="{{ $approvalProgress }}"
                                                    data-signed-count="{{ $signedCount }}"
                                                    data-total-steps="{{ $totalSteps }}"
                                                    data-caption="{{ $approvalSummaryCaption }}"
                                                    data-summary="{{ $approvalSummaryLabel }}"
                                                    data-checklist='@json($approvalChecklist)'
                                                    data-actions='@json($activeApprovalModalActions)'
                                                    title="Detail approval"
                                                >
                                                    <i data-lucide="info" class="h-3 w-3"></i>
                                                </button>
                                            </div>
                                            @if ($isActiveApprovalExpired)
                                                <div class="mt-1 text-[8px] font-semibold text-amber-700">Link expired</div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-3 align-top">
                                    <div class="flex flex-wrap justify-center gap-1.5">
                                        <a href="{{ route('admin.lhpp.pdf', ['nomorOrder' => $lhpp->nomor_order, 'termin' => 'termin-1']) }}?refresh={{ $pdfRefreshToken }}"
                                           target="_blank"
                                           rel="noopener"
                                           title="Lihat BAST Termin 1 (PDF)"
                                           aria-label="Lihat BAST Termin 1 PDF"
                                           class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-[9px] font-black text-rose-700 shadow-sm transition hover:bg-rose-100">
                                            T1
                                        </a>

                                        @if ($hasTerminTwo)
                                            <a href="{{ route('admin.lhpp.pdf', ['nomorOrder' => $terminTwo->nomor_order, 'termin' => 'termin-2']) }}?refresh={{ $pdfRefreshToken }}"
                                               target="_blank"
                                               rel="noopener"
                                               title="Lihat BAST Termin 2 (PDF)"
                                               aria-label="Lihat BAST Termin 2 PDF"
                                               class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-sky-200 bg-sky-50 text-[9px] font-black text-sky-700 shadow-sm transition hover:bg-sky-100">
                                                T2
                                            </a>
                                        @elseif ($isWithoutWarranty)
                                            <span class="text-center text-[10px] font-medium text-slate-400">Tidak ada Termin 2</span>
                                        @else
                                            <span class="text-center text-[10px] font-medium text-slate-400">Termin 2 belum dibuat</span>
                                        @endif

                                        @if ($isDiropsPending)
                                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-orange-200 bg-orange-50 text-orange-700" title="Menunggu dokumen final PKM">
                                                <i data-lucide="upload" class="h-3 w-3"></i>
                                            </span>
                                        @endif

                                        @if ($diropsSignedDocumentUrl)
                                            <a href="{{ $diropsSignedDocumentUrl }}" target="_blank" rel="noopener" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 shadow-sm transition hover:bg-emerald-100" title="Final DIROPS T1">
                                                <i data-lucide="file-check-2" class="h-3 w-3"></i>
                                            </a>
                                        @endif

                                        @if ($terminTwoIsDiropsPending)
                                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-orange-200 bg-orange-50 text-orange-700" title="Menunggu dokumen final PKM T2">
                                                <i data-lucide="upload" class="h-3 w-3"></i>
                                            </span>
                                        @endif

                                        @if ($terminTwoDiropsSignedDocumentUrl)
                                            <a href="{{ $terminTwoDiropsSignedDocumentUrl }}" target="_blank" rel="noopener" class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 shadow-sm transition hover:bg-emerald-100" title="Final DIROPS T2">
                                                <i data-lucide="file-check-2" class="h-3 w-3"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-[11px] text-slate-500">Belum ada data BAST yang tersedia.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($lhpps->hasPages())
                <div class="mt-4 border-t border-slate-200 px-4 py-4">
                    {{ $lhpps->links() }}
                </div>
            @endif
        </section>
    </div>

    <div id="bastApprovalFlowModal" class="fixed inset-0 z-[120] hidden overflow-y-auto" aria-hidden="true">
        <div class="absolute inset-0 bg-slate-900/45"></div>
        <div class="relative flex min-h-full items-start justify-center px-4 pb-6 pt-28 sm:pb-8 sm:pt-32">
            <div data-bast-approval-panel class="my-2 w-full max-w-md overflow-hidden rounded-[1.2rem] border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-4 py-3.5">
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-[0.18em] text-blue-600">Detail Approval BAST</div>
                        <h2 id="bastApprovalFlowModalTitle" class="mt-1.5 text-[1.2rem] font-bold leading-none tracking-tight text-slate-900">-</h2>
                        <p class="mt-2 text-[11px] text-slate-500">Progress tanda tangan BAST yang sedang berjalan.</p>
                    </div>
                    <button
                        type="button"
                        id="bastApprovalFlowModalClose"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                        aria-label="Tutup detail approval BAST"
                    >
                        <i data-lucide="x" class="h-3.5 w-3.5"></i>
                    </button>
                </div>

                <div class="max-h-[58vh] space-y-3 overflow-y-auto px-4 py-3.5">
                    <div class="flex flex-wrap items-center gap-2">
                        <span id="bastApprovalFlowModalCount" class="inline-flex rounded-full bg-blue-50 px-2.5 py-1 text-[10px] font-bold text-blue-700 ring-1 ring-blue-100">0/0 TTD</span>
                        <span id="bastApprovalFlowModalPercent" class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[10px] font-bold text-slate-600">0%</span>
                    </div>

                    <div id="bastApprovalFlowModalChecklist" class="space-y-2"></div>
                </div>
            </div>
        </div>
    </div>

    <div id="bastSignatureModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/55 px-4 py-6">
        <div class="w-full max-w-md overflow-hidden rounded-xl bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-3 border-b border-slate-200 px-4 py-3">
                <div>
                    <div class="text-[10px] font-semibold uppercase tracking-[0.16em] text-blue-600">Detail Approval BAST</div>
                    <h2 id="bastSignatureModalTitle" class="mt-1 text-base font-bold text-slate-900">TTD</h2>
                </div>
                <button type="button" data-close-bast-signature-modal class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:bg-slate-50">
                    <i data-lucide="x" class="h-4 w-4"></i>
                </button>
            </div>

            <div class="space-y-3 px-4 py-3 text-[11px] text-slate-600">
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2">
                    <div class="text-[9px] font-semibold uppercase tracking-[0.14em] text-slate-400">Dokumen</div>
                    <div id="bastSignatureModalDocument" class="mt-1 font-bold text-slate-900">-</div>
                </div>

                <div class="grid gap-2 sm:grid-cols-2">
                    <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                        <div class="text-[9px] font-semibold uppercase tracking-[0.14em] text-slate-400">Role TTD</div>
                        <div id="bastSignatureModalRole" class="mt-1 font-semibold text-slate-900">-</div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                        <div class="text-[9px] font-semibold uppercase tracking-[0.14em] text-slate-400">Signer</div>
                        <div id="bastSignatureModalSigner" class="mt-1 font-semibold text-slate-900">-</div>
                    </div>
                </div>

                <div class="grid gap-2 sm:grid-cols-2">
                    <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                        <div class="text-[9px] font-semibold uppercase tracking-[0.14em] text-slate-400">Expired Token</div>
                        <div id="bastSignatureModalExpiry" class="mt-1 font-semibold text-slate-900">-</div>
                    </div>
                    <div class="rounded-lg border border-slate-200 bg-white px-3 py-2">
                        <div class="text-[9px] font-semibold uppercase tracking-[0.14em] text-slate-400">Status</div>
                        <div id="bastSignatureModalStatus" class="mt-1 inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold">-</div>
                    </div>
                </div>

                <div id="bastSignatureModalNote" class="rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 text-blue-700">
                    Link approval masih aktif dan bisa dikirim ulang.
                </div>
            </div>

            <div class="flex flex-col-reverse gap-2 border-t border-slate-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-end">
                <button type="button" data-close-bast-signature-modal class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50">
                    Tutup
                </button>
                <a id="bastSignatureWhatsappButton" href="#" target="_blank" rel="noopener noreferrer" class="hidden items-center justify-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100">
                    <i data-lucide="message-circle" class="h-3.5 w-3.5"></i>
                    Kirim WhatsApp
                </a>
                <button id="bastSignatureCopyButton" type="button" class="hidden items-center justify-center gap-1 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100">
                    <i data-lucide="copy" class="h-3.5 w-3.5"></i>
                    Salin Link
                </button>
                <form id="bastSignatureResendForm" method="POST" action="#" class="hidden">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center gap-1 rounded-lg border border-sky-200 bg-sky-50 px-3 py-2 text-xs font-semibold text-sky-700 transition hover:bg-sky-100">
                        <i data-lucide="send" class="h-3.5 w-3.5"></i>
                        Resend Email
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const statusAlert = document.getElementById('admin-bast-status-alert');

            if (statusAlert?.dataset.message && window.Swal) {
                window.Swal.fire({
                    icon: 'success',
                    title: 'Berhasil',
                    text: statusAlert.dataset.message,
                    timer: 1800,
                    showConfirmButton: false,
                });
            }

            const signatureModal = document.getElementById('bastSignatureModal');
            const signatureTitle = document.getElementById('bastSignatureModalTitle');
            const signatureDocument = document.getElementById('bastSignatureModalDocument');
            const signatureRole = document.getElementById('bastSignatureModalRole');
            const signatureSigner = document.getElementById('bastSignatureModalSigner');
            const signatureExpiry = document.getElementById('bastSignatureModalExpiry');
            const signatureStatus = document.getElementById('bastSignatureModalStatus');
            const signatureNote = document.getElementById('bastSignatureModalNote');
            const signatureWhatsappButton = document.getElementById('bastSignatureWhatsappButton');
            const signatureCopyButton = document.getElementById('bastSignatureCopyButton');
            const signatureResendForm = document.getElementById('bastSignatureResendForm');

            const copyToClipboard = async (text) => {
                if (navigator.clipboard?.writeText) {
                    await navigator.clipboard.writeText(text);
                    return;
                }

                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.setAttribute('readonly', 'readonly');
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
            };

            const openSignatureModal = (button) => {
                if (!signatureModal) {
                    return;
                }

                const isExpired = button.dataset.expired === '1';
                const isDirops = button.dataset.dirops === '1';
                const link = button.dataset.link || '';
                const whatsappUrl = button.dataset.waUrl || '';
                const resendUrl = button.dataset.resendUrl || '';

                signatureTitle.textContent = button.dataset.title || 'Detail TTD';
                signatureDocument.textContent = button.dataset.document || '-';
                signatureRole.textContent = button.dataset.role || '-';
                signatureSigner.textContent = button.dataset.signer || '-';
                signatureExpiry.textContent = button.dataset.expiry || '-';

                signatureStatus.className = 'mt-1 inline-flex rounded-full px-2 py-0.5 text-[10px] font-semibold';

                if (isExpired) {
                    signatureStatus.classList.add('bg-amber-100', 'text-amber-800', 'ring-1', 'ring-amber-200');
                    signatureStatus.textContent = 'Token expired';
                    signatureNote.className = 'rounded-lg border border-amber-100 bg-amber-50 px-3 py-2 text-amber-800';
                    signatureNote.textContent = 'Token approval sudah expired. Buat/kirim ulang token dari alur approval bila diperlukan.';
                } else if (isDirops) {
                    signatureStatus.classList.add('bg-orange-100', 'text-orange-800', 'ring-1', 'ring-orange-200');
                    signatureStatus.textContent = 'Menunggu PKM';
                    signatureNote.className = 'rounded-lg border border-orange-100 bg-orange-50 px-3 py-2 text-orange-800';
                    signatureNote.textContent = 'Step DIROPS menunggu dokumen final dari PKM, sehingga tidak memakai link approval email biasa.';
                } else if (link) {
                    signatureStatus.classList.add('bg-emerald-100', 'text-emerald-800', 'ring-1', 'ring-emerald-200');
                    signatureStatus.textContent = 'Link aktif';
                    signatureNote.className = 'rounded-lg border border-blue-100 bg-blue-50 px-3 py-2 text-blue-700';
                    signatureNote.textContent = whatsappUrl
                        ? 'Link approval masih aktif. Anda bisa mengirim link via WhatsApp atau mengirim ulang email approval.'
                        : 'Link approval masih aktif, tetapi nomor WhatsApp approver belum tersedia di user panel. Email approval masih bisa dikirim ulang.';
                } else {
                    signatureStatus.classList.add('bg-slate-100', 'text-slate-700', 'ring-1', 'ring-slate-200');
                    signatureStatus.textContent = 'Tidak ada link aktif';
                    signatureNote.className = 'rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-slate-600';
                    signatureNote.textContent = 'Belum ada link approval aktif untuk step ini.';
                }

                signatureWhatsappButton?.classList.toggle('hidden', !whatsappUrl);
                signatureWhatsappButton?.classList.toggle('inline-flex', Boolean(whatsappUrl));
                if (signatureWhatsappButton && whatsappUrl) {
                    signatureWhatsappButton.href = whatsappUrl;
                }

                signatureCopyButton?.classList.toggle('hidden', !link);
                signatureCopyButton?.classList.toggle('inline-flex', Boolean(link));
                if (signatureCopyButton && link) {
                    signatureCopyButton.dataset.link = link;
                    signatureCopyButton.innerHTML = '<i data-lucide="copy" class="h-3.5 w-3.5"></i> Salin Link';
                }

                signatureResendForm?.classList.toggle('hidden', !resendUrl);
                signatureResendForm?.classList.toggle('block', Boolean(resendUrl));
                if (signatureResendForm && resendUrl) {
                    signatureResendForm.action = resendUrl;
                }

                signatureModal.classList.remove('hidden');
                signatureModal.classList.add('flex');

                if (window.lucide) {
                    window.lucide.createIcons();
                }
            };

            const closeSignatureModal = () => {
                signatureModal?.classList.add('hidden');
                signatureModal?.classList.remove('flex');
            };

            document.querySelectorAll('.bast-signature-detail-trigger').forEach((button) => {
                button.addEventListener('click', () => openSignatureModal(button));
            });

            document.querySelectorAll('[data-close-bast-signature-modal]').forEach((button) => {
                button.addEventListener('click', closeSignatureModal);
            });

            signatureModal?.addEventListener('click', (event) => {
                if (event.target === signatureModal) {
                    closeSignatureModal();
                }
            });

            signatureCopyButton?.addEventListener('click', async () => {
                const link = signatureCopyButton.dataset.link || '';

                if (!link) {
                    return;
                }

                try {
                    await copyToClipboard(link);
                    signatureCopyButton.innerHTML = '<i data-lucide="check" class="h-3.5 w-3.5"></i> Disalin';
                    window.lucide?.createIcons();
                    setTimeout(() => {
                        signatureCopyButton.innerHTML = '<i data-lucide="copy" class="h-3.5 w-3.5"></i> Salin Link';
                        window.lucide?.createIcons();
                    }, 1400);
                } catch (error) {
                    signatureCopyButton.innerHTML = '<i data-lucide="copy" class="h-3.5 w-3.5"></i> Salin Link';
                }
            });

            const approvalFlowModal = document.getElementById('bastApprovalFlowModal');
            const approvalFlowModalTitle = document.getElementById('bastApprovalFlowModalTitle');
            const approvalFlowModalCount = document.getElementById('bastApprovalFlowModalCount');
            const approvalFlowModalPercent = document.getElementById('bastApprovalFlowModalPercent');
            const approvalFlowModalChecklist = document.getElementById('bastApprovalFlowModalChecklist');
            const approvalFlowModalClose = document.getElementById('bastApprovalFlowModalClose');
            const escapeHtml = (value) => String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
            const parseArrayData = (value) => {
                try {
                    const parsed = JSON.parse(value || '[]');
                    return Array.isArray(parsed) ? parsed : [];
                } catch (error) {
                    return [];
                }
            };
            const parseObjectData = (value) => {
                try {
                    const parsed = JSON.parse(value || '{}');
                    return parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed : {};
                } catch (error) {
                    return {};
                }
            };
            const approvalStatusConfig = {
                signed: { label: 'OK', badgeClass: 'border-emerald-200 bg-emerald-50 text-emerald-700', rowClass: 'border-emerald-200 bg-emerald-50' },
                pending: { label: 'Aktif', badgeClass: 'border-blue-200 bg-blue-50 text-blue-700', rowClass: 'border-blue-200 bg-blue-50' },
                locked: { label: 'Menunggu', badgeClass: 'border-slate-200 bg-slate-100 text-slate-500', rowClass: 'border-slate-200 bg-slate-50' },
                skipped: { label: 'Skip', badgeClass: 'border-amber-200 bg-amber-50 text-amber-700', rowClass: 'border-amber-200 bg-amber-50' },
            };

            const openApprovalFlowModal = (button) => {
                if (!approvalFlowModal) {
                    return;
                }

                const checklist = parseArrayData(button.dataset.checklist);
                const actions = parseObjectData(button.dataset.actions);
                const approvalLink = actions.link || '';
                const whatsappUrl = actions.whatsapp_url || '';
                const resendUrl = actions.resend_url || '';
                const signedCount = button.dataset.signedCount || '0';
                const totalSteps = button.dataset.totalSteps || '0';
                const progress = button.dataset.progress || '0';

                approvalFlowModalTitle.textContent = button.dataset.title || '-';
                approvalFlowModalCount.textContent = `${signedCount}/${totalSteps} TTD`;
                approvalFlowModalPercent.textContent = `${progress}%`;
                approvalFlowModalChecklist.innerHTML = checklist.map((item) => {
                    const config = approvalStatusConfig[item.status] || approvalStatusConfig.locked;
                    const isActive = item.status === 'pending' && approvalLink;
                    const actionButtons = isActive
                        ? `
                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                <button type="button" class="bast-modal-copy-link inline-flex items-center gap-1 rounded-lg border border-blue-200 bg-white px-2.5 py-1.5 text-[10px] font-semibold text-blue-700 transition hover:bg-blue-100" data-link="${escapeHtml(approvalLink)}">
                                    <i data-lucide="copy" class="h-3 w-3"></i>
                                    Salin Link
                                </button>
                                ${whatsappUrl ? `
                                    <a href="${escapeHtml(whatsappUrl)}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-1 rounded-lg border border-emerald-200 bg-white px-2.5 py-1.5 text-[10px] font-semibold text-emerald-700 transition hover:bg-emerald-100">
                                        <i data-lucide="message-circle" class="h-3 w-3"></i>
                                        WhatsApp
                                    </a>
                                ` : ''}
                                ${resendUrl ? `
                                    <form method="POST" action="${escapeHtml(resendUrl)}" class="inline-block">
                                        <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                        <button type="submit" class="inline-flex items-center gap-1 rounded-lg border border-sky-200 bg-white px-2.5 py-1.5 text-[10px] font-semibold text-sky-700 transition hover:bg-sky-100">
                                            <i data-lucide="send" class="h-3 w-3"></i>
                                            Resend
                                        </button>
                                    </form>
                                ` : ''}
                            </div>
                        `
                        : '';

                    return `
                        <div class="rounded-xl border px-3 py-2.5 ${config.rowClass}">
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="truncate text-[13px] font-medium text-slate-800">${escapeHtml(item.label || '-')}</div>
                                    <div class="mt-1 truncate text-[11px] text-slate-500">${escapeHtml(item.name || '-')}</div>
                                </div>
                                <span class="inline-flex shrink-0 rounded-full border px-2.5 py-1 text-[10px] font-bold ${config.badgeClass}">
                                    ${config.label}
                                </span>
                            </div>
                            ${actionButtons}
                        </div>
                    `;
                }).join('');

                approvalFlowModal.classList.remove('hidden');
                approvalFlowModal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('overflow-hidden');
                window.lucide?.createIcons();
            };

            const closeApprovalFlowModal = () => {
                approvalFlowModal?.classList.add('hidden');
                approvalFlowModal?.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('overflow-hidden');
            };

            document.querySelectorAll('.bast-approval-flow-trigger').forEach((button) => {
                button.addEventListener('click', () => openApprovalFlowModal(button));
            });
            approvalFlowModalClose?.addEventListener('click', closeApprovalFlowModal);
            approvalFlowModal?.addEventListener('click', (event) => {
                if (!event.target.closest('[data-bast-approval-panel]')) {
                    closeApprovalFlowModal();
                }
            });
            approvalFlowModalChecklist?.addEventListener('click', async (event) => {
                const copyButton = event.target.closest('.bast-modal-copy-link');

                if (!copyButton) {
                    return;
                }

                await copyToClipboard(copyButton.dataset.link || '');
                copyButton.innerHTML = '<i data-lucide="check" class="h-3 w-3"></i> Disalin';
                window.lucide?.createIcons();
            });
        });
    </script>
</x-layouts.admin>
