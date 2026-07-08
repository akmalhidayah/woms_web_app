<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head', ['title' => 'Approval Quality Control'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    @php
        $report = $signature->qualityControlReport;
        $order = $report->order;
        $canSign = $signature->isPending() && ! $isExpired;
        $statusLabel = $signature->isSigned()
            ? 'Sudah Ditandatangani'
            : ($isExpired ? 'Token Kedaluwarsa' : ($canSign ? 'Menunggu Tanda Tangan' : 'Step Belum Aktif'));
        $statusClasses = $signature->isSigned()
            ? 'bg-emerald-100 text-emerald-700 ring-emerald-200'
            : ($isExpired ? 'bg-amber-100 text-amber-700 ring-amber-200' : 'bg-blue-100 text-blue-700 ring-blue-200');
        $qualityControlTotalSteps = $report->approvalStepCount();
        $qualityControlProgressPercent = $report->approvalProgressPercent();
    @endphp

    <main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.10)]">
            <div class="border-b-4 border-[#8f1d2c] bg-[#5b0f1b] px-5 py-6 text-white sm:px-8 sm:py-7">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                    <div class="min-w-0">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.28em] text-white/70">
                            Quality Control Digital Approval
                        </div>
                        <h1 class="mt-3 break-words text-2xl font-bold tracking-tight sm:text-3xl">
                            {{ $order?->nama_pekerjaan ?: ($report?->report_no ?: 'Approval Quality Control') }}
                        </h1>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-white/75">
                            Halaman approval bertoken ini hanya dapat digunakan oleh akun penanda tangan yang ditetapkan.
                        </p>
                    </div>

                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-end xl:min-w-[22rem]">
                        <div class="w-fit rounded-2xl bg-white/95 p-2 shadow-sm">
                            <img src="{{ asset('assets/branding/logos/logo-st.png') }}" alt="Logo ST" class="h-12 w-auto object-contain sm:h-14">
                        </div>

                        <div class="rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-sm shadow-sm backdrop-blur">
                            <div class="text-white/70">Login sebagai</div>
                            <div class="mt-1 font-semibold text-white">{{ auth()->user()->name }}</div>
                            <div class="text-xs text-white/70">{{ auth()->user()->email }}</div>
                            <a href="{{ route(auth()->user()->dashboardRouteName()) }}" class="mt-3 inline-flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-xs font-semibold text-[#5b0f1b] transition hover:bg-white/90">
                                <i data-lucide="layout-dashboard" class="h-3.5 w-3.5"></i>
                                Ke Dashboard
                            </a>
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

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Penanda Tangan</div>
                        <div class="mt-2 break-words text-sm font-bold text-slate-900">{{ $signature->signer_name }}</div>
                        <div class="mt-1 text-sm leading-5 text-slate-600">{{ $signature->signer_position ?: $signature->displayRoleLabel() }}</div>
                        <span class="mt-3 inline-flex w-fit rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $statusClasses }}">{{ $statusLabel }}</span>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="space-y-4">
                            <div class="flex items-start gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-slate-500 ring-1 ring-slate-200">
                                    <i data-lucide="hash" class="h-4 w-4"></i>
                                </span>
                                <div class="min-w-0">
                                    <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Nomor Order</div>
                                    <div class="mt-1 break-words text-sm font-bold text-slate-900">{{ $order?->nomor_order ?: '-' }}</div>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-600 ring-1 ring-blue-100">
                                    <i data-lucide="bell" class="h-4 w-4"></i>
                                </span>
                                <div class="min-w-0">
                                    <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Notifikasi</div>
                                    <div class="mt-1 break-words text-sm font-bold text-slate-900">{{ $order?->notifikasi ?: '-' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 md:col-span-2">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Pekerjaan</div>
                        <div class="mt-2 break-words text-sm font-bold text-slate-900">{{ $order?->nama_pekerjaan ?: '-' }}</div>
                        <div class="mt-3 flex flex-wrap gap-2 text-[11px] font-semibold">
                            <span class="inline-flex max-w-full items-center gap-1.5 rounded-full bg-blue-50 px-2.5 py-1 text-blue-700 ring-1 ring-blue-100">
                                <i data-lucide="building-2" class="h-3.5 w-3.5 shrink-0"></i>
                                <span class="min-w-0 break-words">Unit: {{ $order?->unit_kerja ?: '-' }}</span>
                            </span>
                            <span class="inline-flex max-w-full items-center gap-1.5 rounded-full bg-slate-100 px-2.5 py-1 text-slate-600 ring-1 ring-slate-200">
                                <i data-lucide="network" class="h-3.5 w-3.5 shrink-0"></i>
                                <span class="min-w-0 break-words">Seksi: {{ $order?->seksi ?: '-' }}</span>
                            </span>
                        </div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Step</div>
                        <div class="mt-2 text-sm font-bold text-slate-900">{{ $signature->step_order }} dari {{ $qualityControlTotalSteps }}</div>
                        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-100">
                            <div class="h-full rounded-full bg-blue-600" style="width: {{ $qualityControlProgressPercent }}%"></div>
                        </div>
                    </div>
                </div>

                <div class="grid gap-5 xl:grid-cols-[1.55fr_0.95fr]">
                    <div class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
                        <div class="border-b border-slate-200 px-4 py-4 sm:px-5">
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Preview Dokumen</div>
                                    <h2 class="mt-1 text-lg font-bold text-slate-900">PDF Quality Control</h2>
                                </div>
                            </div>
                        </div>
                        <div class="p-4 sm:p-5">
                            @include('approval.partials.pdfjs-preview', [
                                'title' => 'PDF Quality Control',
                                'url' => $qualityControlPdfUrl,
                            ])
                        </div>
                    </div>

                    <div class="space-y-5">
                        @if ($signature->isSigned())
                            <div class="rounded-[1.75rem] border border-emerald-200 bg-emerald-50 p-5 text-center shadow-sm">
                                <h2 class="text-xl font-bold text-emerald-900">Tanda tangan tersimpan</h2>
                                <p class="mt-2 text-sm leading-6 text-emerald-700">
                                    Dokumen telah ditandatangani pada {{ optional($signature->signed_at)->format('d/m/Y H:i') }}.
                                </p>
                            </div>
                        @elseif ($isExpired)
                            <div class="rounded-[1.75rem] border border-amber-200 bg-amber-50 p-5 text-center shadow-sm">
                                <h2 class="text-xl font-bold text-amber-900">Token kedaluwarsa</h2>
                                <p class="mt-2 text-sm leading-6 text-amber-700">
                                    Token ini berlaku sampai {{ optional($signature->token_expires_at)->format('d/m/Y H:i') }}.
                                </p>
                            </div>
                        @else
                            <form method="POST" action="{{ route('approval.quality-control.sign', $token) }}" id="signatureForm" enctype="multipart/form-data" class="rounded-[1.75rem] border border-slate-200 bg-slate-50 p-5 shadow-sm">
                                @csrf
                                <input type="file" name="signature_file" id="signatureFile" accept="image/png,image/jpeg" class="hidden">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Tanda Tangan Digital</div>
                                <div id="signaturePadShell" class="relative mt-3 overflow-hidden rounded-2xl border-2 border-dashed border-slate-300 bg-slate-50 p-3 transition">
                                    <canvas id="signatureCanvas" width="620" height="260" class="relative z-10 h-60 w-full rounded-xl bg-transparent sm:h-72"></canvas>
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
                                <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:justify-between">
                                    <button type="button" id="clearSignature" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                        Hapus
                                    </button>
                                    <button type="submit" class="rounded-xl bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                                        Simpan Tanda Tangan
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    </main>

    @include('approval.partials.signed-success-alert')
    @include('approval.partials.signature-pad-visuals')

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const canvas = document.getElementById('signatureCanvas');
            const form = document.getElementById('signatureForm');
            const signatureFile = document.getElementById('signatureFile');
            const clearButton = document.getElementById('clearSignature');
            const signatureVisuals = window.createSignaturePadVisuals?.();
            signatureVisuals?.idle();

            if (!canvas || !form || !signatureFile) {
                return;
            }

            const ctx = canvas.getContext('2d');
            let drawing = false;
            let touched = false;
            let preparedSubmit = false;
            let lastPoint = null;
            let strokePointCount = 0;
            let strokeDistance = 0;

            const minimumStrokePoints = 8;
            const minimumStrokeDistance = 40;

            const resizeCanvas = () => {
                const rect = canvas.getBoundingClientRect();
                const ratio = window.devicePixelRatio || 1;
                const snapshot = touched ? canvas.toDataURL('image/png') : null;
                canvas.width = Math.max(1, Math.floor(rect.width * ratio));
                canvas.height = Math.max(1, Math.floor(rect.height * ratio));
                ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
                ctx.lineWidth = 2.4;
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';
                ctx.strokeStyle = '#0f172a';

                if (snapshot) {
                    const image = new Image();
                    image.onload = () => ctx.drawImage(image, 0, 0, rect.width, rect.height);
                    image.src = snapshot;
                }
            };

            const point = (event) => {
                const source = event.touches ? event.touches[0] : event;
                const rect = canvas.getBoundingClientRect();

                return {
                    x: source.clientX - rect.left,
                    y: source.clientY - rect.top,
                };
            };

            const start = (event) => {
                event.preventDefault();
                drawing = true;
                signatureVisuals?.active();
                const p = point(event);
                lastPoint = p;
                ctx.beginPath();
                ctx.moveTo(p.x, p.y);
            };

            const move = (event) => {
                if (!drawing) return;
                event.preventDefault();
                const p = point(event);
                if (lastPoint) {
                    strokeDistance += Math.hypot(p.x - lastPoint.x, p.y - lastPoint.y);
                }
                strokePointCount++;
                lastPoint = p;
                ctx.lineTo(p.x, p.y);
                ctx.stroke();
                touched = true;
            };

            const stop = () => {
                drawing = false;
                lastPoint = null;

                if (touched) {
                    signatureVisuals?.completed();
                }
            };

            canvas.addEventListener('mousedown', start);
            canvas.addEventListener('mousemove', move);
            window.addEventListener('mouseup', stop);
            canvas.addEventListener('touchstart', start, { passive: false });
            canvas.addEventListener('touchmove', move, { passive: false });
            canvas.addEventListener('touchend', stop);
            window.addEventListener('resize', resizeCanvas);

            clearButton?.addEventListener('click', () => {
                const rect = canvas.getBoundingClientRect();
                ctx.clearRect(0, 0, rect.width, rect.height);
                touched = false;
                lastPoint = null;
                strokePointCount = 0;
                strokeDistance = 0;
                signatureFile.value = '';
                signatureVisuals?.idle();
            });

            const hasEnoughSignatureStroke = () => {
                return touched
                    && strokePointCount >= minimumStrokePoints
                    && strokeDistance >= minimumStrokeDistance;
            };

            form.addEventListener('submit', async (event) => {
                if (preparedSubmit) {
                    return;
                }

                if (!hasEnoughSignatureStroke()) {
                    event.preventDefault();
                    const message = touched
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

            resizeCanvas();
        });
    </script>
</body>
</html>
