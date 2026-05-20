<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head', ['title' => 'Approval BAST'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    @php
        $lhpp = $signature?->lhppBast;
        $isRejected = $lhpp?->approval_status === \App\Models\LhppBast::APPROVAL_REJECTED;
        $isDirops = $signature?->role_key === 'dirops';
        $canSign = $signature?->isPending() && ! $isExpired && ! $isRejected && ! $isDirops;
        $statusLabel = match (true) {
            ! $signature => 'Token Tidak Valid',
            $isRejected => 'Dokumen Ditolak',
            $signature->isSigned() => 'Sudah Ditandatangani',
            $signature->isLocked() => 'Step Belum Aktif',
            $isExpired => 'Token Kedaluwarsa',
            $isDirops && $signature->isPending() => 'Menunggu Upload DIROPS',
            default => 'Menunggu Tanda Tangan',
        };
        $statusClasses = match (true) {
            ! $signature, $isRejected => 'bg-rose-100 text-rose-700 ring-rose-200',
            $signature?->isSigned() => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
            $signature?->isLocked() => 'bg-slate-100 text-slate-700 ring-slate-200',
            $isExpired => 'bg-amber-100 text-amber-700 ring-amber-200',
            $isDirops => 'bg-orange-100 text-orange-700 ring-orange-200',
            default => 'bg-blue-100 text-blue-700 ring-blue-200',
        };
    @endphp

    <main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <section class="overflow-hidden rounded-[1.6rem] border border-slate-200 bg-white shadow-[0_18px_48px_rgba(15,23,42,0.10)]">
            <div class="border-b-4 border-[#ca642f] bg-slate-950 px-5 py-6 text-white sm:px-8 sm:py-7">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                    <div class="min-w-0">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.28em] text-orange-300">BAST Digital Approval</div>
                        <h1 class="mt-3 break-words text-2xl font-bold tracking-tight sm:text-3xl">
                            {{ $lhpp?->approval_case ?: 'Approval BAST' }}
                        </h1>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">
                            Halaman approval bertoken ini hanya dapat digunakan oleh akun penanda tangan yang ditetapkan.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-700 bg-slate-900 px-4 py-3 text-sm">
                        <div class="text-slate-400">Login sebagai</div>
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
                            Link ini tidak terdaftar pada approval BAST aktif. Gunakan link terbaru dari halaman BAST.
                        </p>
                    </div>
                @else
                    <div class="rounded-[1.4rem] border border-slate-200 bg-slate-50 p-4 shadow-sm sm:p-5 lg:p-6">
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Nomor Order</div>
                                <div class="mt-2 break-words text-sm font-bold text-slate-900">{{ $lhpp?->nomor_order ?: '-' }}</div>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Termin</div>
                                <div class="mt-2 text-sm font-bold text-slate-900">{{ $lhpp?->termin_type === 'termin_2' ? 'Termin 2' : 'Termin 1' }}</div>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 md:col-span-2">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Pekerjaan</div>
                                <div class="mt-2 break-words text-sm font-bold text-slate-900">{{ $lhpp?->deskripsi_pekerjaan ?: '-' }}</div>
                                <div class="mt-3 flex flex-wrap gap-2 text-[11px] font-semibold">
                                    <span class="rounded-full bg-orange-50 px-2.5 py-1 text-orange-700 ring-1 ring-orange-100">
                                        {{ $lhpp?->approval_threshold === 'over_250' ? 'Diatas 250 JT' : 'Dibawah 250 JT' }}
                                    </span>
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600 ring-1 ring-slate-200">
                                        {{ $lhpp?->tipe_pekerjaan ?: '-' }}
                                    </span>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Step</div>
                                <div class="mt-2 text-sm font-bold text-slate-900">{{ $signature->step_order }} dari {{ $totalSteps }}</div>
                                <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                    <div class="h-full rounded-full bg-[#ca642f]" style="width: {{ $progressPercent }}%"></div>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 md:col-span-2 xl:col-span-3">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Penanda Tangan</div>
                                <div class="mt-2 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                                    <div>
                                        <div class="text-base font-bold text-slate-900">{{ $signature->signer_name_snapshot }}</div>
                                        <div class="text-sm text-slate-600">{{ $signature->signer_position_snapshot }}</div>
                                    </div>
                                    <span class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-semibold ring-1 {{ $statusClasses }}">
                                        {{ $statusLabel }}
                                    </span>
                                </div>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 md:col-span-2">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Progress Approval</div>
                                <div class="mt-2 text-sm font-bold text-slate-900">{{ $signedCount }}/{{ $totalSteps }} signed</div>
                                <p class="mt-2 text-sm leading-6 text-slate-500">Step sebelumnya wajib selesai sebelum step berikutnya aktif.</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-5 xl:grid-cols-[1.55fr_0.95fr]">
                        <div class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
                            <div class="flex flex-col gap-4 border-b border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                                <div>
                                    <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Preview Dokumen</div>
                                    <h2 class="mt-1 text-lg font-bold text-slate-900">Preview PDF BAST</h2>
                                </div>

                                <a href="{{ $bastPdfUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100">
                                    <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                                    Buka Dokumen
                                </a>
                            </div>

                            <div class="p-4 sm:p-5">
                                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                                    <iframe src="{{ $bastPdfUrl }}" class="h-[32rem] w-full bg-white sm:h-[42rem] xl:h-[54rem]"></iframe>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-5">
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
                                    </div>
                                </div>
                            @elseif ($isRejected)
                                <div class="rounded-[1.75rem] border border-rose-200 bg-white p-5 shadow-sm">
                                    <div class="flex min-h-[18rem] flex-col items-center justify-center text-center">
                                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-rose-100 text-rose-700">
                                            <i data-lucide="octagon-x" class="h-7 w-7"></i>
                                        </div>
                                        <h2 class="mt-4 text-xl font-bold text-slate-900">Dokumen BAST ditolak</h2>
                                        <p class="mt-2 max-w-sm text-sm leading-6 text-slate-500">PKM perlu merevisi BAST dan membuat ulang pengajuan.</p>
                                    </div>
                                </div>
                            @elseif ($signature->isLocked())
                                <div class="rounded-[1.75rem] border border-slate-200 bg-white p-5 shadow-sm">
                                    <div class="flex min-h-[18rem] flex-col items-center justify-center text-center">
                                        <div class="flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 text-slate-700">
                                            <i data-lucide="lock" class="h-7 w-7"></i>
                                        </div>
                                        <h2 class="mt-4 text-xl font-bold text-slate-900">Step belum aktif</h2>
                                        <p class="mt-2 max-w-sm text-sm leading-6 text-slate-500">Tunggu step approval sebelumnya selesai terlebih dahulu.</p>
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
                                            Tahap DIROPS diselesaikan oleh PKM melalui upload dokumen BAST final yang sudah ditandatangani.
                                        </p>
                                    </div>
                                </div>
                            @else
                                <div class="rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                                    <form method="POST" action="{{ route('approval.bast.sign', $token) }}" id="signatureForm" enctype="multipart/form-data" class="space-y-4">
                                        @csrf
                                        <input type="hidden" name="approval_action" id="approvalAction" value="sign">
                                        <input type="file" name="signature_file" id="signatureFile" accept="image/png,image/jpeg" class="hidden">

                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                                <div>
                                                    <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Tanda Tangan Digital</div>
                                                    <h2 class="mt-1 text-lg font-bold text-slate-900">Area Penandatanganan</h2>
                                                </div>
                                                <div class="text-xs text-slate-400">Mouse / touch screen didukung</div>
                                            </div>

                                            <div class="mt-4 rounded-2xl border border-slate-200 bg-white px-4 py-4">
                                                <label for="approvalNote" class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Catatan Approval</label>
                                                <textarea id="approvalNote" name="approval_note" rows="4" maxlength="2000" class="mt-3 w-full rounded-2xl border border-slate-300 bg-white px-4 py-3 text-sm text-slate-700 placeholder:text-slate-400 focus:border-[#ca642f] focus:outline-none" placeholder="Tulis catatan approval bila diperlukan...">{{ old('approval_note', $signature->approval_note) }}</textarea>
                                            </div>

                                            <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-white p-3">
                                                <canvas id="signatureCanvas" width="620" height="260" class="h-60 w-full rounded-xl bg-white sm:h-72"></canvas>
                                            </div>

                                            <div class="flex flex-col gap-2 pt-4 sm:flex-row sm:justify-between">
                                                <button type="button" id="clearSignature" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">Hapus</button>

                                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                                    <button type="submit" data-action="reject" class="rounded-xl bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700">Reject</button>
                                                    <button type="submit" data-action="sign" class="rounded-xl bg-[#ca642f] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#b85b2b]">Simpan Tanda Tangan</button>
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

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const canvas = document.getElementById('signatureCanvas');
            const form = document.getElementById('signatureForm');
            const signatureFile = document.getElementById('signatureFile');
            const approvalAction = document.getElementById('approvalAction');
            const clearButton = document.getElementById('clearSignature');

            if (window.lucide) {
                window.lucide.createIcons();
            }

            if (!canvas || !form || !signatureFile || !approvalAction) {
                return;
            }

            const ctx = canvas.getContext('2d');
            let drawing = false;
            let hasDrawn = false;
            let preparedSubmit = false;

            const resizeCanvas = () => {
                const rect = canvas.getBoundingClientRect();
                const ratio = window.devicePixelRatio || 1;
                const image = hasDrawn ? canvas.toDataURL('image/png') : null;

                canvas.width = Math.max(1, Math.floor(rect.width * ratio));
                canvas.height = Math.max(1, Math.floor(rect.height * ratio));
                ctx.setTransform(ratio, 0, 0, ratio, 0, 0);
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';
                ctx.lineWidth = 2.2;
                ctx.strokeStyle = '#0f172a';

                if (image) {
                    const img = new Image();
                    img.onload = () => ctx.drawImage(img, 0, 0, rect.width, rect.height);
                    img.src = image;
                }
            };

            const point = (event) => {
                const rect = canvas.getBoundingClientRect();
                const touch = event.touches?.[0] || event.changedTouches?.[0];
                const clientX = touch ? touch.clientX : event.clientX;
                const clientY = touch ? touch.clientY : event.clientY;

                return {
                    x: clientX - rect.left,
                    y: clientY - rect.top,
                };
            };

            const start = (event) => {
                event.preventDefault();
                drawing = true;
                hasDrawn = true;
                const pos = point(event);
                ctx.beginPath();
                ctx.moveTo(pos.x, pos.y);
            };

            const move = (event) => {
                if (!drawing) {
                    return;
                }

                event.preventDefault();
                const pos = point(event);
                ctx.lineTo(pos.x, pos.y);
                ctx.stroke();
            };

            const stop = () => {
                drawing = false;
            };

            resizeCanvas();
            window.addEventListener('resize', resizeCanvas);
            canvas.addEventListener('mousedown', start);
            canvas.addEventListener('mousemove', move);
            window.addEventListener('mouseup', stop);
            canvas.addEventListener('touchstart', start, { passive: false });
            canvas.addEventListener('touchmove', move, { passive: false });
            canvas.addEventListener('touchend', stop);

            clearButton?.addEventListener('click', () => {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                hasDrawn = false;
                signatureFile.value = '';
            });

            form.addEventListener('submit', async (event) => {
                if (preparedSubmit) {
                    return;
                }

                const action = event.submitter?.dataset.action || 'sign';
                approvalAction.value = action;

                if (action === 'reject') {
                    return;
                }

                if (!hasDrawn) {
                    event.preventDefault();
                    alert('Silakan isi tanda tangan terlebih dahulu.');
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
        });
    </script>
</body>
</html>
