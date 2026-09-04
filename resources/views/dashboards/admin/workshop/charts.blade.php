@php($workshopSummary = $workshopDashboard['summary'])

<section class="grid min-w-0 gap-3 lg:grid-cols-5">
    <article class="min-w-0 overflow-hidden rounded-xl border border-slate-200 bg-white p-4 shadow-sm lg:col-span-2">
        <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600">
                <i data-lucide="chart-no-axes-combined" class="h-4 w-4"></i>
            </span>
            <div>
                <h2 class="text-xs font-bold uppercase tracking-[0.1em] text-slate-800">Penyelesaian Order</h2>
                <p class="mt-0.5 text-[10px] text-slate-500">Selesai ketika masuk Serah Terima atau ditandai sebagai data legacy</p>
            </div>
        </div>

        @if ($workshopDashboard['has_orders'])
            <div class="relative mt-3 h-[250px] min-w-0 w-full overflow-hidden">
                <canvas id="workshopCompletionChart" class="h-full w-full max-w-full" role="img" aria-label="Donut penyelesaian Order Pekerjaan Bengkel"></canvas>
            </div>
        @else
            <div class="mt-3 flex h-[250px] items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 text-center text-xs text-slate-500">
                Belum ada Order Pekerjaan Bengkel pada periode ini.
            </div>
        @endif
    </article>

    @include('dashboards.admin.workshop.regu-summary')
</section>

<section class="min-w-0 overflow-hidden rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 pb-3">
        <div class="flex items-center gap-2">
            <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-blue-50 text-blue-600">
                <i data-lucide="chart-spline" class="h-4 w-4"></i>
            </span>
            <div>
                <h2 class="text-xs font-bold uppercase tracking-[0.1em] text-slate-800">Trend Penyelesaian Order</h2>
                <p class="mt-0.5 text-[10px] text-slate-500">Kumulatif berdasarkan tanggal order dan waktu penyelesaian</p>
            </div>
        </div>
        <span class="text-[10px] font-bold text-blue-700">Target {{ $workshopSummary['completion_target'] }}%</span>
    </div>

    @if ($workshopDashboard['trend_has_orders'])
        <div class="relative mt-3 h-[300px] min-w-0 w-full overflow-hidden">
            <canvas id="workshopCompletionTrendChart" class="h-full w-full max-w-full" role="img" aria-label="Grafik trend penyelesaian Order Pekerjaan Bengkel"></canvas>
        </div>
    @else
        <div class="mt-3 flex h-[220px] items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 text-center text-xs text-slate-500">
            Belum ada data trend penyelesaian pada tahun ini.
        </div>
    @endif
</section>

<section class="min-w-0 overflow-hidden rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="flex items-center gap-2 border-b border-slate-100 pb-3">
        <span class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-indigo-50 text-indigo-600">
            <i data-lucide="chart-column-big" class="h-4 w-4"></i>
        </span>
        <div>
            <h2 class="text-xs font-bold uppercase tracking-[0.1em] text-slate-800">Biaya Order Bengkel Per Bulan</h2>
            <p class="mt-0.5 text-[10px] text-slate-500">Nilai berasal dari field Biaya pada Order Pekerjaan Bengkel</p>
        </div>
    </div>

    @if ($workshopDashboard['has_orders'])
        <div class="relative mt-3 h-[300px] min-w-0 w-full overflow-hidden">
            <canvas id="workshopMonthlyCostChart" class="h-full w-full max-w-full" role="img" aria-label="Grafik Biaya Order Bengkel per bulan"></canvas>
        </div>
    @else
        <div class="mt-3 flex h-[220px] items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50 px-4 text-center text-xs text-slate-500">
            Belum ada data Biaya Order Bengkel pada periode ini.
        </div>
    @endif
</section>
