<!DOCTYPE html>
<html lang="id">
<head>
    @include('partials.head', ['title' => 'Approval HPP'])
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    @php
        $hpp = $signature?->hpp;
        $isRejected = $hpp?->status === \App\Models\Hpp::STATUS_REJECTED;
        $canSign = $signature?->isPending() && ! $isExpired && ! $isRejected;
        $noteGroupLabel = $signature?->noteGroupLabel() ?? 'Catatan Approval';
        $statusLabel = match (true) {
            ! $signature => 'Token Tidak Valid',
            $isRejected => 'Dokumen Ditolak',
            $signature->isSigned() => 'Sudah Ditandatangani',
            $signature->isLocked() => 'Step Belum Aktif',
            $isExpired => 'Token Kedaluwarsa',
            default => 'Menunggu Tanda Tangan',
        };
        $statusClasses = match (true) {
            ! $signature => 'bg-rose-100 text-rose-700 ring-rose-200',
            $isRejected => 'bg-rose-100 text-rose-700 ring-rose-200',
            $signature->isSigned() => 'bg-emerald-100 text-emerald-700 ring-emerald-200',
            $signature->isLocked() => 'bg-slate-100 text-slate-700 ring-slate-200',
            $isExpired => 'bg-amber-100 text-amber-700 ring-amber-200',
            default => 'bg-blue-100 text-blue-700 ring-blue-200',
        };
    @endphp

    <main class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
        <section class="overflow-hidden rounded-[1.6rem] border border-slate-200 bg-white shadow-[0_18px_48px_rgba(15,23,42,0.10)]">
            <div class="border-b-4 border-blue-600 bg-slate-950 px-5 py-6 text-white sm:px-8 sm:py-7">
                <div class="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                    <div class="min-w-0">
                        <div class="text-[11px] font-semibold uppercase tracking-[0.28em] text-blue-300">
                            HPP Digital Approval
                        </div>
                        <h1 class="mt-3 break-words text-2xl font-bold tracking-tight sm:text-3xl">
                            {{ $hpp?->approval_case ?: 'Approval HPP' }}
                        </h1>
                        <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-300">
                            Halaman approval bertoken ini hanya dapat digunakan oleh akun penanda tangan yang ditetapkan.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-slate-700 bg-slate-900 px-4 py-3 text-sm">
                        <div class="text-slate-400">Login sebagai</div>
                        <div class="mt-1 font-semibold text-white">{{ auth()->user()->name }}</div>
                        <div class="text-xs text-slate-400">{{ auth()->user()->email }}</div>
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
                            Link ini tidak terdaftar pada approval HPP aktif. Gunakan link terbaru dari halaman Create HPP.
                        </p>
                    </div>
                @else
                    <div class="rounded-[1.4rem] border border-slate-200 bg-slate-50 p-4 shadow-sm sm:p-5 lg:p-6">
                        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Nomor Order</div>
                                <div class="mt-2 break-words text-sm font-bold text-slate-900">{{ $hpp?->nomor_order ?: '-' }}</div>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Approval Case</div>
                                <div class="mt-2 text-sm font-bold text-slate-900">{{ $hpp?->approval_case ?: '-' }}</div>
                            </div>

                            <div class="rounded-2xl border border-slate-200 bg-white px-4 py-4 md:col-span-2">
                                <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Pekerjaan</div>
                                <div class="mt-2 break-words text-sm font-bold text-slate-900">{{ $hpp?->nama_pekerjaan ?: '-' }}</div>
                                <div class="mt-3 flex flex-wrap gap-2 text-[11px] font-semibold">
                                    <span class="rounded-full bg-blue-50 px-2.5 py-1 text-blue-700 ring-1 ring-blue-100">
                                        {{ $hpp?->kategori_pekerjaan ?: '-' }}
                                    </span>
                                    <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-600 ring-1 ring-slate-200">
                                        {{ $hpp?->unit_kerja ?: '-' }}
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
                                <p class="mt-2 text-sm leading-6 text-slate-500">
                                    Step sebelumnya wajib selesai sebelum step berikutnya aktif.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="grid gap-5 xl:grid-cols-[1.55fr_0.95fr]">
                        <div class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm">
                            <div class="flex flex-col gap-4 border-b border-slate-200 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-5">
                                <div>
                                    <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Preview Dokumen</div>
                                    <h2 id="activePreviewTitle" class="mt-1 text-lg font-bold text-slate-900">Preview PDF HPP</h2>
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
                                        class="preview-tab-btn rounded-xl border border-transparent px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-rose-50 hover:text-rose-700"
                                        data-preview-target="abnormalitas"
                                    >
                                        Abnormalitas
                                    </button>
                                    <button
                                        type="button"
                                        class="preview-tab-btn rounded-xl border border-transparent px-3 py-2 text-xs font-semibold text-slate-600 transition hover:bg-sky-50 hover:text-sky-700"
                                        data-preview-target="gambar-teknik"
                                    >
                                        Gambar Teknik
                                    </button>
                                    <a id="activePreviewOpen" href="{{ $hppPdfUrl }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 rounded-xl border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-100">
                                        <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                                        Buka Dokumen
                                    </a>
                                </div>
                            </div>

                            <div class="p-4 sm:p-5">
                                <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
                                    <iframe id="activePreviewFrame" src="{{ $hppPdfUrl }}" class="h-[32rem] w-full bg-white sm:h-[42rem] xl:h-[54rem]"></iframe>
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
                            @else
                                <div class="rounded-[1.75rem] border border-slate-200 bg-white p-4 shadow-sm sm:p-5">
                                    <form method="POST" action="{{ route('approval.hpp.sign', $token) }}" id="signatureForm" class="space-y-4">
                                        @csrf
                                        <input type="hidden" name="signature_data" id="signatureData">

                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-4">
                                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                                                <div>
                                                    <div class="text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">Tanda Tangan Digital</div>
                                                    <h2 class="mt-1 text-lg font-bold text-slate-900">Area Penandatanganan</h2>
                                                </div>
                                                <div class="text-xs text-slate-400">Mouse / touch screen didukung</div>
                                            </div>

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

                                            <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-white p-3">
                                                <canvas id="signatureCanvas" width="620" height="260" class="h-60 w-full rounded-xl bg-white sm:h-72"></canvas>
                                            </div>

                                            <div class="flex flex-col gap-2 pt-4 sm:flex-row sm:justify-between">
                                                <button type="button" id="clearSignature" class="rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                                                    Hapus
                                                </button>

                                                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                                                    <button type="submit" name="approval_action" value="reject" class="rounded-xl bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700">
                                                        Reject
                                                    </button>
                                                    <button type="submit" name="approval_action" value="sign" class="rounded-xl bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">
                                                        Simpan Tanda Tangan
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
                },
                'gambar-teknik': {
                    title: 'Preview Gambar Teknik',
                    url: @json($gambarTeknikUrl),
                    activeBtn: 'border-sky-200 bg-sky-50 text-sky-700',
                    inactiveBtn: 'border-transparent text-slate-600 hover:bg-sky-50 hover:text-sky-700',
                },
            };

            const previewFrame = document.getElementById('activePreviewFrame');
            const previewTitle = document.getElementById('activePreviewTitle');
            const previewOpen = document.getElementById('activePreviewOpen');
            const previewButtons = document.querySelectorAll('.preview-tab-btn');

            const setActivePreview = (key) => {
                const config = previewConfig[key];

                if (!config || !config.url || !previewFrame || !previewTitle || !previewOpen) {
                    return;
                }

                previewFrame.src = config.url;
                previewTitle.textContent = config.title;
                previewOpen.href = config.url;

                previewButtons.forEach((button) => {
                    const target = button.dataset.previewTarget;
                    const targetConfig = previewConfig[target];
                    const isActive = target === key;

                    button.className = `preview-tab-btn rounded-xl border px-3 py-2 text-xs font-semibold transition ${isActive ? config.activeBtn : targetConfig.inactiveBtn}`;
                });
            };

            previewButtons.forEach((button) => {
                button.addEventListener('click', () => setActivePreview(button.dataset.previewTarget));
            });

            @if ($canSign)
                const canvas = document.getElementById('signatureCanvas');
                const form = document.getElementById('signatureForm');
                const signatureData = document.getElementById('signatureData');
                const clearButton = document.getElementById('clearSignature');
                const approvalNote = document.getElementById('approvalNote');

                if (canvas && form && signatureData) {
                    const context = canvas.getContext('2d');
                    let drawing = false;
                    let hasStroke = false;

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
                        const point = getPoint(event);
                        context.beginPath();
                        context.moveTo(point.x, point.y);
                    };

                    const draw = (event) => {
                        if (!drawing) return;

                        event.preventDefault();
                        const point = getPoint(event);
                        context.lineTo(point.x, point.y);
                        context.stroke();
                        hasStroke = true;
                    };

                    const stopDrawing = () => {
                        drawing = false;
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
                        context.clearRect(0, 0, canvas.width, canvas.height);
                        setupCanvasStyle();
                        hasStroke = false;
                    });

                    form.addEventListener('submit', (event) => {
                        const action = event.submitter?.value || 'sign';

                        if (action === 'reject') {
                            if (!approvalNote?.value.trim()) {
                                event.preventDefault();
                                alert('Silakan isi alasan reject terlebih dahulu.');
                            }

                            signatureData.value = '';
                            return;
                        }

                        if (!hasStroke) {
                            event.preventDefault();
                            alert('Silakan isi tanda tangan terlebih dahulu.');
                            return;
                        }

                        signatureData.value = canvas.toDataURL('image/png');
                    });
                }
            @endif
        });
    </script>
</body>
</html>
