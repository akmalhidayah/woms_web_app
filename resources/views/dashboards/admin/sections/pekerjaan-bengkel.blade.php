@php
    $workshopFilters = $workshopDashboard['filters'];
    $workshopMonthNames = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];
    $workshopPeriodLabel = ($workshopFilters['month'] === null
        ? 'Semua Bulan'
        : $workshopMonthNames[$workshopFilters['month']]).' '.$workshopFilters['year'];
@endphp

<div id="dashboardBengkelContent" class="min-w-0 space-y-3">
    <section class="flex min-w-0 flex-col gap-2 rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-[0.12em] text-slate-800">
                <i data-lucide="factory" class="h-4 w-4 text-blue-600"></i>
                Ringkasan Pekerjaan Bengkel
            </div>
            <p class="mt-1 text-[11px] text-slate-500">Periode order: {{ $workshopPeriodLabel }}</p>
        </div>
        <span class="inline-flex w-fit items-center rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-[10px] font-bold text-blue-700">
            Target penyelesaian ≥ {{ $workshopDashboard['summary']['completion_target'] }}%
        </span>
    </section>

    @include('dashboards.admin.workshop.kpi')
    @include('dashboards.admin.workshop.charts')
</div>
