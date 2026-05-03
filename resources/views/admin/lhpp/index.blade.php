<x-layouts.admin title="BAST">
    @if (session('status'))
        <div id="admin-bast-status-alert" data-message="{{ session('status') }}" class="hidden"></div>
    @endif

    <div class="space-y-5">
        <section class="rounded-[1.35rem] border border-blue-100 px-5 py-4 shadow-sm" style="background: linear-gradient(135deg, #eef4ff 0%, #f8fbff 48%, #e6f1ff 100%);">
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

        <section class="overflow-hidden rounded-[1.35rem] border border-slate-200 bg-white shadow-sm">
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
                                $isActiveApprovalExpired = $activeSignature?->tokenExpired() ?? false;
                                $isDiropsPending = $activeSignature?->role_key === 'dirops';
                                $terminTwoActiveSignature = $terminTwo?->activeSignature ?: ($terminTwo?->signatures?->first(fn (\App\Models\LhppBastSignature $signature): bool => $signature->isPending()));
                                $terminTwoActiveApprovalLink = $terminTwoActiveSignature?->approvalUrl();
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
                                            <div class="w-[170px] rounded-xl border border-blue-100 bg-blue-50 p-2 text-left shadow-sm">
                                                <div class="flex items-center gap-1 text-[10px] font-bold text-blue-800">
                                                    <i data-lucide="signature" class="h-3 w-3"></i>
                                                    TTD Termin 1
                                                </div>
                                                <div class="mt-1 truncate text-[9px] font-medium text-blue-700">
                                                    {{ $activeSignature->role_label }} - {{ $activeSignature->signer_name_snapshot ?: '-' }}
                                                </div>
                                                @if ($activeApprovalLink && ! $isDiropsPending && ! $isActiveApprovalExpired)
                                                    <div class="mt-2">
                                                        <button type="button" data-copy-bast-approval-link="{{ $activeApprovalLink }}" class="inline-flex w-full items-center justify-center gap-1 rounded-lg bg-white px-2 py-1.5 text-[9px] font-semibold text-blue-700 ring-1 ring-blue-200 transition hover:bg-blue-100">
                                                            <i data-lucide="copy" class="h-3 w-3"></i>
                                                            Salin
                                                        </button>
                                                    </div>
                                                    <div class="mt-1 text-[8px] text-blue-600">Exp: {{ $activeSignature->token_expires_at?->format('d/m H:i') }}</div>
                                                @elseif ($isActiveApprovalExpired)
                                                    <div class="mt-2 inline-flex items-center gap-1 rounded-lg bg-amber-100 px-2 py-1 text-[9px] font-semibold text-amber-800 ring-1 ring-amber-200">
                                                        <i data-lucide="clock-3" class="h-3 w-3"></i>
                                                        Token expired
                                                    </div>
                                                @endif
                                            </div>
                                        @endif

                                        @if ($terminTwoActiveSignature && $terminTwo?->approval_status !== \App\Models\LhppBast::APPROVAL_APPROVED)
                                            <div class="w-[170px] rounded-xl border border-sky-100 bg-sky-50 p-2 text-left shadow-sm">
                                                <div class="flex items-center gap-1 text-[10px] font-bold text-sky-800">
                                                    <i data-lucide="signature" class="h-3 w-3"></i>
                                                    TTD Termin 2
                                                </div>
                                                <div class="mt-1 truncate text-[9px] font-medium text-sky-700">
                                                    {{ $terminTwoActiveSignature->role_label }} - {{ $terminTwoActiveSignature->signer_name_snapshot ?: '-' }}
                                                </div>
                                                @if ($terminTwoActiveApprovalLink && ! $terminTwoIsDiropsPending && ! $terminTwoIsActiveApprovalExpired)
                                                    <div class="mt-2">
                                                        <button type="button" data-copy-bast-approval-link="{{ $terminTwoActiveApprovalLink }}" class="inline-flex w-full items-center justify-center gap-1 rounded-lg bg-white px-2 py-1.5 text-[9px] font-semibold text-sky-700 ring-1 ring-sky-200 transition hover:bg-sky-100">
                                                            <i data-lucide="copy" class="h-3 w-3"></i>
                                                            Salin
                                                        </button>
                                                    </div>
                                                    <div class="mt-1 text-[8px] text-sky-600">Exp: {{ $terminTwoActiveSignature->token_expires_at?->format('d/m H:i') }}</div>
                                                @elseif ($terminTwoIsActiveApprovalExpired)
                                                    <div class="mt-2 inline-flex items-center gap-1 rounded-lg bg-amber-100 px-2 py-1 text-[9px] font-semibold text-amber-800 ring-1 ring-amber-200">
                                                        <i data-lucide="clock-3" class="h-3 w-3"></i>
                                                        Token T2 expired
                                                    </div>
                                                @endif
                                            </div>
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

            const copyToClipboard = async (text) => {
                if (navigator.clipboard?.writeText) {
                    await navigator.clipboard.writeText(text);
                    return;
                }

                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.setAttribute('readonly', '');
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
            };

            document.querySelectorAll('[data-copy-bast-approval-link]').forEach((button) => {
                button.addEventListener('click', async () => {
                    const link = button.dataset.copyBastApprovalLink || '';

                    if (!link) {
                        return;
                    }

                    const originalHtml = button.innerHTML;

                    try {
                        await copyToClipboard(link);
                        button.innerHTML = '<i data-lucide="check" class="h-3 w-3"></i> Disalin';
                        if (window.lucide) {
                            window.lucide.createIcons();
                        }
                        setTimeout(() => {
                            button.innerHTML = originalHtml;
                            if (window.lucide) {
                                window.lucide.createIcons();
                            }
                        }, 1600);
                    } catch (error) {
                        button.innerHTML = originalHtml;
                    }
                });
            });
        });
    </script>
</x-layouts.admin>
