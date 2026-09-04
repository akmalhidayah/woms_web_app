@php
    $workshopSummary = $workshopDashboard['summary'];
    $workshopPercentageLabel = number_format($workshopSummary['completion_percentage'], 2, ',', '.');
    $workshopKpis = [
        ['label' => 'Total Order', 'value' => number_format($workshopSummary['total'], 0, ',', '.'), 'icon' => 'clipboard-list', 'iconClass' => 'bg-blue-50 text-blue-600'],
        ['label' => 'Dalam Proses', 'value' => number_format($workshopSummary['in_progress'], 0, ',', '.'), 'icon' => 'loader-circle', 'iconClass' => 'bg-amber-50 text-amber-600'],
        ['label' => 'Selesai', 'value' => number_format($workshopSummary['completed'], 0, ',', '.'), 'icon' => 'circle-check-big', 'iconClass' => 'bg-emerald-50 text-emerald-600'],
        ['label' => 'Belum Selesai', 'value' => number_format($workshopSummary['incomplete'], 0, ',', '.'), 'icon' => 'clock-3', 'iconClass' => 'bg-slate-100 text-slate-600'],
        ['label' => 'Penyelesaian', 'value' => $workshopPercentageLabel.'%', 'icon' => 'gauge', 'iconClass' => $workshopSummary['target_met'] ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'],
        ['label' => 'Total Biaya', 'value' => 'Rp. '.number_format($workshopSummary['total_cost'], 0, ',', '.'), 'icon' => 'wallet-cards', 'iconClass' => 'bg-indigo-50 text-indigo-600'],
    ];
@endphp

<section class="grid min-w-0 grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
    @foreach ($workshopKpis as $kpi)
        <article class="min-w-0 rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
            <div class="flex min-w-0 items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-[9px] font-bold uppercase tracking-[0.12em] text-slate-500">{{ $kpi['label'] }}</p>
                    <p class="mt-2 break-words text-lg font-extrabold leading-6 text-slate-950">{{ $kpi['value'] }}</p>
                </div>
                <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg {{ $kpi['iconClass'] }}">
                    <i data-lucide="{{ $kpi['icon'] }}" class="h-4 w-4"></i>
                </span>
            </div>

            @if ($kpi['label'] === 'Penyelesaian')
                <div class="mt-2 text-[9px] font-semibold {{ $workshopSummary['target_met'] ? 'text-emerald-600' : 'text-rose-600' }}">
                    {{ $workshopSummary['target_met'] ? 'Target tercapai' : 'Di bawah target' }} · Target ≥ {{ $workshopSummary['completion_target'] }}%
                </div>
            @elseif ($kpi['label'] === 'Total Biaya')
                <div class="mt-2 text-[9px] font-medium text-slate-400">Biaya Order Bengkel</div>
            @endif
        </article>
    @endforeach
</section>
