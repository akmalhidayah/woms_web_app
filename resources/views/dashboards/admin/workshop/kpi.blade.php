@php
    $workshopSummary = $workshopDashboard['summary'];
    $workshopKpis = [
        ['label' => 'Total Order', 'value' => number_format($workshopSummary['total'], 0, ',', '.'), 'cardClass' => 'border-b border-white/30 bg-blue-700 text-white sm:border-r xl:border-b-0', 'accentClass' => 'border-blue-300 text-blue-100', 'valueClass' => 'text-white'],
        ['label' => 'Dalam Proses', 'value' => number_format($workshopSummary['in_progress'], 0, ',', '.'), 'cardClass' => 'border-b border-white/30 bg-amber-500 text-slate-950 xl:border-b-0 xl:border-r', 'accentClass' => 'border-amber-900 text-amber-950', 'valueClass' => 'text-slate-950'],
        ['label' => 'Selesai', 'value' => number_format($workshopSummary['completed'], 0, ',', '.'), 'cardClass' => 'border-b border-white/30 bg-emerald-700 text-white sm:border-b-0 sm:border-r', 'accentClass' => 'border-emerald-300 text-emerald-100', 'valueClass' => 'text-white'],
        ['label' => 'Belum Selesai', 'value' => number_format($workshopSummary['incomplete'], 0, ',', '.'), 'cardClass' => 'bg-indigo-700 text-white', 'accentClass' => 'border-indigo-300 text-indigo-100', 'valueClass' => 'text-white'],
    ];
@endphp

<section class="rounded-xl bg-blue-100 px-3 py-2.5">
    <div class="mb-3 flex items-center gap-2 border-b border-slate-300 pb-2.5">
        <i data-lucide="factory" class="h-4 w-4 text-blue-600"></i>
        <h2 class="text-[12px] font-bold tracking-[0.08em] text-slate-800">RINGKASAN PEKERJAAN BENGKEL</h2>
    </div>

    <div class="grid overflow-hidden sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($workshopKpis as $kpi)
            <article class="min-w-0 px-3 py-2.5 {{ $kpi['cardClass'] }}">
                <div class="border-l-4 pl-2 text-[9px] font-bold uppercase tracking-[0.12em] {{ $kpi['accentClass'] }}">
                    {{ $kpi['label'] }}
                </div>
                <div class="mt-2 break-words text-base font-bold {{ $kpi['valueClass'] }}">{{ $kpi['value'] }}</div>
            </article>
        @endforeach
    </div>
</section>
