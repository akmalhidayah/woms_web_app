<article class="min-w-0 rounded-xl border border-slate-200 bg-white p-4 shadow-sm lg:col-span-3">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
        <div class="flex items-center gap-2">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
                <i data-lucide="users-round" class="h-4 w-4"></i>
            </span>
            <div>
                <h2 class="text-xs font-bold uppercase tracking-[0.1em] text-slate-800">Ringkasan Per Regu</h2>
                <p class="mt-0.5 text-[10px] text-slate-500">Mengikuti regu resmi Order Pekerjaan Bengkel</p>
            </div>
        </div>

        @if ($workshopDashboard['unknown_regu_count'] > 0)
            <span class="rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-[9px] font-bold text-amber-700">
                Regu belum ditentukan: {{ $workshopDashboard['unknown_regu_count'] }}
            </span>
        @endif
    </div>

    <div class="mt-3 grid min-w-0 gap-3 sm:grid-cols-3">
        @foreach ($workshopDashboard['regu'] as $regu)
            <section class="min-w-0 rounded-xl border border-slate-200 bg-slate-50 p-3">
                <h3 class="break-words text-[10px] font-extrabold uppercase tracking-[0.08em] text-slate-800">{{ $regu['name'] }}</h3>
                <div class="mt-3 grid grid-cols-2 gap-x-3 gap-y-2">
                    <div>
                        <div class="text-[8px] font-bold uppercase tracking-[0.1em] text-slate-400">Total</div>
                        <div class="mt-0.5 text-sm font-bold text-slate-900">{{ $regu['total'] }}</div>
                    </div>
                    <div>
                        <div class="text-[8px] font-bold uppercase tracking-[0.1em] text-slate-400">Proses</div>
                        <div class="mt-0.5 text-sm font-bold text-amber-600">{{ $regu['in_progress'] }}</div>
                    </div>
                    <div>
                        <div class="text-[8px] font-bold uppercase tracking-[0.1em] text-slate-400">Selesai</div>
                        <div class="mt-0.5 text-sm font-bold text-emerald-600">{{ $regu['completed'] }}</div>
                    </div>
                    <div>
                        <div class="text-[8px] font-bold uppercase tracking-[0.1em] text-slate-400">Belum</div>
                        <div class="mt-0.5 text-sm font-bold text-slate-600">{{ $regu['incomplete'] }}</div>
                    </div>
                </div>
                <div class="mt-3 border-t border-slate-200 pt-2">
                    <span class="text-[9px] font-semibold text-slate-500">Penyelesaian</span>
                    <span class="float-right text-[10px] font-extrabold text-slate-800">{{ number_format($regu['completion_percentage'], 2, ',', '.') }}%</span>
                </div>
            </section>
        @endforeach
    </div>
</article>
