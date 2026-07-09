<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head', ['title' => 'Approval Initial Work'])
</head>
<body class="min-h-screen bg-[#f4f1ea] text-slate-900">
    @php
        $initialWork = $signature->initialWork;
        $order = $initialWork->order;
        $canSign = $signature->isPending() && ! $isExpired;
        $priorityLabel = $order?->priorityLabel() ?: '-';
        $priorityGroup = \App\Models\Order::priorityPrimaryFor($order?->prioritas);
        $priorityBadgeClasses = match ($priorityGroup) {
            'emergency' => 'bg-rose-100 text-rose-700 ring-rose-200',
            'high' => 'bg-amber-100 text-amber-700 ring-amber-200',
            default => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
        };
        $initialWorkTotalSteps = $initialWork->approvalStepCount();
        $initialWorkProgressPercent = $initialWork->approvalProgressPercent();
        $statusLabel = $signature->isSigned()
            ? 'Sudah Ditandatangani'
            : ($isExpired ? 'Token Kedaluwarsa' : 'Menunggu Tanda Tangan');
        $statusClasses = $signature->isSigned()
            ? 'bg-emerald-100 text-emerald-700 ring-emerald-200'
            : ($isExpired ? 'bg-amber-100 text-amber-700 ring-amber-200' : 'bg-blue-100 text-blue-700 ring-blue-200');
    @endphp

    <main class="mx-auto w-full max-w-[90rem] px-4 py-5 sm:px-5 2xl:max-w-[96rem]">
        <section class="overflow-hidden rounded-[2rem] border border-stone-200 bg-white shadow-[0_20px_60px_rgba(28,25,23,0.10)]">
            <div class="border-b-4 border-[#8f1d2c] bg-[#5b0f1b] px-5 py-6 text-white sm:px-8 sm:py-7">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                    <div class="min-w-0">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.28em] text-white/70">
                            Initial Work Digital Approval
                        </div>

                        <h1 class="mt-3 break-words text-2xl font-bold tracking-tight sm:text-3xl">
                            {{ $initialWork?->nama_pekerjaan ?: ($initialWork?->nomor_initial_work ?: 'Approval Initial Work') }}
                        </h1>

                        <p class="mt-2 max-w-2xl text-sm leading-6 text-white/75">
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

            <div class="space-y-5 px-5 py-5 sm:px-8 sm:py-6">
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

                {{-- Info utama --}}
                <div class="rounded-[1.2rem] border border-stone-200 bg-stone-50 p-3 sm:p-4">
                    <div class="grid gap-3 lg:grid-cols-2 xl:grid-cols-5">
                        <div class="rounded-2xl border border-stone-200 bg-white px-4 py-4">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-stone-400">Penanda Tangan</div>
                            <div class="mt-2 break-words text-sm font-bold text-slate-900">{{ $signature->signer_name }}</div>
                            <div class="mt-1 text-sm leading-5 text-slate-600">{{ $signature->displayRoleLabel() }}</div>
                            <span class="mt-3 inline-flex w-fit rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $statusClasses }}">
                                {{ $statusLabel }}
                            </span>
                        </div>

                        <div class="rounded-2xl border border-stone-200 bg-white px-4 py-4">
                            <div class="space-y-4">
                                <div class="flex items-start gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-stone-100 text-stone-500 ring-1 ring-stone-200">
                                        <i data-lucide="hash" class="h-4 w-4"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-stone-400">Nomor Order</div>
                                        <div class="mt-1 break-words text-sm font-bold text-slate-900">{{ $initialWork->nomor_order ?: '-' }}</div>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100">
                                        <i data-lucide="bell" class="h-4 w-4"></i>
                                    </span>
                                    <div class="min-w-0">
                                        <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-stone-400">Notifikasi</div>
                                        <div class="mt-1 break-words text-sm font-bold text-slate-900">{{ $initialWork->notifikasi ?: ($order?->notifikasi ?: '-') }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-stone-200 bg-white px-4 py-4 lg:col-span-2 xl:col-span-2">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-stone-400">Pekerjaan</div>
                            <div class="mt-2 break-words text-sm font-bold text-slate-900">
                                {{ $initialWork->nama_pekerjaan ?: '-' }}
                            </div>
                            <div class="mt-3 flex flex-wrap items-center gap-2">
                                <span class="inline-flex max-w-full items-center gap-1.5 rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-700 ring-1 ring-blue-100">
                                    <i data-lucide="building-2" class="h-3.5 w-3.5 shrink-0"></i>
                                    <span class="min-w-0 break-words">Unit: {{ $initialWork->unit_kerja ?: ($order?->unit_kerja ?: '-') }}</span>
                                </span>
                                <span class="inline-flex max-w-full items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold text-slate-600 ring-1 ring-slate-200">
                                    <i data-lucide="network" class="h-3.5 w-3.5 shrink-0"></i>
                                    <span class="min-w-0 break-words">Seksi: {{ $initialWork->seksi ?: ($order?->seksi ?: '-') }}</span>
                                </span>
                            </div>
                        </div>

                        <div class="rounded-2xl border border-stone-200 bg-white px-4 py-4">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-stone-400">Step</div>
                            <div class="mt-2 text-sm font-bold text-slate-900">
                                {{ $signature->step_order }} dari {{ $initialWorkTotalSteps }}
                            </div>
                            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-stone-100">
                                <div class="h-full rounded-full bg-blue-600" style="width: {{ $initialWorkProgressPercent }}%"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Preview + TTD --}}
                <div class="grid gap-4 xl:grid-cols-[minmax(0,1.62fr)_minmax(20rem,0.78fr)]">
                    {{-- Preview Dokumen --}}
                    <div class="min-w-0 overflow-hidden rounded-[1.25rem] border border-stone-200 bg-white shadow-sm">
                        <div class="border-b border-stone-200 px-4 py-3">
                            <div class="flex flex-col gap-3 xl:flex-row xl:items-center xl:justify-between">
                                <div>
                                    <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-stone-400">
                                        Preview Dokumen
                                    </div>
                                    <h2 class="mt-1 text-lg font-bold text-slate-900">
                                        Preview PDF Initial Work
                                    </h2>
                                    <p class="mt-1 text-sm text-slate-500">
                                        Pilih dokumen yang ingin ditinjau sebelum melakukan penandatanganan.
                                    </p>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <button
                                        type="button"
                                        class="preview-tab-btn rounded-xl border px-3 py-2 text-xs font-semibold transition {{ $abnormalitasUrl ? 'border-transparent text-slate-600 hover:bg-rose-50 hover:text-rose-700' : 'cursor-not-allowed border-stone-200 bg-stone-50 text-stone-400' }}"
                                        data-preview-target="abnormalitas"
                                        @disabled(! $abnormalitasUrl)
                                    >
                                        Abnormalitas
                                    </button>

                                    <button
                                        type="button"
                                        class="preview-tab-btn rounded-xl border px-3 py-2 text-xs font-semibold transition {{ $gambarTeknikUrl ? 'border-transparent text-slate-600 hover:bg-blue-50 hover:text-blue-700' : 'cursor-not-allowed border-stone-200 bg-stone-50 text-stone-400' }}"
                                        data-preview-target="gambar-teknik"
                                        @disabled(! $gambarTeknikUrl)
                                    >
                                        Gambar Teknik
                                    </button>

                                    <button
                                        type="button"
                                        class="preview-tab-btn rounded-xl border border-orange-100 bg-orange-50 px-3 py-2 text-xs font-semibold text-orange-700 transition"
                                        data-preview-target="initial-work"
                                    >
                                        PDF Initial Work
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="p-4">
                            @include('approval.partials.pdfjs-preview', [
                                'title' => 'Preview PDF Initial Work',
                                'url' => $initialWorkPdfUrl,
                            ])
                        </div>
                    </div>

                    {{-- Kolom tanda tangan --}}
                    <div class="min-w-0 space-y-4">
                        @if ($signature->isSigned())
                            <div class="rounded-[1.75rem] border border-stone-200 bg-[#fbfaf7] p-5 shadow-sm">
                                <div class="flex min-h-[18rem] flex-col items-center justify-center text-center">
                                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                                        <span class="text-lg font-bold">OK</span>
                                    </div>
                                    <h2 class="mt-4 text-xl font-bold text-slate-900">Tanda tangan tersimpan</h2>
                                    <p class="mt-2 max-w-sm text-sm leading-6 text-slate-500">
                                        Dokumen telah ditandatangani pada
                                        {{ optional($signature->signed_at)->format('d/m/Y H:i') }}.
                                    </p>
                                </div>
                            </div>
                        @elseif ($isExpired)
                            <div class="rounded-[1.75rem] border border-stone-200 bg-[#fbfaf7] p-5 shadow-sm">
                                <div class="flex min-h-[18rem] flex-col items-center justify-center text-center">
                                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                                        <span class="text-2xl font-bold">!</span>
                                    </div>
                                    <h2 class="mt-4 text-xl font-bold text-slate-900">Token kedaluwarsa</h2>
                                    <p class="mt-2 max-w-sm text-sm leading-6 text-slate-500">
                                        Token ini berlaku sampai
                                        {{ optional($signature->token_expires_at)->format('d/m/Y H:i') }}.
                                    </p>
                                </div>
                            </div>
                        @else
                            <div class="rounded-[1.75rem] border border-stone-200 bg-[#fbfaf7] p-4 shadow-sm sm:p-5">
                                <form method="POST" action="{{ route('approval.initial-work.sign', $token) }}" id="signatureForm" enctype="multipart/form-data" class="space-y-4">
                                    @csrf
                                    <input type="file" name="signature_file" id="signatureFile" accept="image/png,image/jpeg" class="hidden">

                                    <div class="rounded-2xl border border-stone-200 bg-white px-4 py-4">
                                        <div class="flex flex-col gap-2">
                                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                                <div>
                                                    <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-stone-400">
                                                        Tanda Tangan Digital
                                                    </div>
                                                    <h2 class="mt-1 text-lg font-bold text-slate-900">
                                                        Area Penandatanganan
                                                    </h2>
                                                    <p class="mt-1 text-sm leading-6 text-slate-500">
                                                        Gunakan mouse atau touch screen, lalu simpan tanda tangan setelah selesai.
                                                    </p>
                                                </div>

                                                <div class="text-xs text-slate-400">
                                                    Mouse / touch screen didukung
                                                </div>
                                            </div>

                                            <div id="signaturePadShell" class="relative mt-2 overflow-hidden rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 p-3 transition">
                                                <div class="absolute right-3 top-3 z-20 flex flex-wrap justify-end gap-2">
                                                    @if ($canSign && !empty($recentSignatureDataUrl ?? null))
                                                        <button type="button" id="useRecentSignature" data-signature-src="{{ $recentSignatureDataUrl }}" class="rounded-full border border-blue-200 bg-white/95 px-3 py-1.5 text-[11px] font-semibold text-blue-700 shadow-sm transition hover:bg-blue-50">
                                                            Pakai TTD Terakhir
                                                        </button>
                                                    @endif
                                                    <button type="button" id="clearSignature" class="rounded-full border border-stone-300 bg-white/95 px-3 py-1.5 text-[11px] font-semibold text-slate-700 shadow-sm transition hover:bg-stone-50">
                                                        Clear
                                                    </button>
                                                </div>
                                                <canvas
                                                    id="signatureCanvas"
                                                    width="620"
                                                    height="260"
                                                    class="relative z-10 h-48 w-full rounded-xl bg-transparent sm:h-56 xl:h-60"
                                                ></canvas>
                                                <div id="signaturePadPlaceholder" class="pointer-events-none absolute inset-3 z-0 flex items-center justify-center rounded-xl text-center">
                                                    <div class="px-4 text-slate-400">
                                                        <i data-lucide="pen-line" class="mx-auto h-8 w-8 opacity-70"></i>
                                                        <div class="mt-2 text-sm font-bold text-slate-500">Tanda tangan di sini</div>
                                                        <div class="mt-1 text-xs font-medium text-slate-400">Gunakan mouse atau layar sentuh</div>
                                                    </div>
                                                </div>
                                            </div>
                                            <p id="signaturePadReadyState" class="mt-2 hidden text-xs font-semibold text-emerald-700">Tanda tangan siap disimpan</p>
                                            <p id="signaturePadErrorState" class="mt-2 hidden text-xs font-semibold text-rose-700">Silakan tanda tangan terlebih dahulu.</p>

                                            <div class="flex flex-col gap-2 pt-1 sm:flex-row sm:justify-end">
                                                <button
                                                    type="submit"
                                                    class="rounded-xl bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
                                                >
                                                    Simpan Tanda Tangan
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        @endif

                        <div class="rounded-[1.75rem] border border-stone-200 bg-white p-4 shadow-sm sm:p-5">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-stone-400">
                                Catatan
                            </div>
                            <ul class="mt-3 space-y-2 text-sm leading-6 text-slate-600">
                                <li>Pastikan dokumen yang dipreview sudah sesuai sebelum tanda tangan.</li>
                                <li>Tanda tangan yang disimpan akan langsung tercatat pada approval ini.</li>
                                <li>Gunakan tombol <span class="font-semibold text-slate-800">Clear</span> jika ingin mengulang tanda tangan.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    @include('approval.partials.signed-success-alert')
    @include('approval.partials.signature-pad-visuals')

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const previewConfig = {
                abnormalitas: {
                    title: 'Preview Abnormalitas',
                    url: @json($abnormalitasUrl),
                    activeBtn: 'bg-rose-50 text-rose-700 border-rose-100',
                    inactiveBtn: 'text-slate-600 hover:bg-rose-50 hover:text-rose-700 border-transparent',
                    unavailableBtn: 'cursor-not-allowed border-stone-200 bg-stone-50 text-stone-400',
                },
                'gambar-teknik': {
                    title: 'Preview Gambar Teknik',
                    url: @json($gambarTeknikUrl),
                    activeBtn: 'bg-blue-50 text-blue-700 border-blue-100',
                    inactiveBtn: 'text-slate-600 hover:bg-blue-50 hover:text-blue-700 border-transparent',
                    unavailableBtn: 'cursor-not-allowed border-stone-200 bg-stone-50 text-stone-400',
                },
                'initial-work': {
                    title: 'Preview PDF Initial Work',
                    url: @json($initialWorkPdfUrl),
                    activeBtn: 'bg-orange-50 text-orange-700 border-orange-100',
                    inactiveBtn: 'text-slate-600 hover:bg-orange-50 hover:text-orange-700 border-transparent',
                },
            };

            const previewTitle = document.getElementById('activePreviewTitle');
            const previewOpen = document.getElementById('activePreviewOpen');
            const previewButtons = document.querySelectorAll('.preview-tab-btn');

            const setActivePreview = (key) => {
                const config = previewConfig[key];
                if (!config || !config.url) return;

                if (previewTitle) {
                    previewTitle.textContent = config.title;
                }

                if (previewOpen) {
                    previewOpen.href = config.url;
                }

                window.approvalPdfPreview?.load(config.title, config.url);

                previewButtons.forEach((button) => {
                    const target = button.dataset.previewTarget;
                    const isActive = target === key;
                    const targetConfig = previewConfig[target];

                    if (!targetConfig?.url) {
                        button.disabled = true;
                        button.className = `preview-tab-btn rounded-xl border px-3 py-2 text-xs font-semibold transition ${targetConfig?.unavailableBtn ?? 'cursor-not-allowed border-stone-200 bg-stone-50 text-stone-400'}`;

                        return;
                    }

                    button.disabled = false;
                    button.className = `preview-tab-btn rounded-xl border px-3 py-2 text-xs font-semibold transition ${isActive ? config.activeBtn : targetConfig.inactiveBtn}`;
                });
            };

            previewButtons.forEach((button) => {
                button.addEventListener('click', () => {
                    setActivePreview(button.dataset.previewTarget);
                });
            });

            @if ($canSign)
                const canvas = document.getElementById('signatureCanvas');
                const form = document.getElementById('signatureForm');
                const signatureFile = document.getElementById('signatureFile');
                const clearButton = document.getElementById('clearSignature');
                const useRecentButton = document.getElementById('useRecentSignature');
                const signatureVisuals = window.createSignaturePadVisuals?.();
                signatureVisuals?.idle();

                if (canvas && form && signatureFile) {
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
                        context.strokeStyle = '#111827';
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
                            image.onload = () => {
                                context.drawImage(image, 0, 0, rect.width, rect.height);
                            };
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
                            alert('TTD terakhir tidak dapat dimuat. Silakan tanda tangan ulang.');
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

                    form.addEventListener('submit', async (event) => {
                        if (preparedSubmit) {
                            return;
                        }

                        if (!hasEnoughSignatureStroke()) {
                            event.preventDefault();
                            const message = hasStroke
                                ? 'Tanda tangan terlalu sedikit. Silakan tanda tangani dengan coretan yang jelas.'
                                : 'Silakan tanda tangan terlebih dahulu.';
                            signatureVisuals?.error(message);
                            alert(message);
                            return;
                        }

                        event.preventDefault();
                        const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));

                        if (!blob) {
                            alert('Tanda tangan belum terbaca. Silakan tanda tangani ulang.');
                            return;
                        }

                        const transfer = new DataTransfer();
                        transfer.items.add(new File([blob], 'signature.png', { type: 'image/png' }));
                        signatureFile.files = transfer.files;
                        preparedSubmit = true;
                        form.submit();
                    });
                }
            @endif
        });
    </script>
</body>
</html>
