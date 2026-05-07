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
    @endphp

    <main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <section class="overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_20px_60px_rgba(15,23,42,0.10)]">
            <div class="border-b border-slate-800 bg-slate-950 px-5 py-6 text-white sm:px-8">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                    <div class="min-w-0">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.28em] text-violet-300">
                            Quality Control Approval
                        </div>
                        <h1 class="mt-3 break-words text-2xl font-bold tracking-tight sm:text-3xl">
                            {{ $report->report_no ?: 'QC-'.$report->id }}
                        </h1>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">
                            Halaman ini hanya dapat diakses oleh akun approval yang ditetapkan pada struktur organisasi.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/10 bg-white/10 px-4 py-3 text-sm">
                        <div class="text-slate-300">Login sebagai</div>
                        <div class="mt-1 font-semibold text-white">{{ auth()->user()->name }}</div>
                        <div class="text-xs text-slate-400">{{ auth()->user()->email }}</div>
                        <a href="{{ route(auth()->user()->dashboardRouteName()) }}" class="mt-3 inline-flex items-center gap-2 rounded-xl bg-white px-3 py-2 text-xs font-semibold text-slate-900 transition hover:bg-slate-100">
                            <i data-lucide="layout-dashboard" class="h-3.5 w-3.5"></i>
                            Ke Dashboard
                        </a>
                    </div>
                </div>
            </div>

            <div class="space-y-5 px-5 py-5 sm:px-8 sm:py-6">
                @if (session('status'))
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($nextApprovalUrl)
                    <div class="rounded-2xl border border-blue-200 bg-blue-50 px-4 py-3">
                        <div class="text-sm font-semibold text-blue-900">Token approval berikutnya aktif</div>
                        <p class="mt-1 text-xs leading-5 text-blue-700">
                            Link ini tetap aman karena hanya akun manager yang ditetapkan yang bisa menandatangani.
                        </p>
                        <a href="{{ $nextApprovalUrl }}" class="mt-3 inline-flex rounded-xl bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700">
                            Buka Halaman TTD Berikutnya
                        </a>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Nomor Order</div>
                        <div class="mt-2 break-words text-sm font-bold text-slate-900">{{ $order?->nomor_order ?: '-' }}</div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Tanggal QC</div>
                        <div class="mt-2 text-sm font-bold text-slate-900">{{ optional($report->report_date)->format('d/m/Y') ?: '-' }}</div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4 md:col-span-2">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Pekerjaan</div>
                        <div class="mt-2 break-words text-sm font-bold text-slate-900">{{ $order?->nama_pekerjaan ?: '-' }}</div>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Status</div>
                        <div class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $statusClasses }}">{{ $statusLabel }}</div>
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
                                <a href="{{ $qualityControlPdfUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100">
                                    <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                                    Buka Dokumen
                                </a>
                            </div>
                        </div>
                        <div class="p-4 sm:p-5">
                            <iframe src="{{ $qualityControlPdfUrl }}" class="h-[32rem] w-full rounded-2xl border border-slate-200 bg-white sm:h-[42rem] xl:h-[54rem]"></iframe>
                        </div>
                    </div>

                    <div class="space-y-5">
                        <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                            <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Penanda Tangan</div>
                            <div class="mt-3 text-base font-bold text-slate-900">{{ $signature->signer_name }}</div>
                            <div class="text-sm text-slate-600">{{ $signature->signer_position ?: $signature->role_label }}</div>
                            <div class="mt-2 text-xs leading-5 text-slate-500">
                                {{ $signature->source_unit ?: '-' }} / {{ $signature->source_section ?: '-' }}
                            </div>
                        </div>

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
                            <form method="POST" action="{{ route('approval.quality-control.sign', $token) }}" id="signatureForm" class="rounded-[1.75rem] border border-slate-200 bg-slate-50 p-5 shadow-sm">
                                @csrf
                                <input type="hidden" name="signature_data" id="signatureData">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Tanda Tangan Digital</div>
                                <div class="mt-3 rounded-2xl border border-dashed border-slate-300 bg-white p-3">
                                    <canvas id="signatureCanvas" width="620" height="260" class="h-60 w-full rounded-xl bg-white sm:h-72"></canvas>
                                </div>
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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const canvas = document.getElementById('signatureCanvas');
            const form = document.getElementById('signatureForm');
            const signatureData = document.getElementById('signatureData');
            const clearButton = document.getElementById('clearSignature');

            if (!canvas || !form || !signatureData) {
                return;
            }

            const ctx = canvas.getContext('2d');
            let drawing = false;
            let touched = false;

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
                touched = true;
                const p = point(event);
                ctx.beginPath();
                ctx.moveTo(p.x, p.y);
            };

            const move = (event) => {
                if (!drawing) return;
                event.preventDefault();
                const p = point(event);
                ctx.lineTo(p.x, p.y);
                ctx.stroke();
            };

            const stop = () => {
                drawing = false;
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
                signatureData.value = '';
            });

            form.addEventListener('submit', (event) => {
                if (!touched) {
                    event.preventDefault();
                    alert('Silakan isi tanda tangan terlebih dahulu.');
                    return;
                }

                signatureData.value = canvas.toDataURL('image/png');
            });

            resizeCanvas();
        });
    </script>
</body>
</html>
