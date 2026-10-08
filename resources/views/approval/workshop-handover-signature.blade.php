<!doctype html>
<html lang="id">
<head>
    @include('partials.head', ['title' => 'Approval Serah Terima Bengkel'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <main class="mx-auto max-w-6xl space-y-4 px-3 py-5 sm:px-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-[11px] font-semibold uppercase tracking-[.2em] text-slate-500">Approval Serah Terima Bengkel</p>
            <h1 class="mt-2 text-2xl font-bold">{{ $handover->job_name_snapshot ?: 'Serah Terima' }}</h1>
            <div class="mt-3 grid gap-2 text-sm text-slate-600 sm:grid-cols-2">
                <div>Dokumen: <strong class="text-slate-900">{{ $handover->document_no }}</strong></div>
                <div>Order: <strong class="text-slate-900">{{ $handover->order_no_snapshot }}</strong></div>
                <div>Unit/Seksi: <strong class="text-slate-900">{{ $handover->unit_snapshot ?: '-' }} / {{ $handover->section_snapshot ?: '-' }}</strong></div>
                <div>Jalur: <strong class="text-slate-900">{{ $handover->path }}</strong></div>
                <div>Tanggal: <strong class="text-slate-900">{{ optional($handover->handed_over_at)->format('d/m/Y H:i') }}</strong></div>
                <div>Diserahkan oleh: <strong class="text-slate-900">{{ $handover->admin_name_snapshot }}</strong></div>
            </div>
        </section>

        @if ($handover->order?->workPackages?->isNotEmpty())
            <section class="rounded-2xl border border-blue-100 bg-blue-50/40 p-4 shadow-sm">
                <h2 class="text-sm font-bold">Pembagian Pekerjaan</h2>
                <div class="mt-3 space-y-2">@foreach($handover->order->workPackages as $package)<div class="rounded-xl border border-slate-200 bg-white p-3 text-xs"><div class="font-semibold text-blue-700">{{ $package->display_no }} — {{ $package->job_name }}</div><div class="mt-1 text-slate-600">@foreach($package->assignments as $assignment){{ $assignment->pic_name_snapshot }}: {{ implode('; ', (array) ($assignment->work_descriptions ?? [])) }}@if(!$loop->last)<br>@endif @endforeach</div><div class="mt-1 text-slate-500">Status: {{ $package->statusLabel() }}@if($package->completed_at) · {{ $package->completed_at->format('d/m/Y H:i') }}@endif</div></div>@endforeach</div>
            </section>
        @endif

        @if (session('status'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first() }}</div>
        @endif

        <section class="grid gap-4 lg:grid-cols-[minmax(0,1.5fr)_minmax(20rem,.8fr)]">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="mb-3 text-sm font-bold">Preview Dokumen</h2>
                @include('approval.partials.pdfjs-preview', ['title' => 'Bukti Serah Terima', 'url' => $pdfUrl])
            </div>
            <div class="space-y-4">
                <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 class="text-sm font-bold">Foto Bukti</h2>
                    <div class="mt-3 grid grid-cols-2 gap-2">
                        @forelse ($photoUrls as $index => $url)
                            <img src="{{ $url }}" alt="Foto bukti {{ $index + 1 }}" class="h-28 w-full rounded-lg object-cover ring-1 ring-slate-200">
                        @empty
                            <p class="col-span-2 text-sm text-slate-500">Foto bukti belum tersedia.</p>
                        @endforelse
                    </div>
                </div>
                @if ($handover->isCompleted())
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">
                        <strong>Serah Terima selesai.</strong><br>Ditandatangani oleh {{ $handover->recipient_name_snapshot }} pada {{ optional($handover->user_signed_at)->format('d/m/Y H:i') }}.
                    </div>
                @elseif ($isExpired)
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">Token Serah Terima sudah kedaluwarsa. Hubungi Admin Workshop.</div>
                @elseif ($canSign)
                    <form method="POST" action="{{ route('approval.workshop-handover.sign', $token) }}" class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm" id="workshop-handover-sign-form">
                        @csrf
                        <h2 class="text-sm font-bold">Tanda Tangan Manager User</h2>
                        <canvas id="workshop-handover-user-signature" class="mt-3 h-40 w-full touch-none rounded-lg bg-white ring-1 ring-slate-200"></canvas>
                        <input type="hidden" name="signature_data" id="workshop-handover-user-signature-data">
                        <div class="mt-3 flex items-center justify-between gap-2"><button type="button" id="workshop-handover-user-clear" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold">Hapus/Ulangi</button><button class="rounded-lg bg-blue-700 px-4 py-2 text-xs font-semibold text-white">Tanda Tangan</button></div>
                        <p id="workshop-handover-user-error" class="mt-2 hidden text-xs font-semibold text-rose-700">Tanda tangan wajib diisi.</p>
                    </form>
                @endif
            </div>
        </section>
    </main>
    @if ($canSign)
    <script>
        (() => {
            const canvas = document.getElementById('workshop-handover-user-signature');
            const form = document.getElementById('workshop-handover-sign-form');
            const input = document.getElementById('workshop-handover-user-signature-data');
            const clear = document.getElementById('workshop-handover-user-clear');
            const error = document.getElementById('workshop-handover-user-error');
            if (!canvas || !form || !input) return;
            const ctx = canvas.getContext('2d');
            let drawing = false;
            let stroke = false;
            let activePointerId = null;
            let resizeTimer = null;
            let resizePending = false;
            const applyCanvasStyle = () => { ctx.lineWidth = 2; ctx.lineCap = 'round'; ctx.strokeStyle = '#0f172a'; };
            const resize = () => {
                const rect = canvas.getBoundingClientRect();
                const ratio = window.devicePixelRatio || 1;
                const targetWidth = Math.max(1, Math.floor(rect.width * ratio));
                const targetHeight = Math.max(1, Math.floor(rect.height * ratio));

                if (canvas.width === targetWidth && canvas.height === targetHeight) return;

                const snapshot = stroke ? document.createElement('canvas') : null;

                if (snapshot) {
                    snapshot.width = canvas.width;
                    snapshot.height = canvas.height;
                    snapshot.getContext('2d')?.drawImage(canvas, 0, 0);
                }

                canvas.width = targetWidth;
                canvas.height = targetHeight;
                ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
                applyCanvasStyle();

                if (snapshot) {
                    ctx.drawImage(snapshot, 0, 0, snapshot.width, snapshot.height, 0, 0, rect.width, rect.height);
                }
            };
            const scheduleResize = () => {
                resizePending = true;
                window.clearTimeout(resizeTimer);
                resizeTimer = window.setTimeout(() => {
                    if (drawing) return;
                    resizePending = false;
                    resize();
                }, 200);
            };
            const point = event => {
                const rect = canvas.getBoundingClientRect();
                return [event.clientX - rect.left, event.clientY - rect.top];
            };
            const stopDrawing = event => {
                if (!drawing || event.pointerId !== activePointerId) return;
                drawing = false;
                activePointerId = null;
                if (resizePending) scheduleResize();
            };
            canvas.addEventListener('pointerdown', event => {
                if (activePointerId !== null || (event.pointerType === 'mouse' && event.button !== 0)) return;
                event.preventDefault();
                drawing = true;
                activePointerId = event.pointerId;
                canvas.setPointerCapture?.(event.pointerId);
                const [x, y] = point(event);
                ctx.beginPath();
                ctx.moveTo(x, y);
            });
            canvas.addEventListener('pointermove', event => {
                if (!drawing || event.pointerId !== activePointerId) return;
                event.preventDefault();
                const pointerEvents = event.getCoalescedEvents?.();
                (pointerEvents?.length ? pointerEvents : [event]).forEach((pointerEvent) => {
                    const [x, y] = point(pointerEvent);
                    ctx.lineTo(x, y);
                    ctx.stroke();
                    stroke = true;
                });
            });
            ['pointerup', 'pointercancel', 'lostpointercapture'].forEach((name) => canvas.addEventListener(name, stopDrawing));
            clear?.addEventListener('click', () => { ctx.clearRect(0, 0, canvas.clientWidth, canvas.clientHeight); input.value = ''; stroke = false; });
            form.addEventListener('submit', event => { if (!stroke) { event.preventDefault(); error?.classList.remove('hidden'); return; } input.value = canvas.toDataURL('image/png'); });
            resize();
            window.addEventListener('resize', scheduleResize);
            window.addEventListener('orientationchange', scheduleResize);
        })();
    </script>
    @endif
</body>
</html>
