<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head', ['title' => 'Approval Inspeksi Peralatan'])
    <script defer src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    @php
        $totalSteps = count(\App\Models\EquipmentInspectionSignature::STEPS);
        $completedSteps = max(0, min($approval->step_order - 1, $totalSteps));
    @endphp

    <x-inspections.sweet-alerts :error-messages="$errors->all()" />

    <main class="mx-auto w-full px-1.5 py-4 sm:px-3 lg:px-4">
        <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.10)]">
            <header class="border-b-4 border-[#8f1d2c] bg-[#5b0f1b] px-5 py-6 text-white sm:px-8 sm:py-7">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                    <div class="min-w-0">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.28em] text-white/70">Inspeksi Peralatan Digital Approval</div>
                        <h1 class="mt-3 break-words text-2xl font-bold tracking-tight sm:text-3xl">{{ $inspection->form_name }}</h1>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-white/75">Halaman approval bertoken ini hanya dapat digunakan oleh akun penanda tangan yang ditetapkan.</p>
                    </div>
                    <div class="xl:min-w-[22rem] xl:max-w-[24rem]">
                        <div class="flex items-start gap-3 rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-sm shadow-sm backdrop-blur">
                            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/95 p-1.5 shadow-sm">
                                <img src="{{ asset('assets/branding/logos/logo-st.png') }}" alt="Logo ST" class="h-full w-full object-contain">
                            </div>
                            <div class="min-w-0">
                                <div class="text-white/70">Login sebagai</div>
                                <div class="mt-1 break-words font-semibold text-white">{{ auth()->user()->name }}</div>
                                <div class="break-all text-xs text-white/70">{{ auth()->user()->email }}</div>
                                <a href="{{ route(auth()->user()->dashboardRouteName()) }}" class="mt-3 inline-flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-xs font-semibold text-[#5b0f1b] transition hover:bg-white/90">
                                    <i data-lucide="layout-dashboard" class="h-3.5 w-3.5"></i>
                                    Ke Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            <div class="space-y-5 px-3 py-4 sm:px-5 sm:py-5">
                <div class="grid gap-3 rounded-[1.25rem] border border-slate-200 bg-slate-50 p-4 lg:grid-cols-2 xl:grid-cols-5">
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Penanda Tangan</div>
                        <div class="mt-2 break-words text-sm font-bold text-slate-900">{{ $approval->signer_name }}</div>
                        <div class="mt-1 text-sm leading-5 text-slate-600">{{ $approval->signer_position }}</div>
                        <span class="mt-3 inline-flex w-fit rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-200">Menunggu Tanda Tangan</span>
                    </div>
                    <div class="space-y-4 rounded-2xl border border-slate-200 bg-white px-4 py-4">
                        <div class="flex items-start gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500 ring-1 ring-slate-200">
                                <i data-lucide="hash" class="h-4 w-4"></i>
                            </span>
                            <div class="min-w-0">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Nomor Dokumen</div>
                                <div class="mt-1 break-words text-sm font-bold text-slate-900">{{ $inspection->document_no }}</div>
                            </div>
                        </div>
                        <div class="flex items-start gap-3">
                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-orange-50 text-orange-600 ring-1 ring-orange-100">
                                <i data-lucide="calendar-days" class="h-4 w-4"></i>
                            </span>
                            <div class="min-w-0">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Tanggal Inspeksi</div>
                                <div class="mt-1 text-sm font-bold text-slate-900">{{ $inspection->inspection_date->format('d/m/Y') }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 lg:col-span-2 xl:col-span-2">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Peralatan</div>
                        <div class="mt-2 break-words text-sm font-bold text-slate-900">{{ $inspection->form_name }}</div>
                        <div class="mt-3 flex flex-wrap gap-2 text-[11px] font-semibold">
                            <span class="inline-flex max-w-full items-center gap-1.5 rounded-full bg-orange-50 px-2.5 py-1 text-orange-700 ring-1 ring-orange-100">
                                <i data-lucide="user-round" class="h-3.5 w-3.5 shrink-0"></i>
                                <span class="min-w-0 break-words">Inspektor: {{ $inspection->inspector_name }}</span>
                            </span>
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-slate-600 ring-1 ring-slate-200">
                                <i data-lucide="files" class="h-3.5 w-3.5"></i>
                                Versi {{ $inspection->document_version }}
                            </span>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Tahap Approval</div>
                        <div class="mt-2 text-sm font-bold text-slate-900">Tahap {{ $approval->step_order }} dari {{ $totalSteps }}</div>
                        <div role="progressbar" aria-label="Tanda tangan tersimpan" aria-valuemin="0" aria-valuemax="{{ $totalSteps }}" aria-valuenow="{{ $completedSteps }}" class="mt-3 flex h-1.5 overflow-hidden rounded-full bg-slate-100">
                            @foreach (\App\Models\EquipmentInspectionSignature::STEPS as $step)
                                <div @class(['h-full flex-1', 'bg-[#c86432]' => $step <= $completedSteps, 'bg-slate-100' => $step > $completedSteps])></div>
                            @endforeach
                        </div>
                        <p class="mt-2 text-xs leading-5 text-slate-500">Inspektor lalu Manager Workshop</p>
                    </div>
                </div>

                <div class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-600">
                    <i data-lucide="info" class="mt-1 h-4 w-4 shrink-0 text-slate-400"></i>
                    <p>Persetujuan mencatat review laporan, bukan pernyataan alat layak operasi. Kondisi C/Rusak tetap tercatat dan boleh disetujui. Catatan persetujuan opsional; alasan revisi wajib.</p>
                </div>

                <div class="grid gap-4 xl:grid-cols-[minmax(0,1.62fr)_minmax(20rem,0.78fr)]">
                    <section class="min-w-0 overflow-hidden rounded-[1.25rem] border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-4 py-3">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Preview Dokumen</div>
                            <h2 id="activePreviewTitle" class="mt-1 text-lg font-bold text-slate-900">PDF Inspeksi Peralatan</h2>
                        </div>
                        <div class="p-4">
                            @include('approval.partials.pdfjs-preview', [
                                'title' => 'PDF Inspeksi Peralatan',
                                'url' => route('approval.equipment-inspection.pdf', $token),
                            ])
                        </div>
                    </section>

                    <section class="min-w-0 space-y-4">
                        <form id="signatureForm" method="POST" enctype="multipart/form-data" action="{{ route('approval.equipment-inspection.decide', $token) }}" class="space-y-4 rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                            @csrf
                            <input type="hidden" name="decision" value="approve">
                            <input type="hidden" name="document_version" value="{{ $inspection->document_version }}">
                            <input type="file" name="signature_file" id="signatureFile" accept="image/png" class="hidden">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                                <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Tanda Tangan Digital</div>
                                        <h2 class="mt-1 text-lg font-bold text-slate-900">Area Penandatanganan</h2>
                                    </div>
                                    <div class="text-xs text-slate-400">Mouse / layar sentuh didukung</div>
                                </div>
                                <div id="signaturePadShell" class="relative mt-4 overflow-hidden rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 p-3 transition">
                                    <div class="absolute right-3 top-3 z-20 flex flex-wrap justify-end gap-2">
                                        @if ($recentSignatureDataUrl)
                                            <button type="button" id="useRecentSignature" data-signature-src="{{ $recentSignatureDataUrl }}" class="rounded-full border border-[#e2b39a] bg-white/95 px-3 py-1.5 text-[11px] font-semibold text-[#b85b2b] shadow-sm transition hover:bg-orange-50">Gunakan TTD Terakhir</button>
                                        @endif
                                        <button type="button" id="clearSignature" class="rounded-full border border-slate-300 bg-white/95 px-3 py-1.5 text-[11px] font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">Bersihkan</button>
                                    </div>
                                    <canvas id="signatureCanvas" width="620" height="260" class="relative z-10 h-48 w-full touch-none rounded-xl bg-transparent sm:h-56 xl:h-60"></canvas>
                                    <div id="signaturePadPlaceholder" class="pointer-events-none absolute inset-3 z-0 flex items-center justify-center rounded-xl text-center">
                                        <div class="px-4 text-slate-400">
                                            <i data-lucide="pen-line" class="mx-auto h-8 w-8 opacity-70"></i>
                                            <div class="mt-2 text-sm font-bold text-slate-500">Tanda tangan di sini</div>
                                            <div class="mt-1 text-xs font-medium text-slate-400">Gunakan mouse atau layar sentuh</div>
                                        </div>
                                    </div>
                                </div>
                                <p id="signaturePadReadyState" class="mt-2 hidden text-xs font-semibold text-emerald-700">Tanda tangan siap disimpan</p>
                                <p id="signaturePadErrorState" class="mt-2 hidden text-xs font-semibold text-rose-700"></p>

                                <div class="mt-4 rounded-2xl border border-slate-200 bg-white p-4">
                                    <label for="approvalNote" class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Catatan persetujuan (opsional)</label>
                                    <textarea id="approvalNote" name="decision_note" rows="3" maxlength="2000" placeholder="Tulis catatan persetujuan bila diperlukan..." class="mt-3 w-full resize-y rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 focus:border-[#c86432] focus:ring-[#c86432]">{{ old('decision_note') }}</textarea>
                                </div>
                            </div>
                            <label class="flex items-start gap-3 text-sm leading-6 text-slate-600">
                                <input type="checkbox" name="confirmed" value="1" required class="mt-1 rounded border-slate-300 text-[#5b0f1b] focus:ring-[#5b0f1b]">
                                Saya sudah meninjau laporan versi ini dan menyetujuinya.
                            </label>
                            <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                                <i data-lucide="signature" class="h-4 w-4"></i>
                                Simpan Tanda Tangan
                            </button>
                        </form>

                        <form method="POST" action="{{ route('approval.equipment-inspection.decide', $token) }}" class="rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm sm:p-5" data-swal-confirm data-swal-title="Kembalikan untuk revisi?" data-swal-text="Versi laporan ini akan dikunci dan Inspektor harus memperbaiki serta menandatangani ulang." data-swal-confirm-text="Ya, kembalikan">
                            @csrf
                            <input type="hidden" name="decision" value="return">
                            <input type="hidden" name="document_version" value="{{ $inspection->document_version }}">
                            <input type="hidden" name="confirmed" value="1">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Revisi Laporan</div>
                            <h2 class="mt-1 text-lg font-bold text-slate-900">Kembalikan ke Inspektor</h2>
                            <label for="revisionNote" class="mt-4 block text-sm font-semibold text-slate-700">Alasan revisi (wajib)</label>
                            <textarea id="revisionNote" name="decision_note" rows="3" required maxlength="2000" placeholder="Jelaskan bagian laporan yang perlu diperbaiki..." class="mt-2 w-full resize-y rounded-2xl border border-slate-300 px-4 py-3 text-sm placeholder:text-slate-400 focus:border-amber-500 focus:ring-amber-500">{{ old('decision_note') }}</textarea>
                            <button type="submit" class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm font-semibold text-amber-800 transition hover:bg-amber-100">
                                <i data-lucide="undo-2" class="h-4 w-4"></i>
                                Kembalikan untuk Revisi
                            </button>
                        </form>
                    </section>
                </div>

                <a href="{{ route('approval-documents.index') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-slate-600 transition hover:text-[#5b0f1b]">
                    <i data-lucide="arrow-left" class="h-4 w-4"></i>
                    Kembali ke Dokumen Approval
                </a>
            </div>
        </section>
    </main>

    @include('approval.partials.signature-pad-visuals')
    @include('approval.partials.equipment-inspection-signature-script')
</body>
</html>
