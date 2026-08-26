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
        document.addEventListener('DOMContentLoaded', () => {
            const canvas = document.getElementById('workshop-handover-user-signature');
            const form = document.getElementById('workshop-handover-sign-form');
            const input = document.getElementById('workshop-handover-user-signature-data');
            const clear = document.getElementById('workshop-handover-user-clear');
            const error = document.getElementById('workshop-handover-user-error');
            if (!canvas || !form || !input) return;
            const ctx = canvas.getContext('2d'); let drawing = false; let stroke = false;
            const resize = () => { const r = canvas.getBoundingClientRect(), d = window.devicePixelRatio || 1; canvas.width = r.width*d; canvas.height = r.height*d; ctx.setTransform(d,0,0,d,0,0); ctx.lineWidth=2; ctx.lineCap='round'; ctx.strokeStyle='#0f172a'; };
            const point = e => { const r=canvas.getBoundingClientRect(); return [e.clientX-r.left,e.clientY-r.top]; };
            canvas.addEventListener('pointerdown', e => { drawing=true; canvas.setPointerCapture?.(e.pointerId); const [x,y]=point(e); ctx.beginPath(); ctx.moveTo(x,y); });
            canvas.addEventListener('pointermove', e => { if(!drawing)return; const [x,y]=point(e); ctx.lineTo(x,y); ctx.stroke(); stroke=true; });
            ['pointerup','pointercancel'].forEach(n=>canvas.addEventListener(n,()=>drawing=false));
            clear?.addEventListener('click',()=>{ctx.clearRect(0,0,canvas.clientWidth,canvas.clientHeight); input.value=''; stroke=false;});
            form.addEventListener('submit', e => { if(!stroke){e.preventDefault(); error?.classList.remove('hidden'); return;} input.value=canvas.toDataURL('image/png'); });
            requestAnimationFrame(resize); window.addEventListener('resize', resize);
        });
    </script>
    @endif
</body>
</html>
