<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head', ['title' => 'Approval HPP'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    @php
        $hpp = $signature?->hpp;
        $isRejected = $hpp?->status === \App\Models\Hpp::STATUS_REJECTED;
        $isDirops = $signature?->role_key === 'dirops';
        $isInitialApproval = (bool) ($isInitialApproval ?? false);
        $markTitle = $isInitialApproval ? 'Paraf Digital' : 'Tanda Tangan Digital';
        $markAreaTitle = $isInitialApproval ? 'Area Paraf' : 'Area Penandatanganan';
        $useRecentLabel = $isInitialApproval ? 'Pakai Paraf Terakhir' : 'Pakai TTD Terakhir';
        $placeholderTitle = $isInitialApproval ? 'Paraf di sini' : 'Tanda tangan di sini';
        $readyLabel = $isInitialApproval ? 'Paraf siap disimpan' : 'Tanda tangan siap disimpan';
        $errorLabel = $isInitialApproval ? 'Silakan paraf terlebih dahulu.' : 'Silakan tanda tangan terlebih dahulu.';
        $saveLabel = $isInitialApproval ? 'Simpan Paraf' : 'Simpan Tanda Tangan';
        $canSign = $signature?->isPending() && ! $isExpired && ! $isRejected && ! $isDirops;
        $noteGroupLabel = $signature?->noteGroupLabel() ?? 'Catatan Approval';
        $statusLabel = match (true) {
            ! $signature => 'Token Tidak Valid',
            $isRejected => 'Dokumen Ditolak',
            $signature->isSigned() => $isInitialApproval ? 'Sudah Diparaf' : 'Sudah Ditandatangani',
            $signature->isLocked() => 'Step Belum Aktif',
            $isExpired => 'Token Kedaluwarsa',
            $isDirops && $signature->isPending() => 'Menunggu Upload DIROPS',
            default => $isInitialApproval ? 'Menunggu Paraf' : 'Menunggu Tanda Tangan',
        };
        $statusClasses = match (true) {
            ! $signature => 'bg-rose-100 text-rose-700 ring-rose-200',
            $isRejected => 'bg-rose-100 text-rose-700 ring-rose-200',
            $signature->isSigned() => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
            $signature->isLocked() => 'bg-slate-100 text-slate-700 ring-slate-200',
            $isExpired => 'bg-amber-100 text-amber-700 ring-amber-200',
            $isDirops => 'bg-orange-100 text-orange-700 ring-orange-200',
            default => 'bg-blue-100 text-blue-700 ring-blue-200',
        };
    @endphp

    <main class="mx-auto w-full px-1.5 py-4 sm:px-3 lg:px-4">
        <section class="overflow-hidden rounded-[1.6rem] border border-slate-200 bg-white shadow-[0_18px_48px_rgba(15,23,42,0.10)]">
            <div class="border-b-4 border-[#8f1d2c] bg-[#5b0f1b] px-5 py-6 text-white sm:px-8 sm:py-7">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                    <div class="min-w-0">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.28em] text-white/70">
                            HPP Digital Approval
                        </div>
                        <h1 class="mt-3 break-words text-2xl font-bold tracking-tight sm:text-3xl">
                            {{ $hpp?->nama_pekerjaan ?: ($hpp?->approval_case ?: 'Approval HPP') }}
                        </h1>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-white/75">
                            Halaman approval bertoken ini hanya dapat digunakan oleh akun penanda tangan yang ditetapkan.
                        </p>
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
            </div>

            <div class="space-y-5 px-3 py-4 sm:px-5 sm:py-5">
                @if (session('status'))
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                @if (! $signature)
                    <div class="rounded-[1.75rem] border border-rose-200 bg-rose-50 p-6 text-center">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-white text-rose-700 ring-1 ring-rose-200">
                            <span class="text-xl font-bold">!</span>
                        </div>
                        <h2 class="mt-4 text-xl font-bold text-rose-950">Token approval tidak valid</h2>
                        <p class="mx-auto mt-2 max-w-xl text-sm leading-6 text-rose-700">
                            Link ini tidak terdaftar pada approval HPP aktif. Gunakan link terbaru dari halaman Create HPP.
                        </p>
                    </div>
                @else
                    <div class="rounded-[1.2rem] border border-slate-200 bg-slate-50 p-3 sm:p-4">
                        <div class="grid gap-3 lg:grid-cols-2 xl:grid-cols-5">
                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Penanda Tangan</div>
                                <div class="mt-2 break-words text-sm font-bold text-slate-900">{{ $signature->signer_name_snapshot }}</div>
                                <div class="mt-1 text-sm leading-5 text-slate-600">{{ $signature->acting_as_label ?: $signature->signer_position_snapshot }}</div>
                                <span class="mt-3 inline-flex w-fit rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $statusClasses }}">
                                    {{ $statusLabel }}
                                </span>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                                <div class="space-y-4">
                                    <div class="flex items-start gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500 ring-1 ring-slate-200">
                                            <i data-lucide="hash" class="h-4 w-4"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Nomor Order</div>
                                            <div class="mt-1 break-words text-sm font-bold text-slate-900">{{ $hpp?->nomor_order ?: '-' }}</div>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100">
                                            <i data-lucide="bell" class="h-4 w-4"></i>
                                        </span>
                                        <div class="min-w-0">
                                            <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Notifikasi</div>
                                            <div class="mt-1 break-words text-sm font-bold text-slate-900">{{ $hpp?->order?->notifikasi ?: '-' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 lg:col-span-2 xl:col-span-2">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Pekerjaan</div>
                                <div class="mt-2 break-words text-sm font-bold text-slate-900">{{ $hpp?->nama_pekerjaan ?: '-' }}</div>
                                <div class="mt-3 flex flex-wrap gap-2 text-[11px] font-semibold">
                                    <span class="inline-flex max-w-full items-center gap-1.5 rounded-full bg-blue-50 px-2.5 py-1 text-blue-700 ring-1 ring-blue-100">
                                        <i data-lucide="building-2" class="h-3.5 w-3.5 shrink-0"></i>
                                        <span class="min-w-0 break-words">Unit: {{ $hpp?->unit_kerja ?: '-' }}</span>
                                    </span>
                                    <span class="inline-flex max-w-full items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-slate-600 ring-1 ring-slate-200">
                                        <i data-lucide="network" class="h-3.5 w-3.5 shrink-0"></i>
                                        <span class="min-w-0 break-words">Seksi: {{ $hpp?->order?->seksi ?: '-' }}</span>
                                    </span>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Step</div>
                                <div class="mt-2 text-sm font-bold text-slate-900">
                                    {{ $signature->step_order }} dari {{ $totalSteps }}
                                </div>
                                <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-blue-600" style="width: {{ $progressPercent }}%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-4 xl:grid-cols-[minmax(0,1.62fr)_minmax(20rem,0.78fr)]">
                        <div class="min-w-0 overflow-hidden rounded-[1.25rem] border border-slate-200 bg-white shadow-sm">
                            <div class="flex flex-col gap-3 border-b border-slate-200 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Preview Dokumen</div>
                                    <h2 class="mt-1 text-lg font-bold text-slate-900">Preview PDF HPP</h2>
                                </div>

                                <div class="flex flex-wrap items-center gap-2">
                                    <button
                                        type="button"
                                        class="preview-tab-btn rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 transition"
                                        data-preview-target="hpp"
                                    >
                                        HPP
                                    </button>
                                    <button
                                        type="button"
                                        class="preview-tab-btn rounded-xl border px-3 py-2 text-xs font-semibold transition {{ $abnormalitasUrl ? 'border-transparent text-slate-600 hover:bg-rose-50 hover:text-rose-700' : 'cursor-not-allowed border-slate-200 bg-slate-50 text-slate-400' }}"
                                        data-preview-target="abnormalitas"
                                        @disabled(! $abnormalitasUrl)
                                    >
                                        Abnormalitas
                                    </button>
                                    <button
                                        type="button"
                                        class="preview-tab-btn rounded-xl border px-3 py-2 text-xs font-semibold transition {{ $gambarTeknikUrl ? 'border-transparent text-slate-600 hover:bg-sky-50 hover:text-sky-700' : 'cursor-not-allowed border-slate-200 bg-slate-50 text-slate-400' }}"
                                        data-preview-target="gambar-teknik"
                                        @disabled(! $gambarTeknikUrl)
                                    >
                                        Gambar Teknik
                                    </button>
                                </div>
                            </div>

                            <div class="p-4">
                                @include('approval.partials.pdfjs-preview', [
                                    'title' => 'Preview PDF HPP',
                                    'url' => $hppPdfUrl,
                                ])
                            </div>
                        </div>

                        <div class="min-w-0 space-y-4">
                            @if ($signature->isSigned())
                                <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                                    <div class="flex min-h-[18rem] flex-col items-center justify-center text-center">
                                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                            <span class="text-lg font-bold">OK</span>
                                        </div>
                                        <h2 class="mt-4 text-xl font-bold text-slate-900">Tanda tangan tersimpan</h2>
                                        <p class="mt-2 max-w-sm text-sm leading-6 text-slate-500">
                                            Dokumen ditandatangani pada {{ optional($signature->signed_at)->format('d/m/Y H:i') }}.
                                        </p>
                                        @if ($signature->approval_note)
                                            <div class="mt-4 w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-left">
                                                <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">{{ $noteGroupLabel }}</div>
                                                <div class="mt-2 text-sm leading-6 text-slate-700">{{ $signature->approval_note }}</div>
                                                <div class="mt-2 text-xs text-slate-500">oleh {{ $signature->signer_name_snapshot }}</div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @elseif ($isRejected)
                                <div class="rounded-[1.75rem] border border-rose-200 bg-white p-5 shadow-sm">
                                    <div class="flex min-h-[18rem] flex-col items-center justify-center text-center">
                                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-rose-100 text-rose-700">
                                            <i data-lucide="octagon-x" class="h-7 w-7"></i>
                                        </div>
                                        <h2 class="mt-4 text-xl font-bold text-slate-900">Dokumen HPP ditolak</h2>
                                        <p class="mt-2 max-w-sm text-sm leading-6 text-slate-500">
                                            HPP ini sudah direject oleh approver. Admin perlu menghapus dokumen ini lalu membuat ulang pengajuan.
                                        </p>
                                        @if ($signature->approval_note)
                                            <div class="mt-4 w-full rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-left">
                                                <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-rose-500">{{ $noteGroupLabel }}</div>
                                                <div class="mt-2 text-sm leading-6 text-slate-700">{{ $signature->approval_note }}</div>
                                                <div class="mt-2 text-xs text-slate-500">oleh {{ $signature->signer_name_snapshot }}</div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @elseif ($signature->isLocked())
                                <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                                    <div class="flex min-h-[18rem] flex-col items-center justify-center text-center">
                                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-700">
                                            <i data-lucide="lock" class="h-7 w-7"></i>
                                        </div>
                                        <h2 class="mt-4 text-xl font-bold text-slate-900">Step belum aktif</h2>
                                        <p class="mt-2 max-w-sm text-sm leading-6 text-slate-500">
                                            Tunggu step approval sebelumnya selesai terlebih dahulu.
                                        </p>
                                    </div>
                                </div>
                            @elseif ($isExpired)
                                <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                                    <div class="flex min-h-[18rem] flex-col items-center justify-center text-center">
                                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                                            <span class="text-2xl font-bold">!</span>
                                        </div>
                                        <h2 class="mt-4 text-xl font-bold text-slate-900">Token kedaluwarsa</h2>
                                        <p class="mt-2 max-w-sm text-sm leading-6 text-slate-500">
                                            Token berlaku sampai {{ optional($signature->token_expires_at)->format('d/m/Y H:i') }}.
                                        </p>
                                    </div>
                                </div>
                            @elseif ($isDirops)
                                <div class="rounded-[1.75rem] border border-orange-200 bg-white p-5 shadow-sm">
                                    <div class="flex min-h-[18rem] flex-col items-center justify-center text-center">
                                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-orange-100 text-orange-700">
                                            <i data-lucide="upload" class="h-7 w-7"></i>
                                        </div>
                                        <h2 class="mt-4 text-xl font-bold text-slate-900">Menunggu dokumen final DIROPS</h2>
                                        <p class="mt-2 max-w-sm text-sm leading-6 text-slate-500">
                                            Tahap DIROPS diselesaikan oleh admin melalui upload dokumen HPP final yang sudah ditandatangani.
                                        </p>
                                    </div>
                                </div>
                            @else
                                <div class="rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                                    <form method="POST" action="{{ route('approval.hpp.sign', $token) }}" id="signatureForm" enctype="multipart/form-data" class="space-y-4">
                                        @csrf
                                        <input type="hidden" name="approval_action" id="approvalAction" value="sign">
                                        <input type="file" name="signature_file" id="signatureFile" accept="image/png,image/jpeg" class="hidden">

                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                                <div>
                                                    <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">{{ $markTitle }}</div>
                                                    <h2 class="mt-1 text-lg font-bold text-slate-900">{{ $markAreaTitle }}</h2>
                                                </div>
                                                <div class="text-xs text-slate-400">Mouse / touch screen didukung</div>
                                            </div>

                                            <div id="signaturePadShell" class="relative mt-4 overflow-hidden rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 p-3 transition">
                                                <div class="absolute right-3 top-3 z-20 flex flex-wrap justify-end gap-2">
                                                    @if ($canSign && !empty($recentSignatureDataUrl ?? null))
                                                        <button type="button" id="useRecentSignature" data-signature-src="{{ $recentSignatureDataUrl }}" class="rounded-full border border-blue-200 bg-white/95 px-3 py-1.5 text-[11px] font-semibold text-blue-700 shadow-sm transition hover:bg-blue-50">
                                                            {{ $useRecentLabel }}
                                                        </button>
                                                    @endif
                                                    <button type="button" id="clearSignature" class="rounded-full border border-slate-300 bg-white/95 px-3 py-1.5 text-[11px] font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50">
                                                        Clear
                                                    </button>
                                                </div>
                                                <canvas id="signatureCanvas" width="620" height="260" class="relative z-10 h-48 w-full rounded-xl bg-transparent sm:h-56 xl:h-60"></canvas>
                                                <div id="signaturePadPlaceholder" class="pointer-events-none absolute inset-3 z-0 flex items-center justify-center rounded-xl text-center">
                                                    <div class="px-4 text-slate-400">
                                                        <i data-lucide="pen-line" class="mx-auto h-8 w-8 opacity-70"></i>
                                                        <div class="mt-2 text-sm font-bold text-slate-500">{{ $placeholderTitle }}</div>
                                                        <div class="mt-1 text-xs font-medium text-slate-400">Gunakan mouse atau layar sentuh</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <p id="signaturePadReadyState" class="mt-2 hidden text-xs font-semibold text-emerald-700">{{ $readyLabel }}</p>
                                            <p id="signaturePadErrorState" class="mt-2 hidden text-xs font-semibold text-rose-700">{{ $errorLabel }}</p>

                                            <div class="mt-4 rounded-2xl border border-slate-200 bg-white px-4 py-4">
                                                <label for="approvalNote" class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">{{ $noteGroupLabel }}</label>
                                                <textarea
                                                    id="approvalNote"
                                                    name="approval_note"
                                                    rows="4"
                                                    maxlength="2000"
                                                    class="mt-3 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-blue-500 focus:outline-none"
                                                    placeholder="Tulis catatan approval bila diperlukan..."
                                                >{{ old('approval_note', $signature->approval_note) }}</textarea>
                                            </div>

                                            <div class="flex flex-col gap-2 pt-4 sm:flex-row sm:justify-end">
                                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                                    <button type="submit" data-action="reject" class="rounded-xl bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700">
                                                        Reject
                                                    </button>
                                                    <button type="submit" data-action="sign" class="rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">
                                                        {{ $saveLabel }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </main>

    @include('approval.partials.signed-success-alert')
    @include('approval.partials.submission-loading-overlay')
    @include('approval.partials.signature-pad-visuals')

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const previewConfig = {
                hpp: {
                    title: 'Preview PDF HPP',
                    url: @json($hppPdfUrl),
                    activeBtn: 'border-blue-200 bg-blue-50 text-blue-700',
                    inactiveBtn: 'border-transparent text-slate-600 hover:bg-blue-50 hover:text-blue-700',
                },
                abnormalitas: {
                    title: 'Preview Abnormalitas',
                    url: @json($abnormalitasUrl),
                    activeBtn: 'border-rose-200 bg-rose-50 text-rose-700',
                    inactiveBtn: 'border-transparent text-slate-600 hover:bg-rose-50 hover:text-rose-700',
                    unavailableBtn: 'cursor-not-allowed border-slate-200 bg-slate-50 text-slate-400',
                },
                'gambar-teknik': {
                    title: 'Preview Gambar Teknik',
                    url: @json($gambarTeknikUrl),
                    activeBtn: 'border-sky-200 bg-sky-50 text-sky-700',
                    inactiveBtn: 'border-transparent text-slate-600 hover:bg-sky-50 hover:text-sky-700',
                    unavailableBtn: 'cursor-not-allowed border-slate-200 bg-slate-50 text-slate-400',
                },
            };

            const previewTitle = document.getElementById('activePreviewTitle');
            const previewOpen = document.getElementById('activePreviewOpen');
            const previewButtons = document.querySelectorAll('.preview-tab-btn');

            const setActivePreview = (key) => {
                const config = previewConfig[key];

                if (!config || !config.url) {
                    return;
                }

                if (previewTitle) {
                    previewTitle.textContent = config.title;
                }

                if (previewOpen) {
                    previewOpen.href = config.url;
                }

                window.approvalPdfPreview?.load(config.title, config.url);

                previewButtons.forEach((button) => {
                    const target = button.dataset.previewTarget;
                    const targetConfig = previewConfig[target];
                    const isActive = target === key;

                    if (!targetConfig?.url) {
                        button.disabled = true;
                        button.className = `preview-tab-btn rounded-xl border px-3 py-2 text-xs font-semibold transition ${targetConfig?.unavailableBtn ?? 'cursor-not-allowed border-slate-200 bg-slate-50 text-slate-400'}`;

                        return;
                    }

                    button.disabled = false;
                    button.className = `preview-tab-btn rounded-xl border px-3 py-2 text-xs font-semibold transition ${isActive ? config.activeBtn : targetConfig.inactiveBtn}`;
                });
            };

            previewButtons.forEach((button) => {
                button.addEventListener('click', () => setActivePreview(button.dataset.previewTarget));
            });

            @if ($canSign)
                const canvas = document.getElementById('signatureCanvas');
                const form = document.getElementById('signatureForm');
                const signatureFile = document.getElementById('signatureFile');
                const approvalAction = document.getElementById('approvalAction');
                const clearButton = document.getElementById('clearSignature');
                const useRecentButton = document.getElementById('useRecentSignature');
                const approvalNote = document.getElementById('approvalNote');
                const loadingOverlay = document.getElementById('submissionLoadingOverlay');
                const loadingTitle = document.getElementById('submissionLoadingTitle');
                const signatureVisuals = window.createSignaturePadVisuals?.();
                const markErrorLabel = @json($errorLabel);
                const markName = @json($isInitialApproval ? 'paraf' : 'tanda tangan');
                signatureVisuals?.idle();

                if (canvas && form && signatureFile && approvalAction) {
                    const context = canvas.getContext('2d');
                    let drawing = false;
                    let hasStroke = false;
                    let preparedSubmit = false;
                    let lastPoint = null;
                    let strokePointCount = 0;
                    let strokeDistance = 0;

                    const minimumStrokePoints = 8;
                    const minimumStrokeDistance = 40;

                    const setupCanvasStyle = () => {
                        context.lineWidth = 2.4;
                        context.lineCap = 'round';
                        context.lineJoin = 'round';
                        context.strokeStyle = '#0f172a';
                    };

                    const resizeCanvas = () => {
                        const ratio = Math.max(window.devicePixelRatio || 1, 1);
                        const rect = canvas.getBoundingClientRect();
                        const imageData = hasStroke ? canvas.toDataURL('image/png') : null;

                        canvas.width = rect.width * ratio;
                        canvas.height = rect.height * ratio;
                        context.setTransform(1, 0, 0, 1, 0, 0);
                        context.scale(ratio, ratio);
                        setupCanvasStyle();

                        if (imageData) {
                            const image = new Image();
                            image.onload = () => context.drawImage(image, 0, 0, rect.width, rect.height);
                            image.src = imageData;
                        }
                    };

                    const clearCanvas = () => {
                        context.clearRect(0, 0, canvas.width, canvas.height);
                        setupCanvasStyle();
                    };

                    const drawImageToCanvas = (image) => {
                        const rect = canvas.getBoundingClientRect();
                        const maxWidth = rect.width * 0.8;
                        const maxHeight = rect.height * 0.75;
                        const scale = Math.min(maxWidth / image.naturalWidth, maxHeight / image.naturalHeight);
                        const width = image.naturalWidth * scale;
                        const height = image.naturalHeight * scale;
                        const x = (rect.width - width) / 2;
                        const y = (rect.height - height) / 2;

                        clearCanvas();
                        context.drawImage(image, x, y, width, height);
                    };

                    const loadRecentSignature = () => {
                        const src = useRecentButton?.dataset.signatureSrc;

                        if (!src) {
                            return;
                        }

                        const image = new Image();
                        image.onload = () => {
                            drawImageToCanvas(image);
                            hasStroke = true;
                            lastPoint = null;
                            strokePointCount = minimumStrokePoints;
                            strokeDistance = minimumStrokeDistance;
                            signatureFile.value = '';
                            signatureVisuals?.completed();
                        };
                        image.onerror = () => {
                            alert(`${markName === 'paraf' ? 'Paraf' : 'TTD'} terakhir tidak dapat dimuat. Silakan ${markName} ulang.`);
                        };
                        image.src = src;
                    };

                    const getPoint = (event) => {
                        const rect = canvas.getBoundingClientRect();
                        const source = event.touches?.[0] || event;

                        return {
                            x: source.clientX - rect.left,
                            y: source.clientY - rect.top,
                        };
                    };

                    const startDrawing = (event) => {
                        event.preventDefault();
                        drawing = true;
                        signatureVisuals?.active();
                        const point = getPoint(event);
                        lastPoint = point;
                        context.beginPath();
                        context.moveTo(point.x, point.y);
                    };

                    const draw = (event) => {
                        if (!drawing) return;

                        event.preventDefault();
                        const point = getPoint(event);
                        if (lastPoint) {
                            strokeDistance += Math.hypot(point.x - lastPoint.x, point.y - lastPoint.y);
                        }
                        strokePointCount++;
                        lastPoint = point;
                        context.lineTo(point.x, point.y);
                        context.stroke();
                        hasStroke = true;
                    };

                    const stopDrawing = () => {
                        drawing = false;
                        lastPoint = null;

                        if (hasStroke) {
                            signatureVisuals?.completed();
                        }
                    };

                    resizeCanvas();
                    window.addEventListener('resize', resizeCanvas);
                    canvas.addEventListener('mousedown', startDrawing);
                    canvas.addEventListener('mousemove', draw);
                    window.addEventListener('mouseup', stopDrawing);
                    canvas.addEventListener('touchstart', startDrawing, { passive: false });
                    canvas.addEventListener('touchmove', draw, { passive: false });
                    canvas.addEventListener('touchend', stopDrawing);

                    clearButton?.addEventListener('click', () => {
                        clearCanvas();
                        hasStroke = false;
                        lastPoint = null;
                        strokePointCount = 0;
                        strokeDistance = 0;
                        signatureFile.value = '';
                        signatureVisuals?.idle();
                    });

                    useRecentButton?.addEventListener('click', loadRecentSignature);

                    const hasEnoughSignatureStroke = () => {
                        return hasStroke
                            && strokePointCount >= minimumStrokePoints
                            && strokeDistance >= minimumStrokeDistance;
                    };

                    const beginSubmission = (action) => {
                        preparedSubmit = true;
                        form.setAttribute('aria-busy', 'true');

                        form.querySelectorAll('button').forEach((button) => {
                            button.disabled = true;
                            button.classList.add('cursor-wait', 'opacity-60');
                        });

                        if (loadingTitle) {
                            loadingTitle.textContent = action === 'reject'
                                ? 'Memproses penolakan...'
                                : `Menyimpan ${markName}...`;
                        }

                        loadingOverlay?.classList.remove('hidden');
                        loadingOverlay?.classList.add('flex');
                        loadingOverlay?.setAttribute('aria-hidden', 'false');
                    };

                    const cancelSubmission = () => {
                        preparedSubmit = false;
                        form.removeAttribute('aria-busy');

                        form.querySelectorAll('button').forEach((button) => {
                            button.disabled = false;
                            button.classList.remove('cursor-wait', 'opacity-60');
                        });

                        loadingOverlay?.classList.add('hidden');
                        loadingOverlay?.classList.remove('flex');
                        loadingOverlay?.setAttribute('aria-hidden', 'true');
                    };

                    form.addEventListener('submit', async (event) => {
                        if (preparedSubmit) {
                            event.preventDefault();
                            return;
                        }

                        const action = event.submitter?.dataset.action || 'sign';
                        approvalAction.value = action;

                        if (action === 'reject') {
                            if (!approvalNote?.value.trim()) {
                                event.preventDefault();
                                alert('Silakan isi alasan reject terlebih dahulu.');
                                return;
                            }

                            beginSubmission(action);
                            return;
                        }

                        if (!hasEnoughSignatureStroke()) {
                            event.preventDefault();
                            const message = hasStroke
                                ? `${markName === 'paraf' ? 'Paraf' : 'Tanda tangan'} terlalu sedikit. Silakan buat coretan yang jelas.`
                                : markErrorLabel;
                            signatureVisuals?.error(message);
                            alert(message);
                            return;
                        }

                        event.preventDefault();
                        beginSubmission(action);
                        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));

                        if (!blob) {
                            cancelSubmission();
                            alert(`${markName === 'paraf' ? 'Paraf' : 'Tanda tangan'} belum terbaca. Silakan ulangi.`);
                            return;
                        }

                        const transfer = new DataTransfer();
                        transfer.items.add(new File([blob], 'signature.png', { type: 'image/png' }));
                        signatureFile.files = transfer.files;
                        form.submit();
                    });
                }
            @endif
        });
    </script>
</body>
</html>
