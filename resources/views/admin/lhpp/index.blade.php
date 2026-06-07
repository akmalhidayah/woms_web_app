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
                        <col class="w-[15%]">
                        <col class="w-[18%]">
                        <col class="w-[12%]">
                        <col class="w-[18%]">
                        <col class="w-[17%]">
                        <col class="w-[20%]">
                        <col class="w-[12%]">
                    </colgroup>
                    <thead class="bg-slate-100 text-slate-700 uppercase tracking-wide">
                        <tr>
                            <th class="px-4 py-2 text-left font-semibold">Order / PO</th>
                            <th class="px-4 py-2 text-left font-semibold">Unit Kerja</th>
                            <th class="px-4 py-2 text-left font-semibold">Selesai / Waktu</th>
                            <th class="px-4 py-2 text-left font-semibold">Biaya / Garansi</th>
                            <th class="px-4 py-2 text-left font-semibold">Quality Control</th>
                            <th class="px-4 py-2 text-center font-semibold">PDF BAST</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($lhpps as $lhpp)
                            @php
                                $nomorOrder = $lhpp->nomor_order ?: ($lhpp->order?->nomor_order ?? '-');
                                $nomorPo = $lhpp->purchase_order_number ?: ($lhpp->purchaseOrder?->purchase_order_number ?? '-');
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
                            @endphp

                            <tr class="transition duration-150 hover:bg-slate-50">
                                <td class="px-4 py-3 align-top">
                                    <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
                                        <div class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Order</div>
                                        <div class="mt-1 break-words text-[13px] font-bold leading-tight text-slate-900">{{ $nomorOrder }}</div>
                                        <div class="mt-2 border-t border-slate-100 pt-2 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">PO</div>
                                        <div class="mt-1 break-words text-[11px] font-semibold leading-tight text-slate-700">{{ $nomorPo }}</div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-[10px] text-slate-600 shadow-sm">
                                        <div class="font-semibold leading-snug text-slate-800">{{ $seksi }}</div>
                                        <div class="mt-1 border-t border-slate-200 pt-1.5 leading-snug">{{ $unitKerja }}</div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-[11px] shadow-sm">
                                        <div class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Tanggal</div>
                                        <div class="mt-1 font-bold text-slate-900">{{ $tanggalSelesai }}</div>
                                        <div class="mt-2 border-t border-slate-100 pt-2 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Waktu</div>
                                        <div class="mt-1 font-semibold text-slate-700">{{ $waktuPengerjaan }}</div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 align-top">
                                    <div class="rounded-xl border border-slate-200 bg-white px-3 py-2 shadow-sm">
                                        <div class="text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Total Biaya</div>
                                        <div class="mt-1 font-bold text-slate-900">Rp{{ number_format($totalBiaya, 0, ',', '.') }}</div>
                                        @if (!is_null($termin1Amount))
                                            <div class="mt-1 text-[10px] font-medium text-emerald-600">
                                                {{ $isWithoutWarranty ? 'Total Dibayar' : 'Termin 1' }}: Rp{{ number_format($termin1Amount, 0, ',', '.') }}
                                            </div>
                                        @endif
                                        @if (!is_null($termin2Amount))
                                            <div class="mt-1 text-[10px] font-medium text-sky-600">
                                                Termin 2: Rp{{ number_format($termin2Amount, 0, ',', '.') }}
                                            </div>
                                        @endif

                                        <div class="mt-2 border-t border-slate-100 pt-2 text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">Garansi</div>
                                        @if ($garansiMonths === null)
                                            <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-1 text-[10px] font-semibold text-amber-700 ring-1 ring-amber-200">
                                                <i data-lucide="clock-3" class="h-3 w-3"></i>
                                                Belum diatur
                                            </span>
                                        @elseif ($isWithoutWarranty)
                                            <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-700 ring-1 ring-slate-200">
                                                <i data-lucide="ban" class="h-3 w-3"></i>
                                                Tanpa Garansi
                                            </span>
                                            <div class="mt-1 text-[10px] text-slate-500">Pembayaran cukup 1 termin.</div>
                                        @else
                                            <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2 py-1 text-[10px] font-semibold text-indigo-700 ring-1 ring-indigo-200">
                                                <i data-lucide="shield-check" class="h-3 w-3"></i>
                                                {{ $garansiMonths }} Bulan
                                            </span>
                                            <div class="mt-1 text-[10px] text-slate-500">Menggunakan Termin 1 & 2.</div>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-4 py-3 align-top">
                                    <form method="POST" action="{{ route('admin.lhpp.quality-control', ['lhppId' => $lhpp->id]) }}" class="space-y-1.5">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="search" value="{{ $search }}">
                                        <input type="hidden" name="page" value="{{ $lhpps->currentPage() }}">
                                        <select name="quality_control_status" onchange="this.form.submit()" class="w-full rounded-lg border px-2.5 py-1.5 text-[11px] font-semibold focus:outline-none {{ $qualityControlSelectClass }}">
                                            <option value="pending" @selected($qualityControlStatus === 'pending')>Pilih Aksi</option>
                                            <option value="approved" @selected($qualityControlStatus === 'approved')>Setujui</option>
                                            <option value="rejected" @selected($qualityControlStatus === 'rejected')>Tolak</option>
                                        </select>
                                        <p class="text-[10px] leading-snug {{ $qualityControlHelperClass }}">{{ $qualityControlHelper }}</p>
                                    </form>
                                    <div class="mt-2 rounded-lg border border-slate-200 bg-white px-2.5 py-2">
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[9px] font-semibold ring-1 {{ $approvalClass }}">
                                            {{ $approvalLabel }}
                                        </span>
                                        @if ($activeSignature)
                                            <div class="mt-1 text-[9px] text-slate-500">
                                                {{ $activeSignature->signer_name_snapshot ?: '-' }}
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-4 py-3 align-top">
                                    <div class="flex flex-col items-center gap-1.5">
                                        <a href="{{ route('admin.lhpp.pdf', ['nomorOrder' => $lhpp->nomor_order, 'termin' => 'termin-1']) }}?refresh={{ $pdfRefreshToken }}"
                                           target="_blank"
                                           rel="noopener"
                                           title="Lihat BAST Termin 1 (PDF)"
                                           aria-label="Lihat BAST Termin 1 PDF"
                                           class="inline-flex w-[116px] items-center justify-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1.5 text-[9px] font-semibold text-rose-700 shadow-sm transition hover:bg-rose-100">
                                            <i data-lucide="file-text" class="h-3 w-3"></i>
                                            {{ $isWithoutWarranty ? 'BAST Final' : 'BAST Termin 1' }}
                                        </a>

                                        @if ($hasTerminTwo)
                                            <a href="{{ route('admin.lhpp.pdf', ['nomorOrder' => $terminTwo->nomor_order, 'termin' => 'termin-2']) }}?refresh={{ $pdfRefreshToken }}"
                                               target="_blank"
                                               rel="noopener"
                                               title="Lihat BAST Termin 2 (PDF)"
                                               aria-label="Lihat BAST Termin 2 PDF"
                                               class="inline-flex w-[116px] items-center justify-center gap-1 rounded-lg border border-sky-200 bg-sky-50 px-2.5 py-1.5 text-[9px] font-semibold text-sky-700 shadow-sm transition hover:bg-sky-100">
                                                <i data-lucide="file-text" class="h-3 w-3"></i>
                                                BAST Termin 2
                                            </a>
                                        @elseif ($isWithoutWarranty)
                                            <span class="text-center text-[10px] font-medium text-slate-400">Tidak ada Termin 2</span>
                                        @else
                                            <span class="text-center text-[10px] font-medium text-slate-400">Termin 2 belum dibuat</span>
                                        @endif

                                        @if ($activeSignature && $approvalStatus !== \App\Models\LhppBast::APPROVAL_APPROVED)
                                            <button
                                                type="button"
                                                class="bast-signature-detail-trigger inline-flex w-[116px] items-center justify-center gap-1 rounded-lg border border-blue-200 bg-blue-50 px-2.5 py-1.5 text-[9px] font-semibold text-blue-700 shadow-sm transition hover:bg-blue-100"
                                                data-title="TTD Termin 1"
                                                data-document="{{ $nomorOrder }}"
                                                data-role="{{ $activeSignature->role_label }}"
                                                data-signer="{{ $activeSignature->signer_name_snapshot ?: '-' }}"
                                                data-expired="{{ $isActiveApprovalExpired ? '1' : '0' }}"
                                                data-dirops="{{ $isDiropsPending ? '1' : '0' }}"
                                                data-expiry="{{ $activeSignature->token_expires_at?->format('d/m/Y H:i') ?: '-' }}"
                                                data-link="{{ $activeApprovalLink && ! $isDiropsPending && ! $isActiveApprovalExpired ? $activeApprovalLink : '' }}"
                                                data-wa-url="{{ $activeApprovalLink && ! $isDiropsPending && ! $isActiveApprovalExpired ? $activeApprovalWhatsappUrl : '' }}"
                                                data-resend-url="{{ $activeApprovalLink && ! $isDiropsPending && ! $isActiveApprovalExpired ? route('admin.lhpp.approval.resend', ['lhppId' => $lhpp->id]) : '' }}"
                                            >
                                                <i data-lucide="signature" class="h-3 w-3"></i>
                                                Detail TTD T1
                                            </button>
                                        @endif

                                        @if ($terminTwoActiveSignature && $terminTwo?->approval_status !== \App\Models\LhppBast::APPROVAL_APPROVED)
                                            <button
                                                type="button"
                                                class="bast-signature-detail-trigger inline-flex w-[116px] items-center justify-center gap-1 rounded-lg border border-sky-200 bg-sky-50 px-2.5 py-1.5 text-[9px] font-semibold text-sky-700 shadow-sm transition hover:bg-sky-100"
                                                data-title="TTD Termin 2"
                                                data-document="{{ $nomorOrder }}"
                                                data-role="{{ $terminTwoActiveSignature->role_label }}"
                                                data-signer="{{ $terminTwoActiveSignature->signer_name_snapshot ?: '-' }}"
                                                data-expired="{{ $terminTwoIsActiveApprovalExpired ? '1' : '0' }}"
                                                data-dirops="{{ $terminTwoIsDiropsPending ? '1' : '0' }}"
                                                data-expiry="{{ $terminTwoActiveSignature->token_expires_at?->format('d/m/Y H:i') ?: '-' }}"
                                                data-link="{{ $terminTwoActiveApprovalLink && ! $terminTwoIsDiropsPending && ! $terminTwoIsActiveApprovalExpired ? $terminTwoActiveApprovalLink : '' }}"
                                                data-wa-url="{{ $terminTwoActiveApprovalLink && ! $terminTwoIsDiropsPending && ! $terminTwoIsActiveApprovalExpired ? $terminTwoActiveApprovalWhatsappUrl : '' }}"
                                                data-resend-url="{{ $terminTwoActiveApprovalLink && ! $terminTwoIsDiropsPending && ! $terminTwoIsActiveApprovalExpired ? route('admin.lhpp.approval.resend', ['lhppId' => $terminTwo->id]) : '' }}"
                                            >
                                                <i data-lucide="signature" class="h-3 w-3"></i>
                                                Detail TTD T2
                                            </button>
                                        @endif

                                        @if ($isDiropsPending)
                                            <span class="inline-flex w-[116px] items-center justify-center gap-1 rounded-lg border border-orange-200 bg-orange-50 px-2.5 py-1.5 text-[9px] font-semibold text-orange-700">
                                                <i data-lucide="upload" class="h-3 w-3"></i>
                                                Menunggu PKM
                                            </span>
                                        @endif

                                        @if ($diropsSignedDocumentUrl)
                                            <a href="{{ $diropsSignedDocumentUrl }}" target="_blank" rel="noopener" class="inline-flex w-[116px] items-center justify-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-[9px] font-semibold text-emerald-700 shadow-sm transition hover:bg-emerald-100">
                                                <i data-lucide="file-check-2" class="h-3 w-3"></i>
                                                Final DIROPS
                                            </a>
                                        @endif

                                        @if ($terminTwoIsDiropsPending)
                                            <span class="inline-flex w-[116px] items-center justify-center gap-1 rounded-lg border border-orange-200 bg-orange-50 px-2.5 py-1.5 text-[9px] font-semibold text-orange-700">
                                                <i data-lucide="upload" class="h-3 w-3"></i>
                                                PKM DIROPS T2
                                            </span>
                                        @endif

                                        @if ($terminTwoDiropsSignedDocumentUrl)
                                            <a href="{{ $terminTwoDiropsSignedDocumentUrl }}" target="_blank" rel="noopener" class="inline-flex w-[116px] items-center justify-center gap-1 rounded-lg border border-emerald-200 bg-emerald-50 px-2.5 py-1.5 text-[9px] font-semibold text-emerald-700 shadow-sm transition hover:bg-emerald-100">
                                                <i data-lucide="file-check-2" class="h-3 w-3"></i>
                                                DIROPS T2
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

        });
    </script>
</x-layouts.admin>
